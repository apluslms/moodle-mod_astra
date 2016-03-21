<?php
/** Page for deleting a Stratum2 exercise.
 */
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php'); // defines MOODLE_INTERNAL for libraries
require_once(dirname(__FILE__).'/editcourse_lib.php');
require_once(dirname(dirname(__FILE__)).'/locallib.php');

$id       = required_param('id', PARAM_INT); // exercise ID to delete

$exercise = mod_stratumtwo_exercise::createFromId($id);
$exround = $exercise->getExerciseRound();
$course = get_course($exround->getCourse()->courseid);

require_login($course, false);
$context = context_course::instance($course->id);
require_capability('mod/stratumtwo:addinstance', $context);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['delete'])) {
        $exercise->deleteInstance(); // updates gradebook
    }
    
    redirect(\mod_stratumtwo\urls\urls::editCourse($exround->getCourse()->courseid, true));
    exit(0);
}

$page_url = new moodle_url('/mod/'. mod_stratumtwo_exercise_round::TABLE .'/teachers/delete_exercise.php', array('id' => $id));
// Print the page header.
$PAGE->set_pagelayout('incourse');
$PAGE->set_url($page_url);
$PAGE->set_title(format_string(get_string('deleteexercise', mod_stratumtwo_exercise_round::MODNAME)));
$PAGE->set_heading(format_string($course->fullname));

stratumtwo_page_require($PAGE); // Bootstrap CSS etc.

// navbar
stratumtwo_edit_course_navbar_add($PAGE, $course->id,
        get_string('deleteexercise', mod_stratumtwo_exercise_round::MODNAME),
        $page_url, 'deleteexercise');

$output = $PAGE->get_renderer(mod_stratumtwo_exercise_round::MODNAME);

echo $output->header();

$message = get_string('learningobjectremoval', mod_stratumtwo_exercise_round::MODNAME, $exercise->getName()) .
        get_string('exerciseremovalnote', mod_stratumtwo_exercise_round::MODNAME);

$delete_page = new \mod_stratumtwo\output\delete_page($course->id,
        get_string('learningobjectlow', mod_stratumtwo_exercise_round::MODNAME),
        $message);
echo $output->render($delete_page);

echo $output->footer();
