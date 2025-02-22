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
 ********************************************************************
 */

namespace ILIAS\MyStaff;

use ilObjectAccess;
use ilOrgUnitOperation;
use ilOrgUnitOperationContext;
use ilOrgUnitOperationContextQueries;
use ilOrgUnitOperationQueries;
use ilOrgUnitUserAssignmentQueries;

/**
 * Class ilMyStaffAccess
 * @author: Martin Studer <ms@studer-raimann.ch>
 */
class ilMyStaffAccess extends ilObjectAccess
{
    public const TMP_DEFAULT_TABLE_NAME_PREFIX_IL_OBJ_SPEC_PERMISSIONS = 'tmp_obj_spec_perm';
    public const TMP_DEFAULT_TABLE_NAME_PREFIX_IL_OBJ_DEFAULT_PERMISSIONS = 'tmp_obj_def_perm';
    public const TMP_DEFAULT_TABLE_NAME_PREFIX_IL_ORGU_DEFAULT_PERMISSIONS = 'tmp_orgu_def_perm';
    public const TMP_DEFAULT_TABLE_NAME_PREFIX_CRS_MEMBERS = 'tmp_crs_members';
    public const TMP_DEFAULT_TABLE_NAME_PREFIX_ORGU_MEMBERS = 'tmp_orgu_members';
    public const TMP_DEFAULT_TABLE_NAME_PREFIX_IL_OBJ_USER_MATRIX = 'tmp_obj_user_matr';
    public const ACCESS_ENROLMENTS_ORG_UNIT_OPERATION = ilOrgUnitOperation::OP_ACCESS_ENROLMENTS;
    public const COURSE_CONTEXT = ilOrgUnitOperationContext::CONTEXT_CRS;
    public const EXERCISE_CONTEXT = ilOrgUnitOperationContext::CONTEXT_EXC;
    public const GROUP_CONTEXT = ilOrgUnitOperationContext::CONTEXT_GRP;
    public const SURVEY_CONTEXT = ilOrgUnitOperationContext::CONTEXT_SVY;
    public const TEST_CONTEXT = ilOrgUnitOperationContext::CONTEXT_TST;
    public const EMPLOYEE_TALK_CONTEXT = ilOrgUnitOperationContext::CONTEXT_ETAL;

    protected static ?self $instance = null;

    protected array $users_for_user = [];

    public static function getInstance(): self
    {
        global $DIC;

        if (self::$instance === null) {
            self::$instance = new self();

            self::$instance->dropTempTable(self::TMP_DEFAULT_TABLE_NAME_PREFIX_IL_OBJ_SPEC_PERMISSIONS . "_" . self::ACCESS_ENROLMENTS_ORG_UNIT_OPERATION . "_"
                . self::COURSE_CONTEXT);
            self::$instance->dropTempTable(self::TMP_DEFAULT_TABLE_NAME_PREFIX_IL_OBJ_DEFAULT_PERMISSIONS . "_" . self::ACCESS_ENROLMENTS_ORG_UNIT_OPERATION
                . "_" . self::COURSE_CONTEXT);
            self::$instance->dropTempTable(self::TMP_DEFAULT_TABLE_NAME_PREFIX_IL_ORGU_DEFAULT_PERMISSIONS . "_" . self::ACCESS_ENROLMENTS_ORG_UNIT_OPERATION
                . "_" . self::COURSE_CONTEXT);
            self::$instance->dropTempTable(self::TMP_DEFAULT_TABLE_NAME_PREFIX_CRS_MEMBERS . "_user_id_" . $DIC->user()->getId());
            self::$instance->dropTempTable(self::TMP_DEFAULT_TABLE_NAME_PREFIX_ORGU_MEMBERS . "_user_id_" . $DIC->user()->getId());
            self::$instance->dropTempTable(self::TMP_DEFAULT_TABLE_NAME_PREFIX_IL_OBJ_USER_MATRIX . "_" . self::ACCESS_ENROLMENTS_ORG_UNIT_OPERATION . "_"
                . self::COURSE_CONTEXT);
        }

        return self::$instance;
    }

    public function __construct()
    {
    }

    public function hasCurrentUserAccessToMyStaff(): bool
    {
        global $DIC;

        if (!$DIC->settings()->get("enable_my_staff")) {
            return false;
        }

        if ($this->hasCurrentUserAccessToCourseMemberships()) {
            return true;
        }

        if ($this->hasCurrentUserAccessToCertificates()) {
            return true;
        }

        if ($this->hasCurrentUserAccessToCompetences()) {
            return true;
        }

        if ($this->hasCurrentUserAccessToTalks()) {
            return true;
        }

        if ($this->hasCurrentUserAccessToUser()) {
            return true;
        }

        return false;
    }

