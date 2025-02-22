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
 * Learning module presentation linker
 * @author Alexander Killing <killing@leifos.de>
 */
class ilLMPresentationLinker implements \ILIAS\COPage\PageLinker
{
    public const TARGET_GUI = "illmpresentationgui";
    protected int $obj_id;
    protected string $frame;
    protected int $requested_ref_id;
    protected string $profile_back_url = "";

    protected bool $offline;
    protected bool $embed_mode;
    protected ilCtrl $ctrl;
    protected ilLMTree $lm_tree;
    protected ilObjLearningModule $lm;
    protected int $current_page;
    protected string $back_pg;
    protected string $from_page;
    protected bool $export_all_languages;
    protected string $lang;
    protected string $export_format;

    public function __construct(
        ilObjLearningModule $lm,
        ilLMTree $lm_tree,
        int $current_page,
        int $ref_id,
        string $lang,
        string $back_pg,
        string $from_pg,
        bool $offline,
        string $export_format,
        bool $export_all_languages,
        ilCtrl $ctrl = null,
        bool $embed_mode = false,
        string $frame = "",
        int $obj_id = 0
    ) {
        global $DIC;

        $this->ctrl = is_null($ctrl)
            ? $DIC->ctrl()
            : $ctrl;

        $this->lm_tree = $lm_tree;
        $this->lm = $lm;
        $this->current_page = $current_page;
        $this->back_pg = $back_pg;
        $this->from_page = $from_pg;
        $this->export_all_languages = $export_all_languages;
        $this->lang = $lang;
        $this->requested_ref_id = $ref_id;
        $this->offline = $offline;
        $this->export_format = $export_format;
        $this->embed_mode = $embed_mode;
        $this->frame = $frame;
        $this->obj_id = $obj_id;
    }

    public function setOffline(
        bool $offline = true
    ): void {
        $this->offline = $offline;
    }

    public function setProfileBackUrl(string $url): void
    {
        $this->profile_back_url = $url;
    }

