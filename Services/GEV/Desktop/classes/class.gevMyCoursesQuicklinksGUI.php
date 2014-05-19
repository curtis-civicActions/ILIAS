<?php

/* Copyright (c) 1998-2014 ILIAS open source, Extended GPL, see docs/LICENSE */#

/**
* My Courses GUI for Generali
*
* @author	Richard Klees <richard.klees@concepts-and-training.de>
* @version	$Id$
*/

require_once("Services/CaTUIComponents/classes/class.catTitleGUI.php");
require_once("Services/GEV/Utils/classes/class.gevUserUtils.php");
require_once("Services/GEV/Utils/classes/class.gevCourseUtils.php");

class gevMyCoursesQuicklinksGUI {
	public function __construct() {
		global $lng, $ilCtrl, $ilUser;
		
		$this->lng = &$lng;
		$this->ctrl = &$ilCtrl;
		$this->user = &$ilUser;
	}
	
	public function render() {
		$ql_title = new catTitleGUI("gev_quicklinks", "gev_quicklinks_desc", "gev_quicklink_header.png");
		
		$tpl = new ilTemplate("tpl.gev_my_courses_quicklinks.html", true, true, "Services/GEV/Desktop");
		
		$user_utils = gevUserUtils::getInstance($this->user->getId());
		$next_crs = gevCourseUtils::getInstance($user_utils->getNextCourse());
		$last_crs = gevCourseUtils::getInstance($user_utils->getLastCourse());
		
		$qls = array( "gev_next_course" => array($next_crs->getLink(), "ql_next_course.png")
					, "gev_last_course" => array($last_crs->getLink(), "ql_last_course.png")
					, "gev_my_edu_bio" => array($user_utils->getEduBioLink(), "ql_edu_bio.png")
					);
		
		$start = true;
		foreach ($qls as $key => $entry) {
			if (!$start) {
				$tpl->touchBlock("vspacer");
			}
			else {
				$start = false;
			}
			
			$tpl->setCurrentBlock("tile");
			$tpl->setVariable("IMG_SRC", ilUtil::getImagePath($entry[1]));
			$tpl->setVariable("TARGET", $entry[0]);
			$tpl->setVariable("TEXT", $this->lng->txt($key));
			$tpl->parseCurrentBlock();
		}
		
		return $ql_title->render() . $tpl->get();
	}
}

?>