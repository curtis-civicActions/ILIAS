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
 * TableGUI class for table NewsForContext
 *
 * @author Alexander Killing <killing@leifos.de>
 */
class ilNewsForContextTableGUI extends ilTable2GUI
{
    protected int $perm_ref_id = 0;
    protected ilAccessHandler $access;

    public function __construct(
        ilNewsItemGUI $a_parent_obj,
        string $a_parent_cmd = "",
        int $a_perm_ref_id = 0
    ) {
        global $DIC;

        $this->ctrl = $DIC->ctrl();
        $this->lng = $DIC->language();
        $this->access = $DIC->access();
        $ilCtrl = $DIC->ctrl();
        $lng = $DIC->language();

        parent::__construct($a_parent_obj, $a_parent_cmd);

        $this->perm_ref_id = $a_perm_ref_id;

        $this->addColumn("", "f", "1");
        $this->addColumn($lng->txt("news_news_item_content"));
        $this->addColumn($lng->txt("news_attached_to"));
        $this->addColumn($lng->txt("access"));
        $this->addColumn($lng->txt("author"));
        $this->addColumn($lng->txt("created"));
        $this->addColumn($lng->txt("last_update"));
        $this->addColumn($lng->txt("actions"));
        $this->setFormAction($ilCtrl->getFormAction($a_parent_obj));
        $this->setRowTemplate(
            "tpl.table_row_news_for_context.html",
            "Services/News"
        );
    }

    /**
    * Standard Version of Fill Row. Most likely to
    * be overwritten by derived class.
    */
    protected function fillRow(array $a_set): void
    {
        $lng = $this->lng;
        $ilCtrl = $this->ctrl;
        $ilAccess = $this->access;

        $news_set = new ilSetting("news");
        $enable_internal_rss = $news_set->get("enable_rss_for_internal");

        // user
        if ($a_set["user_id"] > 0) {
            $this->tpl->setCurrentBlock("user_info");
            $user_obj = new ilObjUser($a_set["user_id"]);
            $this->tpl->setVariable("VAL_AUTHOR", $user_obj->getLogin());
            $this->tpl->setVariable("TXT_AUTHOR", $lng->txt("author"));
            $this->tpl->parseCurrentBlock();
        }

        // access
        if ($enable_internal_rss) {
            $this->tpl->setCurrentBlock("access");
            $this->tpl->setVariable("TXT_ACCESS", $lng->txt("news_news_item_visibility"));
            if ($a_set["visibility"] === NEWS_PUBLIC ||
                ((int) $a_set["priority"] === 0 &&
                ilBlockSetting::_lookup(
                    "news",
                    "public_notifications",
                    0,
                    $a_set["context_obj_id"]
                ))) {
                $this->tpl->setVariable("VAL_ACCESS", $lng->txt("news_visibility_public"));
            } else {
                $this->tpl->setVariable("VAL_ACCESS", $lng->txt("news_visibility_users"));
            }
            $this->tpl->parseCurrentBlock();
        }

        // last update
        if ($a_set["creation_date"] !== $a_set["update_date"]) {
            $this->tpl->setCurrentBlock("ni_update");
            $this->tpl->setVariable("TXT_LAST_UPDATE", $lng->txt("last_update"));
            $this->tpl->setVariable(
                "VAL_LAST_UPDATE",
                ilDatePresentation::formatDate(new ilDateTime($a_set["update_date"], IL_CAL_DATETIME))
            );
            $this->tpl->parseCurrentBlock();
        }

        // creation date
        $this->tpl->setVariable(
            "VAL_CREATION_DATE",
            ilDatePresentation::formatDate(new ilDateTime($a_set["creation_date"], IL_CAL_DATETIME))
        );
        $this->tpl->setVariable("TXT_CREATED", $lng->txt("created"));

        // title
        $this->tpl->setVariable("VAL_TITLE", $a_set["title"]);

        // content
        if ($a_set["content"] != "") {
            $this->tpl->setCurrentBlock("content");
            $this->tpl->setVariable(
                "VAL_CONTENT",
                ilStr::shortenTextExtended($a_set["content"], 80, true, true)
            );
            $this->tpl->parseCurrentBlock();
        }

        $perm_ref_id = ($this->perm_ref_id > 0)
            ? $this->perm_ref_id
            : $a_set["ref_id"];

        if ($ilAccess->checkAccess("write", "", $perm_ref_id)) {
            $this->tpl->setCurrentBlock("edit");
            $this->tpl->setVariable("TXT_EDIT", $lng->txt("edit"));
            $ilCtrl->setParameterByClass("ilnewsitemgui", "news_item_id", $a_set["id"]);
            $this->tpl->setVariable(
                "CMD_EDIT",
                $ilCtrl->getLinkTargetByClass("ilnewsitemgui", "editNewsItem")
            );
            $this->tpl->parseCurrentBlock();
        }

        // context
        $this->tpl->setVariable(
            "CONTEXT",
            $lng->txt("obj_" . $a_set["context_obj_type"]) . ":<br />" .
            ilObject::_lookupTitle($a_set["context_obj_id"])
        );

        $this->tpl->setVariable("VAL_ID", $a_set["id"]);
    }
}
