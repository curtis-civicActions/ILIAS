<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2006 ILIAS open source, University of Cologne            |
	|                                                                             |
	| This program is free software; you can redistribute it and/or               |
	| modify it under the terms of the GNU General Public License                 |
	| as published by the Free Software Foundation; either version 2              |
	| of the License, or (at your option) any later version.                      |
	|                                                                             |
	| This program is distributed in the hope that it will be useful,             |
	| but WITHOUT ANY WARRANTY; without even the implied warranty of              |
	| MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the               |
	| GNU General Public License for more details.                                |
	|                                                                             |
	| You should have received a copy of the GNU General Public License           |
	| along with this program; if not, write to the Free Software                 |
	| Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. |
	+-----------------------------------------------------------------------------+
*/


include_once("./classes/class.ilObjectGUI.php");
include_once("./Modules/MediaPool/classes/class.ilObjMediaPool.php");
include_once("./Services/Table/classes/class.ilTableGUI.php");
include_once("./Modules/Folder/classes/class.ilObjFolderGUI.php");
include_once("./Services/MediaObjects/classes/class.ilObjMediaObjectGUI.php");
include_once("./Services/MediaObjects/classes/class.ilObjMediaObject.php");
include_once("./Services/Clipboard/classes/class.ilEditClipboardGUI.php");


/**
* User Interface class for media pool objects
*
* @author Alex Killing <alex.killing@gmx.de>
*
* $Id$
*
* @ilCtrl_Calls ilObjMediaPoolGUI: ilObjMediaObjectGUI, ilObjFolderGUI, ilEditClipboardGUI, ilPermissionGUI
*
* @ingroup ModulesMediaPool
*/
class ilObjMediaPoolGUI extends ilObjectGUI
{
	var $output_prepared;

	/**
	* Constructor
	*
	* @access	public
	*/
	function ilObjMediaPoolGUI($a_data,$a_id = 0,$a_call_by_reference = true, $a_prepare_output = false)
	{
		global $lng, $ilCtrl, $lng;

//echo "<br>ilobjmediapoolgui-constructor-id-$a_id";

		$this->ctrl =& $ilCtrl;
		$lng->loadLanguageModule("mep");
		
		if ($this->ctrl->getCmd() == "explorer")
		{
			$this->ctrl->saveParameter($this, array("ref_id"));
		}
		else
		{
			$this->ctrl->saveParameter($this, array("ref_id", "obj_id"));
		}
		
		$this->type = "mep";
		$lng->loadLanguageModule("content");
		parent::ilObjectGUI($a_data, $a_id, $a_call_by_reference, $a_prepare_output);

		$this->output_prepared = $a_prepare_output;

	}

