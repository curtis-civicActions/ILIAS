<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

declare(strict_types=1);

use ILIAS\Setup;

class ilDatabasePopulatedObjective extends \ilDatabaseObjective
{
    public const MIN_NUMBER_OF_ILIAS_TABLES = 200; // educated guess

    public function getHash(): string
    {
        return hash("sha256", implode("-", [
            self::class,
            $this->config->getHost(),
            $this->config->getPort(),
            $this->config->getDatabase()
        ]));
    }

    public function getLabel(): string
    {
        return "The database is populated with ILIAS-tables.";
    }

    public function isNotable(): bool
    {
        return true;
    }

    /**
     * @return \ilDatabaseExistsObjective[]
     */
    public function getPreconditions(Setup\Environment $environment): array
    {
        if ($environment->getResource(Setup\Environment::RESOURCE_DATABASE)) {
            return [];
        }
        return [
            new \ilDatabaseExistsObjective($this->config)
        ];
    }

    public function achieve(Setup\Environment $environment): Setup\Environment
    {
        /**
         * @var $db ilDBInterface
         * @var $io Setup\CLI\IOWrapper
         */
        $db = $environment->getResource(Setup\Environment::RESOURCE_DATABASE);
        $io = $environment->getResource(Setup\Environment::RESOURCE_ADMIN_INTERACTION);

        // $this->setDefaultEngine($db); // maybe we could set the default?
        $default = $this->getDefaultEngine($db);

        $io->text("Default DB engine is $default");


        switch ($default) {
            case 'innodb':
                $io->text("reading dump file, this may take a while...");
                $this->readDumpFile($db);
                break;

            default:
                throw new Setup\UnachievableException(
                    "Cannot determine database default engine, must be InnoDB, `$default` given."
                );
        }

        return $environment;
    }

    /**
     * @inheritDoc
     */
    public function isApplicable(Setup\Environment $environment): bool
    {
        $db = $environment->getResource(Setup\Environment::RESOURCE_DATABASE);

        return !$this->isDatabasePopulated($db);
    }

    protected function isDatabasePopulated(ilDBInterface $db): bool
    {
        $probe_tables = ['usr_data', 'object_data', 'object_reference'];
        $number_of_probe_tables = count($probe_tables);
        $tables = $db->listTables();
        $number_of_tables = count($tables);

        return
            $number_of_tables > self::MIN_NUMBER_OF_ILIAS_TABLES
            && count(array_intersect($tables, $probe_tables)) === $number_of_probe_tables;
    }

    /**
     * @throws ilDatabaseException
     */
    private function readDumpFile(ilDBInterface $db): void
    {
        $path_to_db_dump = $this->config->getPathToDBDump();
        if (!is_file(realpath($path_to_db_dump)) ||
            !is_readable(realpath($path_to_db_dump))) {
            throw new Setup\UnachievableException(
                "Cannot read database dump file: $path_to_db_dump"
            );
        }
        foreach ($this->queryReader(realpath($path_to_db_dump)) as $query) {
            try {
                $statement = $db->prepareManip($query);
                $db->execute($statement);
            } catch (Throwable $e) {
                throw new Setup\UnachievableException(
                    "Cannot populate database with dump file: $path_to_db_dump. Query failed: $query wih message " . $e->getMessage(
                    )
                );
            }
        }
    }

    private function queryReader(string $path_to_db_dump): Generator
    {
        $stack = '';
        $handle = fopen($path_to_db_dump, "r");
        while (($line = fgets($handle)) !== false) {
            if (preg_match('/^--/', $line)) { // Skip comments
                continue;
            }
            if (preg_match('/^\/\*/', $line)) { // Run Variables Assignments as single query
                yield $line;
                $stack = '';
                continue;
            }
            if (!preg_match('/;$/', $line)) { // Break after ; character which indicates end of query
                $stack .= $line;
            } else {
                $stack .= $line;
                yield $stack;
                $stack = '';
            }
        }

        fclose($handle);
    }

    /**
     * @param ilDBInterface|null $db
     * @noRector
     */
    private function setDefaultEngine(ilDBInterface $db): void
    {
        switch ($db->getDBType()) {
            case 'pdo-mysql-innodb':
            case ilDBConstants::TYPE_INNODB:
            case ilDBConstants::TYPE_GALERA:
            case ilDBConstants::TYPE_MYSQL:
                $db->manipulate('SET default_storage_engine=InnoDB;');
                break;
        }
    }

    private function getDefaultEngine(ilDBInterface $db): string
    {
        try {
            $r = $db->query('SHOW ENGINES ');

            $default = '';
            while ($d = $db->fetchObject($r)) {
                if (strtoupper($d->Support) === 'DEFAULT') {
                    $default = $d->Engine;
                    break;
                }
            }
            return strtolower($default);
        } catch (Throwable $e) {
            return 'unknown';
        }
    }
}
