<?php
/** Page that lets the user upload all/many submissions to the exercise service for regrading.
 */
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php'); // defines MOODLE_INTERNAL for libraries

$cid = required_param('course', PARAM_INT); // Course ID
$course = get_course($cid);

require_login($course, false);
$context = context_course::instance($cid);
require_capability('mod/stratumtwo:addinstance', $context); // editing teacher

$title = get_string('massregrading', mod_stratumtwo_exercise_round::MODNAME);

$PAGE->set_pagelayout('incourse');
$PAGE->set_url(\mod_stratumtwo\urls\urls::massRegrading($cid, true));
$PAGE->set_title(format_string($title));
$PAGE->set_heading(format_string($course->fullname));

// navbar
$courseNav = $PAGE->navigation->find($cid, navigation_node::TYPE_COURSE);
$massNav = $courseNav->add($title,
        \mod_stratumtwo\urls\urls::massRegrading($cid, true),
        navigation_node::TYPE_CUSTOM, null, 'massregrading');
$massNav->make_active();

// output starts
$form = new \mod_stratumtwo\form\mass_regrading_form($cid, 'mass_regrading.php?course='. $cid);
if ($form->is_cancelled()) {
    // Handle form cancel operation, if cancel button is present on form
    redirect(new moodle_url('/course/view.php', array('id' => $cid)));
    exit(0);
}

$output = $PAGE->get_renderer(mod_stratumtwo_exercise_round::MODNAME);
echo $output->header();
echo $output->heading($title);

if ($fromform = $form->get_data()) {
    // form submitted, prepare parameters for the adhoc task
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


    // create adhoc task, set custom data, add to queue
    $regrade_task = new \mod_stratumtwo\task\mass_regrading_task();
    $regrade_task->set_custom_data(array(
            'exercise_ids' => $exerciseIds,
            'student_user_ids' => $studentUserIds,
            'submissions' => $fromform->selectsubmissions,
            'course_id' => $cid,
    ));
    if (\core\task\manager::queue_adhoc_task($regrade_task)) {
        // task queued successfully
        $msgKey = 'massregrtasksuccess';
    } else {
        // creating the task failed
        $msgKey = 'massregrtaskerror';
    }
    echo '<p>'. get_string($msgKey, mod_stratumtwo_exercise_round::MODNAME) .'</p>';

} else {
    // this branch is executed if the form is submitted but the data doesn't validate
    // and the form should be redisplayed, or on the first display of the form.
    echo '<p>'. get_string('massregradingdesc', mod_stratumtwo_exercise_round::MODNAME) .'</p>';

    $form->display();
}

echo $output->footer();
