<?php

/**
 * Library of interface functions and constants for module stratumtwo
 *
 * All the core Moodle functions, neeeded to allow the module to work
 * integrated in Moodle should be placed here.
 *
 * All the stratumtwo specific functions, needed to implement all the module
 * logic, should go to locallib.php. This will help to save some memory when
 * Moodle is performing actions across all modules.
 *
 * @package    mod_stratumtwo
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
function stratumtwo_supports($feature) {

    switch($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        default:
            return null;
    }
}

/**
 * Saves a new instance of the stratumtwo into the database 
 * (a new empty exercise round).
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param stdClass $stratumtwo Submitted data from the form in mod_form.php
 * @param mod_stratumtwo_mod_form $mform The form instance itself (if needed)
 * @return int The id of the newly inserted stratum record, 0 if failed
 */
function stratumtwo_add_instance(stdClass $stratumtwo, mod_stratumtwo_mod_form $mform = null) {
    return mod_stratumtwo_exercise_round::addInstance($stratumtwo);
}

/**
 * Updates an instance of the stratumtwo in the database.
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param stdClass $stratumtwo An object from the form in mod_form.php
 * @param mod_stratumtwo_mod_form $mform The form instance itself (if needed)
 * @return boolean Success/Fail
 */
function stratumtwo_update_instance(stdClass $stratumtwo, mod_stratumtwo_mod_form $mform = null) {

    $stratumtwo->id = $stratumtwo->instance;
    return mod_stratumtwo_exercise_round::updateInstance($stratumtwo);
}

/**
 * Removes an instance of the stratumtwo (exercise round) from the database.
 *
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function stratumtwo_delete_instance($id) {
    global $DB;

    if (! $stratumtwo = $DB->get_record(mod_stratumtwo_exercise_round::TABLE, array('id' => $id))) {
        return false;
    }
    $exround = new mod_stratumtwo_exercise_round($stratumtwo);
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
 * @param stdClass $stratumtwo The stratumtwo instance record
 * @return stdClass|null
 */
