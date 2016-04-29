<?php
/** Page that lets the user export submitted files in the course to a zip archive.
 */
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php'); // defines MOODLE_INTERNAL for libraries
require_once(dirname(dirname(__FILE__)) .'/locallib.php');
require_once($CFG->libdir .'/filelib.php');

$cid = required_param('course', PARAM_INT); // Course ID
$course = get_course($cid);

require_login($course, false);
$context = context_course::instance($cid);
require_capability('mod/stratumtwo:addinstance', $context); // editing teacher

$title = get_string('exportsubmittedfiles', mod_stratumtwo_exercise_round::MODNAME);

$PAGE->set_pagelayout('incourse');
$PAGE->set_url(\mod_stratumtwo\urls\urls::exportSubmittedFiles($cid, true));
$PAGE->set_title(format_string($title));
$PAGE->set_heading(format_string($course->fullname));

// navbar
$courseNav = $PAGE->navigation->find($cid, navigation_node::TYPE_COURSE);
$exportNav = $courseNav->add($title,
        \mod_stratumtwo\urls\urls::exportSubmittedFiles($cid, true),
        navigation_node::TYPE_CUSTOM, null, 'exportfiles');
$exportNav->make_active();

// output starts
$form = new \mod_stratumtwo\form\export_results_form($cid, 'exportfilesinclallsubmissions',
        'exportsubmittedfiles', 'export_sbms_files.php?course='. $cid);
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

    $zip_path = stratumtwo_export_submitted_files($cid, $exerciseIds, $studentUserIds, $submittedBefore, $fromform->inclallsubmissions);
    if ($zip_path == false) {
        // error in creating the archive
        throw new moodle_exception('exportfilesziperror', mod_stratumtwo_exercise_round::MODNAME);
    } else {
        // force the user to download the file
        $date_now = date('d-m-Y\TH-i-s');
        $filename = "export_files_$date_now.zip";
        send_temp_file($zip_path, $filename);
    }

} else {
    // this branch is executed if the form is submitted but the data doesn't validate
    // and the form should be redisplayed, or on the first display of the form.

    $output = $PAGE->get_renderer(mod_stratumtwo_exercise_round::MODNAME);
    echo $output->header();
    echo $output->heading($title);
    echo '<p>'. get_string('exportsubmittedfilesdesc', mod_stratumtwo_exercise_round::MODNAME) .'</p>';

    $form->display();

    echo $output->footer();
}