    public function hasCurrentUserAccessToCertificates(): bool
    {
        global $DIC;

        if (!$DIC->settings()->get("enable_my_staff")) {
            return false;
        }

        if ($this->countOrgusOfUserWithOperationAndContext(
            $DIC->user()->getId(),
            ilOrgUnitOperation::OP_VIEW_CERTIFICATES,
            self::COURSE_CONTEXT
        )
            > 0
        ) {
            return true;
        }

        if ($this->countOrgusOfUserWithOperationAndContext(
            $DIC->user()->getId(),
            ilOrgUnitOperation::OP_VIEW_CERTIFICATES,
            self::EXERCISE_CONTEXT
        )
            > 0
        ) {
            return true;
        }

        if ($this->countOrgusOfUserWithOperationAndContext(
            $DIC->user()->getId(),
            ilOrgUnitOperation::OP_VIEW_CERTIFICATES,
            self::TEST_CONTEXT
        )
            > 0
        ) {
            return true;
        }

        return false;
    }

    public function hasCurrentUserAccessToTalks(): bool
    {
        global $DIC;

        if (!$DIC->settings()->get("enable_my_staff")) {
            return false;
        }

        if ($this->countOrgusOfUserWithOperationAndContext(
            $DIC->user()->getId(),
            ilOrgUnitOperation::OP_CREATE_EMPLOYEE_TALK,
            self::EMPLOYEE_TALK_CONTEXT
        )
            > 0
        ) {
            return true;
        }

        if ($this->countOrgusOfUserWithOperationAndContext(
            $DIC->user()->getId(),
            ilOrgUnitOperation::OP_EDIT_EMPLOYEE_TALK,
            self::EMPLOYEE_TALK_CONTEXT
        )
            > 0
        ) {
            return true;
        }

        if ($this->countOrgusOfUserWithOperationAndContext(
            $DIC->user()->getId(),
            ilOrgUnitOperation::OP_READ_EMPLOYEE_TALK,
            self::EMPLOYEE_TALK_CONTEXT
        )
            > 0
        ) {
            return true;
        }

        return false;
    }

    public function hasCurrentUserAccessToCompetences(): bool
    {
        global $DIC;

        if (!$DIC->settings()->get("enable_my_staff")) {
            return false;
        }

        if ($this->countOrgusOfUserWithOperationAndContext(
            $DIC->user()->getId(),
            ilOrgUnitOperation::OP_VIEW_COMPETENCES,
            self::COURSE_CONTEXT
        )
            > 0
        ) {
            return true;
        }

        if ($this->countOrgusOfUserWithOperationAndContext(
            $DIC->user()->getId(),
            ilOrgUnitOperation::OP_VIEW_COMPETENCES,
            self::GROUP_CONTEXT
        )
            > 0
        ) {
            return true;
        }

        if ($this->countOrgusOfUserWithOperationAndContext(
            $DIC->user()->getId(),
            ilOrgUnitOperation::OP_VIEW_COMPETENCES,
            self::SURVEY_CONTEXT
        )
            > 0
        ) {
            return true;
        }

        if ($this->countOrgusOfUserWithOperationAndContext(
            $DIC->user()->getId(),
            ilOrgUnitOperation::OP_VIEW_COMPETENCES,
            self::TEST_CONTEXT
        )
            > 0
        ) {
            return true;
        }

        return false;
    }

    public function hasCurrentUserAccessToCourseMemberships(): bool
    {
        global $DIC;

        if (!$DIC->settings()->get("enable_my_staff")) {
            return false;
        }

        if ($this->countOrgusOfUserWithOperationAndContext(
            $DIC->user()->getId(),
            self::ACCESS_ENROLMENTS_ORG_UNIT_OPERATION,
            self::COURSE_CONTEXT
        )
            > 0
        ) {
            return true;
        }

        return false;
    }

    public function hasCurrentUserAccessToUser(int $usr_id = 0): bool
    {
        global $DIC;

        if (!$DIC->settings()->get("enable_my_staff")) {
            return false;
        }

        $arr_users = $this->getUsersForUser($DIC->user()->getId());
        if (count($arr_users) > 0 && $usr_id === 0) {
            return true;
        }

        if (count($arr_users) > 0 && in_array($usr_id, $arr_users)) {
            return true;
        }

        return false;
    }

    public function hasCurrentUserAccessToLearningProgressInObject(int $ref_id = 0): bool
    {
        global $DIC;

        return $DIC->access()->checkPositionAccess(ilOrgUnitOperation::OP_READ_LEARNING_PROGRESS, $ref_id);
    }

    public function hasCurrentUserAccessToCourseLearningProgressForAtLeastOneUser(): bool
    {
        global $DIC;

        $arr_usr_id = $this->getUsersForUserOperationAndContext(
            $DIC->user()->getId(),
            ilOrgUnitOperation::OP_READ_LEARNING_PROGRESS,
            self::COURSE_CONTEXT
        );
        if (count($arr_usr_id) > 0) {
            return true;
        }

        return false;
    }

    public function countOrgusOfUserWithAtLeastOneOperation(int $user_id): int
    {
        global $DIC;

        $q = "SELECT COUNT(orgu_ua.orgu_id) AS 'cnt' FROM il_orgu_permissions AS perm
				INNER JOIN il_orgu_ua AS orgu_ua ON orgu_ua.position_id = perm.position_id
				INNER JOIN il_orgu_op_contexts AS contexts on contexts.id = perm.context_id AND contexts.context is not NULL
				WHERE orgu_ua.user_id = " . $DIC->database()->quote(
            $user_id,
            'integer'
        ) . " AND perm.operations is not NULL AND perm.parent_id = -1";

