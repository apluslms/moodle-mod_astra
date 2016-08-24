<?php
/** Page that resubmits a submission to the exercise service for grading and
 * redirects the user back to the inspect page. This page should only be POSTed to, no GET.
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


$PAGE->set_url(\mod_astra\urls\urls::resubmitToService($submission, true));
$PAGE->set_title(get_string('resubmittoservice', mod_astra_exercise_round::MODNAME));
$PAGE->set_heading(format_string($course->fullname));

// resubmit the submission
// If this throws Exceptions, Moodle default exception handler should show
// an error message to the user.
$exercise->uploadSubmissionToService($submission);

redirect(\mod_astra\urls\urls::inspectSubmission($submission, true));
exit(0);
