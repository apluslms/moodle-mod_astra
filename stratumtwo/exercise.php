<?php

/**
 * Displays a Stratum2 exercise.
 *
 * @package    mod_stratumtwo
 * @copyright  2016 Aalto SCI CS dept.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');

$id = required_param('id', PARAM_INT); // exercise ID

$exerciseRecord = $DB->get_record(mod_stratumtwo_exercise::TABLE, array('id' => $id), '*', MUST_EXIST);
$exroundRecord  = $DB->get_record(mod_stratumtwo_exercise_round::TABLE, array('id' => $exerciseRecord->roundid), '*', MUST_EXIST);
list($course, $cm) = get_course_and_cm_from_instance($exroundRecord->id, mod_stratumtwo_exercise_round::TABLE);

$exround = new mod_stratumtwo_exercise_round($exroundRecord);
$exercise = new mod_stratumtwo_exercise($exerciseRecord);

require_login($course, false, $cm);
$context = context_module::instance($cm->id);
// this should prevent guest access
require_capability('mod/stratumtwo:view', $context);
if ((!$cm->visible || $exround->isHidden() || $exercise->isHidden()) &&
        !has_capability('moodle/course:manageactivities', $context)) {
            // show hidden exercise only to teachers
            throw new required_capability_exception($context,
                    'moodle/course:manageactivities', 'nopermissions', '');
}

// Event for logging (viewing the page)
$event = \mod_stratumtwo\event\exercise_viewed::create(array(
        'objectid' => $id,
        'context' => $PAGE->context,
));
$event->trigger();

// Print the page header.
//TODO require Bootstrap CSS and jQuery
//$PAGE->requires->js(new moodle_url('https://code.jquery.com/jquery-1.12.0.js')); // Moodle has 1.11.3 bundled
$PAGE->requires->css(new moodle_url('/mod/'. mod_stratumtwo_exercise_round::TABLE .'/assets/bootstrap/css/bootstrap.min.css'));
$PAGE->requires->js(new moodle_url('/mod/'. mod_stratumtwo_exercise_round::TABLE .'/assets/bootstrap/js/bootstrap.min.js'));
$PAGE->requires->css(new moodle_url('/mod/'. mod_stratumtwo_exercise_round::TABLE .'/assets/css/main.css'));
// highlight.js for source code syntax highlighting
//$PAGE->requires->css(new moodle_url('https://cdnjs.cloudflare.com/ajax/libs/highlight.js/8.7/styles/github.min.css'));
//$PAGE->requires->js(new moodle_url('https://cdnjs.cloudflare.com/ajax/libs/highlight.js/8.6/highlight.min.js'));

// add Moodle navbar item for the exercise, round is already there
$page_url = new moodle_url('/mod/'. mod_stratumtwo_exercise_round::TABLE .'/exercise.php', array('id' => $id));

$roundNav = $PAGE->navigation->find($cm->id, navigation_node::TYPE_ACTIVITY);
$exerciseNav = $roundNav->add($exercise->getName(), $page_url, navigation_node::TYPE_CUSTOM, null, $id);
$exerciseNav->make_active();

$PAGE->set_url($page_url);
$PAGE->set_title(format_string($exerciseRecord->name));
$PAGE->set_heading(format_string($course->fullname));

// render page content
$output = $PAGE->get_renderer(mod_stratumtwo_exercise_round::TABLE);

echo $output->header();

$renderable = new \mod_stratumtwo\output\exercise_page($exround, $exercise, $USER);
echo $output->render($renderable);

echo $output->footer();
