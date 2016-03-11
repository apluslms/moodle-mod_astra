<?php

/**
 * Displays a submission to a Stratum2 exercise.
 *
 * @package    mod_stratumtwo
 * @copyright  2016 Aalto SCI CS dept.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/locallib.php');

$id = required_param('id', PARAM_INT); // submission ID

$submissionRecord = $DB->get_record(mod_stratumtwo_submission::TABLE, array('id' => $id), '*', MUST_EXIST);
$exerciseRecord = $DB->get_record(mod_stratumtwo_exercise::TABLE, array('id' => $submissionRecord->exerciseid), '*', MUST_EXIST);
$exroundRecord  = $DB->get_record(mod_stratumtwo_exercise_round::TABLE, array('id' => $exerciseRecord->roundid), '*', MUST_EXIST);
list($course, $cm) = get_course_and_cm_from_instance($exroundRecord->id, mod_stratumtwo_exercise_round::TABLE);

$submission = new mod_stratumtwo_submission($submissionRecord);
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

// check that the user is allowed to see the submission (students see only their own submissions)
if ($USER->id != $submission->getSubmitter()->id && 
        !has_capability('mod/stratumtwo:viewallsubmissions', $context)) {
    throw new required_capability_exception($context,
            'mod/stratumtwo:viewallsubmissions', 'nopermissions', '');
}

// Print the page header.
// add CSS and JS
stratumtwo_page_require($PAGE);

// add Moodle navbar item for the exercise and the submission, round is already there
$exercise_page_url = new moodle_url('/mod/'. mod_stratumtwo_exercise_round::TABLE .'/exercise.php',
        array('id' => $exercise->getId()));
$page_url = new moodle_url('/mod/'. mod_stratumtwo_exercise_round::TABLE .'/submission.php',
        array('id' => $id));

$roundNav = $PAGE->navigation->find($cm->id, navigation_node::TYPE_ACTIVITY);
$exerciseNav = $roundNav->add($exercise->getName(), $exercise_page_url, navigation_node::TYPE_CUSTOM,
        null, 'ex'.$exercise->getId());
$submissionNav = $exerciseNav->add(get_string('submissionnumber', mod_stratumtwo_exercise_round::MODNAME, $id),
        $page_url, navigation_node::TYPE_CUSTOM, null, 'sub'.$id);
$submissionNav->make_active();

$PAGE->set_url($page_url);
$PAGE->set_title(format_string($exerciseRecord->name));
$PAGE->set_heading(format_string($course->fullname));

// render page content
$output = $PAGE->get_renderer(mod_stratumtwo_exercise_round::MODNAME);

echo $output->header();

$renderable = new \mod_stratumtwo\output\submission_page($exround, $exercise, $submission, $USER);
echo $output->render($renderable);

echo $output->footer();
