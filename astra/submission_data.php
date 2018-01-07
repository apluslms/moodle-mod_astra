<?php

/**
 * Return data of a submission in JSON format.
 *
 * @package    mod_astra
 * @copyright  2018 Aalto SCI CS dept.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');

$id = required_param('id', PARAM_INT); // submission ID

$submission = mod_astra_submission::createFromId($id);
$exercise = $submission->getExercise();
$exround = $exercise->getExerciseRound();
list($course, $cm) = get_course_and_cm_from_instance($exround->getId(), mod_astra_exercise_round::TABLE);

// access control
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

// set Content-Type header
header('Content-Type: application/json');

$data = $submission->getRecord();
// remove unnecessary properties
unset($data->hash);
unset($data->feedback);
unset($data->assistfeedback);
unset($data->servicepoints);
unset($data->servicemaxpoints);
unset($data->gradingdata);
// rename fields to match the name in A+, decode JSON fields
$data->submission_data = $submission->getSubmissionData();
unset($data->submissiondata);
// status as human-readable string
$data->status = strtolower($submission->getStatus(true));

$result = json_encode($data);
if ($result === false) {
    $error = json_last_error_msg();
    $result = json_encode(array(
        'error' => $error,
    ));
}

echo $result;

