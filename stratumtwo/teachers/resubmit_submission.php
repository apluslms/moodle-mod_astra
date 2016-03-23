<?php
/** Page that resubmits a submission to the exercise service for grading and
 * redirects the user back to the inspect page. This page should only be POSTed to, no GET.
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


$PAGE->set_url(\mod_stratumtwo\urls\urls::resubmitToService($submission, true));
$PAGE->set_title(get_string('resubmittoservice', mod_stratumtwo_exercise_round::MODNAME));
$PAGE->set_heading(format_string($course->fullname));

// resubmit the submission
// If this throws Exceptions, Moodle default exception handler should show
// an error message to the user.
$exercise->uploadSubmissionToService($submission);

redirect(\mod_stratumtwo\urls\urls::inspectSubmission($submission, true));
exit(0);
