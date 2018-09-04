<?php

/**
 * Library of interface functions and constants for module astra
 *
 * All the core Moodle functions, neeeded to allow the module to work
 * integrated in Moodle should be placed here.
 *
 * All the astra specific functions, needed to implement all the module
 * logic, should go to locallib.php. This will help to save some memory when
 * Moodle is performing actions across all modules.
 *
 * @package    mod_astra
 * @copyright  2016 Aalto SCI CS dept.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/* Moodle core API */

/**
 * Returns the information on whether the module supports a feature
 *
 * See {@link plugin_supports()} for more info.
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed true if the feature is supported, null if unknown
 */
function astra_supports($feature) {

    switch($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return true;
        case FEATURE_CONTROLS_GRADE_VISIBILITY:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        default:
            return null;
    }
}

/**
 * Saves a new instance of the astra into the database 
 * (a new empty exercise round).
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param stdClass $astra Submitted data from the form in mod_form.php
 * @param mod_astra_mod_form $mform The form instance itself (if needed)
 * @return int The id of the newly inserted record, 0 if failed
 */
function astra_add_instance(stdClass $astra, mod_astra_mod_form $mform = null) {
    return mod_astra_exercise_round::addInstance($astra);
}

/**
 * Updates an instance of the astra in the database.
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param stdClass $astra An object from the form in mod_form.php
 * @param mod_astra_mod_form $mform The form instance itself (if needed)
 * @return boolean Success/Fail
 */
function astra_update_instance(stdClass $astra, mod_astra_mod_form $mform = null) {

    $astra->id = $astra->instance;
    return mod_astra_exercise_round::updateInstance($astra);
}

/**
 * Removes an instance of the astra (exercise round) from the database.
 *
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function astra_delete_instance($id) {
    global $DB;

    if (! $astra = $DB->get_record(mod_astra_exercise_round::TABLE, array('id' => $id))) {
        return false;
    }
    $exround = new mod_astra_exercise_round($astra);
    return $exround->deleteInstance();
}

/**
 * Returns a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 *
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @param stdClass $course The course record
 * @param stdClass $user The user record
 * @param cm_info|stdClass $cm The course module info object or record
 * @param stdClass $astra The astra instance record
 * @return stdClass|null
 */
function astra_user_outline($course, $user, $cm, $astra) {
    // this callback is called from report/outline/user.php (course totals for user activity report)
    // report is accessible from the course user profile page, site may disallow students from viewing their reports
    
    // return the user's best total grade in the round and nothing else as the outline
    
    $exround = new mod_astra_exercise_round($astra);
    $summary = new \mod_astra\summary\user_module_summary($exround, $user);
    
    $return = new stdClass();
    $return->time = null;
    
    if ($summary->isSubmitted()) {
        $maxPoints = $summary->getMaxPoints();
        $points = $summary->getTotalPoints();
        $return->info = get_string('grade', mod_astra_exercise_round::MODNAME) ." $points/$maxPoints";
        $return->time = $summary->getLatestSubmissionTime();
    } else {
        $return->info = get_string('nosubmissions', mod_astra_exercise_round::MODNAME);
    }
    
    return $return;
}

/**
 * Prints a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * It is supposed to echo directly without returning a value.
 *
 * @param stdClass $course the current course record
 * @param stdClass $user the record of the user we are generating report for
 * @param cm_info $cm course module info
 * @param stdClass $astra the module instance record
 */
