<?php

/**
 * Poll for exercise submission status. Returns the status as plain text response.
 *
 * @package    mod_astra
 * @copyright  2017 Aalto SCI CS dept.
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
header('Content-Type: text/plain');

echo strtolower($submission->getStatus(true));
