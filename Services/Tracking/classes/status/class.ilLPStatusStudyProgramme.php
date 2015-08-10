<?php
/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
* @author Richard Klees <richard.klees@concepts-and-training.de>
*
* @version $Id: class.ilLPStatusCollectionManual.php 40252 2013-03-01 12:21:49Z jluetzen $
*
* @package ilias-tracking
*
*/

include_once './Services/Tracking/classes/class.ilLPStatus.php';

class ilLPStatusStudyProgramme extends ilLPStatus
{
	function _getCountInProgress($a_obj_id) {
		return count($this->_getInProgress($a_obj_id));
	}
	
	function _getInProgress($a_obj_id)
	{
		require_once("Modules/StudyProgramme/classes/class.ilObjStudyProgramme.php");
		$prg = new ilObjStudyProgramme($a_obj_id, false);
		return $prg->getIdsOfUsersWithNotCompletedAndRelevantProgress();
	}
	
	function _getCountCompleted($a_obj_id) {
		return count($this->_getCompleted($a_obj_id));
	}
	
	function _getCompleted($a_obj_id)
	{		
		require_once("Modules/StudyProgramme/classes/class.ilObjStudyProgramme.php");
		$prg = new ilObjStudyProgramme($a_obj_id, false);
		return $prg->getIdsOfUsersWithCompletedProgress();
	}
	
	function determineStatus($a_obj_id, $a_user_id, $a_obj = null)
	{
		require_once("Modules/StudyProgramme/classes/class.ilObjStudyProgramme.php");
		$prg = new ilObjStudyProgramme($a_obj_id, false);
		$progresses = $prg->getProgressesOf($a_user_id);
		if (count($progresses) == 0) {
			return null;
		}
		
		foreach ($progresses as $progress) {
			if ($progress->isCompleted()) {
				return LPStatus::LP_STATUS_COMPLETED_NUM;
			}
		}
		
		return LPStatus::LP_STATUS_IN_PROGRESS_NUM;
	}
}

?>