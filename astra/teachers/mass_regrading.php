<?php
/** Page that lets the user upload all/many submissions to the exercise service for regrading.
 */
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php'); // defines MOODLE_INTERNAL for libraries

$cid = required_param('course', PARAM_INT); // Course ID
$course = get_course($cid);

require_login($course, false);
$context = context_course::instance($cid);
require_capability('mod/astra:addinstance', $context); // editing teacher

$title = get_string('massregrading', mod_astra_exercise_round::MODNAME);

$PAGE->set_pagelayout('incourse');
$PAGE->set_url(\mod_astra\urls\urls::massRegrading($cid, true));
$PAGE->set_title(format_string($title));
$PAGE->set_heading(format_string($course->fullname));

// navbar
$courseNav = $PAGE->navigation->find($cid, navigation_node::TYPE_COURSE);
$massNav = $courseNav->add($title,
        \mod_astra\urls\urls::massRegrading($cid, true),
        navigation_node::TYPE_CUSTOM, null, 'massregrading');
$massNav->make_active();

// output starts
$form = new \mod_astra\form\export_results_form($cid, 'regradesubmissions',
        'mass_regrading.php?course='. $cid);
if ($form->is_cancelled()) {
    // Handle form cancel operation, if cancel button is present on form
    redirect(new moodle_url('/course/view.php', array('id' => $cid)));
    exit(0);
}

$output = $PAGE->get_renderer(mod_astra_exercise_round::MODNAME);
echo $output->header();
echo $output->heading($title);

if ($fromform = $form->get_data()) {
    // form submitted, prepare parameters for the adhoc task
    $exerciseIds = \mod_astra\form\export_results_form::parse_exercises($fromform);

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

    // create adhoc task, set custom data, add to queue
    $regrade_task = new \mod_astra\task\mass_regrading_task();
    $regrade_task->set_custom_data(array(
            'exercise_ids' => $exerciseIds,
            'student_user_ids' => $studentUserIds,
            'submissions' => $fromform->selectsubmissions,
            'course_id' => $cid,
            'submitted_before' => $submittedBefore,
    ));
    if (\core\task\manager::queue_adhoc_task($regrade_task)) {
        // task queued successfully
        $msgKey = 'massregrtasksuccess';
    } else {
        // creating the task failed
        $msgKey = 'massregrtaskerror';
    }
    echo '<p>'. get_string($msgKey, mod_astra_exercise_round::MODNAME) .'</p>';

} else {
    // this branch is executed if the form is submitted but the data doesn't validate
    // and the form should be redisplayed, or on the first display of the form.
    echo '<p>'. get_string('massregradingdesc', mod_astra_exercise_round::MODNAME) .'</p>';

    $form->display();
}

echo $output->footer();
