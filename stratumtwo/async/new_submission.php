<?php

/**
 * Create a new, graded submission. Only the exercise service should HTTP POST to this script
 * in order to asynchronously create a new graded submission in Moodle.
 *
 * @package    mod_stratumtwo
 * @copyright  2016 Aalto SCI CS dept.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true); // not an HTML page
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once(dirname(__FILE__).'/async_lib.php');

$id = required_param('id', PARAM_INT); // exercise ID
$userid = required_param('userid', PARAM_INT);
$hash = required_param('hash', PARAM_ALPHANUM); // submission hash

$exerciseRecord = $DB->get_record(mod_stratumtwo_exercise::TABLE, array('id' => $id), '*', IGNORE_MISSING);
if ($exerciseRecord === false) {
    http_response_code(404);
    exit(0);
}
$exercise = new mod_stratumtwo_exercise($exerciseRecord);

$user = $DB->get_record('user', array('id' => $userid), '*', IGNORE_MISSING);
if ($user === false) {
    http_response_code(404);
    exit(0);
}

$validHash = $exercise->getAsyncHash($userid);
if ($hash != $validHash) {
    http_response_code(404);
    exit(0);
}

$PAGE->set_context(context_module::instance($exercise->getExerciseRound()->getCourseModule()->id));

stratumtwo_async_submission_handler($exercise, $user, $_POST);
