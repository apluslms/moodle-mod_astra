<?php
/** Page that explains and links to the different export methods.
 *  1) Export course results (points) to a JSON file that the teacher can download
 *  2) Export submitted files to a zip archive
 *  3) Export submitted form input to a JSON file (form input = text fields etc., not submitted files)
 */
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php'); // defines MOODLE_INTERNAL for libraries
require_once(dirname(dirname(__FILE__)) .'/locallib.php');

$cid = required_param('course', PARAM_INT); // Course ID
$course = get_course($cid);

require_login($course, false);
$context = context_course::instance($cid);
require_capability('mod/astra:addinstance', $context); // editing teacher

$title = get_string('exportdata', mod_astra_exercise_round::MODNAME);

$PAGE->set_pagelayout('incourse');
$PAGE->set_url(\mod_astra\urls\urls::exportIndex($cid, true));
$PAGE->set_title(format_string($title));
$PAGE->set_heading(format_string($course->fullname));

astra_page_require($PAGE); // Bootstrap CSS etc.

// navbar
$courseNav = $PAGE->navigation->find($cid, navigation_node::TYPE_COURSE);
$exportNav = $courseNav->add($title,
        \mod_astra\urls\urls::exportIndex($cid, true),
        navigation_node::TYPE_CUSTOM, null, 'exportindex');
$exportNav->make_active();

$output = $PAGE->get_renderer(mod_astra_exercise_round::MODNAME);
echo $output->header();

$renderable = new \mod_astra\output\export_index_page($cid);
echo $output->render($renderable);

echo $output->footer();