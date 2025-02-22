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

namespace ILIAS\UICore;

use ILIAS\Data\URI;
use ILIAS\GlobalScreen\Scope\Layout\Factory\ContentModification;
use ILIAS\GlobalScreen\Scope\Layout\Factory\FooterModification;
use ILIAS\GlobalScreen\Scope\Layout\Provider\AbstractModificationProvider;
use ILIAS\GlobalScreen\ScreenContext\Stack\CalledContexts;
use ILIAS\GlobalScreen\ScreenContext\Stack\ContextCollection;
use ILIAS\UI\Component\Legacy\Legacy;
use ILIAS\UI\Component\MainControls\Footer;
use ILIAS\GlobalScreen\Scope\Layout\Factory\TitleModification;
use ILIAS\GlobalScreen\Scope\Layout\Factory\ShortTitleModification;
use ILIAS\GlobalScreen\Scope\Layout\Factory\ViewTitleModification;

/**
 * @author Fabian Schmid <fs@studer-raimann.ch>
 * @author Michael Jansen <mjansen@databay.de>
 * @author Maximilian Becker <mbecker@databay.de>
 */
class PageContentProvider extends AbstractModificationProvider
{
    private static string $content = "";
    private static string $perma_link = "";
    private static string $title = "";
    private static string $short_title = "";
    private static string $view_title = "";

    public static function setContent(string $content): void
    {
        self::$content = $content;
    }

    public static function setTitle(string $title): void
    {
        self::$title = $title;
    }

    public static function setShortTitle(string $short_title): void
    {
        self::$short_title = $short_title;
    }

    public static function setViewTitle(string $view_title): void
    {
        self::$view_title = $view_title;
    }

    public static function setPermaLink(string $perma_link): void
    {
        self::$perma_link = $perma_link;
    }

    public function isInterestedInContexts(): ContextCollection
    {
        return $this->context_collection->main();
    }

    public function getContentModification(CalledContexts $screen_context_stack): ?ContentModification
    {
        return $this->globalScreen()->layout()->factory()->content()->withModification(function (
            ?Legacy $content
        ): ?Legacy {
            $ui = $this->dic->ui();
            return $ui->factory()->legacy(
                $ui->renderer()->render($content) . self::$content
            );
        })->withLowPriority();
    }

    public function getTitleModification(CalledContexts $screen_context_stack): ?TitleModification
    {
        /** @var $modification TitleModification */
        $modification = $this->globalScreen()->layout()->factory()->title()->withModification(
            fn (?string $content): ?string => self::$title
        )->withLowPriority();

        return $modification;
    }

    public function getShortTitleModification(CalledContexts $screen_context_stack): ?ShortTitleModification
    {
        /** @var $modification ShortTitleModification */
        $modification = $this->globalScreen()->layout()->factory()->short_title()->withModification(
            fn (?string $content): ?string => self::$short_title
        )->withLowPriority();

        return $modification;
    }

    public function getViewTitleModification(CalledContexts $screen_context_stack): ?ViewTitleModification
    {
        /** @var $modification ViewTitleModification */
        $modification = $this->globalScreen()->layout()->factory()->view_title()->withModification(
            fn (?string $content): ?string => self::$view_title
        )->withLowPriority();

        return $modification;
    }

    public function getFooterModification(CalledContexts $screen_context_stack): ?FooterModification
    {
        return $this->globalScreen()->layout()->factory()->footer()->withModification(function (?Footer $footer): ?Footer {
            $f = $this->dic->ui()->factory();

            $links = [];
            // ILIAS Version and Text
            $ilias_version = ILIAS_VERSION;
            $text = "powered by ILIAS (v{$ilias_version})";

            // Imprint
            $base_class = ($this->dic->http()->wrapper()->query()->has(\ilCtrlInterface::PARAM_BASE_CLASS)) ?
                $this->dic->http()->wrapper()->query()->retrieve(
                    \ilCtrlInterface::PARAM_BASE_CLASS,
                    $this->dic->refinery()->kindlyTo()->string()
                ) : null;

            if ($base_class !== \ilImprintGUI::class && \ilImprint::isActive()) {
                $imprint_title = $this->dic->language()->txt("imprint");
                $imprint_url = \ilLink::_getStaticLink(0, "impr");
                $links[] = $f->link()->standard($imprint_title, $imprint_url);
            }

            // system support contacts
            if (($system_support_url = \ilSystemSupportContactsGUI::getFooterLink()) !== '') {
                $system_support_title = \ilSystemSupportContactsGUI::getFooterText();
                $links[] = $f->link()->standard($system_support_title, $system_support_url);
            }

            // output translation link
            if (\ilObjLanguageAccess::_checkTranslate() && !\ilObjLanguageAccess::_isPageTranslation()) {
                $translation_url = \ilObjLanguageAccess::_getTranslationLink();
                $translation_title = $this->dic->language()->txt('translation');
                $links[] = $f->link()->standard($translation_title, $translation_url)->withOpenInNewViewport(true);
            }

            // accessibility control concept
            if (($accessibility_control_url = \ilAccessibilityControlConceptGUI::getFooterLink()) !== '') {
                $accessibility_control_title = \ilAccessibilityControlConceptGUI::getFooterText();
                $links[] = $f->link()->standard($accessibility_control_title, $accessibility_control_url);
            }

            // report accessibility issue
            if (($accessibility_report_url = \ilAccessibilitySupportContactsGUI::getFooterLink()) !== '') {
                $accessibility_report_title = \ilAccessibilitySupportContactsGUI::getFooterText();
                $links[] = $f->link()->standard($accessibility_report_title, $accessibility_report_url);
            }

            $footer = $f->mainControls()->footer($links, $text);

            $tosWithdrawalGui = new \ilTermsOfServiceWithdrawalGUIHelper($this->dic->user());
            $footer = $tosWithdrawalGui->modifyFooter($footer);

            if (self::$perma_link !== "") {
                $footer = $footer->withPermanentURL(new URI(self::$perma_link));
            }

            return $footer;
        });
    }
}
