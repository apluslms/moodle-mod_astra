<?php
/** Page for deleting an Astra object (exercise round/exercise/chapter/category).
 */
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php'); // defines MOODLE_INTERNAL for libraries
require_once($CFG->dirroot .'/course/lib.php');
require_once(dirname(__FILE__).'/editcourse_lib.php');
require_once(dirname(dirname(__FILE__)).'/locallib.php');

$id       = required_param('id', PARAM_INT); // ID of the object to delete
$type     = required_param('type', PARAM_ALPHAEXT);

if ($type == 'exercise') { // exercise or chapter
    $exercise = mod_astra_learning_object::createFromId($id);
    $exround = $exercise->getExerciseRound();
    $courseid = $exround->getCourse()->courseid;
    $page_url = \mod_astra\urls\urls::deleteExercise($exercise, true);
    
    $typeInTitle = get_string('learningobjectlow', mod_astra_exercise_round::MODNAME);
    $msg = new stdClass();
    $msg->type = $typeInTitle;
    $msg->name = $exercise->getName();
    $message = get_string('learningobjectremoval', mod_astra_exercise_round::MODNAME, $msg) .
            get_string('lobjectremovalnote', mod_astra_exercise_round::MODNAME);
    $affectedLearningObjects = $exercise->getChildren(true);
    array_unshift($affectedLearningObjects, $exercise);
} else if ($type == 'category') {
    $category = mod_astra_category::createFromId($id);
    $courseid = $category->getCourse()->courseid;
    $page_url = \mod_astra\urls\urls::deleteCategory($category, true);
    
    $typeInTitle = get_string('categorylow', mod_astra_exercise_round::MODNAME);
    $msg = new stdClass();
    $msg->type = $typeInTitle;
    $msg->name = $category->getName();
    $message = get_string('learningobjectremoval', mod_astra_exercise_round::MODNAME, $msg) .
            get_string('categoryremovalnote', mod_astra_exercise_round::MODNAME);
    $affectedLearningObjects = $category->getLearningObjects(true);
} else if ($type == 'round') {
    $exround = mod_astra_exercise_round::createFromId($id);
    $courseid = $exround->getCourse()->courseid;
    $page_url = \mod_astra\urls\urls::deleteExerciseRound($exround, true);
    
    $typeInTitle = get_string('roundlow', mod_astra_exercise_round::MODNAME);
    $msg = new stdClass();
    $msg->type = $typeInTitle;
    $msg->name = $exround->getName();
    $message = get_string('learningobjectremoval', mod_astra_exercise_round::MODNAME, $msg) .
            get_string('roundremovalnote', mod_astra_exercise_round::MODNAME);
    $affectedLearningObjects = $exround->getLearningObjects(true, true);
} else {
    print_error('invalidobjecttype', mod_astra_exercise_round::MODNAME, '', $type);
}

$course = get_course($courseid);
require_login($course, false);
$context = context_course::instance($course->id);
require_capability('mod/astra:addinstance', $context);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['delete'])) {
        if ($type == 'exercise') {
            $exercise->deleteInstance(); // updates gradebook, if it is an exercise
            // Update round grades in the gradebook since these exercise grades are removed.
            $exround->synchronizeGrades();
        } else if ($type == 'category') {
            // Gather affected rounds (exercises in the category are also deleted).
            $rounds = array();
            foreach ($category->getExercises(true) as $ex) {
                $round = $ex->getExerciseRound();
                $rounds[$round->getId()] = $round;
                // No duplicate rounds since the index is overridden.
            }

            $category->delete();

            foreach ($rounds as $round) {
                $round->synchronizeGrades();
            }
        } else {
            // round: must also delete the Moodle course module
            // this will also call the module callback in lib.php
            course_delete_module($exround->getCourseModule()->id);
        }
    }
    
    redirect(\mod_astra\urls\urls::editCourse($courseid, true));
    exit(0);
}

// Print the page header.
$PAGE->set_pagelayout('incourse');
$PAGE->set_url($page_url);
$PAGE->set_title(format_string(get_string('deleteobject', mod_astra_exercise_round::MODNAME)));
$PAGE->set_heading(format_string($course->fullname));

astra_page_require($PAGE); // Bootstrap CSS etc.

// navbar
astra_edit_course_navbar_add($PAGE, $course->id,
        get_string('deleteobject', mod_astra_exercise_round::MODNAME),
        $page_url, 'deleteobject');

$output = $PAGE->get_renderer(mod_astra_exercise_round::MODNAME);

echo $output->header();

$delete_page = new \mod_astra\output\delete_page($course->id,
        $typeInTitle, $message, $page_url->out(), $affectedLearningObjects);
echo $output->render($delete_page);

echo $output->footer();