    /**
     * handles links for learning module presentation
     */
    public function getLink(
        string $a_cmd = "",
        int $a_obj_id = 0,
        string $a_frame = "",
        string $a_type = "",
        string $a_back_link = "append",
        string $a_anchor = "",
        string $a_srcstring = ""
    ): string {
        if ($a_cmd == "") {
            $a_cmd = "layout";
        }

        $link = "";

        // handling of free pages
        $cur_page_id = $this->current_page;
        $back_pg = $this->back_pg;
        if ($a_obj_id != "" && !$this->lm_tree->isInTree($a_obj_id) && $cur_page_id != "" &&
            $a_back_link == "append") {
            if ($back_pg != "") {
                $back_pg = $cur_page_id . ":" . $back_pg;
            } else {
                $back_pg = $cur_page_id;
            }
        } else {
            if ($a_back_link == "reduce") {
                $limpos = strpos($this->back_pg, ":");

                if ($limpos > 0) {
                    $back_pg = substr($back_pg, strpos($back_pg, ":") + 1);
                } else {
                    $back_pg = "";
                }
            } elseif ($a_back_link != "keep") {
                $back_pg = "";
            }
        }

        // handle kiosk mode links
        if ($this->embed_mode && in_array($a_cmd, ["downloadFile", "download_paragraph", "fullscreen"])) {
            $this->ctrl->setParameterByClass(\ilLMPresentationGUI::class, "ref_id", $this->lm->getRefId());
            $base = $this->ctrl->getLinkTargetByClass([
                \ilLMPresentationGUI::class, \ilLMPageGUI::class
            ]);
            switch ($a_cmd) {
                case "downloadFile":
                    return $base . "&cmd=downloadFile";
                case "download_paragraph":
                    return $base . "&cmd=download_paragraph";
                case "fullscreen":
                    return $base . "&cmd=displayMediaFullscreen";
            }
            return "";
        // handle online links
        } elseif (!$this->offline) {
            if ($this->from_page == "") {
                // added if due to #23216 (from page has been set in lots of usual navigation links)
                if (!in_array($a_frame, array("", "_blank"))) {
                    $this->ctrl->setParameterByClass(self::TARGET_GUI, "from_page", $cur_page_id);
                }
            } else {
                // faq link on page (in faq frame) includes faq link on other page
                // if added due to bug #11007
                if (!in_array($a_frame, array("", "_blank"))) {
                    $this->ctrl->setParameterByClass(self::TARGET_GUI, "from_page", $this->from_page);
                }
            }

            if ($a_anchor != "") {
                $this->ctrl->setParameterByClass(self::TARGET_GUI, "anchor", rawurlencode($a_anchor));
            }
            if ($a_srcstring != "") {
                $this->ctrl->setParameterByClass(self::TARGET_GUI, "srcstring", $a_srcstring);
            }
            $this->ctrl->setParameterByClass(self::TARGET_GUI, "ref_id", $this->lm->getRefId());
            switch ($a_cmd) {
                case "fullscreen":
                    $link = $this->ctrl->getLinkTargetByClass(self::TARGET_GUI, "fullscreen", "", false, false);
                    break;

                case "sourcecodeDownload":
                    $this->ctrl->setParameterByClass(self::TARGET_GUI, "obj_id", $a_obj_id);
                    $link = $this->ctrl->getLinkTargetByClass([self::TARGET_GUI, "ilLMPageGUI"], "", "", false, false);
                    break;

                default:
                    $link = "";
                    if ($back_pg != "") {
                        $this->ctrl->setParameterByClass(self::TARGET_GUI, "back_pg", $back_pg);
                    }
                    if ($a_frame != "") {
                        $this->ctrl->setParameterByClass(self::TARGET_GUI, "frame", $a_frame);
                    }
                    if ($a_obj_id != "") {
                        switch ($a_type) {
                            case "MediaObject":
                                $this->ctrl->setParameterByClass(self::TARGET_GUI, "mob_id", $a_obj_id);
                                break;

                            default:
                                $this->ctrl->setParameterByClass(self::TARGET_GUI, "obj_id", $a_obj_id);
                                $link .= "&amp;obj_id=" . $a_obj_id;
                                break;
                        }
                    }
                    if ($a_type != "") {
                        $this->ctrl->setParameterByClass(self::TARGET_GUI, "obj_type", $a_type);
                    }
                    $link = $this->ctrl->getLinkTargetByClass(
                        self::TARGET_GUI,
                        $a_cmd,
                        $a_anchor,
                        false,
                        true
                    );
//					$link = str_replace("&", "&amp;", $link);

                    $this->ctrl->setParameterByClass(self::TARGET_GUI, "frame", null);
                    $this->ctrl->setParameterByClass(self::TARGET_GUI, "obj_id", null);
                    $this->ctrl->setParameterByClass(self::TARGET_GUI, "mob_id", null);
                    break;
            }
        } else {	// handle offline links
            $lang_suffix = "";
            if ($this->export_all_languages) {
                if ($this->lang != "" && $this->lang != "-") {
                    $lang_suffix = "_" . $this->lang;
                }
            }

            switch ($a_cmd) {

                case "fullscreen":
                    $link = "fullscreen.html";		// id is handled by xslt
                    break;

                case "layout":

                    if ($a_obj_id == "") {
                        $a_obj_id = $this->lm_tree->getRootId();
                        $pg_node = $this->lm_tree->fetchSuccessorNode($a_obj_id, "pg");
                        $a_obj_id = $pg_node["obj_id"];
                    }
                    if ($a_type == "StructureObject") {
                        $pg_node = $this->lm_tree->fetchSuccessorNode($a_obj_id, "pg");
                        $a_obj_id = $pg_node["obj_id"];
                    }
                    if ($a_frame != "" && $a_frame != "_blank") {
                        if ($a_frame != "toc") {
                            $link = "frame_" . $a_obj_id . "_" . $a_frame . $lang_suffix . ".html";
                        } else {	// don't save multiple toc frames (all the same)
                            $link = "frame_" . $a_frame . $lang_suffix . ".html";
                        }
                    } else {
                        //if ($nid = ilLMObject::_lookupNID($this->lm->getId(), $a_obj_id, "pg"))
                        if ($nid = ilLMPageObject::getExportId($this->lm->getId(), $a_obj_id)) {
                            $link = "lm_pg_" . $nid . $lang_suffix . ".html";
                        } else {
                            $link = "lm_pg_" . $a_obj_id . $lang_suffix . ".html";
                        }
                    }
                    break;

                case "glossary":
                    $link = "term_" . $a_obj_id . ".html";
                    break;

                case "media":
                    $link = "media_" . $a_obj_id . ".html";
                    break;

                case "downloadFile":
                default:
                    break;
            }
        }
        $this->ctrl->clearParametersByClass(self::TARGET_GUI);

        return $link;
    }