	/**
	* execute command
	*/
	function &executeCommand()
	{
		global $ilTabs, $lng;
		
		if ($this->ctrl->getRedirectSource() == "ilinternallinkgui")
		{
			$this->explorer();
			return;
		}

		$next_class = $this->ctrl->getNextClass($this);
		$cmd = $this->ctrl->getCmd();

		$new_type = $_POST["new_type"]
			? $_POST["new_type"]
			: $_GET["new_type"];

		if ($new_type != "" && ($cmd != "confirmRemove" && $cmd != "copyToClipboard"
			&& $cmd != "pasteFromClipboard"))
		{
			$this->setCreationMode(true);
		}

		if (!$this->getCreationMode())
		{
			$tree =& $this->object->getTree();
			if ($_GET["obj_id"] == "")
			{
				$_GET["obj_id"] = $tree->getRootId();
			}
		}

		if ($cmd == "create")
		{
			switch($_POST["new_type"])
			{
				case "mob":
					$this->ctrl->redirectByClass("ilobjmediaobjectgui", "create");
					break;
					
				case "fold":
					$this->ctrl->redirectByClass("ilobjfoldergui", "create");
					break;
			}
		}

		switch($next_class)
		{
			case "ilobjmediaobjectgui":

				//$cmd.="Object";
				if ($cmd == "create" || $cmd == "save" || $cmd == "cancel")
				{
					$ret_obj = $_GET["obj_id"];
					$ilObjMediaObjectGUI =& new ilObjMediaObjectGUI("", 0, false, false);
				}
				else
				{
					$ret_obj = $tree->getParentId($_GET["obj_id"]);
					$ilObjMediaObjectGUI =& new ilObjMediaObjectGUI("", $_GET["obj_id"], false, false);
					$this->ctrl->setParameter($this, "obj_id", $this->getParentFolderId());
					$ilTabs->setBackTarget($lng->txt("back"),
						$this->ctrl->getLinkTarget($this, "listMedia"));
				}
				if ($this->ctrl->getCmdClass() == "ilinternallinkgui")
				{
					$this->ctrl->setReturn($this, "explorer");
				}
				else
				{
					$this->ctrl->setParameter($this, "obj_id", $ret_obj);
					$this->ctrl->setReturn($this, "listMedia");
					$this->ctrl->setParameter($this, "obj_id", $_GET["obj_id"]);
				}
				$this->getTemplate();
				$ilObjMediaObjectGUI->setAdminTabs();
				$this->setLocator();

//echo ":".$tree->getParentId($_GET["obj_id"]).":";
				//$ret =& $ilObjMediaObjectGUI->executeCommand();
				$ret =& $this->ctrl->forwardCommand($ilObjMediaObjectGUI);

//echo "<br>ilObjMediaPoolGUI:afterexecute:<br>"; exit;
				switch($cmd)
				{
					case "save":
						$parent = ($_GET["obj_id"] == "")
							? $tree->getRootId()
							: $_GET["obj_id"];
						$tree->insertNode($ret->getId(), $parent);
						ilUtil::redirect("ilias.php?baseClass=ilMediaPoolPresentationGUI&cmd=listMedia&ref_id=".
							$_GET["ref_id"]."&obj_id=".$_GET["obj_id"]);
						break;

					default:
						$this->tpl->show();
						break;
				}
				break;

			case "ilobjfoldergui":
				$folder_gui = new ilObjFolderGUI("", 0, false, false);
				$this->ctrl->setReturn($this, "listMedia");
				$cmd.="Object";
//echo "-$cmd-";
				switch($cmd)
				{
					case "createObject":
						$this->prepareOutput();
						$folder_gui =& new ilObjFolderGUI("", 0, false, false);
						$folder_gui->setFormAction("save",
							$this->ctrl->getFormActionByClass("ilobjfoldergui"));
						$folder_gui->createObject();
						$this->tpl->show();
						break;

					case "saveObject":
						//$folder_gui->setReturnLocation("save", $this->ctrl->getLinkTarget($this, "listMedia"));
						$parent = ($_GET["obj_id"] == "")
							? $tree->getRootId()
							: $_GET["obj_id"];
						$folder_gui->setFolderTree($tree);
						$folder_gui->saveObject($parent);
						//$this->ctrl->redirect($this, "listMedia");
						break;

					case "editObject":
						$this->prepareOutput();
						$folder_gui =& new ilObjFolderGUI("", $_GET["obj_id"], false, false);
						$this->ctrl->setParameter($this, "foldereditmode", "1");
						$folder_gui->setFormAction("update", $this->ctrl->getFormActionByClass("ilobjfoldergui"));
						$folder_gui->editObject();
						$this->tpl->show();
						break;

					case "updateObject":
						$folder_gui =& new ilObjFolderGUI("", $_GET["obj_id"], false, false);
						$this->ctrl->setParameter($this, "obj_id", $this->getParentFolderId());
						$this->ctrl->setReturn($this, "listMedia");
						$folder_gui->updateObject(true);		// this returns to parent
						break;

					case "cancelObject":
						ilUtil::sendInfo($this->lng->txt("action_aborted"), true);
						if ($_GET["foldereditmode"])
						{
							$this->ctrl->setParameter($this, "obj_id", $this->getParentFolderId());
						}
						$this->ctrl->redirect($this, "listMedia");
						break;
				}
				break;

			case "ileditclipboardgui":
				$this->prepareOutput();
				$this->ctrl->setReturn($this, "listMedia");
				$clip_gui = new ilEditClipboardGUI();
				$clip_gui->setMultipleSelections(true);
				$clip_gui->setInsertButtonTitle($lng->txt("mep_copy_to_mep"));
				$ilTabs->setTabActive("clipboard");
				//$ret =& $clip_gui->executeCommand();
				$ret =& $this->ctrl->forwardCommand($clip_gui);
				$this->tpl->show();
				break;
				
			case 'ilpermissiongui':
				$this->prepareOutput();
				include_once("./classes/class.ilPermissionGUI.php");
				$perm_gui =& new ilPermissionGUI($this);
				$ret =& $this->ctrl->forwardCommand($perm_gui);
				$this->tpl->show();
				break;

			default:
				$this->prepareOutput();
				$cmd = $this->ctrl->getCmd("frameset");
				if ($this->creation_mode)
				{
					$cmd.= "Object";
				}
				$this->$cmd();	
				break;
		}
	}
	
