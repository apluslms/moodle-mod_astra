<?php
/** Page that lets the user export course results (points) to a JSON file.
 */
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php'); // defines MOODLE_INTERNAL for libraries
require_once(dirname(dirname(__FILE__)) .'/locallib.php');
require_once($CFG->libdir .'/filelib.php');

$cid = required_param('course', PARAM_INT); // Course ID
$course = get_course($cid);

require_login($course, false);
$context = context_course::instance($cid);
require_capability('mod/stratumtwo:addinstance', $context); // editing teacher

$title = get_string('exportresults', mod_stratumtwo_exercise_round::MODNAME);

$PAGE->set_pagelayout('incourse');
$PAGE->set_url(\mod_stratumtwo\urls\urls::exportResults($cid, true));
$PAGE->set_title(format_string($title));
$PAGE->set_heading(format_string($course->fullname));

// navbar
$courseNav = $PAGE->navigation->find($cid, navigation_node::TYPE_COURSE);
$exportNav = $courseNav->add($title,
        \mod_stratumtwo\urls\urls::exportResults($cid, true),
        navigation_node::TYPE_CUSTOM, null, 'exportresults');
$exportNav->make_active();


if (!function_exists('json_last_error_msg')) { // added in PHP 5.5
    function json_last_error_msg() {
        // Source: http://php.net/manual/en/function.json-last-error-msg.php#117393
        static $ERRORS = array(
                JSON_ERROR_NONE => 'No error',
                JSON_ERROR_DEPTH => 'Maximum stack depth exceeded',
                JSON_ERROR_STATE_MISMATCH => 'State mismatch (invalid or malformed JSON)',
                JSON_ERROR_CTRL_CHAR => 'Control character error, possibly incorrectly encoded',
                JSON_ERROR_SYNTAX => 'Syntax error',
                JSON_ERROR_UTF8 => 'Malformed UTF-8 characters, possibly incorrectly encoded'
        );

        $error = json_last_error();
        return isset($ERRORS[$error]) ? $ERRORS[$error] : 'Unknown error';
    }
}

// output starts
$form = new \mod_stratumtwo\form\export_results_form($cid, 'export_results.php?course='. $cid);
if ($form->is_cancelled()) {
    // Handle form cancel operation, if cancel button is present on form
    redirect(new moodle_url('/course/view.php', array('id' => $cid)));
    exit(0);
}


if ($fromform = $form->get_data()) {
    // form submitted, prepare parameters for the export function
    if (isset($fromform->inclallexercises) && $fromform->inclallexercises) {
        $exerciseIds = null; // all exercises
    } else if (!empty($fromform->selectexercises)) {
        // only these exercises
        $exerciseIds = $fromform->selectexercises;
    } else if (!empty($fromform->selectcategories)) {
        // all exercises in these categories
        $exerciseIds = array_keys(
                $DB->get_records_list(mod_stratumtwo_learning_object::TABLE, 'categoryid', $fromform->selectcategories, '', 'id'));
    } else { // (!empty($fromform['selectrounds']))
        // all exercises in these rounds
        $exerciseIds = array_keys(
                $DB->get_records_list(mod_stratumtwo_learning_object::TABLE, 'roundid', $fromform->selectrounds, '', 'id'));
    }
    
    if (empty($fromform->selectstudents)) {
        $studentUserIds = null;
    } else {
        $studentUserIds = $fromform->selectstudents;
    }
    
    if (isset($fromform->submittedbefore)) {
        $submittedBefore = $fromform->submittedbefore;
    } else {
        $submittedBefore = 0;
    }
    
    $json = stratumtwo_export_results($cid, $exerciseIds, $studentUserIds, $submittedBefore, $fromform->inclallsubmissions);
    $json_str = json_encode($json);
    if ($json_str == false) {
        // JSON encoding error, probably a bug
        throw new coding_exception('JSON encoding error: '. json_last_error_msg());
    } else {
        // force the user to download the file
        $date_now = date('d-m-Y\TH-i-s');
        $filename = "export_results_$date_now.json";
        send_temp_file($json_str, $filename, true);
    }
    
} else {
    // this branch is executed if the form is submitted but the data doesn't validate
    // and the form should be redisplayed, or on the first display of the form.
    
    $output = $PAGE->get_renderer(mod_stratumtwo_exercise_round::MODNAME);
    echo $output->header();
    echo $output->heading($title);
    echo '<p>'. get_string('exportpassedlist', mod_stratumtwo_exercise_round::MODNAME,
            \mod_stratumtwo\urls\urls::exportPassedList($cid)) .
    '</p>';
    echo '<hr>';
    echo '<p>'. get_string('exportdescription', mod_stratumtwo_exercise_round::MODNAME) .'</p>';
    
    $form->display();
    
    echo $output->footer();
}
