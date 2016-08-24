<?php
/** Page for manually assessing a submission to an exercise.
 */
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php'); // defines MOODLE_INTERNAL for libraries
require_once(dirname(dirname(__FILE__)).'/locallib.php');

$id = required_param('id', PARAM_INT); // submission ID

$submission = mod_astra_submission::createFromId($id);
$exercise = $submission->getExercise();
$exround = $exercise->getExerciseRound();
$cm = $exround->getCourseModule();

$course = get_course($exround->getCourse()->courseid);

require_login($course, false, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/astra:grademanually', $context);
if (!$exercise->isAssistantGradingAllowed() && !has_capability('mod/astra:addinstance', $context)) {
    // assistant grading not allowed and the user is not an editing teacher
    throw new moodle_exception('assistgradingnotallowed', mod_astra_exercise_round::MODNAME,
            \mod_astra\urls\urls::exercise($exercise));
}


// add CSS and JS
astra_page_require($PAGE);

$page_url = \mod_astra\urls\urls::assessSubmissionManually($submission, true);

$PAGE->set_url($page_url);
$PAGE->set_title(get_string('assesssubmission', mod_astra_exercise_round::MODNAME));
$PAGE->set_heading(format_string($course->fullname));

// navbar
$exerciseNav = astra_navbar_add_exercise($PAGE, $cm->id, $exercise);
$inspectNav = astra_navbar_add_inspect_submission($exerciseNav, $exercise, $submission);
$assessNav = $inspectNav->add(get_string('assesssubmission', mod_astra_exercise_round::MODNAME),
        $page_url,
        navigation_node::TYPE_CUSTOM,
        null, 'assess');
$assessNav->make_active();

// output starts
$form = new \mod_astra\form\assess_submission_form('assess_submission.php?id='.$id);
if ($form->is_cancelled()) {
    redirect(\mod_astra\urls\urls::inspectSubmission($submission));
    exit(0);
}

$output = $PAGE->get_renderer(mod_astra_exercise_round::MODNAME);

$previousData = new stdClass();
$previousData->grade = $submission->getGrade();
$previousData->feedback = $submission->getFeedback();
$previousData->assistfeedback = $submission->getAssistantFeedback();
$form->set_data($previousData);

if ($fromform = $form->get_data()) {
    // form submitted and valid, save changes to the database
    $submission->setRawGrade($fromform->grade);
    $submission->setFeedback($fromform->feedback);
    $submission->setAssistantFeedback($fromform->assistfeedback);
    $submission->setReady();
    $submission->setGrader($USER);
    // do not modify gradingtime, it is used for automatic grading
    $submission->save();
    
    // update gradebook, in case best points changed due to the manual assessment
    // (previous best points could have been reduced now or new points could be better than previous best)
    $submitterId = $submission->getSubmitter()->id;
    $bestSubmission = $exercise->getBestSubmissionForStudent($submitterId);
    $bestSubmission->writeToGradebook(true);
    
    // send a notification to the student that she has received new assistant feedback
    astra_send_assistant_feedback_notification($submission, $USER, $submission->getSubmitter());
    
    redirect(\mod_astra\urls\urls::inspectSubmission($submission));
    exit(0);
    
} else {
    // Print the page header (Moodle navbar etc.).
    echo $output->header();
    
    $renderable = new \mod_astra\output\assess_page($submission, $form->render());
    echo $output->render($renderable);
    
    echo $output->footer();
}
