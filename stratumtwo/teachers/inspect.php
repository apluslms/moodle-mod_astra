<?php
/** Page for inspecting a submission to an exercise.
 */
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php'); // defines MOODLE_INTERNAL for libraries
require_once(dirname(dirname(__FILE__)).'/locallib.php');

$id = required_param('id', PARAM_INT); // submission ID

$submission = mod_stratumtwo_submission::createFromId($id);
$exercise = $submission->getExercise();
$exround = $exercise->getExerciseRound();
$cm = $exround->getCourseModule();

$course = get_course($exround->getCourse()->courseid);

require_login($course, false, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/stratumtwo:viewallsubmissions', $context);
if (!$exercise->isAssistantViewingAllowed() && !has_capability('mod/stratumtwo:addinstance', $context)) {
    // assistant viewing not allowed and the user is not an editing teacher
    throw new moodle_exception('assistviewingnotallowed', mod_stratumtwo_exercise_round::MODNAME,
            \mod_stratumtwo\urls\urls::exercise($exercise));
}

// add CSS and JS
stratumtwo_page_require($PAGE);

$PAGE->set_url(\mod_stratumtwo\urls\urls::inspectSubmission($submission, true));
$PAGE->set_title(get_string('inspectsubmission', mod_stratumtwo_exercise_round::MODNAME));
$PAGE->set_heading(format_string($course->fullname));

// navbar
$exerciseNav = stratumtwo_navbar_add_exercise($PAGE, $cm->id, $exercise);
$inspectNav = stratumtwo_navbar_add_inspect_submission($exerciseNav, $exercise, $submission);
$inspectNav->make_active();

// render page content
$output = $PAGE->get_renderer(mod_stratumtwo_exercise_round::MODNAME);

// Print the page header (Moodle navbar etc.).
echo $output->header();

$renderable = new \mod_stratumtwo\output\inspect_page($submission);
echo $output->render($renderable);

echo $output->footer();
