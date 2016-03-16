<?php
/** Page for manual editing/creation of a Stratum2 exercise category.
 */
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php'); // defines MOODLE_INTERNAL for libraries

$id       = optional_param('id', 0, PARAM_INT); // category ID, edit existing
$courseid = optional_param('course', 0, PARAM_INT); // course ID, if creating new

if ($id) {
    $catRecord = $DB->get_record(mod_stratumtwo_category::TABLE, array('id' => $id), '*', MUST_EXIST);
    //$category = new mod_stratumtwo_category($catRecord);
    $courseid = $catRecord->course;
    $page_url = new moodle_url('/mod/'. mod_stratumtwo_exercise_round::TABLE .'/teachers/edit_category.php', array('id' => $id));
    $form_action = 'edit_category.php?id='. $id;
    $heading = get_string('editcategory', mod_stratumtwo_exercise_round::MODNAME);
} else if ($courseid) {
    $page_url = new moodle_url('/mod/'. mod_stratumtwo_exercise_round::TABLE .'/teachers/edit_category.php', array('course' => $courseid));
    $form_action = 'edit_category.php?course='. $courseid;
    $heading = get_string('createcategory', mod_stratumtwo_exercise_round::MODNAME);
} else {
    // missing parameter: cannot create new or modify existing
    print_error('missingparam', '', '', 'id');
}

$course = get_course($courseid);

require_login($course, false);
$context = context_course::instance($courseid);
require_capability('mod/stratumtwo:addinstance', $context);

// Print the page header.
$PAGE->set_pagelayout('incourse');
$PAGE->set_url($page_url);
$PAGE->set_title(format_string(get_string('editcategory', mod_stratumtwo_exercise_round::MODNAME)));
$PAGE->set_heading(format_string($course->fullname));

// navbar
$courseNav = $PAGE->navigation->find($courseid, navigation_node::TYPE_COURSE);
$editNav = $courseNav->add('Edit exercises', new moodle_url('/blocks/stratumtwo_setup/edit_exercises.php', array('course' => $courseid)),
        navigation_node::TYPE_CUSTOM, null, 'editexercises'); //TODO
$editCatNav = $editNav->add(get_string('editcategory', mod_stratumtwo_exercise_round::MODNAME),
        $page_url, navigation_node::TYPE_CUSTOM, null, 'editcategory');
$editCatNav->make_active();

// Output starts here.
// gotcha: moodle forms should be initialized before $OUTPUT->header
$form = new \mod_stratumtwo\form\edit_category_form($courseid, $id, $form_action);
if ($form->is_cancelled()) {
    // Handle form cancel operation, if cancel button is present on form
    redirect(new moodle_url('/course/view.php', array('id' => $courseid)));
    exit(0);
}

$output = $PAGE->get_renderer(mod_stratumtwo_exercise_round::MODNAME);

echo $output->header();
echo $output->heading($heading);

if ($fromform = $form->get_data()) {
    // form submitted and input is valid
    $fromform->course = $courseid;
    if ($id) { // edit
        $fromform->id = $id;
        $cat = new mod_stratumtwo_category($fromform);
        $cat->save();
        $message = get_string('cateditsuccess', mod_stratumtwo_exercise_round::MODNAME);
        
    } else { // create new
        if (mod_stratumtwo_category::createNew($fromform)) {
            // success
            $message = get_string('catcreatesuccess', mod_stratumtwo_exercise_round::MODNAME);
        } else {
            $message = get_string('catcreatefailure', mod_stratumtwo_exercise_round::MODNAME);
        }
    }
    
    echo '<p>'. $message .'</p>';
    echo '<p>'. //TODO back to exit exercises page
            html_writer::link(new moodle_url('/course/view.php', array('id' => $courseid)),
              get_string('backtocourse', mod_stratumtwo_exercise_round::MODNAME)) .
         '</p>';
    
} else {
    if ($id && !$form->is_submitted()) { // if editing, fill the form with old values
        $form->set_data($catRecord);
    }
    $form->display();
}

// Finish the page.
echo $output->footer();