	function createObject()
	{
		parent::createObject();
		$this->tpl->setVariable("TARGET", ' target="'.
				ilFrameTargetInfo::_getFrame("MainContent").'" ');
	}
	
	function createMediaObject()
	{
		$this->ctrl->redirectByClass("ilobjmediaobjectgui", "create");
	}
	
	// for admin compatiblity
	function view()
	{
		$this->viewObject();
	}

	/**
	* save object
	* @access	public
	*/
	function saveObject()
	{
		global $rbacadmin;

		// create and insert forum in objecttree
		$newObj = parent::saveObject();

		// setup rolefolder & default local roles
		//$roles = $newObj->initDefaultRoles();

		// ...finally assign role to creator of object
		//$rbacadmin->assignUser($roles[0], $newObj->getOwner(), "y");

		// put here object specific stuff

		// always send a message
		ilUtil::sendInfo($this->lng->txt("object_added"),true);

		//ilUtil::redirect($this->getReturnLocation("save","adm_object.php?".$this->link_params));
		ilUtil::redirect("ilias.php?baseClass=ilMediaPoolPresentationGUI&ref_id=".$newObj->getRefId());

	}

	/**
	* edit properties of object (admin form)
	*
	* @access	public
	*/
	function editObject()
	{
		global $ilAccess, $tree, $tpl;

		if (!$ilAccess->checkAccess("write", "", $this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("permission_denied"),$this->ilias->error_obj->MESSAGE);
		}

		// edit button
		$this->tpl->addBlockfile("BUTTONS", "buttons", "tpl.buttons.html");

		parent::editObject();
	}

	/**
	* edit properties of object (module form)
	*/
	function edit()
	{
		$this->editObject();
		$this->tpl->show();
	}

	/**
	* cancel editing
	*/
	function cancel()
	{
		$this->ctrl->redirect($this, "listMedia");
	}
	
	/**
	* cancel action and go back to previous page
	* @access	public
	*
	*/
	function cancelObject($in_rep = false)
	{
		ilUtil::sendInfo($this->lng->txt("msg_cancel"),true);
		ilUtil::redirect("repository.php?cmd=frameset&ref_id=".$_GET["ref_id"]);
		//$this->ctrl->redirectByClass("ilrepositorygui", "frameset");
	}


	/**
	* update properties
	*/
	function update()
	{
		global $ilAccess;

		if (!$ilAccess->checkAccess("write", "", $this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("permission_denied"),$this->ilias->error_obj->MESSAGE);
		}

		$this->updateObject();
	}

	function afterUpdate()
	{
		$this->ctrl->redirect($this, "listMedia");
	}

	/**
	* list media objects
	*/
	function listMedia()
	{
		global $tree, $ilAccess, $tpl;

		if (!$ilAccess->checkAccess("read", "", $this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("permission_denied"),$this->ilias->error_obj->MESSAGE);
		}

		include_once("./Modules/MediaPool/classes/class.ilMediaPoolTableGUI.php");
		$mep_table_gui = new ilMediaPoolTableGUI($this, "listMedia", $this->object);
		$tpl->setContent($mep_table_gui->getHTML());
		$this->tpl->show();
	}

	/**
	* Get standard template
	*/
	function getTemplate()
	{
		$this->tpl->getStandardTemplate();
	}


