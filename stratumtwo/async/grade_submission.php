<?php

/**
 * Grade a submission. Only the exercise service should HTTP POST to this script
 * in order to asynchronously grade a submission.
 *
 * @package    mod_stratumtwo
 * @copyright  2016 Aalto SCI CS dept.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true); // not an HTML page
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once(dirname(__FILE__).'/async_lib.php');

$id = required_param('id', PARAM_INT); // submission ID
$hash = required_param('hash', PARAM_ALPHANUM); // submission hash

$submissionRecord = $DB->get_record(mod_stratumtwo_submission::TABLE, array(
        'id'   => $id,
        'hash' => $hash,
), '*', IGNORE_MISSING);
if ($submissionRecord === false) {
    http_response_code(404);
    exit(0);
}

$submission = new mod_stratumtwo_submission($submissionRecord);
$exerciseRecord = $DB->get_record_sql(mod_stratumtwo_learning_object::getSubtypeJoinSQL(mod_stratumtwo_exercise::TABLE) .
        ' WHERE lob.id = ?',
        array($submissionRecord->exerciseid),
        IGNORE_MISSING);
if ($exerciseRecord === false) {
    http_response_code(404);
    exit(0);
}
$exercise = new mod_stratumtwo_exercise($exerciseRecord);

$PAGE->set_context(context_module::instance($exercise->getExerciseRound()->getCourseModule()->id));

stratumtwo_async_submission_handler($exercise, $submission->getSubmitter(), $_POST, $submission);
