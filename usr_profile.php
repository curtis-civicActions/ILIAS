<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2001 ILIAS open source, University of Cologne            |
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


/**
* change user profile
*
* @author Peter Gabriel <pgabriel@databay.de>
* @version $Id$
*
* @package ilias
*/
require_once "./include/inc.header.php";

// catch hack attempts
if ($_SESSION["AccountId"] == ANONYMOUS_USER_ID)
{
	$ilias->raiseError($lng->txt("msg_not_available_for_anon"),$ilias->error_obj->MESSAGE);
}

if (isset($_POST))
{
	foreach ($_POST as $key => $val)
	{
		$POST[$key] = ilUtil::prepareFormOutput($val);
	}
}

$webspace_dir = $ilias->ini->readVariable("server","webspace_dir");

//$image_dir = $webspace_dir."/usr_images";

// purpose is to upload file of user
// function added by ratana ty
function upload_file()
{
	global $upload_error,$lng;
	global $userfile, $userfile_name, $userfile_size,
	$userfile_type, $archive_dir, $WINDIR,$ilias,$lng;
	global $target_file, $return_path;

	if ($_FILES["userfile"]["size"] == 0)
	{
		$upload_error=$lng->txt("msg_no_file");
		return;
	}

// TODO
// Check the type of file and then check the size
// of the file whether we allow people to upload or not


	$webspace_dir = ilUtil::getWebspaceDir();
	$image_dir = $webspace_dir."/usr_images";

	$path_info = pathinfo($_FILES["userfile"]["name"]);
	$target_file = $image_dir."/usr_".$ilias->account->getId()."."."jpg";
	$store_file = "usr_".$ilias->account->getID()."."."jpg";
	$ilias->account->setPref("profile_image", $store_file);
	$ilias->account->update();
	move_uploaded_file($_FILES["userfile"]["tmp_name"],$target_file);
	//echo "from:".$_FILES["userfile"]["tmp_name"]."to:".$target_file."<br>";
	// by default after copy it will loss
	// some permission so set it to readable
	chmod($target_file, 0770);

	// got file name (ex-usr_6.jpg) change then convert it to
	// the appropriate size and only jpg format
	$rename_file = "usr_".$ilias->account->getId().
		".".$path_info["extension"];
	$part = explode(".",$rename_file);
	$show_file = $image_dir."/".$part[0].".jpg";
	$thumb_file = $image_dir."/".$part[0]."_small.jpg";

	if (@is_file($thumb_file))
	{
		unlink($thumb_file);
	}
	system(ilUtil::getConvertCmd()." $target_file -geometry 200x200 JPEG:$show_file");
	system(ilUtil::getConvertCmd()." $target_file -geometry 100x100  JPEG:$thumb_file");

	if (!@is_file($thumb_file))
	{
		//$ilias->raiseError($lng->txt("image_gen_unsucc"), $ilias->error_obj->MESSAGE);
		sendInfo($lng->txt("image_gen_unsucc"), true);
		header ("Location: usr_profile.php");
		exit();

	}

	return $target_file;
}
// End of function upload file

// change user password
function change_password()
{
	global $ilias, $lng, $tpl, $password_error;

	// check old password
	if (md5($_POST["current_password"]) != $ilias->account->getPasswd())
	{
		$password_error=$lng->txt("passwd_wrong");
		//$ilias->raiseError($lng->txt("passwd_wrong"),$ilias->error_obj->MESSAGE);
	}

	// check new password
	else if ($_POST["desired_password"] != $_POST["retype_password"])
	{
		$password_error=$lng->txt("passwd_not_match");
		//$ilias->raiseError($lng->txt("passwd_not_match"),$ilias->error_obj->MESSAGE);
	}

	// validate password
	else if (!ilUtil::isPassword($_POST["desired_password"]))
	{
		$password_error=$lng->txt("passwd_invalid");
		//$ilias->raiseError($lng->txt("passwd_invalid"),$ilias->error_obj->MESSAGE);
	}

	else if ($_POST["current_password"] != "")
	{
		$ilias->account->updatePassword($_POST["current_password"], $_POST["desired_password"], $_POST["retype_password"]);

		/*if ($ilias->account->updatePassword($_POST["current_password"], $_POST["desired_password"], $_POST["retype_password"]))
		{
			sendInfo($lng->txt("msg_changes_ok"));

		}
		else
		{
			sendInfo($lng->txt("msg_failed"));

		}*/
	}
}
// End of function chnage_password

