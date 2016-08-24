<?php
/** Page for listing student-specific submission deviations in the course.
 */
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php'); // defines MOODLE_INTERNAL for libraries
require_once(dirname(__FILE__) .'/editcourse_lib.php');
require_once(dirname(dirname(__FILE__)) .'/locallib.php');

$courseid  = optional_param('course', 0, PARAM_INT); // Course ID
$delete_id = optional_param('id', 0, PARAM_INT); // ID of a deviation to delete
if ($delete_id) {
    $type = required_param('remove', PARAM_ALPHA);
    if ($type == 'dl') {
        $deviation = mod_astra_deadline_deviation::createFromId($delete_id);
    } else {
        $deviation = mod_astra_submission_limit_deviation::createFromId($delete_id);
    }
    $courseid = $deviation->getExercise()->getExerciseRound()->getCourse()->courseid;
    
} else if (!$courseid) {
    print_error('missingparam', '', '', 'course');
}

$course = get_course($courseid);
require_login($course, false);
$context = context_course::instance($courseid);
require_capability('mod/astra:addinstance', $context); // editing teacher

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $delete_id) {
    $deviation->delete();
    
    redirect(\mod_astra\urls\urls::deviations($courseid, true));
    exit(0);
    
} else if ($delete_id) {
    // wrong HTTP request method for deleting
    print_error('invalidrequest');
}

astra_page_require($PAGE); // Bootstrap CSS etc.
// Print the page header.
$PAGE->set_pagelayout('incourse');
$PAGE->set_url(\mod_astra\urls\urls::deviations($courseid, true));
$PAGE->set_title(format_string(get_string('deviations', mod_astra_exercise_round::MODNAME)));
$PAGE->set_heading(format_string($course->fullname));

// navbar
astra_deviations_navbar($PAGE, $courseid);

// Output starts here.
$output = $PAGE->get_renderer(mod_astra_exercise_round::MODNAME);

echo $output->header();

$renderable = new \mod_astra\output\deviations_list_page($courseid);
echo $output->render($renderable);

// Finish the page.
echo $output->footer();
