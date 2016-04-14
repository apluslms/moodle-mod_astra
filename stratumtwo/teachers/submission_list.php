<?php
/** Page for listing all submissions to an exercise.
 */
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php'); // defines MOODLE_INTERNAL for libraries
require_once(dirname(dirname(__FILE__)).'/locallib.php');

$id = required_param('id', PARAM_INT); // exercise learning object ID

$exercise = mod_stratumtwo_learning_object::createFromId($id);
if (!$exercise->isSubmittable()) {
    // no submissions in a chapter
    print_error('exerciselobjectexpected', mod_stratumtwo_exercise_round::MODNAME);
}
$exround = $exercise->getExerciseRound();
$cm = $exround->getCourseModule();

$course = get_course($exround->getCourse()->courseid);

require_login($course, false, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/stratumtwo:viewallsubmissions', $context);


// add CSS and JS
stratumtwo_page_require($PAGE);

$PAGE->set_url(\mod_stratumtwo\urls\urls::submissionList($exercise, true));
$PAGE->set_title(get_string('allsubmissions', mod_stratumtwo_exercise_round::MODNAME));
$PAGE->set_heading(format_string($course->fullname));

// navbar
$exerciseNav = stratumtwo_navbar_add_exercise($PAGE, $cm->id, $exercise);
$allSbmsNav = $exerciseNav->add(get_string('allsubmissions', mod_stratumtwo_exercise_round::MODNAME),
        \mod_stratumtwo\urls\urls::submissionList($exercise, true),
        navigation_node::TYPE_CUSTOM,
        null, 'allsubmissions');
$allSbmsNav->make_active();

// render page content
$output = $PAGE->get_renderer(mod_stratumtwo_exercise_round::MODNAME);

// Print the page header (Moodle navbar etc.).
echo $output->header();

$renderable = new \mod_stratumtwo\output\all_submissions_page($exercise);
echo $output->render($renderable);

echo $output->footer();