$tpl->addBlockFile("CONTENT", "content", "tpl.usr_profile.html");
$tpl->addBlockFile("STATUSLINE", "statusline", "tpl.statusline.html");
$tpl->addBlockFile("LOCATOR", "locator", "tpl.locator.html");
//$tpl->addBlockfile("BUTTONS", "buttons", "tpl.buttons.html");

// set locator
$tpl->setVariable("TXT_LOCATOR",$lng->txt("locator"));
$tpl->touchBlock("locator_separator");
$tpl->setCurrentBlock("locator_item");
$tpl->setVariable("ITEM", $lng->txt("personal_desktop"));
$tpl->setVariable("LINK_ITEM", "usr_personaldesktop.php");
$tpl->parseCurrentBlock();

$tpl->setCurrentBlock("locator_item");
$tpl->setVariable("ITEM", $lng->txt("personal_profile"));
$tpl->setVariable("LINK_ITEM", "usr_profile.php");
$tpl->parseCurrentBlock();

// catch feedback message
sendInfo();
// display infopanel if something happened
infoPanel();

// display tabs
include "./include/inc.personaldesktop_buttons.php";

// To display picture after Uploaded
//$tpl->setVariable("IMAGE_PATH","./".$webspace_dir."/usr_images/".$ilias->account->prefs["profile_image"]);

// if data are posted check on upload button

//if data are posted
if ($_GET["cmd"] == "save")
{
	$upload_error;

	// upload usr_image
	if (!empty($_POST["usr_upload"]))
	{
		upload_file();
	}
	
	// error content
	$password_error;

	//change password
	if (!empty($_POST["usr_change_password"]))
	{
		change_password();
	}

	//init checking var
	$form_valid = true;

	// testing by ratana ty:
	// if people check on check box it will
	// write some datata to table usr_pref
	// if check on Public Profile
	if (($_POST["chk_pub"])=="on")
	{
		$ilias->account->setPref("public_profile","y");
	}
	else
	{
		$ilias->account->setPref("public_profile","n");
	}

	// if check on Institute
	$val_array = array("institution", "department", "upload", "street",
		"zip", "city", "country", "phone_office", "phone_home", "phone_mobile",
		"fax", "email", "hobby");

	foreach ($val_array as $key => $value)
	{
		if (($_POST["chk_".$value]) == "on")
		{
			$ilias->account->setPref("public_".$value,"y");
		}
		else
		{
			$ilias->account->setPref("public_".$value,"n");
		}
	}

	// check required fields
	if (empty($_POST["usr_fname"]) or empty($_POST["usr_lname"])
		 or empty($_POST["usr_email"]) or empty($_POST["usr_gender"]))
	{
		sendInfo($lng->txt("fill_out_all_required_fields"));
		$form_valid = false;
	}

	// check email adress
	if (!ilUtil::is_email($_POST["usr_email"]) and !empty($_POST["usr_email"]) and $form_valid)
	{
		sendInfo($lng->txt("email_not_valid"));
		$form_valid = false;
	}

	//update user data (not saving!)
	$ilias->account->setFirstName(ilUtil::stripSlashes($_POST["usr_fname"]));
	$ilias->account->setLastName(ilUtil::stripSlashes($_POST["usr_lname"]));
	$ilias->account->setGender($_POST["usr_gender"]);
	$ilias->account->setUTitle(ilUtil::stripSlashes($_POST["usr_title"]));
	$ilias->account->setFullname();
	// added for upload by ratana ty
	//$ilias->account->setFile($_POST["usr_file"]);
	$ilias->account->setInstitution(ilUtil::stripSlashes($_POST["usr_institution"]));
	$ilias->account->setDepartment(ilUtil::stripSlashes($_POST["usr_department"]));
	$ilias->account->setStreet(ilUtil::stripSlashes($_POST["usr_street"]));
	$ilias->account->setZipcode($_POST["usr_zipcode"]);
	$ilias->account->setCity(ilUtil::stripSlashes($_POST["usr_city"]));
	$ilias->account->setCountry(ilUtil::stripSlashes($_POST["usr_country"]));
	$ilias->account->setPhoneOffice($_POST["usr_phone_office"]);
	$ilias->account->setPhoneHome($_POST["usr_phone_home"]);
	$ilias->account->setPhoneMobile($_POST["usr_phone_mobile"]);
	$ilias->account->setFax($_POST["usr_fax"]);
	$ilias->account->setEmail($_POST["usr_email"]);
	$ilias->account->setHobby(ilUtil::stripSlashes($_POST["usr_hobby"]));

	// everthing's ok. save form data
	if ($form_valid)
	{
		// init reload var. page should only be reloaded if skin or style were changed
		$reload = false;

		//set user skin and style
		if ($_POST["usr_skin_style"] != "")
		{
			$sknst = explode(":", $_POST["usr_skin_style"]);

			if ($ilias->account->getPref("style") != $sknst[1] ||
				$ilias->account->getPref("skin") != $sknst[0])
			{
				$ilias->account->setPref("skin", $sknst[0]);
				$ilias->account->setPref("style", $sknst[1]);
				$reload = true;
			}
		}

		// set user language
		if ($_POST["usr_language"] != "" and $_POST["usr_language"] != $ilias->account->getLanguage())
		{
				$ilias->account->setPref("language",$_POST["usr_language"]);
				$reload = true;
		}

		// set user hits per page
		if ($_POST["usr_hits_per_page"] != "")
		{
			$ilias->account->setPref("hits_per_page",$_POST["usr_hits_per_page"]);
		}

		// save user data & object_data
		$ilias->account->setTitle($ilias->account->getFullname());
		$ilias->account->setDescription($ilias->account->getEmail());
		$ilias->account->update();

		// reload page only if skin or style were changed
		if ($reload)
		{
			// feedback
			sendInfo($lng->txt("saved_successfully"));
			$tpl->setVariable("RELOAD","<script language=\"Javascript\">\ntop.location.href = \"./start.php\";\n</script>\n");
		}
		else
		{
			// feedback
			if (!empty($password_error))
			{
				sendInfo($password_error,true);
			}
			elseif (!empty($upload_error))
			{
				sendInfo($upload_error,true);
			}
			else
			{
				sendInfo($lng->txt("saved_successfully"),true);
			}
			
			header ("Location: usr_profile.php");
			exit();
		}

	}
}

