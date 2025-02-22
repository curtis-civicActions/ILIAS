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

/**
*
* @author Helmut Schottmüller <ilias@aurealis.de>
* @version $Id$
*
* @ingroup ModulesQuestionPool
*/

class ilQuestionPoolPrintViewTableGUI extends ilTable2GUI
{
    protected $outputmode;

    protected $totalPoints;

    public function __construct($a_parent_obj, $a_parent_cmd, $outputmode = '')
    {
        $this->setId("qpl_print");
        parent::__construct($a_parent_obj, $a_parent_cmd);

        global $DIC;
        $lng = $DIC['lng'];
        $ilCtrl = $DIC['ilCtrl'];

        $this->lng = $lng;
        $this->ctrl = $ilCtrl;
        $this->outputmode = $outputmode;
        $this->ctrl->setParameterByClass('ilObjQuestionPoolGUI', 'output', $outputmode);

        $this->setFormName('printviewform');
        $this->setStyle('table', 'fullwidth');

        $this->addCommandButton('print', $this->lng->txt('print'), "javascript:window.print();return false;");

        $this->setRowTemplate("tpl.il_as_qpl_printview_row.html", "Modules/TestQuestionPool");

        $this->setFormAction($this->ctrl->getFormAction($a_parent_obj, $a_parent_cmd));
        $this->setDefaultOrderField("title");
        $this->setDefaultOrderDirection("asc");

        $this->enable('sort');
        $this->enable('header');
        $this->disable('select_all');
    }

    public function initColumns(): void
    {
        $this->addColumn($this->lng->txt("title"), 'title', '');

        foreach ($this->getSelectedColumns() as $c) {
            if (strcmp($c, 'description') == 0) {
                $this->addColumn($this->lng->txt("description"), 'description', '');
            }
            if (strcmp($c, 'author') == 0) {
                $this->addColumn($this->lng->txt("author"), 'author', '');
            }
            if (strcmp($c, 'ttype') == 0) {
                $this->addColumn($this->lng->txt("question_type"), 'ttype', '');
            }
            if (strcmp($c, 'points') == 0) {
                $this->addColumn($this->getPointsColumnHeader(), 'points', '');
            }
            if (strcmp($c, 'created') == 0) {
                $this->addColumn($this->lng->txt("create_date"), 'created', '');
            }
            if (strcmp($c, 'updated') == 0) {
                $this->addColumn($this->lng->txt("last_update"), 'updated', '');
            }
        }
    }

    private function getPointsColumnHeader(): string
    {
        return $this->lng->txt("points") . ' (' . $this->getTotalPoints() . ')';
    }

    public function getSelectableColumns(): array
    {
        global $DIC;
        $lng = $DIC['lng'];
        $cols["description"] = array(
            "txt" => $lng->txt("description"),
            "default" => true
        );
        $cols["author"] = array(
            "txt" => $lng->txt("author"),
            "default" => true
        );
        $cols["ttype"] = array(
            "txt" => $lng->txt("question_type"),
            "default" => true
        );
        $cols["points"] = array(
            "txt" => $lng->txt("points"),
            "default" => true
        );
        $cols["created"] = array(
            "txt" => $lng->txt("create_date"),
            "default" => true
        );
        $cols["updated"] = array(
            "txt" => $lng->txt("last_update"),
            "default" => true
        );
        return $cols;
    }

    /**
     * fill row
     * @access public
     * @param
     * @return void
     */
    public function fillRow(array $a_set): void
    {
        ilDatePresentation::setUseRelativeDates(false);
        $this->tpl->setVariable("TITLE", ilLegacyFormElementsUtil::prepareFormOutput($a_set['title']));
        foreach ($this->getSelectedColumns() as $c) {
            if (strcmp($c, 'description') == 0) {
                $this->tpl->setCurrentBlock('description');
                $this->tpl->setVariable(
                    "DESCRIPTION",
                    ilLegacyFormElementsUtil::prepareFormOutput((string) $a_set['description'])
                );
                $this->tpl->parseCurrentBlock();
            }
            if (strcmp($c, 'author') == 0) {
                $this->tpl->setCurrentBlock('author');
                $this->tpl->setVariable("AUTHOR", ilLegacyFormElementsUtil::prepareFormOutput((string) $a_set['author']));
                $this->tpl->parseCurrentBlock();
            }
            if (strcmp($c, 'ttype') == 0) {
                $this->tpl->setCurrentBlock('ttype');
                $this->tpl->setVariable("TYPE", ilLegacyFormElementsUtil::prepareFormOutput((string) $a_set['ttype']));
                $this->tpl->parseCurrentBlock();
            }
            if (strcmp($c, 'points') == 0) {
                $this->tpl->setCurrentBlock('points');
                $this->tpl->setVariable("POINTS", ilLegacyFormElementsUtil::prepareFormOutput((string) $a_set['points']));
                $this->tpl->parseCurrentBlock();
            }
            if (strcmp($c, 'created') == 0) {
                $this->tpl->setCurrentBlock('created');
                $this->tpl->setVariable('CREATED', ilDatePresentation::formatDate(new ilDateTime($a_set['created'], IL_CAL_UNIX)));
                $this->tpl->parseCurrentBlock();
            }
            if (strcmp($c, 'updated') == 0) {
                $this->tpl->setCurrentBlock('updated');
                $this->tpl->setVariable('UPDATED', ilDatePresentation::formatDate(new ilDateTime($a_set['updated'], IL_CAL_UNIX)));
                $this->tpl->parseCurrentBlock();
            }
        }
        if ((strcmp($this->outputmode, "detailed") == 0) || (strcmp($this->outputmode, "detailed_printview") == 0)) {
            $this->tpl->setCurrentBlock("overview_row_detail");
            include_once "./Modules/TestQuestionPool/classes/class.assQuestion.php";
            $question_gui = assQuestion::instantiateQuestionGUI($a_set["question_id"]);
            $question_gui->setRenderPurpose(assQuestionGUI::RENDER_PURPOSE_PREVIEW);
            if (strcmp($this->outputmode, "detailed") == 0) {
                $solutionoutput = $question_gui->getSolutionOutput("", null, false, false, false, false, true, false);
                if (strlen($solutionoutput) == 0) {
                    $solutionoutput = $question_gui->getPreview();
                }
                $this->tpl->setVariable("DETAILS", $solutionoutput);
                $this->tpl->setVariable("ROW_DETAIL_COLSPAN", $this->column_count);
            } else {
                $this->tpl->setVariable("DETAILS", $question_gui->getPreview());
                $this->tpl->setVariable("ROW_DETAIL_COLSPAN", $this->column_count);
            }
            $this->tpl->parseCurrentBlock();
        }
        ilDatePresentation::setUseRelativeDates(true);
    }

    /**
     * @param string $a_field
     * @return bool
     */
    public function numericOrdering(string $a_field): bool
    {
        if (in_array($a_field, array('points', 'created', 'updated'))) {
            return true;
        }

        return false;
    }

    public function getTotalPoints()
    {
        return $this->totalPoints;
    }

    public function setTotalPoints($totalPoints): void
    {
        $this->totalPoints = $totalPoints;
    }
}