	/**
	* Get folder parent ID
	*/
	function getParentFolderId()
	{
		if ($_GET["obj_id"] == "")
		{
			return "";
		}
		$par_id = $this->object->tree->getParentId($_GET["obj_id"]);
		if ($par_id != $this->object->tree->getRootId())
		{
			return $par_id;
		}
		else
		{
			return "";
		}
	}
	
	/**
	* show upper icon (standard procedure will work, if no
	* obj_id is given)
	*/
	function showUpperIcon()
	{
		global $tpl;
		
		if ($this->ctrl->getCmd() == "explorer")
		{
			return;
		}
		
		parent::showUpperIcon();
		
		$mep_tree =& $this->object->getTree();
		if ($_GET["obj_id"] != "" && $_GET["obj_id"] != $mep_tree->getRootId())
		{
			$this->ctrl->setParameter($this, "obj_id",
				$this->getParentFolderId());
			$tpl->setUpperIcon($this->ctrl->getLinkTarget($this, "listMedia"));
			$this->ctrl->setParameter($this, "obj_id", $_GET["obj_id"]);
		}
	}
	
	/**
	* output main frameset of media pool
	* left frame: explorer tree of folders
	* right frame: media pool content
	*/
	function frameset()
	{
		include_once("Services/Frameset/classes/class.ilFramesetGUI.php");
		$fs_gui = new ilFramesetGUI();
		$fs_gui->setMainFrameName("content");
		$fs_gui->setSideFrameName("tree");
		$fs_gui->setMainFrameSource(
			$this->ctrl->getLinkTarget($this, "listMedia"));
		$this->ctrl->setParameter($this, "expand", "1");
		$fs_gui->setSideFrameSource(
			$this->ctrl->getLinkTarget($this, "explorer"));
		$fs_gui->setFramesetTitle($this->object->getTitle());
		$fs_gui->show();
		exit;
	}

	/**
	* output explorer tree
	*/
	function explorer()
	{
		$_GET["obj_id"] = "";
		
		$this->tpl = new ilTemplate("tpl.main.html", true, true);

		$this->tpl->setVariable("LOCATION_STYLESHEET", ilUtil::getStyleSheetLocation());

		$this->tpl->addBlockFile("CONTENT", "content", "tpl.explorer.html");
		$this->tpl->setVariable("IMG_SPACE", ilUtil::getImagePath("spacer.gif", false));

		require_once ("./Modules/MediaPool/classes/class.ilMediaPoolExplorer.php");
//echo "-".$this->ctrl->getLinkTarget($this, "listMedia")."-";
		$exp = new ilMediaPoolExplorer($this->ctrl->getLinkTarget($this, "listMedia"), $this->object);
		$exp->setTargetGet("obj_id");
		$exp->setExpandTarget($this->ctrl->getLinkTarget($this, "explorer"));

		$exp->addFilter("root");
		$exp->addFilter("fold");
		$exp->setFiltered(true);
		$exp->setFilterMode(IL_FM_POSITIVE);


		if ($_GET["mepexpand"] == "")
		{
			$mep_tree =& $this->object->getTree();
			$expanded = $mep_tree->readRootId();
		}
		else
		{
			$expanded = $_GET["mepexpand"];
		}

		$exp->setExpand($expanded);

		// build html-output
		$exp->setOutput(0);
		$output = $exp->getOutput();

		$this->tpl->setCurrentBlock("content");
		$this->tpl->setVariable("TXT_EXPLORER_HEADER", $this->lng->txt("cont_mep_structure"));
		$this->tpl->setVariable("EXP_REFRESH", $this->lng->txt("refresh"));
		$this->tpl->setVariable("EXPLORER",$output);
		$this->ctrl->setParameter($this, "mepexpand", $_GET["mepexpand"]);
		$this->tpl->setVariable("ACTION",
			$this->ctrl->getLinkTarget($this, "explorer"));
		$this->tpl->parseCurrentBlock();
		$this->tpl->show(false);

	}
	
