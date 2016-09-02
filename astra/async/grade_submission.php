<?php

/**
 * Grade a submission. Only the exercise service should HTTP POST to this script
 * in order to asynchronously grade a submission.
 *
 * @package    mod_astra
 * @copyright  2016 Aalto SCI CS dept.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true); // not an HTML page
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once(dirname(__FILE__).'/async_lib.php');

$id = required_param('id', PARAM_INT); // submission ID
$hash = required_param('hash', PARAM_ALPHANUM); // submission hash

$submissionRecord = $DB->get_record(mod_astra_submission::TABLE, array(
        'id'   => $id,
        'hash' => $hash,
), '*', IGNORE_MISSING);
if ($submissionRecord === false) {
    $event = \mod_astra\event\async_grading_failed::create(array(
        'context' => context_system::instance(),
        'other' => array(
            'error' => 'Async grading of submission failed: request had invalid submission ID ('. $id .')',
        ),
    ));
    $event->trigger();
    
    http_response_code(404);
    exit(0);
}

$submission = new mod_astra_submission($submissionRecord);
$exerciseRecord = $DB->get_record_sql(mod_astra_learning_object::getSubtypeJoinSQL(mod_astra_exercise::TABLE) .
        ' WHERE lob.id = ?',
        array($submissionRecord->exerciseid),
        IGNORE_MISSING);
if ($exerciseRecord === false) {
    $event = \mod_astra\event\async_grading_failed::create(array(
        'context' => context_system::instance(),
        'other' => array(
            'error' => 'Async grading of submission failed: exercise ID of the submission is invalid',
        ),
    ));
    $event->trigger();
    
    http_response_code(404);
    exit(0);
}
$exercise = new mod_astra_exercise($exerciseRecord);

$PAGE->set_context(context_module::instance($exercise->getExerciseRound()->getCourseModule()->id));

try {
    astra_send_json_response(
        astra_async_submission_handler($exercise, $submission->getSubmitter(), $_POST, $submission));
} catch (mod_astra_async_forbidden_access_exception $e) {
    http_response_code(403);
    astra_send_json_response(array(
            'errors' => array($e->getMessage()),
    ));
}
