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
/*    global $CFG;
    require_once($CFG->libdir .'/gradelib.php');

    // get user grades
    $gradinginfo = grade_get_grades($course->id, 'mod', stratumtwo_PLUGINNAME,
            $stratumtwo->id, $user->id);
    if (empty($gradinginfo->items)) {
        return null;
    }
    $gradingitem = $gradinginfo->items[0]; // 0 = grade_item itemnumber
    // the plugin only uses zero in grade item itemnumbers
    if (!isset($gradingitem->grades[$user->id]->grade)) {
        return null;
    }
    $gradebookgrade = $gradingitem->grades[$user->id];

    $return = new stdClass();
    $return->time = $gradebookgrade->dategraded;
    $return->info = get_string('points', stratumtwo_MODNAME) .': '. $gradebookgrade->str_long_grade;
    return $return;
*/
    $return = new stdClass(); //TODO update from old plugin
    $return->time = 1446714353;
    $return->info = 'NOT YET IMPLEMENTED';
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
    // TODO should this print all submissions and their points?
    // for now, let's keep this short and simple
    $outline = stratumtwo_user_outline($course, $user, $cm, $stratumtwo);
    echo $outline->info;
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
/*    require_once(dirname(__FILE__) .'/recent_lib.php');
    global $USER, $OUTPUT;
    // Recent activity is taken from the grades in the gradebook (they have timestamps)
    // to avoid querying the Stratum server over HTTP for all submissions in the course.
    // Thus, the recent activity only includes submissions that have been graded.

    $context = context_course::instance($course->id);
    if (has_capability('moodle/grade:viewall', $context)) {
        // teacher can see submissions from all students
        $userid = 0;
    } else {
        $userid = $USER->id;
    }

    $items_to_show = stratumtwo_print_recent_activity_helper($course->id,
            $timestart, $userid);

    if (empty($items_to_show)) {
        return false;
    }

    echo $OUTPUT->heading(get_string('gradedasgns', stratumtwo_MODNAME) .':', 3);
    if ($userid === 0) {
        // Do not show a massive list of all students to the teacher.
        // Show just an aggregate value for each asgn/subasgn.
        foreach ($items_to_show as $it) {
            // echo similar HTML structure as function print_recent_activity_note in moodle/lib/weblib.php
            $out = '';
            $out .= '<div class="head">';
            $out .= '<div class="date">'.userdate(time(), get_string('strftimerecent')).'</div>';
            $out .= '<div class="name">'.
                    get_string('gradedstudents', stratumtwo_MODNAME, $it->numstudents).'</div>';
            $out .= '</div>';
            $out .= '<div class="info"><a href="'. $it->link .'">'.
                    format_string($it->text, true).'</a></div>';
            echo $out;
        }
    } else {
        // a single student, show details for each asgn/subasgn
        foreach ($items_to_show as $it) {
            print_recent_activity_note($it->time, $it->user, $it->text, $it->link, false, $viewfullnames);
        }
    }
    return true;
*/
    return false; //TODO update from old plugin
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
/*    require_once(dirname(__FILE__) .'/recent_lib.php');
    global $USER, $DB;

    $context = context_course::instance($courseid);
    // a student should not see other students' grades/activity
    if ($USER->id !== $userid && !has_capability('moodle/grade:viewall', $context)) {
        return;
    }

    $modinfo = get_fast_modinfo($courseid);
    $cm = $modinfo->get_cm($cmid);

    if ($userid == 0) { // $userid may be a string so === should not be used
        $items_to_show = stratumtwo_get_recent_mod_activity_all_students($courseid,
            stratumtwo_PLUGINNAME, $cm->instance, $timestart);
    } else {
        $stratum = $DB->get_record(stratumtwo_PLUGINNAME, array('id' => $cm->instance), '*', MUST_EXIST);
        $item = stratumtwo_get_recent_activity_one_instance($timestart,
            stratumtwo_PLUGINNAME, $stratum, $userid);
        if (is_null($item)) {
            $items_to_show = array();
        } else {
            $items_to_show = array($item);
        }
    }
    foreach ($items_to_show as $item) {
        // add new fields to the objects in the array and
        // append the objects to the $activities array (passed by reference)
        $item->type = stratumtwo_PLUGINNAME;
        $item->cmid = $cmid;

        $activities[$index++] = $item;
    }
*/
    //TODO update from old plugin
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
/*    require_once(dirname(__FILE__) .'/recent_lib.php');

    stratumtwo_print_recent_mod_activity_helper($activity, $courseid, $detail,
        $modnames, $viewfullnames);
*/
    // TODO update from old plugin
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
    //TODO update
    return array(
        'moodle/course:manageactivities', // used in view.php
        //'moodle/grade:viewall',
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