        $set = $DIC->database()->query($q);
        $rec = $DIC->database()->fetchAssoc($set);

        return $rec['cnt'];
    }

    public function countOrgusOfUserWithOperationAndContext(
        int $user_id,
        string $org_unit_operation_string,
        string $context
    ): int {
        global $DIC;

        /**
         * @var ilOrgUnitOperation $operation
         */
        $operation = ilOrgUnitOperationQueries::findByOperationString($org_unit_operation_string, $context);

        $q = "SELECT COUNT(orgu_ua.orgu_id) AS cnt FROM il_orgu_permissions AS perm
				INNER JOIN il_orgu_ua AS orgu_ua ON orgu_ua.position_id = perm.position_id
				INNER JOIN il_orgu_op_contexts AS contexts on contexts.id = perm.context_id AND contexts.context = '" . $context . "'
				and orgu_ua.user_id = " . $DIC->database()->quote(
            $user_id,
            'integer'
        ) . " AND perm.operations REGEXP '[\[,]\"?"
            . $operation->getOperationId() . "\"?[\],]'
				WHERE perm.parent_id = -1";

        $set = $DIC->database()->query($q);
        $rec = $DIC->database()->fetchAssoc($set);

        return $rec['cnt'];
    }

    public function getUsersForUserOperationAndContext(
        int $user_id,
        string $org_unit_operation_string,
        string $context,
        string $tmp_table_name_prefix = self::TMP_DEFAULT_TABLE_NAME_PREFIX_IL_OBJ_USER_MATRIX
    ): array {
        global $DIC;

        $tmp_table_name = $this->buildTempTableIlobjectsUserMatrixForUserOperationAndContext(
            $user_id,
            $org_unit_operation_string,
            $context,
            $tmp_table_name_prefix
        );

        $q = 'SELECT usr_id FROM ' . $tmp_table_name;

        $user_set = $DIC->database()->query($q);

        $arr_users = array();

        while ($rec = $DIC->database()->fetchAssoc($user_set)) {
            $arr_users[$rec['usr_id']] = $rec['usr_id'];
        }

        return $arr_users;
    }

    public function getUsersForUserPerPosition(int $user_id): array
    {
        $users = [];
        $user_assignments = ilOrgUnitUserAssignmentQueries::getInstance()->getAssignmentsOfUserId($user_id);
        foreach ($user_assignments as $user_assignment) {
            $users[$user_assignment->getPositionId()] = $this->getUsersForUser(
                $user_id,
                $user_assignment->getPositionId()
            );
        }

        return $users;
    }

    /**
     * @return int[]
     */
    public function getUsersForUser(int $user_id, ?int $position_id = null): array
    {
        global $DIC;

        if (isset($this->users_for_user[$user_id]) && $position_id === null) {
            return $this->users_for_user[$user_id];
        }

        $tmp_orgu_members = $this->buildTempTableOrguMemberships(
            self::TMP_DEFAULT_TABLE_NAME_PREFIX_ORGU_MEMBERS,
            array()
        );

        $position_limitation = '';
        if (!is_null($position_id)) {
            $position_limitation = ' AND orgu_ua_current_user.position_id = ' . $position_id;
        }

        $q = "SELECT  " . $tmp_orgu_members . ".user_id AS usr_id
        		FROM 
				" . $tmp_orgu_members . "
				INNER JOIN il_orgu_ua AS orgu_ua_current_user on orgu_ua_current_user.user_id = " . $DIC->database()->quote(
            $user_id,
            'integer'
        ) . "
				INNER JOIN il_orgu_authority AS auth ON auth.position_id = orgu_ua_current_user.position_id " . $position_limitation . "
				WHERE
				(
				/* Identische OrgUnit wie Current User; Nicht Rekursiv; Fixe Position */
					(orgu_ua_current_user.orgu_id = " . $tmp_orgu_members . ".orgu_id AND auth.scope = 1
						AND auth.over = " . $tmp_orgu_members . ".user_position_id AND auth.over <> -1
					)
					OR
					/* Identische OrgUnit wie Current User; Nicht Rekursiv; Position egal */
					(orgu_ua_current_user.orgu_id = " . $tmp_orgu_members . ".orgu_id AND auth.scope = 1 AND auth.over = -1)
					OR
					/* Kinder OrgUnit wie Current User */
					(
						(
							" . $tmp_orgu_members . ".orgu_id = orgu_ua_current_user.orgu_id OR
							" . $tmp_orgu_members . ".tree_path LIKE CONCAT(\"%.\",orgu_ua_current_user.orgu_id ,\".%\")
							OR
							" . $tmp_orgu_members . ".tree_path LIKE  CONCAT(\"%.\",orgu_ua_current_user.orgu_id )
						)
						AND 
						(
							(
								(
									/* Gleiche Position */
									auth.over = " . $tmp_orgu_members . ".user_position_id AND auth.over <> -1
								)
								OR
								(
									/* Position Egal */
									auth.over = -1
								)
							)
							AND auth.scope = 2
						)
					)
				)";

        $user_set = $DIC->database()->query($q);

        $arr_users = array();

        while ($rec = $DIC->database()->fetchAssoc($user_set)) {
            $arr_users[$rec['usr_id']] = $rec['usr_id'];
        }

        if ($position_id === null) {
            $this->users_for_user[$user_id] = $arr_users;
        }

        return $arr_users;
    }

    public function getIdsForUserAndOperation(int $user_id, string $operation, bool $return_ref_id = false): array
    {
        $user_assignments = ilOrgUnitUserAssignmentQueries::getInstance()->getAssignmentsOfUserId($user_id);
        $ids = [];
        foreach ($user_assignments as $user_assignment) {
            $ref_ids = $this->getIdsForPositionAndOperation(
                $user_assignment->getPositionId(),
                $operation,
                $return_ref_id
            );
            if (count($ref_ids) > 0) {
                $ids = array_merge($ids, $ref_ids);
            }
        }
        return $ids;
    }

    public function getIdsForPositionAndOperation(int $position_id, string $operation, bool $return_ref_id): array
    {
        $ids = [];
        foreach (ilOrgUnitOperationContext::$available_contexts as $context) {
            $ref_ids = $this->getIdsForPositionAndOperationAndContext(
                $position_id,
                $operation,
                $context,
                $return_ref_id
            );
            if (count($ref_ids) > 0) {
                $ids = array_merge($ids, $ref_ids);
            }
        }

        return $ids;
    }

    /**
     * returns all obj_ids/ref_ids (depending on flag "ref_id") of objects of type $context,
     * to which the position with $position_id has permissions
     * on the operation with $operation_id
     */
    public function getIdsForPositionAndOperationAndContext(
        int $position_id,
        string $operation,
        string $context,
        bool $return_ref_id
    ): array {
        global $DIC;
        $context_id = ilOrgUnitOperationContextQueries::findByName($context)->getId();
        /**
         * @var ilOrgUnitOperation|null $operation_object
         */
        $operation_object = ilOrgUnitOperationQueries::findByOperationString($operation, $context);
        if (is_null($operation_object)) {
            // operation doesn't exist in this context
            return [];
        }
        $operation_id = $operation_object->getOperationId();

        if ($this->hasPositionDefaultPermissionForOperationInContext($position_id, $operation_id, $context_id)) {
            $query = "select " . ($return_ref_id ? "object_reference.ref_id" : "object_data.obj_id") . " from object_data " .
                "inner join object_reference on object_reference.obj_id = object_data.obj_id " .
                "where type = '" . $context . "' " .
                "AND object_reference.ref_id not in " .
                "   (SELECT parent_id FROM il_orgu_permissions " .
                "   where position_id = " . $position_id . " and context_id = " . $context_id . " and operations NOT REGEXP '[\[,]\"?" . $operation_id . "\"?[\],]' and parent_id <> -1)";
        } else {
            $query = $return_ref_id
                ?
                "SELECT parent_id as ref_id FROM il_orgu_permissions "
                :
                "SELECT obj_id FROM il_orgu_permissions INNER JOIN object_reference ON object_reference.ref_id = il_orgu_permissions.parent_id ";
            $query .= " where position_id = " . $position_id . " and context_id = " . $context_id . " and operations REGEXP '[\[,]\"?" . $operation_id . "\"?[\],]' and parent_id <> -1";
        }

        return array_map(function ($item) use ($return_ref_id) {
            return $return_ref_id ? $item['ref_id'] : $item['obj_id'];
        }, $DIC->database()->fetchAll($DIC->database()->query($query)));
    }

    public function hasPositionDefaultPermissionForOperationInContext(
        int $position_id,
        int $operation_id,
        int $context_id
    ): bool {
        global $DIC;
        $res = $DIC->database()->query("SELECT * FROM il_orgu_permissions " .
            " WHERE context_id = " . $context_id . " " .
            "AND operations REGEXP '[\[,]\"?" . $operation_id . "\"?[\],]' " .
            "AND position_id = " . $position_id . " " .
            "AND parent_id = -1");

        return (bool) $DIC->database()->numRows($res) > 0;
    }

    public function getIlobjectsAndUsersForUserOperationAndContext(
        int $user_id,
        string $org_unit_operation_string,
        string $context
    ): array {
        global $DIC;


        $operation = ilOrgUnitOperationQueries::findByOperationString($org_unit_operation_string, $context);
        assert($operation instanceof ilOrgUnitOperation);

        $tmp_table_name = 'tmp_ilobj_user_matrix_' . $operation->getOperationId();

        $this->buildTempTableIlobjectsUserMatrixForUserOperationAndContext(
            $user_id,
            $org_unit_operation_string,
            $context,
            $tmp_table_name
        );

        $q = 'SELECT * FROM ' . $tmp_table_name;

        $user_set = $DIC->database()->query($q);

        $arr_user_obj = array();

        while ($rec = $DIC->database()->fetchAssoc($user_set)) {
            $arr_user_obj[] = $rec;
        }

        return $arr_user_obj;
    }

    public function buildTempTableIlobjectsUserMatrixForUserOperationAndContext(
        int $user_id,
        string $org_unit_operation_string,
        string $context,
        string $temporary_table_name_prefix = self::TMP_DEFAULT_TABLE_NAME_PREFIX_IL_OBJ_USER_MATRIX
    ): string {
        global $DIC;

        $temporary_table_name = $temporary_table_name_prefix . "_" . $org_unit_operation_string . "_" . $context;

        $operation = ilOrgUnitOperationQueries::findByOperationString($org_unit_operation_string, $context);
        assert($operation instanceof ilOrgUnitOperation);

        $all_users_for_user = $this->getUsersForUser($GLOBALS['DIC']->user()->getId());


        $tmp_table_objects_specific_perimissions = $this->buildTempTableIlobjectsSpecificPermissionSetForOperationAndContext(
            $org_unit_operation_string,
            $context,
            self::TMP_DEFAULT_TABLE_NAME_PREFIX_IL_OBJ_SPEC_PERMISSIONS
        );

        $tmp_table_objects_default_perimissions = $this->buildTempTableIlobjectsDefaultPermissionSetForOperationAndContext(
            $org_unit_operation_string,
            $context,
            self::TMP_DEFAULT_TABLE_NAME_PREFIX_IL_OBJ_DEFAULT_PERMISSIONS
        );

        $tmp_table_orgunit_default_perimissions = $this->buildTempTableIlorgunitDefaultPermissionSetForOperationAndContext(
            $org_unit_operation_string,
            $context,
            self::TMP_DEFAULT_TABLE_NAME_PREFIX_IL_ORGU_DEFAULT_PERMISSIONS
        );

        $tmp_table_course_members = $this->buildTempTableCourseMemberships(
            self::TMP_DEFAULT_TABLE_NAME_PREFIX_CRS_MEMBERS,
            $all_users_for_user
        );

        $tmp_table_orgu_members = $this->buildTempTableOrguMemberships(
            self::TMP_DEFAULT_TABLE_NAME_PREFIX_ORGU_MEMBERS,
            $all_users_for_user
        );

        $tmp_table_orgu_member_path = $this->buildTempTableOrguMemberships(
            'tmp_orgu_members_path',
            $all_users_for_user
        );

        if ($temporary_table_name != self::TMP_DEFAULT_TABLE_NAME_PREFIX_IL_OBJ_USER_MATRIX . "_" . self::ACCESS_ENROLMENTS_ORG_UNIT_OPERATION . "_"
            . self::COURSE_CONTEXT
        ) {
            $this->dropTempTable($temporary_table_name);
        }

        $q = "CREATE TEMPORARY TABLE IF NOT EXISTS " . $temporary_table_name . " AS (
				SELECT DISTINCT user_perm_matrix.perm_for_ref_id, user_perm_matrix.usr_id FROM
				(
				 SELECT crs.*," . $tmp_table_course_members . ".ref_id," . $tmp_table_course_members . ".usr_id FROM
					(
						SELECT * FROM " . $tmp_table_objects_specific_perimissions . "
							UNION
						SELECT * FROM " . $tmp_table_objects_default_perimissions . "
					) AS crs
					INNER JOIN " . $tmp_table_course_members . " on " . $tmp_table_course_members . ".ref_id = crs.perm_for_ref_id 
					and (
							(
							" . $tmp_table_course_members . ".orgu_id = crs.perm_for_orgu_id AND " . $tmp_table_course_members . ".position_id = crs.perm_over_user_with_position AND perm_orgu_scope = 1
							)
							or perm_orgu_scope = 2
						)
				UNION
					SELECT " . $tmp_table_orgunit_default_perimissions . ".*, " . $tmp_table_orgu_members . ".orgu_id AS ref_id, "
            . $tmp_table_orgu_members . ".user_id FROM " . $tmp_table_orgunit_default_perimissions . "
					INNER JOIN " . $tmp_table_orgu_members . " on " . $tmp_table_orgu_members . ".orgu_id = "
            . $tmp_table_orgunit_default_perimissions . ".perm_for_ref_id
					and (
							(
							" . $tmp_table_orgu_members . ".orgu_id = " . $tmp_table_orgunit_default_perimissions . ".perm_for_orgu_id AND "
            . $tmp_table_orgu_members . ".user_position_id = " . $tmp_table_orgunit_default_perimissions . ".perm_over_user_with_position AND perm_orgu_scope = 1
							)
							or perm_orgu_scope = 2
						)
					
				) AS user_perm_matrix  
				INNER JOIN " . $tmp_table_orgu_member_path . " AS path on path.user_id = user_perm_matrix.usr_id
				
				INNER JOIN il_orgu_ua AS orgu_ua_current_user on orgu_ua_current_user.user_id = " . $DIC->database()->quote(
                $user_id,
                'integer'
            ) . "
				INNER JOIN il_orgu_permissions AS perm on perm.position_id = orgu_ua_current_user.position_id AND perm.parent_id = -1
				INNER JOIN il_orgu_op_contexts AS contexts on contexts.id = perm.context_id AND contexts.context =  '$context'
				and perm.operations REGEXP '[\[,]\"?" . $operation->getOperationId() . "\"?[\],]'
				
				AND
				( 
					/* Identische OrgUnit wie Current User; Nicht Rekursiv; Fixe Position */
					(orgu_ua_current_user.orgu_id = user_perm_matrix.perm_for_orgu_id AND user_perm_matrix.perm_orgu_scope = 1
					AND orgu_ua_current_user.position_id = user_perm_matrix.perm_for_position_id AND user_perm_matrix.perm_over_user_with_position <> -1
					)
				OR
					/* Identische OrgUnit wie Current User; Nicht Rekursiv; Position egal */
					(orgu_ua_current_user.orgu_id = user_perm_matrix.perm_for_orgu_id AND user_perm_matrix.perm_orgu_scope = 1 AND user_perm_matrix.perm_over_user_with_position = -1)
				OR
					/* Kinder OrgUnit wie Current User */
					(
						orgu_ua_current_user.orgu_id = user_perm_matrix.perm_for_orgu_id
						AND
						(
							path.orgu_id = user_perm_matrix.perm_for_orgu_id OR
							path.tree_path LIKE CONCAT(\"%.\",user_perm_matrix.perm_for_orgu_id ,\".%\")
							OR
							path.tree_path LIKE  CONCAT(\"%.\",user_perm_matrix.perm_for_orgu_id )
						)
						AND 
						(
							(
								(
									/* Gleiche Position */
									orgu_ua_current_user.position_id = user_perm_matrix.perm_for_position_id AND user_perm_matrix.perm_over_user_with_position <> -1
								)
								OR
								(
									/* Position Egal */
									user_perm_matrix.perm_over_user_with_position = -1
								)
							)
							AND user_perm_matrix.perm_orgu_scope = 2
						)
					)
				)	
			);";

        $DIC->database()->manipulate($q);

        return $temporary_table_name;
    }

    public function buildTempTableIlobjectsSpecificPermissionSetForOperationAndContext(
        string $org_unit_operation_string,
        string $context,
        string $temporary_table_name_prefix = self::TMP_DEFAULT_TABLE_NAME_PREFIX_IL_OBJ_SPEC_PERMISSIONS
    ): string {
        global $DIC;

        $temporary_table_name = $temporary_table_name_prefix . "_" . $org_unit_operation_string . "_" . $context;

        $operation = ilOrgUnitOperationQueries::findByOperationString($org_unit_operation_string, $context);
        assert($operation instanceof ilOrgUnitOperation);

        if ($temporary_table_name != self::TMP_DEFAULT_TABLE_NAME_PREFIX_IL_OBJ_SPEC_PERMISSIONS . "_" . self::ACCESS_ENROLMENTS_ORG_UNIT_OPERATION . "_"
            . self::COURSE_CONTEXT
        ) {
            $this->dropTempTable($temporary_table_name);
        }

        $q = "CREATE TEMPORARY TABLE IF NOT EXISTS " . $temporary_table_name . " 
				(INDEX i1 (perm_for_ref_id), INDEX i2 (perm_for_orgu_id), INDEX i3 (perm_orgu_scope), INDEX i4 (perm_for_position_id), INDEX i5 (perm_over_user_with_position))
				AS (
				 SELECT 
					obj_ref.ref_id AS perm_for_ref_id,
					orgu_ua.orgu_id AS perm_for_orgu_id,
			        auth.scope AS perm_orgu_scope,
					orgu_ua.position_id AS perm_for_position_id,
					auth.over AS perm_over_user_with_position
					FROM
					il_orgu_permissions AS perm
					INNER JOIN il_orgu_ua AS orgu_ua ON orgu_ua.position_id = perm.position_id
					INNER JOIN il_orgu_authority AS auth ON auth.position_id = orgu_ua.position_id AND orgu_ua.user_id = " . $GLOBALS['DIC']->user()
                                                                                                                                            ->getId() . "
					INNER JOIN object_reference AS obj_ref ON obj_ref.ref_id =  perm.parent_id
					INNER JOIN object_data AS obj ON obj.obj_id = obj_ref.obj_id AND obj.type = '$context'
					INNER JOIN il_orgu_op_contexts AS contexts on contexts.id = perm.context_id AND contexts.context = '$context'
					WHERE
				    perm.operations REGEXP '[\[,]\"?" . $operation->getOperationId() . "\"?[\],]'
			);";

        $DIC->database()->manipulate($q);

        return $temporary_table_name;
    }

    public function buildTempTableIlobjectsDefaultPermissionSetForOperationAndContext(
        string $org_unit_operation_string,
        string $context,
        string $temporary_table_name_prefix = self::TMP_DEFAULT_TABLE_NAME_PREFIX_IL_OBJ_DEFAULT_PERMISSIONS
    ): string {
        global $DIC;

        $temporary_table_name = $temporary_table_name_prefix . "_" . $org_unit_operation_string . "_" . $context;

        /**
         * @var ilOrgUnitOperation $operation
         */
        $operation = ilOrgUnitOperationQueries::findByOperationString($org_unit_operation_string, $context);

        if ($temporary_table_name != self::TMP_DEFAULT_TABLE_NAME_PREFIX_IL_OBJ_DEFAULT_PERMISSIONS . "_" . self::ACCESS_ENROLMENTS_ORG_UNIT_OPERATION . "_"
            . self::COURSE_CONTEXT
        ) {
            $this->dropTempTable($temporary_table_name);
        }

        $q = "CREATE TEMPORARY TABLE IF NOT EXISTS " . $temporary_table_name . " 
		(INDEX i1 (perm_for_ref_id), INDEX i2 (perm_for_orgu_id), INDEX i3 (perm_orgu_scope), INDEX i4 (perm_for_position_id),INDEX i5 (perm_over_user_with_position))
		AS (
					SELECT 
					obj_ref.ref_id AS perm_for_ref_id,
					orgu_ua.orgu_id AS perm_for_orgu_id,
			        auth.scope AS perm_orgu_scope,
					orgu_ua.position_id AS perm_for_position_id,
					auth.over AS perm_over_user_with_position
				    FROM
				    object_data AS obj
				    INNER JOIN object_reference AS obj_ref ON obj_ref.obj_id = obj.obj_id
				    INNER JOIN il_orgu_permissions AS perm ON perm.operations REGEXP '[\[,]\"?" . $operation->getOperationId() . "\"?[\],]' AND perm.parent_id = -1
				    INNER JOIN il_orgu_op_contexts AS contexts on contexts.id = perm.context_id AND contexts.context = '" . $context . "'
				    INNER JOIN il_orgu_ua AS orgu_ua ON orgu_ua.position_id = perm.position_id AND orgu_ua.user_id = " . $GLOBALS['DIC']->user()
                                                                                                                                        ->getId() . "
				    INNER JOIN il_orgu_authority AS auth ON auth.position_id = orgu_ua.position_id
				    
				    WHERE
				        obj.type = '" . $context . "'
				            AND (obj_ref.ref_id , orgu_ua.position_id) 
				            
				            NOT IN (SELECT 
				                perm.parent_id, orgu_ua.position_id
				            FROM
				                il_orgu_permissions AS perm
				            INNER JOIN il_orgu_ua AS orgu_ua ON orgu_ua.position_id = perm.position_id
				            INNER JOIN il_orgu_op_contexts AS contexts on contexts.id = perm.context_id AND contexts.context = '" . $context . "'
				            WHERE perm.parent_id <> -1)
							);";

        $DIC->database()->manipulate($q);

        return $temporary_table_name;
    }

    /**
     * @param string $org_unit_operation_string
     * @param string $context
     * @param string $temporary_table_name_prefix
     * @return string
     */
    public function buildTempTableIlorgunitDefaultPermissionSetForOperationAndContext(
        string $org_unit_operation_string,
        string $context,
        string $temporary_table_name_prefix = self::TMP_DEFAULT_TABLE_NAME_PREFIX_IL_ORGU_DEFAULT_PERMISSIONS
    ): string {
        global $DIC;

        $temporary_table_name = $temporary_table_name_prefix . "_" . $org_unit_operation_string . "_" . $context;
        /**
         * @var ilOrgUnitOperation $operation
         */
        $operation = ilOrgUnitOperationQueries::findByOperationString($org_unit_operation_string, $context);

        if ($temporary_table_name != self::TMP_DEFAULT_TABLE_NAME_PREFIX_IL_ORGU_DEFAULT_PERMISSIONS . "_" . self::ACCESS_ENROLMENTS_ORG_UNIT_OPERATION . "_"
            . self::COURSE_CONTEXT
        ) {
            $this->dropTempTable($temporary_table_name);
        }

        $q = "CREATE TEMPORARY TABLE IF NOT EXISTS " . $temporary_table_name . " 
			  (INDEX i1 (perm_for_ref_id), INDEX i2 (perm_for_orgu_id), INDEX i3 (perm_orgu_scope), INDEX i4 (perm_for_position_id), INDEX i5 (perm_over_user_with_position))
		AS (
					SELECT 
		            orgu_ua.orgu_id AS perm_for_ref_id, /* Table has to be identical to the other Permission For Operation And Context-Tables! */
					orgu_ua.orgu_id AS perm_for_orgu_id,
			        auth.scope AS perm_orgu_scope,
					orgu_ua.position_id AS perm_for_position_id,
					auth.over AS perm_over_user_with_position
				    FROM
					il_orgu_permissions AS perm
				    INNER JOIN il_orgu_ua AS orgu_ua ON orgu_ua.position_id = perm.position_id AND perm.parent_id = -1 AND orgu_ua.user_id = "
            . $GLOBALS['DIC']->user()->getId() . "
				    INNER JOIN il_orgu_authority AS auth ON auth.position_id = orgu_ua.position_id
				    INNER JOIN il_orgu_op_contexts AS contexts on contexts.id = perm.context_id AND contexts.context = '" . $context . "'
				    WHERE
				    perm.operations REGEXP '[\[,]\"?" . $operation->getOperationId() . "\"?[\],]'
							);";

        $DIC->database()->manipulate($q);

        return $temporary_table_name;
    }

    public function buildTempTableCourseMemberships(
        string $temporary_table_name_prefix = self::TMP_DEFAULT_TABLE_NAME_PREFIX_CRS_MEMBERS,
        array $only_courses_of_user_ids = array()
    ): string {
        global $DIC;

        $temporary_table_name = $temporary_table_name_prefix . "_user_id_" . $DIC->user()->getId();

        if ($temporary_table_name != self::TMP_DEFAULT_TABLE_NAME_PREFIX_CRS_MEMBERS . "_user_id_" . $DIC->user()->getId()
            || count($only_courses_of_user_ids) > 0
        ) {
            $this->dropTempTable($temporary_table_name);
        }

        $q = "CREATE TEMPORARY TABLE IF NOT EXISTS " . $temporary_table_name . " 
		(INDEX i1(ref_id), INDEX i2 (usr_id), INDEX i3 (position_id), INDEX i4 (orgu_id))
		AS (
					SELECT crs_members_crs_ref.ref_id, crs_members.usr_id, orgu_ua.position_id, orgu_ua.orgu_id
						FROM (
							SELECT obj_id, usr_id FROM obj_members WHERE admin > 0 OR tutor > 0 OR member > 0
							AND " . $DIC->database()->in(
            'obj_members.usr_id',
            $only_courses_of_user_ids,
            false,
            'integer'
        ) . " 
						UNION
							SELECT obj_id, usr_id FROM crs_waiting_list
							WHERE " . $DIC->database()->in(
            'crs_waiting_list.usr_id',
            $only_courses_of_user_ids,
            false,
            'integer'
        ) . " 
						UNION
							SELECT obj_id, usr_id FROM il_subscribers
							WHERE " . $DIC->database()->in(
            'il_subscribers.usr_id',
            $only_courses_of_user_ids,
            false,
            'integer'
        ) . " 
						) AS crs_members
						INNER JOIN object_reference AS crs_members_crs_ref on crs_members_crs_ref.obj_id = crs_members.obj_id
						INNER JOIN il_orgu_ua AS orgu_ua on orgu_ua.user_id = crs_members.usr_id
			  );";

        $DIC->database()->manipulate($q);

        return $temporary_table_name;
    }

    public function buildTempTableOrguMemberships(
        string $temporary_table_name_prefix = self::TMP_DEFAULT_TABLE_NAME_PREFIX_ORGU_MEMBERS,
        array $only_orgus_of_user_ids = array()
    ): string {
        global $DIC;

        $temporary_table_name = $temporary_table_name_prefix . "_user_id_" . $DIC->user()->getId();

        if ($temporary_table_name != self::TMP_DEFAULT_TABLE_NAME_PREFIX_ORGU_MEMBERS . "_user_id_" . $DIC->user()->getId()
            || count($only_orgus_of_user_ids) > 0
        ) {
            $this->dropTempTable($temporary_table_name);
        }

        $q = "CREATE TEMPORARY TABLE IF NOT EXISTS " . $temporary_table_name . " 
			(INDEX i1(orgu_id), INDEX i2 (tree_path(255)), INDEX i3 (tree_child), INDEX i4 (tree_parent), INDEX i5 (tree_lft), INDEX i6 (tree_rgt), INDEX i7 (user_position_id), INDEX i8 (user_id))
		AS (
					SELECT  orgu_ua.orgu_id AS orgu_id,
							tree_orgu.path AS tree_path,
							tree_orgu.child AS tree_child,
							tree_orgu.parent AS tree_parent,
							tree_orgu.lft AS tree_lft,
							tree_orgu.rgt AS tree_rgt,
							orgu_ua.position_id AS user_position_id,
							orgu_ua.user_id AS user_id
							FROM
							il_orgu_ua AS orgu_ua
							INNER JOIN object_reference AS obj_ref on obj_ref.ref_id = orgu_ua.orgu_id AND obj_ref.deleted is null
							LEFT JOIN tree AS tree_orgu ON tree_orgu.child = orgu_ua.orgu_id";

        if (count($only_orgus_of_user_ids) > 0) {
            $q .= " WHERE " . $DIC->database()->in('orgu_ua.user_id', $only_orgus_of_user_ids, false, 'integer') . " ";
        }

        $q .= ");";

        $DIC->database()->manipulate($q);

        return $temporary_table_name;
    }

    public function dropTempTable(string $temporary_table_name): void
    {
        global $DIC;

        $q = "DROP TABLE IF EXISTS " . $temporary_table_name;
        $DIC->database()->manipulate($q);
    }
}
