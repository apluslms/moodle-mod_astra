<?php

/**
 * Create a new, graded submission. Only the exercise service should HTTP POST to this script
 * in order to asynchronously create a new graded submission in Moodle.
 *
 * @package    mod_astra
 * @copyright  2016 Aalto SCI CS dept.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true); // not an HTML page
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once(dirname(__FILE__).'/async_lib.php');

$id = required_param('id', PARAM_INT); // exercise learning object ID
$userid = required_param('userid', PARAM_INT);
$hash = required_param('hash', PARAM_ALPHANUM); // submission hash

$exerciseRecord = $DB->get_record_sql(mod_astra_learning_object::getSubtypeJoinSQL(mod_astra_exercise::TABLE) .
        ' WHERE lob.id = ?',
        array($id),
        IGNORE_MISSING);
if ($exerciseRecord === false) {
    $event = \mod_astra\event\async_grading_failed::create(array(
        'context' => context_system::instance(),
        'other' => array(
            'error' => 'Async creation of new graded submission failed: request had invalid learning object ID (supplied '. $id .')',
        ),
    ));
    $event->trigger();
    
    http_response_code(404);
    exit(0);
}
$exercise = new mod_astra_exercise($exerciseRecord);

$user = $DB->get_record('user', array('id' => $userid), '*', IGNORE_MISSING);
if ($user === false) {
    $event = \mod_astra\event\async_grading_failed::create(array(
        'context' => context_module::instance($exercise->getExerciseRound()->getCourseModule()->id),
        'other' => array(
            'error' => 'Async creation of new graded submission failed: request had invalid userid ('. $userid .')',
        ),
    ));
    $event->trigger();
    
    http_response_code(404);
    exit(0);
}

$validHash = $exercise->getAsyncHash($userid);
if ($hash != $validHash) {
    $event = \mod_astra\event\async_grading_failed::create(array(
        'context' => context_module::instance($exercise->getExerciseRound()->getCourseModule()->id),
        'relateduserid' => $user->id,
        'other' => array(
            'error' => 'Async creation of new graded submission failed: request had invalid hash (supplied '. $hash .')',
        ),
    ));
    $event->trigger();
    
    http_response_code(404);
    exit(0);
}

$PAGE->set_context(context_module::instance($exercise->getExerciseRound()->getCourseModule()->id));

try {
    astra_send_json_response(
        astra_async_submission_handler($exercise, $user, $_POST));
} catch (mod_astra_async_forbidden_access_exception $e) {
    http_response_code(403);
    astra_send_json_response(array(
            'errors' => array($e->getMessage()),
    ));
}
