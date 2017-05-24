<?php

/**
 * Return exercise info box (with earned points) as an HTML fragment.
 *
 * @package    mod_astra
 * @copyright  2017 Aalto SCI CS dept.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');

$id = required_param('id', PARAM_INT); // learning object ID
// only exercises have assessment, hence chapters are not expected here

$learningObject = mod_astra_learning_object::createFromId($id);
$exround = $learningObject->getExerciseRound();
list($course, $cm) = get_course_and_cm_from_instance($exround->getId(), mod_astra_exercise_round::TABLE);

require_login($course, false, $cm);
// checks additionally that the user is enrolled in the course
$context = context_module::instance($cm->id);
// this should prevent guest access
require_capability('mod/astra:view', $context);
if ((!$cm->visible || $exround->isHidden() || $learningObject->isHidden()) &&
        !has_capability('moodle/course:manageactivities', $context)) {
    // show hidden exercise only to teachers
    throw new required_capability_exception($context,
        'moodle/course:manageactivities', 'nopermissions', '');
}

// render page content
$output = $PAGE->get_renderer(mod_astra_exercise_round::MODNAME);

$renderable = new \mod_astra\output\exercise_info($learningObject, $USER);
header('Content-Type: text/html');
echo $output->render($renderable);
// no Moodle header/footer in the output
