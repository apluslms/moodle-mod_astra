<?php
/** Page for adding extra submissions to a student in an exercise.
 * This is used as a shortcut in the inspect submission page.
 */
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php'); // defines MOODLE_INTERNAL for libraries
require_once(dirname(dirname(__FILE__)) .'/locallib.php');

$exerciseid   = required_param('exerciseid', PARAM_INT); // Exercise id (lobjectid).
$userid       = required_param('userid', PARAM_INT); // Moodle user id.
$submissionid = required_param('submissionid', PARAM_INT); // Submission id.
$type         = required_param('type', PARAM_ALPHA); // submitlimit (deadline not yet implemented)

$exercise   = mod_astra_exercise::createFromId($exerciseid);
$user       = $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST);
$submission = mod_astra_submission::createFromId($submissionid);
$cm         = $exercise->getExerciseRound()->getCourseModule();
$course     = get_course($cm->course);

require_login($course, false, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/astra:addinstance', $context); // Editing teacher.

if ($type == 'submitlimit') {
    $page_url = \mod_astra\urls\urls::upsertSubmissionLimitDeviation(
            $exercise, $userid, $submission, true);
    $title = get_string('addextrasbms', mod_astra_exercise_round::MODNAME);
} else {
    print_error('missingparam', '', '', 'type');
}

// Print the page header.
$PAGE->set_pagelayout('incourse');
$PAGE->set_url($page_url);
$PAGE->set_title($title);
$PAGE->set_heading(format_string($course->fullname));

// navbar
$exerciseNav = astra_navbar_add_exercise($PAGE, $cm->id, $exercise);
$inspectNav = astra_navbar_add_inspect_submission($exerciseNav, $exercise, $submission);
$addDevNav = $inspectNav->add(
        $title,
        $page_url,
        navigation_node::TYPE_CUSTOM,
        null,
        'adddeviationtostudent');
$addDevNav->make_active();

//if ($type == 'submitlimit')
$form = new \mod_astra\form\upsert_submit_limit_deviation_form($page_url, array(
        'exercise' => $exercise,
        'userfullname' => $submission->getSubmitterName(),
));

if ($form->is_cancelled()) {
    redirect(\mod_astra\urls\urls::inspectSubmission($submission, true));
    exit(0);
} else if ($fromform = $form->get_data()) {
    // Add extra submissions. The user inserted the amount of extra submissions in the form.
    mod_astra_submission_limit_deviation::createOrUpdate($exerciseid, $userid, $fromform->extrasubmissions);

    redirect(\mod_astra\urls\urls::inspectSubmission($submission, true),
            get_string('extrasbmsaddedsuccess', mod_astra_exercise_round::MODNAME));
    exit(0);
} else {
    $output = $PAGE->get_renderer(mod_astra_exercise_round::MODNAME);
    echo $output->header();
    echo $output->heading($title);
    $form->display();
    echo $output->footer();
}