//get all languages
$languages = $lng->getInstalledLanguages();

// preselect previous chosen language otherwise saved language
$selected_lang = (isset($_POST["usr_language"])) ? $_POST["usr_language"] : $ilias->account->getLanguage();

//go through languages
foreach ($languages as $lang_key)
{
	$tpl->setCurrentBlock("sel_lang");
	$tpl->setVariable("LANG", $lng->txt("lang_".$lang_key));
	$tpl->setVariable("LANGSHORT", $lang_key);

	if ($selected_lang == $lang_key)
	{
		$tpl->setVariable("SELECTED_LANG", "selected=\"selected\"");
	}

	$tpl->parseCurrentBlock();
}

// get all templates
$templates = $styleDefinition->getAllTemplates();

foreach ($templates as $template)
{
	// get styles information of template
	$styleDef =& new ilStyleDefinition($template["id"]);
	$styleDef->startParsing();
	$styles = $styleDef->getStyles();

	foreach($styles as $style)
	{
		$tpl->setCurrentBlock("selectskin");

		if ($ilias->account->skin == $template["id"] &&
			$ilias->account->prefs["style"] == $style["id"])
		{
			$tpl->setVariable("SKINSELECTED", "selected=\"selected\"");
		}

		$tpl->setVariable("SKINVALUE", $template["id"].":".$style["id"]);
		$tpl->setVariable("SKINOPTION", $styleDef->getTemplateName()." / ".$style["name"]);
		$tpl->parseCurrentBlock();
	}
}

// hits per page
$hits_options = array(2,10,15,20,30,40,50,100,9999);

