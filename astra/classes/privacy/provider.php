<?php

namespace mod_astra\privacy;

defined('MOODLE_INTERNAL') || die;

use context;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

use mod_astra_exercise_round;
use mod_astra_learning_object;
use mod_astra_submission;
use mod_astra_deadline_deviation;
use mod_astra_submission_limit_deviation;

/**
 * Privacy class for requesting user data.
 *
 * @package    mod_astra
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
        \core_privacy\local\metadata\provider,
        \core_privacy\local\request\plugin\provider,
        \core_privacy\local\request\core_userlist_provider {

    public static function get_metadata(collection $collection) : collection {
        $collection->add_subsystem_link(
            'core_files',
            [],
            'privacy:metadata:core_files'
        );
        $collection->add_subsystem_link(
            'core_message',
            [],
            'privacy:metadata:core_message'
        );

        $collection->add_database_table(
            mod_astra_submission::TABLE,
            [
                'submitter' => 'privacy:metadata:' . mod_astra_submission::TABLE . ':submitter',
                'submissiontime' => 'privacy:metadata:' . mod_astra_submission::TABLE . ':submissiontime',
                'exerciseid' => 'privacy:metadata:' . mod_astra_submission::TABLE . ':exerciseid',
                'feedback' => 'privacy:metadata:' . mod_astra_submission::TABLE . ':feedback',
                'assistfeedback' => 'privacy:metadata:' . mod_astra_submission::TABLE . ':assistfeedback',
                'grade' => 'privacy:metadata:' . mod_astra_submission::TABLE . ':grade',
                'gradingtime' => 'privacy:metadata:' . mod_astra_submission::TABLE . ':gradingtime',
                'latepenaltyapplied' => 'privacy:metadata:' . mod_astra_submission::TABLE . ':latepenaltyapplied',
                'servicepoints' => 'privacy:metadata:' . mod_astra_submission::TABLE . ':servicepoints',
                'submissiondata' => 'privacy:metadata:' . mod_astra_submission::TABLE . ':submissiondata',
                'gradingdata' => 'privacy:metadata:' . mod_astra_submission::TABLE . ':gradingdata',
            ],
            'privacy:metadata:' . mod_astra_submission::TABLE
        );
        $collection->add_database_table(
            mod_astra_deadline_deviation::TABLE,
            [
                'submitter' => 'privacy:metadata:' . mod_astra_deadline_deviation::TABLE . ':submitter',
                'exerciseid' => 'privacy:metadata:' . mod_astra_deadline_deviation::TABLE . ':exerciseid',
                'extraminutes' => 'privacy:metadata:' . mod_astra_deadline_deviation::TABLE . ':extraminutes',
            ],
            'privacy:metadata:' . mod_astra_deadline_deviation::TABLE
        );
        $collection->add_database_table(
            mod_astra_submission_limit_deviation::TABLE,
            [
                'submitter' => 'privacy:metadata:' . mod_astra_submission_limit_deviation::TABLE . ':submitter',
                'exerciseid' => 'privacy:metadata:' . mod_astra_submission_limit_deviation::TABLE . ':exerciseid',
                'extrasubmissions' => 'privacy:metadata:' . mod_astra_submission_limit_deviation::TABLE . ':extrasubmissions',
            ],
            'privacy:metadata:' . mod_astra_submission_limit_deviation::TABLE
        );

        $collection->add_external_location_link(
            'exerciseservice',
            [
                'submissiondata' => 'privacy:metadata:exerciseservice:submissiondata',
            ],
            'privacy:metadata:exerciseservice'
        );
        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param   int           $userid       The user to search.
     * @return  contextlist   $contextlist  The list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {
        $contextlist = new contextlist();
        $sql = "SELECT DISTINCT c.id
                  FROM {context} c
                  JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                  JOIN {". mod_astra_exercise_round::TABLE ."} exround ON exround.id = cm.instance
                  JOIN {". mod_astra_learning_object::TABLE ."} lobject ON lobject.roundid = exround.id
                  JOIN {". mod_astra_submission::TABLE ."} sbms ON sbms.exerciseid = lobject.id
                 WHERE sbms.submitter = :submitter";
        $params = array(
            'contextlevel' => CONTEXT_MODULE,
            'submitter' => $userid,
        );
        $contextlist->add_from_sql($sql, $params);

        $sql = "SELECT DISTINCT c.id
                  FROM {context} c
                  JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                  JOIN {". mod_astra_exercise_round::TABLE ."} exround ON exround.id = cm.instance
                  JOIN {". mod_astra_learning_object::TABLE ."} lobject ON lobject.roundid = exround.id
                  JOIN {". mod_astra_deadline_deviation::TABLE ."} dldeviations ON dldeviations.exerciseid = lobject.id
                 WHERE dldeviations.submitter = :submitter";
        $contextlist->add_from_sql($sql, $params);

        $sql = "SELECT DISTINCT c.id
                  FROM {context} c
                  JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                  JOIN {". mod_astra_exercise_round::TABLE ."} exround ON exround.id = cm.instance
                  JOIN {". mod_astra_learning_object::TABLE ."} lobject ON lobject.roundid = exround.id
                  JOIN {". mod_astra_submission_limit_deviation::TABLE ."} sbmsdevs ON sbmsdevs.exerciseid = lobject.id
                 WHERE sbmsdevs.submitter = :submitter";
        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users
     * who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!$context instanceof \context_module) {
            return;
        }

        $params = array(
            'instanceid' => $context->instanceid,
        );
        $sql = "SELECT sbms.submitter
                  FROM {course_modules} cm
                  JOIN {". mod_astra_exercise_round::TABLE ."} exround ON exround.id = cm.instance
                  JOIN {". mod_astra_learning_object::TABLE ."} lobject ON lobject.roundid = exround.id
                  JOIN {". mod_astra_submission::TABLE ."} sbms ON sbms.exerciseid = lobject.id
                 WHERE cm.id = :instanceid";
        $userlist->add_from_sql('submitter', $sql, $params);

        $sql = "SELECT dldev.submitter
                  FROM {course_modules} cm
                  JOIN {". mod_astra_exercise_round::TABLE ."} exround ON exround.id = cm.instance
                  JOIN {". mod_astra_learning_object::TABLE ."} lobject ON lobject.roundid = exround.id
                  JOIN {". mod_astra_deadline_deviation::TABLE ."} dldev ON dldev.exerciseid = lobject.id
                 WHERE cm.id = :instanceid";
        $userlist->add_from_sql('submitter', $sql, $params);

        $sql = "SELECT sbmsdev.submitter
                  FROM {course_modules} cm
                  JOIN {". mod_astra_exercise_round::TABLE ."} exround ON exround.id = cm.instance
                  JOIN {". mod_astra_learning_object::TABLE ."} lobject ON lobject.roundid = exround.id
                  JOIN {". mod_astra_submission_limit_deviation::TABLE ."} sbmsdev ON sbmsdev.exerciseid = lobject.id
                 WHERE cm.id = :instanceid";
        $userlist->add_from_sql('submitter', $sql, $params);
    }

    /**
     * Export all user data for the specified user, in the specified contexts,
     * using the supplied exporter instance.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;
        if ($contextlist->count() < 1) {
            return;
        }
        $user = $contextlist->get_user();
        $userid = $user->id;
        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);

        // All submissions.
        $sql = "SELECT sbms.*,
                    lobject.name AS exercisename,
                    exround.name AS roundname,
                    c.id AS contextid
                  FROM {context} c
                  JOIN {course_modules} cm ON cm.id = c.instanceid
                  JOIN {". mod_astra_exercise_round::TABLE ."} exround ON exround.id = cm.instance
                  JOIN {". mod_astra_learning_object::TABLE ."} lobject ON lobject.roundid = exround.id
                  JOIN {". mod_astra_submission::TABLE ."} sbms ON sbms.exerciseid = lobject.id
                 WHERE (
                     sbms.submitter = :submitter AND
                     c.id {$contextsql}
                 )";
        $params = array(
            'submitter' => $userid,
        ) + $contextparams;
        $submissions = $DB->get_recordset_sql($sql, $params);
        foreach ($submissions as $sbms) {
            $context = context::instance_by_id($sbms->contextid);
            $subcontext = array('exerciseid_' . $sbms->exerciseid, 'submissions', "sid_{$sbms->id}");
            $submission = new mod_astra_submission($sbms);
            // Convert fields to human-readable format.
            $sbms->status = $submission->getStatus(true, true);
            $sbms->submissiontime = transform::datetime($sbms->submissiontime);
            $sbms->submitter_is_you = transform::yesno($sbms->submitter == $userid);
            $sbms->grader = ($sbms->grader !== null ? transform::user($sbms->grader) : null);
            $sbms->gradingtime = transform::datetime($sbms->gradingtime);
            // Remove fields that must not be visible to students.
            unset($sbms->hash, $sbms->contextid, $sbms->submitter);
            writer::with_context($context)
                ->export_data($subcontext, $sbms)
                ->export_area_files($subcontext,
                        mod_astra_exercise_round::MODNAME,
                        mod_astra_submission::SUBMITTED_FILES_FILEAREA,
                        $sbms->id);
        }
        $submissions->close();

        // All deadline extensions.
        $sql = "SELECT dldev.*,
                    lobject.name AS exercisename,
                    exround.name AS roundname,
                    c.id AS contextid
                  FROM {context} c
                  JOIN {course_modules} cm ON cm.id = c.instanceid
                  JOIN {". mod_astra_exercise_round::TABLE ."} exround ON exround.id = cm.instance
                  JOIN {". mod_astra_learning_object::TABLE ."} lobject ON lobject.roundid = exround.id
                  JOIN {". mod_astra_deadline_deviation::TABLE ."} dldev ON dldev.exerciseid = lobject.id
                 WHERE (
                     dldev.submitter = :submitter AND
                     c.id {$contextsql}
                 )";
        $dldevs = $DB->get_recordset_sql($sql, $params);
        foreach ($dldevs as $dev) {
            $context = context::instance_by_id($dev->contextid);
            $subcontext = array('exerciseid_' . $dev->exerciseid, 'deadline_extensions', "id_{$dev->id}");
            // Convert fields to human-readable format.
            $dev->submitter_is_you = transform::yesno($dev->submitter == $userid);
            $dev->withoutlatepenalty = transform::yesno($dev->withoutlatepenalty);
            // Remove fields that must not be visible to students.
            unset($dev->contextid, $dev->submitter, $dev->id);
            writer::with_context($context)
                ->export_data($subcontext, $dev);
        }
        $dldevs->close();

        // All submission limit extensions.
        $sql = "SELECT sbmsdev.*,
                    lobject.name AS exercisename,
                    exround.name AS roundname,
                    c.id AS contextid
                  FROM {context} c
                  JOIN {course_modules} cm ON cm.id = c.instanceid
                  JOIN {". mod_astra_exercise_round::TABLE ."} exround ON exround.id = cm.instance
                  JOIN {". mod_astra_learning_object::TABLE ."} lobject ON lobject.roundid = exround.id
                  JOIN {". mod_astra_submission_limit_deviation::TABLE ."} sbmsdev ON sbmsdev.exerciseid = lobject.id
                 WHERE (
                     sbmsdev.submitter = :submitter AND
                     c.id {$contextsql}
                 )";
        $sbmsdevs = $DB->get_recordset_sql($sql, $params);
        foreach ($sbmsdevs as $dev) {
            $context = context::instance_by_id($dev->contextid);
            $subcontext = array('exerciseid_' . $dev->exerciseid, 'submission_limit_extensions', "id_{$dev->id}");
            // Convert fields to human-readable format.
            $dev->submitter_is_you = transform::yesno($dev->submitter == $userid);
            // Remove fields that must not be visible to students.
            unset($dev->contextid, $dev->submitter, $dev->id);
            writer::with_context($context)
                ->export_data($subcontext, $dev);
        }
        $sbmsdevs->close();
    }

    /**
     * Delete all personal data for all users in the specified context.
     *
     * @param context $context Context to delete data from.
     */
    public static function delete_data_for_all_users_in_context(context $context) {
        global $DB;

        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }
        $cm = get_coursemodule_from_id(mod_astra_exercise_round::TABLE, $context->instanceid);
        if (!$cm) {
            return;
        }
        $exroundid = $cm->instance;

        // Delete all submissions of the exercise round from the database.
        $DB->delete_records_select(mod_astra_submission::TABLE,
            "exerciseid IN (
                SELECT id
                  FROM {". mod_astra_learning_object::TABLE ."} lobject
                 WHERE roundid = :exroundid
            )",
            array(
                'exroundid' => $exroundid,
            ));
        // Delete submitted files.
        $fs = get_file_storage();
        $fs->delete_area_files($context->id, mod_astra_exercise_round::MODNAME,
            mod_astra_submission::SUBMITTED_FILES_FILEAREA);

        // Delete deadline extensions.
        $DB->delete_records_select(mod_astra_deadline_deviation::TABLE,
            "exerciseid IN (
                SELECT id
                  FROM {". mod_astra_learning_object::TABLE ."} lobject
                 WHERE roundid = :exroundid
            )",
            array(
                'exroundid' => $exroundid,
            ));

        // Delete submission limit extensions.
        $DB->delete_records_select(mod_astra_submission_limit_deviation::TABLE,
            "exerciseid IN (
                SELECT id
                  FROM {". mod_astra_learning_object::TABLE ."} lobject
                 WHERE roundid = :exroundid
            )",
            array(
                'exroundid' => $exroundid,
            ));
    }

    /**
     * User data related to the user in the given contexts should either be
     * completely deleted, or overwritten if a structure needs to be maintained.
     * This will be called when a user has requested the right to be forgotten.
     *
     * @param approved_contextlist $contextlist contexts for a user
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;
        if ($contextlist->count() < 1) {
            return;
        }
        $userid = $contextlist->get_user()->id;
        $contextids = $contextlist->get_contextids();
        if (empty($contextids)) {
            return;
        }

        // Delete submitted files before the submissions since the
        // submission ids must be available for selecting the corresponding files.
        $fs = get_file_storage();
        foreach ($contextids as $cid) {
            $fs->delete_area_files_select($cid, mod_astra_exercise_round::MODNAME,
                mod_astra_submission::SUBMITTED_FILES_FILEAREA,
                "IN (
                    SELECT sbms.id
                      FROM {". mod_astra_submission::TABLE ."} sbms
                      JOIN {". mod_astra_learning_object::TABLE ."} lobject ON lobject.id = sbms.exerciseid
                      JOIN {". mod_astra_exercise_round::TABLE ."} exround ON exround.id = lobject.roundid
                      JOIN {course_modules} cm ON cm.instance = exround.id
                      JOIN {context} c ON c.instanceid = cm.id
                     WHERE sbms.submitter = :submitter AND c.id = :sbmscontextid
                )",
                array(
                    'submitter' => $userid,
                    'sbmscontextid' => $cid,
                ));
        }

        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextids, SQL_PARAMS_NAMED);
        $params = array(
            'submitter' => $userid,
            'contextlevel' => CONTEXT_MODULE,
        ) + $contextparams;

        // Delete the user's submissions in the given contexts.
        $DB->delete_records_select(mod_astra_submission::TABLE,
            "submitter = :submitter AND exerciseid IN (
                SELECT lobject.id
                  FROM {". mod_astra_learning_object::TABLE ."} lobject
                  JOIN {". mod_astra_exercise_round::TABLE ."} exround ON exround.id = lobject.roundid
                  JOIN {course_modules} cm ON cm.instance = exround.id
                  JOIN {context} c ON c.instanceid = cm.id AND c.contextlevel = :contextlevel
                 WHERE (
                     c.id {$contextsql}
                 )
            )",
            $params);
        
        // Delete the user's deadline extensions.
        $DB->delete_records_select(mod_astra_deadline_deviation::TABLE,
            "submitter = :submitter AND exerciseid IN (
                SELECT lobject.id
                  FROM {". mod_astra_learning_object::TABLE ."} lobject
                  JOIN {". mod_astra_exercise_round::TABLE ."} exround ON exround.id = lobject.roundid
                  JOIN {course_modules} cm ON cm.instance = exround.id
                  JOIN {context} c ON c.instanceid = cm.id AND c.contextlevel = :contextlevel
                 WHERE (
                     c.id {$contextsql}
                 )
            )",
            $params);

        // Delete the user's submission limit extensions.
        $DB->delete_records_select(mod_astra_submission_limit_deviation::TABLE,
            "submitter = :submitter AND exerciseid IN (
                SELECT lobject.id
                  FROM {". mod_astra_learning_object::TABLE ."} lobject
                  JOIN {". mod_astra_exercise_round::TABLE ."} exround ON exround.id = lobject.roundid
                  JOIN {course_modules} cm ON cm.instance = exround.id
                  JOIN {context} c ON c.instanceid = cm.id AND c.contextlevel = :contextlevel
                 WHERE (
                     c.id {$contextsql}
                 )
            )",
            $params);
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and
     * user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;
        $context = $userlist->get_context();
        $cm = get_coursemodule_from_id(mod_astra_exercise_round::TABLE, $context->instanceid);
        $userids = $userlist->get_userids();
        if (!$cm || empty($userids)) {
            return;
        }
        $exroundid = $cm->instance;

        list($userinsql, $userinparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $params = array(
            'exroundid' => $exroundid,
        ) + $userinparams;

        $submissionsql = "exerciseid IN (
            SELECT id
              FROM {". mod_astra_learning_object::TABLE ."} lobject
             WHERE lobject.roundid = :exroundid
        ) AND submitter {$userinsql}";
        // Delete submitted files before the submissions since the
        // submission ids must be available for selecting the corresponding files.
        $fs = get_file_storage();
        $fs->delete_area_files_select($context->id,
            mod_astra_exercise_round::MODNAME,
            mod_astra_submission::SUBMITTED_FILES_FILEAREA,
            "IN (
                SELECT id
                  FROM {". mod_astra_submission::TABLE ."}
                 WHERE {$submissionsql}
            )",
            $params);
        // Delete submissions.
        $DB->delete_records_select(mod_astra_submission::TABLE, $submissionsql, $params);

        // Delete deadline extensions for these users in the given context.
        $DB->delete_records_select(mod_astra_deadline_deviation::TABLE,
            $submissionsql,
            $params);

        // Delete submission limit extensions for these users in the given context.
        $DB->delete_records_select(mod_astra_submission_limit_deviation::TABLE,
            $submissionsql,
            $params);
    }
}
