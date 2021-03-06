<?php

/**
 * Prints a particular instance of astra (exercise round).
 *
 * @package    mod_astra
 * @copyright  2016 Aalto SCI CS dept.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/locallib.php');

$id = optional_param('id', 0, PARAM_INT); // Course_module ID, or
$n  = optional_param('s', 0, PARAM_INT);  // ... exercise round ID

if ($id) {
    list($course, $cm) = get_course_and_cm_from_cmid($id, mod_astra_exercise_round::TABLE);
    $astra        = $DB->get_record(mod_astra_exercise_round::TABLE, array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($n) {
    $astra        = $DB->get_record(mod_astra_exercise_round::TABLE, array('id' => $n), '*', MUST_EXIST);
    list($course, $cm) = get_course_and_cm_from_instance($astra->id, mod_astra_exercise_round::TABLE);
} else {
    print_error('missingparam', '', '', 'id');
}

require_login($course, false, $cm);
$context = context_module::instance($cm->id);

$exround = new mod_astra_exercise_round($astra);

// this should prevent guest access
require_capability('mod/astra:view', $context);
if ((!$cm->visible || $exround->isHidden()) &&
        !has_capability('moodle/course:manageactivities', $context)) {
    // show hidden activity (exercise round page) only to teachers
    throw new required_capability_exception($context,
            'moodle/course:manageactivities', 'nopermissions', '');
}

// Event for logging (viewing the page)
$event = \mod_astra\event\course_module_viewed::create(array(
        'objectid' => $PAGE->cm->instance,
        'context' => $PAGE->context,
));
$event->add_record_snapshot('course', $PAGE->course);
$event->add_record_snapshot($PAGE->cm->modname, $astra);
$event->trigger();

// add CSS and JS
astra_page_require($PAGE);

$PAGE->set_url('/mod/'. mod_astra_exercise_round::TABLE .'/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($exround->getName()));
$PAGE->set_heading(format_string($course->fullname));

// render page content
$output = $PAGE->get_renderer(mod_astra_exercise_round::MODNAME);

// Print the page header (Moodle navbar etc.).
echo $output->header();

$renderable = new \mod_astra\output\exercise_round_page($exround, $USER);
echo $output->render($renderable);

echo $output->footer();