	/**
	* show media object
	*/
	function showMedia()
	{
		global $ilAccess;
		
		if (!$ilAccess->checkAccess("read", "", $this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("permission_denied"),$this->ilias->error_obj->MESSAGE);
		}
		
		$this->tpl =& new ilTemplate("tpl.fullscreen.html", true, true, "Services/COPage");
		include_once("Services/Style/classes/class.ilObjStyleSheet.php");
		$this->tpl->setVariable("LOCATION_STYLESHEET", ilUtil::getStyleSheetLocation());
		$this->tpl->setVariable("LOCATION_CONTENT_STYLESHEET",
			ilObjStyleSheet::getContentStylePath(0));

		//$int_links = $page_object->getInternalLinks();
		$med_links = ilMediaItem::_getMapAreasIntLinks($_GET["mob_id"]);
		
		// later
		//$link_xml = $this->getLinkXML($med_links, $this->getLayoutLinkTargets());
		
		$link_xlm = "";

		require_once("./Services/MediaObjects/classes/class.ilObjMediaObject.php");
		$media_obj =& new ilObjMediaObject($_GET["mob_id"]);
		
		$xml = "<dummy>";
		// todo: we get always the first alias now (problem if mob is used multiple
		// times in page)
		$xml.= $media_obj->getXML(IL_MODE_ALIAS);
		$xml.= $media_obj->getXML(IL_MODE_OUTPUT);
		$xml.= $link_xml;
		$xml.="</dummy>";

		$xsl = file_get_contents("./Services/COPage/xsl/page.xsl");
		$args = array( '/_xml' => $xml, '/_xsl' => $xsl );
		$xh = xslt_create();

		$wb_path = ilUtil::getWebspaceDir("output");

		$mode = ($_GET["cmd"] != "showMedia")
			? "fullscreen"
			: "media";
		$enlarge_path = ilUtil::getImagePath("enlarge.gif", false, "output");
		$fullscreen_link =
			$this->ctrl->getLinkTarget($this, "showFullscreen");
		$params = array ('mode' => $mode, 'enlarge_path' => $enlarge_path,
			'link_params' => "ref_id=".$_GET["ref_id"],'fullscreen_link' => $fullscreen_link,
			'ref_id' => $_GET["ref_id"], 'pg_frame' => $pg_frame, 'webspace_path' => $wb_path);
		$output = xslt_process($xh,"arg:/_xml","arg:/_xsl",NULL,$args, $params);
		echo xslt_error($xh);
		xslt_free($xh);

		// unmask user html
		$this->tpl->setVariable("MEDIA_CONTENT", $output);

		$this->tpl->parseCurrentBlock();
		$this->tpl->show();
	}

	/**
	* show fullscreen 
	*/
	function showFullscreen()
	{
		$this->showMedia();
	}
	
