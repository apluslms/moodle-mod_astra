<?php

/**
 * Displays a submission to a Stratum2 exercise.
 *
 * @package    mod_stratumtwo
 * @copyright  2016 Aalto SCI CS dept.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/locallib.php');

$id = required_param('id', PARAM_INT); // submission ID

$submission = mod_stratumtwo_submission::createFromId($id);
$exercise = $submission->getExercise();
$exround = $exercise->getExerciseRound();
list($course, $cm) = get_course_and_cm_from_instance($exround->getId(), mod_stratumtwo_exercise_round::TABLE);

require_login($course, false, $cm);
$context = context_module::instance($cm->id);
// this should prevent guest access
require_capability('mod/stratumtwo:view', $context);
if ((!$cm->visible || $exround->isHidden() || $exercise->isHidden()) &&
        !has_capability('moodle/course:manageactivities', $context)) {
    // show hidden exercise only to teachers
    throw new required_capability_exception($context,
            'moodle/course:manageactivities', 'nopermissions', '');
}

// check that the user is allowed to see the submission (students see only their own submissions)
if ($USER->id != $submission->getSubmitter()->id && 
        !((has_capability('mod/stratumtwo:viewallsubmissions', $context) && $exercise->isAssistantViewingAllowed()) ||
                has_capability('mod/stratumtwo:addinstance', $context))) {
    throw new required_capability_exception($context,
            'mod/stratumtwo:viewallsubmissions', 'nopermissions', '');
}

// Print the page header.
// add CSS and JS
stratumtwo_page_require($PAGE);

// add Moodle navbar item for the exercise and the submission, round is already there
$exerciseNav = stratumtwo_navbar_add_exercise($PAGE, $cm->id, $exercise);
$submissionNav = stratumtwo_navbar_add_submission($exerciseNav, $submission);
$submissionNav->make_active();

$PAGE->set_url(\mod_stratumtwo\urls\urls::submission($submission, true));
$PAGE->set_title(format_string($exercise->getName()));
$PAGE->set_heading(format_string($course->fullname));

// render page content
$output = $PAGE->get_renderer(mod_stratumtwo_exercise_round::MODNAME);

echo $output->header();

$renderable = new \mod_stratumtwo\output\submission_page($exround, $exercise, $submission);
echo $output->render($renderable);

echo $output->footer();
