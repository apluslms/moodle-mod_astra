<?php
/** Page for manually assessing a submission to an exercise.
 */
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php'); // defines MOODLE_INTERNAL for libraries
require_once(dirname(dirname(__FILE__)).'/locallib.php');

$id = required_param('id', PARAM_INT); // submission ID

$submission = mod_stratumtwo_submission::createFromId($id);
$exercise = $submission->getExercise();
$exround = $exercise->getExerciseRound();
$cm = $exround->getCourseModule();

$course = get_course($exround->getCourse()->courseid);

require_login($course, false, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/stratumtwo:grademanually', $context);


// add CSS and JS
stratumtwo_page_require($PAGE);

$page_url = new moodle_url('/mod/'. mod_stratumtwo_exercise_round::TABLE .'/teachers/assess_submission.php', array('id' => $id));

$PAGE->set_url($page_url);
$PAGE->set_title(get_string('assesssubmission', mod_stratumtwo_exercise_round::MODNAME));
$PAGE->set_heading(format_string($course->fullname));

// navbar
$roundNav = $PAGE->navigation->find($cm->id, navigation_node::TYPE_ACTIVITY);
$exerciseNav = $roundNav->add($exercise->getName(),
        new moodle_url(\mod_stratumtwo\urls\urls::exercise($exercise)),
        navigation_node::TYPE_CUSTOM,
        null, 'ex'.$exercise->getId());
$allSbmsNav = $exerciseNav->add(get_string('allsubmissions', mod_stratumtwo_exercise_round::MODNAME),
        new moodle_url(\mod_stratumtwo\urls\urls::submissionList($exercise)),
        navigation_node::TYPE_CUSTOM,
        null, 'allsubmissions');
$submissionNav = $allSbmsNav->add(get_string('submissionnumber', mod_stratumtwo_exercise_round::MODNAME, $id),
        new moodle_url(\mod_stratumtwo\urls\urls::submission($submission)),
        navigation_node::TYPE_CUSTOM,
        null, 'sub'.$id);
$inspectNav = $submissionNav->add(get_string('inspectsubmission', mod_stratumtwo_exercise_round::MODNAME),
        new moodle_url(\mod_stratumtwo\urls\urls::inspectSubmission($submission)),
        navigation_node::TYPE_CUSTOM,
        null, 'inspect');
$assessNav = $inspectNav->add(get_string('assesssubmission', mod_stratumtwo_exercise_round::MODNAME),
        $page_url,
        navigation_node::TYPE_CUSTOM,
        null, 'assess');
$assessNav->make_active();

// output starts
$form = new \mod_stratumtwo\form\assess_submission_form('assess_submission.php?id='.$id);
if ($form->is_cancelled()) {
    redirect(\mod_stratumtwo\urls\urls::inspectSubmission($submission));
    exit(0);
}

$output = $PAGE->get_renderer(mod_stratumtwo_exercise_round::MODNAME);

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
    
    redirect(\mod_stratumtwo\urls\urls::inspectSubmission($submission));
    exit(0);
    
} else {
    // Print the page header (Moodle navbar etc.).
    echo $output->header();
    
    $renderable = new \mod_stratumtwo\output\assess_page($submission, $form->render());
    echo $output->render($renderable);
    
    echo $output->footer();
}