foreach ($hits_options as $hits_option)
{
	$tpl->setCurrentBlock("selecthits");

	if ($ilias->account->prefs["hits_per_page"] == $hits_option)
	{
		$tpl->setVariable("HITSSELECTED", "selected=\"selected\"");
	}

	$tpl->setVariable("HITSVALUE", $hits_option);

	if ($hits_option == 9999)
	{
		$hits_option = $lng->txt("no_limit");
	}

	$tpl->setVariable("HITSOPTION", $hits_option);
	$tpl->parseCurrentBlock();
}

$tpl->setCurrentBlock("content");
$tpl->setVariable("FORMACTION", "usr_profile.php?cmd=save");

$tpl->setVariable("TXT_PAGEHEADLINE", $lng->txt("personal_desktop"));
$tpl->setVariable("TXT_OF",strtolower($lng->txt("of")));
$tpl->setVariable("USR_FULLNAME",$ilias->account->getFullname());

$tpl->setVariable("TXT_USR_DATA", $lng->txt("userdata"));
$tpl->setVariable("TXT_NICKNAME", $lng->txt("username"));
$tpl->setVariable("TXT_PUBLIC_PROFILE", $lng->txt("public_profile"));
$tpl->setVariable("TXT_GENDER",$lng->txt("gender"));
$tpl->setVariable("TXT_GENDER_F",$lng->txt("gender_f"));
$tpl->setVariable("TXT_GENDER_M",$lng->txt("gender_m"));
$tpl->setVariable("TXT_FIRSTNAME",$lng->txt("firstname"));
// todo
// capture image name including path ($archive_dir/$filename)
//$tpl->setVariable("IMAGE_PATH",$return_path);
//$tpl->setVariable("IMAGE_PATH",'$archive_dir."/".$filename');

$tpl->setVariable("TXT_LASTNAME",$lng->txt("lastname"));
$tpl->setVariable("TXT_TITLE",$lng->txt("title"));
$tpl->setVariable("TXT_UPLOAD",$lng->txt("personal_picture"));

$webspace_dir = ilUtil::getWebspaceDir("output");
$full_img = $ilias->account->getPref("profile_image");
$last_dot = strrpos($full_img, ".");
$small_img = substr($full_img, 0, $last_dot).
	"_small".substr($full_img, $last_dot, strlen($full_img) - $last_dot);
$image_file = $webspace_dir."/usr_images/".$small_img;

if (@is_file($image_file))
{
	$tpl->setCurrentBlock("pers_image");
	$tpl->parseCurrentBlock();
	$tpl->setVariable("IMG_PERSONAL", $image_file."?dummy=".rand(1,99999));
	$tpl->setCurrentBlock("content");
}

$tpl->setVariable("UPLOAD",$lng->txt("upload"));
$tpl->setVariable("TXT_FILE", $lng->txt("userfile"));
$tpl->setVariable("USER_FILE", $lng->txt("user_file"));

$tpl->setVariable("TXT_CHANGE_PASSWORD", $lng->txt("chg_password"));
$tpl->setVariable("TXT_CURRENT_PW", $lng->txt("current_password"));
$tpl->setVariable("TXT_DESIRED_PW", $lng->txt("desired_password"));
$tpl->setVariable("TXT_RETYPE_PW", $lng->txt("retype_password"));
$tpl->setVariable("CHANGE_PASSWORD",$lng->txt("chg_password"));