function stratumtwo_user_outline($course, $user, $cm, $stratumtwo) {
    // this callback is called from report/outline/user.php (course totals for user activity report)
    // report is accessible from the course user profile page, site may disallow students from viewing their reports
    
    // return the user's best total grade in the round and nothing else as the outline
    
    $exround = new mod_stratumtwo_exercise_round($stratumtwo);
    $summary = new \mod_stratumtwo\summary\user_module_summary($exround, $user);
    
    $return = new stdClass();
    $return->time = null;
    
    if ($summary->isSubmitted()) {
        $maxPoints = $summary->getMaxPoints();
        $points = $summary->getTotalPoints();
        $return->info = get_string('grade', mod_stratumtwo_exercise_round::MODNAME) ." $points/$maxPoints";
        $return->time = $summary->getLatestSubmissionTime();
    } else {
        $return->info = get_string('nosubmissions', mod_stratumtwo_exercise_round::MODNAME);
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
 * @param stdClass $stratumtwo the module instance record
 */
function stratumtwo_user_complete($course, $user, $cm, $stratumtwo) {
    // this callback is called from report/outline/user.php (course totals for user activity report)
    // report is accessible from the course user profile page, site may disallow students from viewing their reports
    
    // reuse the other callback that gathers all submissions in a round for a user
    $activities = array();
    $index = 0;
    stratumtwo_get_recent_mod_activity($activities, $index, 0, $course->id, $cm->id, $user->id);
    $modnames = get_module_types_names();
    foreach ($activities as $activity) {
        stratumtwo_print_recent_mod_activity($activity, $course->id, true, $modnames, true);
    }
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in stratumtwo activities and print it out.
 *
 * @param stdClass $course The course record
 * @param bool $viewfullnames Should we display full names
 * @param int $timestart Print activity since this timestamp
 * @return boolean True if anything was printed, otherwise false
 */
function stratumtwo_print_recent_activity($course, $viewfullnames, $timestart) {
    // this callback is used by the Moodle recent activity block
    global $USER, $DB, $OUTPUT;
    
    // all submissions in the course since $timestart
    $sql =
        'SELECT s.* 
           FROM {'. mod_stratumtwo_submission::TABLE .'} s 
          WHERE s.exerciseid IN (
            SELECT id 
              FROM {'. mod_stratumtwo_learning_object::TABLE .'} 
             WHERE categoryid IN (
              SELECT id 
                FROM {'. mod_stratumtwo_category::TABLE .'} 
               WHERE course = ?
             )
          ) AND s.submissiontime > ?';
    $params = array($course->id, $timestart);
    
    $context = context_course::instance($course->id);
    $isTeacher = has_capability('mod/stratumtwo:viewallsubmissions', $context);
    if (!$isTeacher) {
        // student only sees her own recent activity, not from other students
        $sql .= ' AND s.submitter = ?';
        $params[] = $USER->id;
    }
    
    $submissionsByExercise = array();
    $submissionRecords = $DB->get_recordset_sql($sql, $params);
    // organize recent submissions by exercise
    foreach ($submissionRecords as $sbmsRec) {
        $sbms = new mod_stratumtwo_submission($sbmsRec);
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
    
    echo $OUTPUT->heading(get_string('exercisessubmitted', mod_stratumtwo_exercise_round::MODNAME) .':', 3);
    
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
                    get_string('submissionsreceived', mod_stratumtwo_exercise_round::MODNAME, $numSubmissions).'</div>';
            $out .= '</div>';
            $out .= '<div class="info"><a href="'. \mod_stratumtwo\urls\urls::submissionList($exercise) .'">'.
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
                $text .= ' ('. get_string('grade', mod_stratumtwo_exercise_round::MODNAME) ." $grade/$maxPoints)";
            }
            print_recent_activity_note($best->getSubmissionTime(), $USER, $text,
                    \mod_stratumtwo\urls\urls::submission($best), false, $viewfullnames);
        }
    }
    
    return true;
}

/**
 * Prepares the recent activity data
 *
 * This callback function is supposed to populate the passed array with
 * custom activity records. These records are then rendered into HTML via
 * {@link stratumtwo_print_recent_mod_activity()}.
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
function stratumtwo_get_recent_mod_activity(&$activities, &$index, $timestart, $courseid, $cmid, $userid=0, $groupid=0) {
    // this callback is called from course/recent.php, which is linked from the recent activity block
    global $USER, $DB;
    
    $context = context_course::instance($courseid);
    $isTeacher = has_capability('mod/stratumtwo:viewallsubmissions', $context);
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
    $exround = mod_stratumtwo_exercise_round::createFromId($cm->instance);
    
    // all submissions in the round given by $cmid since $timestart
    $sql =
        'SELECT s.*
           FROM {'. mod_stratumtwo_submission::TABLE .'} s 
          WHERE s.exerciseid IN (
            SELECT id
              FROM {'. mod_stratumtwo_learning_object::TABLE .'} 
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
        $sbms = new mod_stratumtwo_submission($sbmsRec);
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
                $item->type = mod_stratumtwo_exercise_round::TABLE;
                
                $activities[$index++] = $item;
            }
        }
    }
}

/**
 * Prints single activity item prepared by {@link stratumtwo_get_recent_mod_activity()}
 *
 * @param stdClass $activity activity record with added 'cmid' property
 * @param int $courseid the id of the course we produce the report for
 * @param bool $detail print detailed report
 * @param array $modnames as returned by {@link get_module_types_names()}
 * @param bool $viewfullnames display users' full names
 */
function stratumtwo_print_recent_mod_activity($activity, $courseid, $detail, $modnames, $viewfullnames) {
    // modified from the corresponding function in mod_assign (function assign_print_recent_mod_activity in lib.php)
    global $CFG, $OUTPUT;
    
    echo '<table class="assignment-recent">';
    
    echo '<tr><td class="userpicture">';
    echo $OUTPUT->user_picture($activity->user);
    echo '</td><td>';
    
    $modname = $modnames[mod_stratumtwo_exercise_round::TABLE]; // localized module name
    echo '<div class="title">';
    echo '<img src="' . $OUTPUT->pix_url('icon', mod_stratumtwo_exercise_round::MODNAME) . '" '.
            'class="icon" alt="' . $modname . '">';
    echo '<a href="' . \mod_stratumtwo\urls\urls::submission($activity->submission) . '">';
    echo $activity->name;
    echo '</a>';
    echo '</div>';
    
    if ($activity->isgraded) {
        echo '<div class="grade">';
        echo get_string('grade', mod_stratumtwo_exercise_round::MODNAME) .': ';
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
function stratumtwo_cron () {
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
function stratumtwo_get_extra_capabilities() {
    return array(
        'moodle/course:manageactivities', // used in submission.php and exercise.php
        'moodle/role:assign', // used in auto_setup.php
        'enrol/manual:enrol', // used in auto_setup.php
    );
}

/* Gradebook API */

/**
 * Is a given scale used by the instance of stratumtwo?
 *
 * This function returns if a scale is being used by one stratumtwo
 * if it has support for grading and scales.
 *
 * @param int $stratumtwoid ID of an instance of this module
 * @param int $scaleid ID of the scale
 * @return bool true if the scale is used by the given stratumtwo instance
 */
function stratumtwo_scale_used($stratumtwoid, $scaleid) {
    return false; // stratumtwo does not use scales
    /*global $DB;
    if ($scaleid and $DB->record_exists(mod_stratumtwo_exercise_round::TABLE, array('id' => $stratumtwoid, 'grade' => -$scaleid))) {
        return true;
    } else {
        return false;
    }*/
}

/**
 * Checks if scale is being used by any instance of stratumtwo.
 *
 * This is used to find out if scale used anywhere.
 *
 * @param int $scaleid ID of the scale
 * @return boolean true if the scale is used by any stratumtwo instance
 */
function stratumtwo_scale_used_anywhere($scaleid) {
    return false; // stratumtwo does not use scales
    /*global $DB;
    if ($scaleid and $DB->record_exists(mod_stratumtwo_exercise_round::TABLE, array('grade' => -$scaleid))) {
        return true;
    } else {
        return false;
    }*/
}

/**
 * Creates or updates grade item for the given stratumtwo instance (exercise round).
 *
 * Needed by {@link grade_update_mod_grades()}.
 *
 * @param stdClass $stratumtwo instance object with extra cmidnumber and modname property
 * @param $grades save grades in the gradebook, or give string reset to delete all grades
 * @return void
 */
function stratumtwo_grade_item_update(stdClass $stratumtwo, $grades=NULL) {
    $exround = new mod_stratumtwo_exercise_round($stratumtwo);
    $exround->updateGradebookItem($grades === 'reset');
    
    if ($grades !== null && $grades !== 'reset') {
        // in case someone tries to update grades to students with this function
        // (not recommended, since this function has to update the grade item every time)
        $exround->updateGrades($grades);
    }
}

/**
 * Delete grade item for given stratumtwo instance.
 *
 * @param stdClass $stratumtwo instance object
 * @return grade_item
 */
function stratumtwo_grade_item_delete($stratumtwo) {
    $exround = new mod_stratumtwo_exercise_round($stratumtwo);
    return $exround->deleteGradebookItem();
}

/**
 * Update stratumtwo grades in the gradebook.
 *
 * Needed by {@link grade_update_mod_grades()}.
 *
 * @param stdClass $stratumtwo instance object with extra cmidnumber and modname property
 * @param int $userid update grade of specific user only, 0 means all participants
 * @param bool $nullifnone If a single user is specified, $nullifnone is true and
 *     the user has no grade then a grade item with a null rawgrade should be inserted
 * @return void
 */
function stratumtwo_update_grades(stdClass $stratumtwo, $userid = 0, $nullifnone = true) {
    // this function has no grades parameter, so the grades should be read
    // from some plugin database or an external server
    $exround = new mod_stratumtwo_exercise_round($stratumtwo);
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
function stratumtwo_get_file_areas($course, $cm, $context) {
    return array(
        \mod_stratumtwo_submission::SUBMITTED_FILES_FILEAREA =>
            get_string('submittedfilesareadescription', mod_stratumtwo_exercise_round::MODNAME),
    );
}

/**
 * File browsing support for stratumtwo file areas
 *
 * @package mod_stratumtwo
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
function stratumtwo_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
    global $CFG, $DB, $USER;
    if ($context->contextlevel != CONTEXT_MODULE) {
        return null;
    }
    // Make sure the filearea is one of those used by the plugin.
    if ($filearea !== \mod_stratumtwo_submission::SUBMITTED_FILES_FILEAREA) {
        return null;
    }
    // Check the relevant capabilities - these may vary depending on the filearea being accessed.
    if (!has_capability('mod/stratumtwo:view', $context)) {
        return null;
    }
    // itemid is the ID of the submission which the file was submitted to
    $submissionRecord = $DB->get_record(mod_stratumtwo_submission::TABLE, array('id' => $itemid), '*', IGNORE_MISSING);
    if ($submissionRecord === false) {
        return null;
    }
    // check that the user may view the file
    if ($submissionRecord->submitter != $USER->id && !has_capability('mod/stratumtwo:viewallsubmissions', $context)) {
        return null;
    }
    
    // Retrieve the file from the Files API.
    $fs = get_file_storage();
    $file = $fs->get_file($context->id, mod_stratumtwo_exercise_round::MODNAME, $filearea, $itemid, $filepath, $filename);
    if (!$file) {
        return null; // The file does not exist.
    }
    
    $urlbase = $CFG->wwwroot.'/pluginfile.php'; // standard Moodle script for serving files
    
    return new file_info_stored($browser, $context, $file, $urlbase, $filearea, $itemid, true, true, false);
}

/**
 * Serves the files from the stratumtwo file areas
 *
 * @package mod_stratumtwo
 * @category files
 *
 * @param stdClass $course the course object
 * @param stdClass $cm the course module object
 * @param stdClass $context the stratumtwo's context
 * @param string $filearea the name of the file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 */
function stratumtwo_pluginfile($course, $cm, $context, $filearea, array $args, $forcedownload, array $options=array()) {
    global $DB, $USER;
    // Check the contextlevel is as expected - if your plugin is a block, this becomes CONTEXT_BLOCK, etc.
    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }
    
    // Make sure the filearea is one of those used by the plugin.
    if ($filearea !== \mod_stratumtwo_submission::SUBMITTED_FILES_FILEAREA) {
        return false;
    }
    
    // Make sure the user is logged in and has access to the module (plugins that are not course modules should leave out the 'cm' part).
    require_login($course, true, $cm);
    
    // Check the relevant capabilities - these may vary depending on the filearea being accessed.
    if (!has_capability('mod/stratumtwo:view', $context)) {
        return false;
    }
    
    // Leave this line out if you set the itemid to null in moodle_url::make_pluginfile_url (set $itemid to 0 instead).
    $itemid = (int) array_shift($args); // The first item in the $args array.
    
    // Use the itemid to retrieve any relevant data records and perform any security checks to see if the
    // user really does have access to the file in question.
    // itemid is the ID of the submission which the file was submitted to
    $submissionRecord = $DB->get_record(mod_stratumtwo_submission::TABLE, array('id' => $itemid), '*', IGNORE_MISSING);
    if ($submissionRecord === false) {
        return false;
    }
    if ($submissionRecord->submitter != $USER->id && !has_capability('mod/stratumtwo:viewallsubmissions', $context)) {
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
    $file = $fs->get_file($context->id, mod_stratumtwo_exercise_round::MODNAME, $filearea, $itemid, $filepath, $filename);
    if (!$file) {
        return false; // The file does not exist.
    }
    
    // We can now send the file back to the browser
    send_stored_file($file, 0, 0, $forcedownload, $options);
}

/* Navigation API */

/**
 * Extends the global navigation tree by adding stratumtwo nodes if there is a relevant content
 *
 * This can be called by an AJAX request so do not rely on $PAGE as it might not be set up properly.
 *
 * @param navigation_node $navref An object representing the navigation tree node of the stratumtwo module instance
 * @param stdClass $course current course record
 * @param stdClass $module current stratumtwo instance record
 * @param cm_info $cm course module information
 */
//function stratumtwo_extend_navigation(navigation_node $navref, stdClass $course, stdClass $module, cm_info $cm) {
// Delete this function and its docblock, or implement it.
//}

/**
 * Extends the settings navigation with the stratumtwo settings
 *
 * This function is called when the context for the page is a stratumtwo module. This is not called by AJAX
 * so it is safe to rely on the $PAGE.
 *
 * @param settings_navigation $settingsnav complete settings navigation tree
 * @param navigation_node $stratumtwonode stratumtwo administration node
 */
//function stratumtwo_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $stratumtwonode=null) {
// Delete this function and its docblock, or implement it.
//}
