<?php

/**
 * List all Astra exercise rounds (mod astra instances) in the course.
 *
 * @package    mod_astra
 * @copyright  2016 Aalto SCI CS dept.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/locallib.php');

$id = required_param('id', PARAM_INT); // Course ID

$course = get_course($id);

require_course_login($course);

$params = array(
    'context' => context_course::instance($course->id)
);
$event = \mod_astra\event\course_module_instance_list_viewed::create($params);
$event->add_record_snapshot('course', $course);
$event->trigger();

$strname = get_string('modulenameplural', mod_astra_exercise_round::MODNAME);
$page_url = \mod_astra\urls\urls::roundsIndex($id, true);
$PAGE->set_url($page_url);
$PAGE->navbar->add($strname, $page_url);
$PAGE->set_title("$course->shortname: $strname");
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('incourse');

// add CSS and JS
astra_page_require($PAGE);

// render page content
$output = $PAGE->get_renderer(mod_astra_exercise_round::MODNAME);

// Print the page header (Moodle navbar etc.).
echo $output->header();

$renderable = new \mod_astra\output\index_page($course, $USER);
echo $output->render($renderable);

echo $output->footer();
