<?php
/**
 * AJAX script for downloading learning object HTML that can be embedded into chapters.
 */
define('AJAX_SCRIPT', true);
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');

$id = required_param('id', PARAM_INT); // learning object ID

$learningObject = mod_stratumtwo_learning_object::createFromId($id);
$exround = $learningObject->getExerciseRound();
list($course, $cm) = get_course_and_cm_from_instance($exround->getId(), mod_stratumtwo_exercise_round::TABLE);

require_login($course, false, $cm);
$context = context_module::instance($cm->id);
// this should prevent guest access
require_capability('mod/stratumtwo:view', $context);
if ((!$cm->visible || $exround->isHidden() || $learningObject->isHidden()) &&
        !has_capability('moodle/course:manageactivities', $context)) {
    // show hidden exercise only to teachers
    throw new required_capability_exception($context,
        'moodle/course:manageactivities', 'nopermissions', '');
}

// render page content
$output = $PAGE->get_renderer(mod_stratumtwo_exercise_round::MODNAME);

$renderable = new \mod_stratumtwo\output\exercise_plain_page($exround, $learningObject,
        $USER);
// AJAX_SCRIPT makes Moodle set content type to JSON, but we are outputting HTML now
header('Content-Type: text/html');
echo $output->render($renderable);
// no Moodle header/footer in the output