function astra_user_complete($course, $user, $cm, $astra) {
    // this callback is called from report/outline/user.php (course totals for user activity report)
    // report is accessible from the course user profile page, site may disallow students from viewing their reports
    
    // reuse the other callback that gathers all submissions in a round for a user
    $activities = array();
    $index = 0;
    astra_get_recent_mod_activity($activities, $index, 0, $course->id, $cm->id, $user->id);
    $modnames = get_module_types_names();
    foreach ($activities as $activity) {
        astra_print_recent_mod_activity($activity, $course->id, true, $modnames, true);
    }
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in astra activities and print it out.
 *
 * @param stdClass $course The course record
 * @param bool $viewfullnames Should we display full names
 * @param int $timestart Print activity since this timestamp
 * @return boolean True if anything was printed, otherwise false
 */
function astra_print_recent_activity($course, $viewfullnames, $timestart) {
    // this callback is used by the Moodle recent activity block
    global $USER, $DB, $OUTPUT;
    
    // all submissions in the course since $timestart
    $sql =
        'SELECT s.* 
           FROM {'. mod_astra_submission::TABLE .'} s 
          WHERE s.exerciseid IN (
            SELECT id 
              FROM {'. mod_astra_learning_object::TABLE .'} 
             WHERE categoryid IN (
              SELECT id 
                FROM {'. mod_astra_category::TABLE .'} 
               WHERE course = ?
             )
          ) AND s.submissiontime > ?';
    $params = array($course->id, $timestart);
    
    $context = context_course::instance($course->id);
    $isTeacher = has_capability('mod/astra:viewallsubmissions', $context);
    if (!$isTeacher) {
        // student only sees her own recent activity, not from other students
        $sql .= ' AND s.submitter = ?';
        $params[] = $USER->id;
    }
    
    $submissionsByExercise = array();
    $submissionRecords = $DB->get_recordset_sql($sql, $params);
    // organize recent submissions by exercise
    foreach ($submissionRecords as $sbmsRec) {
        $sbms = new mod_astra_submission($sbmsRec);
        if (isset($submissionsByExercise[$sbmsRec->exerciseid])) {
            $submissionsByExercise[$sbmsRec->exerciseid][] = $sbms; 
        } else {
            $submissionsByExercise[$sbmsRec->exerciseid] = array($sbms);
        }
    }
    $submissionRecords->close();
    
    if (empty($submissionsByExercise)) {
        return false;
    }
    
    echo $OUTPUT->heading(get_string('exercisessubmitted', mod_astra_exercise_round::MODNAME) .':', 3);
    
    if ($isTeacher) {
        // teacher: show the number of recent submissions in each exercise
        foreach ($submissionsByExercise as $submissions) {
            $exercise = $submissions[0]->getExercise();
            $numSubmissions = count($submissions);
            $text = $exercise->getName();
            
            // echo similar HTML structure as function print_recent_activity_note in moodle/lib/weblib.php,
            // but without any specific user
            $out = '';
            $out .= '<div class="head">';
            $out .= '<div class="date">'.userdate(time(), get_string('strftimerecent')).'</div>';
            $out .= '<div class="name">'.
                    get_string('submissionsreceived', mod_astra_exercise_round::MODNAME, $numSubmissions).'</div>';
            $out .= '</div>';
            $out .= '<div class="info"><a href="'. \mod_astra\urls\urls::submissionList($exercise) .'">'.
                    format_string($text, true).'</a></div>';
            echo $out;
        }
    } else {
        // student: of recent submissions, show the best one in each exercise
        foreach ($submissionsByExercise as $submissions) {
            $best = $submissions[0];
            foreach ($submissions as $sbms) {
                if ($sbms->getGrade() > $best->getGrade()) {
                    $best = $sbms;
                }
            }
            
            $text = $best->getExercise()->getName();
            if ($best->isGraded()) {
                $grade = $best->getGrade();
                $maxPoints = $best->getExercise()->getMaxPoints();
                $text .= ' ('. get_string('grade', mod_astra_exercise_round::MODNAME) ." $grade/$maxPoints)";
            }
            print_recent_activity_note($best->getSubmissionTime(), $USER, $text,
                    \mod_astra\urls\urls::submission($best), false, $viewfullnames);
        }
    }
    
    return true;
}

/**
 * Prepares the recent activity data
 *
 * This callback function is supposed to populate the passed array with
 * custom activity records. These records are then rendered into HTML via
 * {@link astra_print_recent_mod_activity()}.
 *
 * Returns void, it adds items into $activities and increases $index.
 *
 * @param array $activities sequentially indexed array of objects with added 'cmid' property
 * @param int $index the index in the $activities to use for the next record
 * @param int $timestart append activity since this time
 * @param int $courseid the id of the course we produce the report for
 * @param int $cmid course module id
 * @param int $userid check for a particular user's activity only, defaults to 0 (all users)
 * @param int $groupid check for a particular group's activity only, defaults to 0 (all groups)
 */
function astra_get_recent_mod_activity(&$activities, &$index, $timestart, $courseid, $cmid, $userid=0, $groupid=0) {
    // this callback is called from course/recent.php, which is linked from the recent activity block
    global $USER, $DB;
    
    $context = context_course::instance($courseid);
    $isTeacher = has_capability('mod/astra:viewallsubmissions', $context);
    if ($userid != $USER->id && !$isTeacher) {
        if ($userid == 0) {
            // all users requested but a student sees only herself
            $userid = $USER->id;
        } else {
            return; // only teachers see other users' activity
        }
    }
    
    $modinfo = get_fast_modinfo($courseid);
    $cm = $modinfo->get_cm($cmid);
    $exround = mod_astra_exercise_round::createFromId($cm->instance);
    
    // all submissions in the round given by $cmid since $timestart
    $sql =
        'SELECT s.*
           FROM {'. mod_astra_submission::TABLE .'} s 
          WHERE s.exerciseid IN (
            SELECT id
              FROM {'. mod_astra_learning_object::TABLE .'} 
             WHERE roundid = ? 
          ) AND s.submissiontime > ?';
    $params = array($exround->getId(), $timestart);
    
    if ($userid != 0) {
        // only one user
        $sql .= ' AND s.submitter = ?';
        $params[] = $userid;
    }
    
    $sql .= ' ORDER BY s.submissiontime ASC';
    
    $submissionsByExercise = array();
    $submissionRecords = $DB->get_recordset_sql($sql, $params);
    // organize recent submissions by exercise
    foreach ($submissionRecords as $sbmsRec) {
        $sbms = new mod_astra_submission($sbmsRec);
        if (isset($submissionsByExercise[$sbmsRec->exerciseid])) {
            $submissionsByExercise[$sbmsRec->exerciseid][] = $sbms;
        } else {
            $submissionsByExercise[$sbmsRec->exerciseid] = array($sbms);
        }
    }
    $submissionRecords->close();
    
    foreach ($exround->getExercises(true) as $exercise) {
        if (isset($submissionsByExercise[$exercise->getId()])) {
            foreach ($submissionsByExercise[$exercise->getId()] as $sbms) {
                $item = new stdClass();
                $item->user = $sbms->getSubmitter();
                $item->time = $sbms->getSubmissionTime();
                $item->grade = $sbms->getGrade();
                $item->maxpoints = $exercise->getMaxPoints();
                $item->isgraded = $sbms->isGraded();
                $item->name = $exercise->getName();
                $item->submission = $sbms;
                // the following fields are required by Moodle
                $item->cmid = $cmid;
                $item->type = mod_astra_exercise_round::TABLE;
                
                $activities[$index++] = $item;
            }
        }
    }
}

/**
 * Prints single activity item prepared by {@link astra_get_recent_mod_activity()}
 *
 * @param stdClass $activity activity record with added 'cmid' property
 * @param int $courseid the id of the course we produce the report for
 * @param bool $detail print detailed report
 * @param array $modnames as returned by {@link get_module_types_names()}
 * @param bool $viewfullnames display users' full names
 */
function astra_print_recent_mod_activity($activity, $courseid, $detail, $modnames, $viewfullnames) {
    // modified from the corresponding function in mod_assign (function assign_print_recent_mod_activity in lib.php)
    global $CFG, $OUTPUT;
    
    echo '<table class="assignment-recent">';
    
    echo '<tr><td class="userpicture">';
    echo $OUTPUT->user_picture($activity->user);
    echo '</td><td>';
    
    $modname = $modnames[mod_astra_exercise_round::TABLE]; // localized module name
    echo '<div class="title">';
    echo $OUTPUT->pix_icon('icon', $modname, mod_astra_exercise_round::MODNAME);
    echo '<a href="' . \mod_astra\urls\urls::submission($activity->submission) . '">';
    echo $activity->name;
    echo '</a>';
    echo '</div>';
    
    if ($activity->isgraded) {
        echo '<div class="grade">';
        echo get_string('grade', mod_astra_exercise_round::MODNAME) .': ';
        echo "{$activity->grade}/{$activity->maxpoints}";
        echo '</div>';
    }
    
    echo '<div class="user">';
    echo "<a href=\"{$CFG->wwwroot}/user/view.php?id={$activity->user->id}&amp;course=$courseid\">";
    echo fullname($activity->user) .'</a>  - ' . userdate($activity->time);
    echo '</div>';
    
    echo '</td></tr></table>';
}

/**
 * Function to be run periodically according to the Moodle cron.
 *
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * Note that this has been deprecated in favour of scheduled task API.
 *
 * @return boolean
 */
function astra_cron () {
    return true; // no failures
}

/**
 * Returns all other caps used in the module
 *
 * For example, this could be array('moodle/site:accessallgroups') if the
 * module uses that capability.
 *
 * @return array
 */
function astra_get_extra_capabilities() {
    return array(
        'moodle/course:manageactivities', // used in submission.php and exercise.php
        'moodle/role:assign', // used in auto_setup.php
        'enrol/manual:enrol', // used in auto_setup.php
    );
}

/* Gradebook API */

/**
 * Is a given scale used by the instance of astra?
 *
 * This function returns if a scale is being used by one astra
 * if it has support for grading and scales.
 *
 * @param int $astraid ID of an instance of this module
 * @param int $scaleid ID of the scale
 * @return bool true if the scale is used by the given astra instance
 */
function astra_scale_used($astraid, $scaleid) {
    return false; // astra does not use scales
    /*global $DB;
    if ($scaleid and $DB->record_exists(mod_astra_exercise_round::TABLE, array('id' => $astraid, 'grade' => -$scaleid))) {
        return true;
    } else {
        return false;
    }*/
}

/**
 * Checks if scale is being used by any instance of astra.
 *
 * This is used to find out if scale used anywhere.
 *
 * @param int $scaleid ID of the scale
 * @return boolean true if the scale is used by any astra instance
 */
function astra_scale_used_anywhere($scaleid) {
    return false; // astra does not use scales
    /*global $DB;
    if ($scaleid and $DB->record_exists(mod_astra_exercise_round::TABLE, array('grade' => -$scaleid))) {
        return true;
    } else {
        return false;
    }*/
}

/**
 * Creates or updates grade item for the given astra instance (exercise round).
 *
 * Needed by {@link grade_update_mod_grades()}.
 *
 * @param stdClass $astra instance object with extra cmidnumber and modname property
 * @param $grades save grades in the gradebook, or give string reset to delete all grades
 * @return void
 */
function astra_grade_item_update(stdClass $astra, $grades=NULL) {
    $exround = new mod_astra_exercise_round($astra);
    $reset = $grades === 'reset';
    $exround->updateGradebookItem($reset);
    
    foreach ($exround->getExercises(true, false) as $exercise) {
        // Moodle core only calls this function if it needs to update the grade item,
        // so the exercise grade items of the round must be updated here too.
        $exercise->updateGradebookItem($reset);
    }
    
    if ($grades !== null && !$reset) {
        // in case someone tries to update grades to students with this function
        // (not recommended, since this function has to update the grade item every time)
        $exround->updateGrades($grades);
    }
}

/**
 * Delete grade item for given astra instance.
 *
 * @param stdClass $astra instance object
 * @return grade_item
 */
function astra_grade_item_delete($astra) {
    $exround = new mod_astra_exercise_round($astra);
    return $exround->deleteGradebookItem();
}

/**
 * Update astra grades in the gradebook.
 *
 * Needed by {@link grade_update_mod_grades()}.
 *
 * @param stdClass $astra instance object with extra cmidnumber and modname property
 * @param int $userid update grade of specific user only, 0 means all participants
 * @param bool $nullifnone If a single user is specified, $nullifnone is true and
 *     the user has no grade then a grade item with a null rawgrade should be inserted
 * @return void
 */
function astra_update_grades(stdClass $astra, $userid = 0, $nullifnone = true) {
    // this function has no grades parameter, so the grades should be read
    // from some plugin database or an external server
    $exround = new mod_astra_exercise_round($astra);
    $exround->writeAllGradesToGradebook($userid, $nullifnone);
}

/* File API */

/**
 * Returns the lists of all browsable file areas within the given module context
 *
 * The file area 'intro' for the activity introduction field is added automatically
 * by {@link file_browser::get_file_info_context_module()}
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @return array of [(string)filearea] => (string)description
 */
function astra_get_file_areas($course, $cm, $context) {
    return array(
        \mod_astra_submission::SUBMITTED_FILES_FILEAREA =>
            get_string('submittedfilesareadescription', mod_astra_exercise_round::MODNAME),
    );
}

/**
 * File browsing support for astra file areas
 *
 * @package mod_astra
 * @category files
 *
 * @param file_browser $browser
 * @param array $areas
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @param string $filearea
 * @param int $itemid
 * @param string $filepath
 * @param string $filename
 * @return file_info instance or null if not found
 */
function astra_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
    global $CFG, $DB, $USER;
    if ($context->contextlevel != CONTEXT_MODULE) {
        return null;
    }
    // Make sure the filearea is one of those used by the plugin.
    if ($filearea !== \mod_astra_submission::SUBMITTED_FILES_FILEAREA) {
        return null;
    }
    // Check the relevant capabilities - these may vary depending on the filearea being accessed.
    if (!has_capability('mod/astra:view', $context)) {
        return null;
    }
    // itemid is the ID of the submission which the file was submitted to
    $submissionRecord = $DB->get_record(mod_astra_submission::TABLE, array('id' => $itemid), '*', IGNORE_MISSING);
    if ($submissionRecord === false) {
        return null;
    }
    // check that the user may view the file
    if ($submissionRecord->submitter != $USER->id && !has_capability('mod/astra:viewallsubmissions', $context)) {
        return null;
    }
    
    // Retrieve the file from the Files API.
    $fs = get_file_storage();
    $file = $fs->get_file($context->id, mod_astra_exercise_round::MODNAME, $filearea, $itemid, $filepath, $filename);
    if (!$file) {
        return null; // The file does not exist.
    }
    
    $urlbase = $CFG->wwwroot.'/pluginfile.php'; // standard Moodle script for serving files
    
    return new file_info_stored($browser, $context, $file, $urlbase, $filearea, $itemid, true, true, false);
}