$tpl->setVariable("TXT_INSTITUTION",$lng->txt("institution"));
$tpl->setVariable("TXT_DEPARTMENT",$lng->txt("department"));
$tpl->setVariable("TXT_STREET",$lng->txt("street"));
$tpl->setVariable("TXT_ZIPCODE",$lng->txt("zipcode"));
$tpl->setVariable("TXT_CITY",$lng->txt("city"));
$tpl->setVariable("TXT_COUNTRY",$lng->txt("country"));
$tpl->setVariable("TXT_PHONE_OFFICE",$lng->txt("phone_office"));
$tpl->setVariable("TXT_PHONE_HOME",$lng->txt("phone_home"));
$tpl->setVariable("TXT_PHONE_MOBILE",$lng->txt("phone_mobile"));
$tpl->setVariable("TXT_FAX",$lng->txt("fax"));
$tpl->setVariable("TXT_EMAIL",$lng->txt("email"));
$tpl->setVariable("TXT_HOBBY",$lng->txt("hobby"));					// here
$tpl->setVariable("TXT_DEFAULT_ROLES",$lng->txt("default_roles"));
$tpl->setVariable("TXT_LANGUAGE",$lng->txt("language"));
$tpl->setVariable("TXT_USR_SKIN_STYLE",$lng->txt("usr_skin_style"));
$tpl->setVariable("TXT_USR_HITS_PER_PAGE",$lng->txt("usr_hits_per_page"));
$tpl->setVariable("TXT_PERSONAL_DATA", $lng->txt("personal_data"));
$tpl->setVariable("TXT_CONTACT_DATA", $lng->txt("contact_data"));
$tpl->setVariable("TXT_SETTINGS", $lng->txt("settings"));

//values
$tpl->setVariable("NICKNAME", ilUtil::prepareFormOutput($ilias->account->getLogin()));
$tpl->setVariable("FIRSTNAME", ilUtil::prepareFormOutput($ilias->account->getFirstname()));
$tpl->setVariable("LASTNAME", ilUtil::prepareFormOutput($ilias->account->getLastname()));

// gender selection
$gender = strtoupper($ilias->account->getGender());

if (!empty($gender))
{
	$tpl->setVariable("BTN_GENDER_".$gender,"checked=\"checked\"");
}

$tpl->setVariable("TITLE", ilUtil::prepareFormOutput($ilias->account->getUTitle()));
$tpl->setVariable("INSTITUTION", ilUtil::prepareFormOutput($ilias->account->getInstitution()));
$tpl->setVariable("DEPARTMENT", ilUtil::prepareFormOutput($ilias->account->getDepartment()));
$tpl->setVariable("STREET", ilUtil::prepareFormOutput($ilias->account->getStreet()));
$tpl->setVariable("ZIPCODE", $ilias->account->getZipcode());
$tpl->setVariable("CITY", ilUtil::prepareFormOutput($ilias->account->getCity()));
$tpl->setVariable("COUNTRY", ilUtil::prepareFormOutput($ilias->account->getCountry()));
$tpl->setVariable("PHONE_OFFICE", $ilias->account->getPhoneOffice());
$tpl->setVariable("PHONE_HOME", $ilias->account->getPhoneHome());
$tpl->setVariable("PHONE_MOBILE", $ilias->account->getPhoneMobile());
$tpl->setVariable("FAX", $ilias->account->getFax());
$tpl->setVariable("EMAIL", $ilias->account->getEmail());
$tpl->setVariable("HOBBY", ilUtil::stripSlashes($ilias->account->getHobby()));		// here

// get assigned global roles (default roles)
$global_roles = $rbacreview->getGlobalRoles();

foreach ($global_roles as $role_id)
{
	if (in_array($role_id,$_SESSION["RoleId"]))
	{
		$roleObj = $ilias->obj_factory->getInstanceByObjId($role_id);
		$role_names .= $roleObj->getTitle().", ";
		unset($roleObj);
	}
}

$tpl->setVariable("DEFAULT_ROLES",substr($role_names,0,-2));

$tpl->setVariable("TXT_REQUIRED_FIELDS",$lng->txt("required_field"));
//button
$tpl->setVariable("TXT_SAVE",$lng->txt("save"));
// addeding by ratana ty
$tpl->setVariable("UPLOAD", $lng->txt("upload"));
// end adding
// Testing by ratana ty
// Show check if value in table usr_pref is y
//
if ($ilias->account->prefs["public_profile"]=="y")
{
	$tpl->setVariable("CHK_PUB","checked");
}

$val_array = array("institution", "department", "upload", "street",
	"zip", "city", "country", "phone_office", "phone_home", "phone_mobile",
	"fax", "email", "hobby");

foreach ($val_array as $key => $value)
{
	if ($ilias->account->prefs["public_".$value] == "y")
	{
		$tpl->setVariable("CHK_".strtoupper($value), "checked");
	}
}
// End of showing
// Testing by ratana ty

$tpl->parseCurrentBlock();
$tpl->show();
?>