	/**
	* confirm remove of mobs
	*/
	function confirmRemove()
	{
		global $ilAccess;
		
		if (!$ilAccess->checkAccess("write", "", $this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("permission_denied"),$this->ilias->error_obj->MESSAGE);
		}

		if(!isset($_POST["id"]))
		{
			$this->ilias->raiseError($this->lng->txt("no_checkbox"),$this->ilias->error_obj->MESSAGE);
		}

		//$this->prepareOutput();

		// SAVE POST VALUES
		$_SESSION["ilMepRemove"] = $_POST["id"];

		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.confirm_deletion.html", "Modules/MediaPool");

		ilUtil::sendInfo($this->lng->txt("info_delete_sure"));

		$this->tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this));

		// BEGIN TABLE HEADER
		$this->tpl->setCurrentBlock("table_header");
		$this->tpl->setVariable("TEXT",$this->lng->txt("objects"));
		$this->tpl->parseCurrentBlock();

		// BEGIN TABLE DATA
		$counter = 0;
		foreach($_POST["id"] as $obj_id)
		{
			$type = ilObject::_lookupType($obj_id);
			$title = ilObject::_lookupTitle($obj_id);
			$this->tpl->setCurrentBlock("table_row");
			$this->tpl->setVariable("CSS_ROW",ilUtil::switchColor(++$counter,"tblrow1","tblrow2"));
			$this->tpl->setVariable("TEXT_CONTENT", $title);
			$this->tpl->setVariable("IMG_OBJ", ilUtil::getImagePath("icon_".$type.".gif"));
			$this->tpl->parseCurrentBlock();
		}

		// cancel/confirm button
		$this->tpl->setVariable("IMG_ARROW",ilUtil::getImagePath("arrow_downright.gif"));
		$buttons = array( "cancelRemove"  => $this->lng->txt("cancel"),
			"remove"  => $this->lng->txt("confirm"));
		foreach ($buttons as $name => $value)
		{
			$this->tpl->setCurrentBlock("operation_btn");
			$this->tpl->setVariable("BTN_NAME",$name);
			$this->tpl->setVariable("BTN_VALUE",$value);
			$this->tpl->parseCurrentBlock();
		}
		$this->tpl->show();
	}
	
	/**
	* paste from clipboard
	*/
	function openClipboard()
	{
		global $ilCtrl, $ilAccess;
		
		if (!$ilAccess->checkAccess("write", "", $this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("permission_denied"),$this->ilias->error_obj->MESSAGE);
		}

		$ilCtrl->setParameterByClass("ileditclipboardgui", "returnCommand",
			rawurlencode($ilCtrl->getLinkTarget($this,
			"insertFromClipboard")));
		$ilCtrl->redirectByClass("ilEditClipboardGUI", "getObject");
	}
	
	
	/**
	* insert media object from clipboard
	*/
	function insertFromClipboard()
	{
		global $ilAccess;
		
		if (!$ilAccess->checkAccess("write", "", $this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("permission_denied"),$this->ilias->error_obj->MESSAGE);
		}

		include_once("./Services/Clipboard/classes/class.ilEditClipboardGUI.php");
		$ids = ilEditClipboardGUI::_getSelectedIDs();
		$not_inserted = array();
		if (is_array($ids))
		{
			foreach ($ids as $id)
			{
				if (!$this->object->insertInTree($id, $_GET["obj_id"]))
				{
					$not_inserted[] = ilObject::_lookupTitle($id)." [".
						$id."]";
				}
			}
		}
		if (count($not_inserted) > 0)
		{
			ilUtil::sendInfo($this->lng->txt("mep_not_insert_already_exist")."<br>".
				implode($not_inserted,"<br>"), true);
		}
		$this->ctrl->redirect($this, "listMedia");
	}


	/**
	* cancel deletion of media objects/folders
	*/
	function cancelRemove()
	{
		session_unregister("ilMepRemove");
		$this->ctrl->redirect($this, "listMedia");
	}

	/**
	* confirm deletion of
	*/
	function remove()
	{
		global $ilAccess;
		
		if (!$ilAccess->checkAccess("write", "", $this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("permission_denied"),$this->ilias->error_obj->MESSAGE);
		}

		foreach($_SESSION["ilMepRemove"] as $obj_id)
		{
			$this->object->deleteChild($obj_id);
		}

		ilUtil::sendInfo($this->lng->txt("cont_obj_removed"),true);
		session_unregister("ilMepRemove");
		$this->ctrl->redirect($this, "listMedia");
	}


	/**
	* copy media objects to clipboard
	*/
	function copyToClipboard()
	{
		global $ilUser, $ilAccess;
		
		if (!$ilAccess->checkAccess("write", "", $this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("permission_denied"),$this->ilias->error_obj->MESSAGE);
		}

		if(!isset($_POST["id"]))
		{
			$this->ilias->raiseError($this->lng->txt("no_checkbox"),$this->ilias->error_obj->MESSAGE);
		}

		foreach ($_POST["id"] as $obj_id)
		{
			$type = ilObject::_lookupType($obj_id);
			if ($type == "fold")
			{
				$this->ilias->raiseError($this->lng->txt("cont_cant_copy_folders"), $this->ilias->error_obj->MESSAGE);
			}
		}

		foreach ($_POST["id"] as $obj_id)
		{
			$ilUser->addObjectToClipboard($obj_id, "mob", "");
		}

		ilUtil::sendInfo($this->lng->txt("copied_to_clipboard"),true);
		$this->ctrl->redirect($this, "listMedia");
	}

	/**
	* add locator items for media pool
	*/
	function addLocatorItems()
	{
		global $ilLocator;
		
		if (!$this->getCreationMode() && $this->ctrl->getCmd() != "explorer")
		{
			$tree =& $this->object->getTree();
			$obj_id = ($_GET["obj_id"] == "")
				? $tree->getRootId()
				: $_GET["obj_id"];
			$path = $tree->getPathFull($obj_id);
			foreach($path as $node)
			{
				if ($node["child"] == $tree->getRootId())
				{
					$this->ctrl->setParameter($this, "obj_id", "");
					$link = $this->ctrl->getLinkTarget($this, "listMedia");
					$title = $this->object->getTitle();
					$this->ctrl->setParameter($this, "obj_id", $_GET["obj_id"]);
					$ilLocator->addItem($title, $link, "", $_GET["ref_id"]);
				}
				else
				{
					$this->ctrl->setParameter($this, "obj_id", $node["child"]);
					$link = $this->ctrl->getLinkTarget($this, "listMedia");
					$title = $node["title"];
					$this->ctrl->setParameter($this, "obj_id", $_GET["obj_id"]);
					$ilLocator->addItem($title, $link);
				}
			}
		}
	}
	
	/**
	* create folder form
	*/
	function createFolderForm()
	{
		global $ilAccess;
		
		if (!$ilAccess->checkAccess("write", "", $this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("permission_denied"),$this->ilias->error_obj->MESSAGE);
		}

		$folder_gui =& new ilObjFolderGUI("", 0, false, false);
		$this->ctrl->setParameterByClass("ilobjfoldergui", "obj_id", $_GET["obj_id"]);
		$folder_gui->setFormAction("save",
			$this->ctrl->getFormActionByClass("ilobjfoldergui"));
		$folder_gui->createObject();
		$this->tpl->show();
	}


	/**
	* output tabs
	*/
	function setTabs()
	{
		$this->getTabs($this->tabs_gui);
	}

	/**
	* adds tabs to tab gui object
	*
	* @param	object		$tabs_gui		ilTabsGUI object
	*/
	function getTabs(&$tabs_gui)
	{
		global $ilAccess;
		
		$tabs_gui->addTarget("view_content", $this->ctrl->getLinkTarget($this, "listMedia"),
			"listMedia", "");

		if ($ilAccess->checkAccess("write", "", $this->object->getRefId()))
		{
			$tabs_gui->addTarget("edit_properties", $this->ctrl->getLinkTarget($this, "edit"),
				"edit", array("", "ilobjmediapoolgui"));
		}

		if ($ilAccess->checkAccess("edit_permission", "", $this->object->getRefId()))
		{
			$tabs_gui->addTarget("perm_settings",
				$this->ctrl->getLinkTargetByClass(array(get_class($this),'ilpermissiongui'), "perm"), array("perm","info","owner"), 'ilpermissiongui');
		}

		if ($ilAccess->checkAccess("write", "", $this->object->getRefId()))
		{
			$tabs_gui->addTarget("clipboard", $this->ctrl->getLinkTarget($this, "openClipboard"),
				"view", "ileditclipboardgui");
		}
	}


	/**
	* goto target media pool
	*/
	function _goto($a_target)
	{
		global $ilAccess, $ilErr, $lng;

		if ($ilAccess->checkAccess("read", "", $a_target))
		{
			$_GET["cmd"] = "frameset";
			$_GET["baseClass"] = "ilMediaPoolPresentationGUI";
			$_GET["ref_id"] = $a_target;
			include("ilias.php");
			exit;
		} else if ($ilAccess->checkAccess("read", "", ROOT_FOLDER_ID))
		{
			$_GET["cmd"] = "frameset";
			$_GET["target"] = "";
			$_GET["ref_id"] = ROOT_FOLDER_ID;
			ilUtil::sendInfo(sprintf($lng->txt("msg_no_perm_read_item"),
				ilObject::_lookupTitle(ilObject::_lookupObjId($a_target))), true);
			include("repository.php");
			exit;
		}

		$ilErr->raiseError($lng->txt("msg_no_perm_read"), $ilErr->FATAL);
	}

}
?>
