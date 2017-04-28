<?php

/**
 * Displays a submission to an Astra exercise.
 *
 * @package    mod_astra
 * @copyright  2016 Aalto SCI CS dept.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/locallib.php');

$id = required_param('id', PARAM_INT); // submission ID
$wait = (bool) optional_param('wait', 0, PARAM_INT); // yes 1 / no 0, poll for the grading status

$submission = mod_astra_submission::createFromId($id);
$exercise = $submission->getExercise();
$exround = $exercise->getExerciseRound();
list($course, $cm) = get_course_and_cm_from_instance($exround->getId(), mod_astra_exercise_round::TABLE);

require_login($course, false, $cm);
$context = context_module::instance($cm->id);
// this should prevent guest access
require_capability('mod/astra:view', $context);
if ((!$cm->visible || $exround->isHidden() || $exercise->isHidden()) &&
        !has_capability('moodle/course:manageactivities', $context)) {
    // show hidden exercise only to teachers
    throw new required_capability_exception($context,
            'moodle/course:manageactivities', 'nopermissions', '');
}

// check that the user is allowed to see the submission (students see only their own submissions)
if ($USER->id != $submission->getSubmitter()->id && 
        !((has_capability('mod/astra:viewallsubmissions', $context) && $exercise->isAssistantViewingAllowed()) ||
                has_capability('mod/astra:addinstance', $context))) {
    throw new required_capability_exception($context,
            'mod/astra:viewallsubmissions', 'nopermissions', '');
}

if (astra_is_ajax()) {
    // render page content
    $output = $PAGE->get_renderer(mod_astra_exercise_round::MODNAME);
    
    $renderable = new \mod_astra\output\submission_plain_page($exround, $exercise, $submission);
    header('Content-Type: text/html');
    echo $output->render($renderable);
    // no Moodle header/footer in the output
} else {

    // Print the page header.
    // add CSS and JS
    astra_page_require($PAGE);
    
    // add Moodle navbar item for the exercise and the submission, round is already there
    $exerciseNav = astra_navbar_add_exercise($PAGE, $cm->id, $exercise);
    $submissionNav = astra_navbar_add_submission($exerciseNav, $submission);
    $submissionNav->make_active();
    
    $PAGE->set_url(\mod_astra\urls\urls::submission($submission, true));
    $PAGE->set_title(format_string($exercise->getName()));
    $PAGE->set_heading(format_string($course->fullname));
    
    // render page content
    $output = $PAGE->get_renderer(mod_astra_exercise_round::MODNAME);
    
    echo $output->header();
    
    $renderable = new \mod_astra\output\submission_page($exround, $exercise, $submission, $wait);
    echo $output->render($renderable);
    
    echo $output->footer();
}
