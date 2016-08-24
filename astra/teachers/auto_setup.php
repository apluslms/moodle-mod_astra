<?php
/** Page for automatic setup of Astra exercises.
 */
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php'); // defines MOODLE_INTERNAL for libraries
require_once(dirname(__FILE__) .'/editcourse_lib.php');

$cid = required_param('course', PARAM_INT); // Course ID
$course = get_course($cid);

require_login($course, false);
$context = context_course::instance($cid);
require_capability('mod/astra:addinstance', $context);

$page_url = new moodle_url('/mod/'. mod_astra_exercise_round::TABLE .'/teachers/auto_setup.php',
        array('course' => $cid));

// Print the page header.
$PAGE->set_pagelayout('incourse');
$PAGE->set_url($page_url);
$PAGE->set_title(format_string(get_string('autosetup', mod_astra_exercise_round::MODNAME)));
$PAGE->set_heading(format_string($course->fullname));

// navbar
astra_edit_course_navbar_add($PAGE, $cid,
        get_string('automaticsetup', mod_astra_exercise_round::MODNAME),
        $page_url, 'autosetup');

$default_values = $DB->get_record(\mod_astra_course_config::TABLE, array('course' => $cid));
if ($default_values === false) {
    $default_values = null;
}

// Output starts here.
// gotcha: moodle forms should be initialized before $OUTPUT->header
$form = new \mod_astra\form\autosetup_form($default_values, 'auto_setup.php?course='. $cid);
if ($form->is_cancelled()) {
    // Handle form cancel operation, if cancel button is present on form
    redirect(\mod_astra\urls\urls::editCourse($cid, true));
    exit(0);
}
$output = $PAGE->get_renderer(mod_astra_exercise_round::MODNAME);

echo $output->header();
echo $output->heading_with_help(get_string('autosetup', mod_astra_exercise_round::MODNAME),
        'autosetup', mod_astra_exercise_round::MODNAME);
echo '<p>'. get_string('createreminder', mod_astra_exercise_round::MODNAME) .'</p>';

// Form processing and displaying is done here
if ($fromform = $form->get_data()) {
    // In this case you process validated data. $mform->get_data() returns data posted in form.
    $errors = \mod_astra\autosetup\auto_setup::configure_content_from_url($cid,
            $fromform->sectionnum, $fromform->configurl, $fromform->apikey);
    if (empty($errors)) {
        echo '<p>'. get_string('autosetupsuccess', mod_astra_exercise_round::MODNAME) .'</p>';
    } else {
        // errors in creating/updating some course content
        echo '<p>'. get_string('autosetuperror', mod_astra_exercise_round::MODNAME) .'</p>';
        echo html_writer::alist($errors);
    }
    
    echo '<p>'.
            html_writer::link(\mod_astra\urls\urls::editCourse($cid, true),
              get_string('backtocourseedit', mod_astra_exercise_round::MODNAME)) .
         '</p>';
} else {
    // this branch is executed if the form is submitted but the data doesn't validate
    // and the form should be redisplayed, or on the first display of the form.
    $form->display();
}

// Finish the page.
echo $output->footer();