    public function getLayoutLinkTargets(): array
    {
        $targets = [
            "New" => [
                "Type" => "New",
                "Frame" => "_blank",
                "OnClick" => ""],
            "FAQ" => [
                "Type" => "FAQ",
                "Frame" => "faq",
                "OnClick" => "return il.LearningModule.showContentFrame(event, 'faq');"],
            "Glossary" => [
                "Type" => "Glossary",
                "OnClick" => "return il.LearningModule.showContentFrame(event, 'glossary');"],
            "Media" => [
                "Type" => "Media",
                "Frame" => "media",
                "OnClick" => "return il.LearningModule.showContentFrame(event, 'media');"]
        ];

        return $targets;
    }

    /**
     * Get XMl for Link Targets
     */
    public function getLinkTargetsXML(): string
    {
        $link_info = "<LinkTargets>";
        foreach ($this->getLayoutLinkTargets() as $k => $t) {
            $link_info .= "<LinkTarget TargetFrame=\"" . $t["Type"] . "\" LinkTarget=\"" . ($t["Frame"] ?? "") . "\" OnClick=\"" . $t["OnClick"] . "\" />";
        }
        $link_info .= "</LinkTargets>";
        return $link_info;
    }

    /**
     * get xml for links
     */
    public function getLinkXML(
        array $int_links
    ): string {
        $ilCtrl = $this->ctrl;
        $a_layoutframes = $this->getLayoutLinkTargets();

        // Determine whether the view of a learning resource should
        // be shown in the frameset of ilias, or in a separate window.
        $showViewInFrameset = true;

        if ($a_layoutframes == "") {
            $a_layoutframes = array();
        }
        $link_info = "<IntLinkInfos>";
        foreach ($int_links as $int_link) {
            $back = "";
            $href = "";
            $ltarget = "";
            $target = $int_link["Target"];
            if (substr($target, 0, 4) == "il__") {
                $target_arr = explode("_", $target);
                $target_id = $target_arr[count($target_arr) - 1];
                $type = $int_link["Type"];
                $targetframe = ($int_link["TargetFrame"] != "")
                    ? $int_link["TargetFrame"]
                    : "None";

                // anchor
                $anc = $anc_add = "";
                if ($int_link["Anchor"] != "") {
                    $anc = $int_link["Anchor"];
                    $anc_add = "_" . rawurlencode($int_link["Anchor"]);
                }
                $lcontent = "";
                switch ($type) {
                    case "PageObject":
                    case "StructureObject":
                        $lm_id = ilLMObject::_lookupContObjID($target_id);
                        if ($lm_id == $this->lm->getId() ||
                            ($targetframe != "None" && $targetframe != "New")) {
                            $ltarget = $a_layoutframes[$targetframe]["Frame"] ?? "";
                            $nframe = ($ltarget == "")
                                ? ""
                                : $ltarget;
                            if ($ltarget == "") {
                                $ltarget = "_parent";
                            }
                            $cmd = "layout";
                            // page command is for displaying in the slate
                            if ($nframe != "" && $nframe != "_blank") {
                                $cmd = "page";
                            }
                            $href =
                                $this->getLink(
                                    $cmd,
                                    $target_id,
                                    $nframe,
                                    $type,
                                    "append",
                                    $anc
                                );
                            if ($lm_id == "") {
                                $href = "";
                            }
                            if ($this->embed_mode) {
                                $ltarget = "_blank";
                            }
                        } else {
                            if (!$this->offline) {
                                if ($type == "PageObject") {
                                    $href = "./goto.php?target=pg_" . $target_id . $anc_add;
                                } else {
                                    $href = "./goto.php?target=st_" . $target_id;
                                }
                            } else {
                                if ($type == "PageObject") {
                                    $href = ILIAS_HTTP_PATH . "/goto.php?target=pg_" . $target_id . $anc_add . "&amp;client_id=" . CLIENT_ID;
                                } else {
                                    $href = ILIAS_HTTP_PATH . "/goto.php?target=st_" . $target_id . "&amp;client_id=" . CLIENT_ID;
                                }
                            }
                            $ltarget = "";
                            if ($targetframe == "New" || $this->embed_mode) {
                                $ltarget = "_blank";
                            }
                        }
                        break;

                    case "GlossaryItem":
                        if ($targetframe == "None") {
                            $targetframe = "Glossary";
                        }
                        $ltarget = $a_layoutframes[$targetframe]["Frame"] ?? "";
                        $nframe = ($ltarget == "")
                            ? $this->frame
                            : $ltarget;
                        $href =
                            $this->getLink($a_cmd = "glossary", (int) $target_id, $nframe, $type);
                        break;

                    case "MediaObject":
                        $ltarget = $a_layoutframes[$targetframe]["Frame"] ?? "";
                        $nframe = ($ltarget == "")
                            ? $this->frame
                            : $ltarget;
                        $href =
                            $this->getLink($a_cmd = "media", $target_id, $nframe, $type);
                        if ($this->offline) {
                            $href = "media_" . $target_id . ".html";
                        } else {
                            $this->ctrl->setParameterByClass("illmpagegui", "ref_id", $this->lm->getRefId());
                            $this->ctrl->setParameterByClass("illmpagegui", "mob_id", $target_id);
                            $this->ctrl->setParameterByClass(self::TARGET_GUI, "obj_id", $this->current_page);
                            $href = $this->ctrl->getLinkTargetByClass(
                                "illmpagegui",
                                "displayMedia",
                                "",
                                false,
                                true
                            );
                            $this->ctrl->setParameterByClass("illmpagegui", "mob_id", "");
                            $ilCtrl->setParameterByClass(self::TARGET_GUI, "obj_id", $this->obj_id);
                        }
                        break;

                    case "RepositoryItem":
                        $obj_type = ilObject::_lookupType((int) $target_id, true);
                        $obj_id = ilObject::_lookupObjId((int) $target_id);
                        if (!$this->offline) {
                            $href = "./goto.php?target=" . $obj_type . "_" . $target_id;
                        } else {
                            $href = ILIAS_HTTP_PATH . "/goto.php?target=" . $obj_type . "_" . $target_id . "&amp;client_id=" . CLIENT_ID;
                        }
                        $ltarget = ilFrameTargetInfo::_getFrame("MainContent");
                        if ($this->embed_mode) {
                            $ltarget = "_blank";
                        }
                        break;

                    case "WikiPage":
                        $wiki_anc = "";
                        if ($int_link["Anchor"] != "") {
                            $wiki_anc = "#".rawurlencode($int_link["Anchor"]);
                        }
                        $href = ilWikiPage::getGotoForWikiPageTarget($target_id) . $wiki_anc;
                        if ($this->embed_mode) {
                            $ltarget = "_blank";
                        }
                        break;

                    case "File":
                        if (!$this->offline) {
                            $ilCtrl->setParameterByClass(self::TARGET_GUI, "obj_id", $this->current_page);
                            $ilCtrl->setParameterByClass(self::TARGET_GUI, "file_id", "il__file_" . $target_id);
                            $href = $ilCtrl->getLinkTargetByClass(
                                self::TARGET_GUI,
                                "downloadFile",
                                "",
                                false,
                                true
                            );
                            $ilCtrl->setParameterByClass(self::TARGET_GUI, "file_id", "");
                            $ilCtrl->setParameterByClass(self::TARGET_GUI, "obj_id", $this->obj_id);
                        }
                        break;

                    case "User":
                        $obj_type = ilObject::_lookupType((int) $target_id);
                        if ($obj_type == "usr") {
                            if (!$this->embed_mode) {
                                $this->ctrl->setParameterByClass(self::TARGET_GUI, "obj_id", $this->current_page);
                                $back = $this->ctrl->getLinkTargetByClass(
                                    self::TARGET_GUI,
                                    "layout",
                                    "",
                                    false,
                                    true
                                );
                            }
                            //var_dump($back); exit;
                            $this->ctrl->setParameterByClass("ilpublicuserprofilegui", "user_id", $target_id);
                            $this->ctrl->setParameterByClass(
                                "ilpublicuserprofilegui",
                                "back_url",
                                rawurlencode($back)
                            );
                            $href = "";
                            if (ilUserUtil::hasPublicProfile($target_id)) {
                                $href = $this->ctrl->getLinkTargetByClass(
                                    "ilpublicuserprofilegui",
                                    "getHTML",
                                    "",
                                    false,
                                    true
                                );
                            }
                            $this->ctrl->setParameterByClass("ilpublicuserprofilegui", "user_id", "");
                            $lcontent = ilUserUtil::getNamePresentation($target_id, false, false);
                        }
                        break;

                }

                $anc_par = 'Anchor="' . $anc . '"';

                if ($href != "") {
                    $link_info .= "<IntLinkInfo Target=\"$target\" Type=\"$type\" " .
                        "TargetFrame=\"$targetframe\" LinkHref=\"$href\" LinkTarget=\"$ltarget\" LinkContent=\"$lcontent\" $anc_par/>";
                }
            }
        }
        $link_info .= "</IntLinkInfos>";

        $link_info .= $this->getLinkTargetsXML();
        return $link_info;
    }

    public function getFullscreenLink(): string
    {
        return $this->getLink("fullscreen");
    }
}