/**
 * Serves the files from the astra file areas
 *
 * @package mod_astra
 * @category files
 *
 * @param stdClass $course the course object
 * @param stdClass $cm the course module object
 * @param stdClass $context the astra's context
 * @param string $filearea the name of the file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 */
function astra_pluginfile($course, $cm, $context, $filearea, array $args, $forcedownload, array $options=array()) {
    global $DB, $USER;
    // Check the contextlevel is as expected - if your plugin is a block, this becomes CONTEXT_BLOCK, etc.
    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }
    
    // Make sure the filearea is one of those used by the plugin.
    if ($filearea !== \mod_astra_submission::SUBMITTED_FILES_FILEAREA) {
        return false;
    }
    
    // Make sure the user is logged in and has access to the module (plugins that are not course modules should leave out the 'cm' part).
    require_login($course, true, $cm);
    
    // Check the relevant capabilities - these may vary depending on the filearea being accessed.
    if (!has_capability('mod/astra:view', $context)) {
        return false;
    }
    
    // Leave this line out if you set the itemid to null in moodle_url::make_pluginfile_url (set $itemid to 0 instead).
    $itemid = (int) array_shift($args); // The first item in the $args array.
    
    // Use the itemid to retrieve any relevant data records and perform any security checks to see if the
    // user really does have access to the file in question.
    // itemid is the ID of the submission which the file was submitted to
    $submissionRecord = $DB->get_record(mod_astra_submission::TABLE, array('id' => $itemid), '*', IGNORE_MISSING);
    if ($submissionRecord === false) {
        return false;
    }
    if ($submissionRecord->submitter != $USER->id && !has_capability('mod/astra:viewallsubmissions', $context)) {
        return false;
    }
    
    // Extract the filename / filepath from the $args array.
    $filename = array_pop($args); // The last item in the $args array.
    if (!$args) {
        $filepath = '/'; // $args is empty => the path is '/'
    } else {
        $filepath = '/'.implode('/', $args).'/'; // $args contains elements of the filepath
    }
    
    // Retrieve the file from the Files API.
    $fs = get_file_storage();
    $file = $fs->get_file($context->id, mod_astra_exercise_round::MODNAME, $filearea, $itemid, $filepath, $filename);
    if (!$file) {
        return false; // The file does not exist.
    }
    
    // We can now send the file back to the browser
    send_stored_file($file, 0, 0, $forcedownload, $options);
}

/* Navigation API */

/**
 * Extends the global navigation tree by adding astra nodes if there is a relevant content
 *
 * This can be called by an AJAX request so do not rely on $PAGE as it might not be set up properly.
 *
 * @param navigation_node $navref An object representing the navigation tree node of the astra module instance
 * @param stdClass $course current course record
 * @param stdClass $module current astra instance record
 * @param cm_info $cm course module information
 */
//function astra_extend_navigation(navigation_node $navref, stdClass $course, stdClass $module, cm_info $cm) {
// Delete this function and its docblock, or implement it.
//}

/**
 * Extends the settings navigation with the astra settings
 *
 * This function is called when the context for the page is a astra module. This is not called by AJAX
 * so it is safe to rely on the $PAGE.
 *
 * @param settings_navigation $settingsnav complete settings navigation tree
 * @param navigation_node $astranode astra administration node
 */
//function astra_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $astranode=null) {
// Delete this function and its docblock, or implement it.
//}

/* Calendar API */

/**
 * Is the event visible?
 *
 * This is used to determine global visibility of an event in all places throughout Moodle.
 * For example, the visibility could change based on the current user's role
 * (student or teacher).
 *
 * @param calendar_event $event
 * @return bool Returns true if the event is visible to the current user, false otherwise.
 */
function mod_astra_core_calendar_is_event_visible(calendar_event $event) {
    // hidden rounds are not shown to students
    $exround = mod_astra_exercise_round::createFromId($event->instance);
    $cm = $exround->getCourseModule();
    $context = context_module::instance($cm->id);

    $visible = $cm->visible && !$exround->isHidden();
    if ($visible) {
        return true;
    } else if (has_capability('mod/astra:addinstance', $context)) {
        // teacher sees everything
        return true;
    }
    return false;
}

/**
 * This function receives a calendar event and returns the action associated with it, or null if there is none.
 *
 * This is used by block_myoverview in order to display the event appropriately. If null is returned then the event
 * is not displayed on the block (but can still be displayed in the calendar).
 *
 * @param calendar_event $event
 * @param \core_calendar\action_factory $factory
 * @return \core_calendar\local\event\entities\action_interface|null
 */
function mod_astra_core_calendar_provide_event_action(calendar_event $event,
        \core_calendar\action_factory $factory) {
    $exround = mod_astra_exercise_round::createFromId($event->instance);

    // do not display the event after the round has closed
    // (if a student has a deadline extension in some exercise, it is not taken into account here)
    if ($exround->hasExpired(null, true)) {
        return null;
    }

    return $factory->create_instance(
        get_string('deadline', mod_astra_exercise_round::MODNAME) .': '. $exround->getName(),
        \mod_astra\urls\urls::exerciseRound($exround, true),
        1,
        $exround->isOpen() || $exround->isLateSubmissionOpen()
    );
}
