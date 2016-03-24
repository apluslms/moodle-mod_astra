<?php
/** Page for manual editing/creation of a Stratum2 exercise category.
 */
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php'); // defines MOODLE_INTERNAL for libraries
require_once(dirname(__FILE__) .'/editcourse_lib.php');

$id       = optional_param('id', 0, PARAM_INT); // category ID, edit existing
$courseid = optional_param('course', 0, PARAM_INT); // course ID, if creating new

if ($id) {
    $catRecord = $DB->get_record(mod_stratumtwo_category::TABLE, array('id' => $id), '*', MUST_EXIST);
    $category = new mod_stratumtwo_category($catRecord);
    $courseid = $catRecord->course;
    $page_url = \mod_stratumtwo\urls\urls::editCategory($category, true);
    $form_action = 'edit_category.php?id='. $id;
    $heading = get_string('editcategory', mod_stratumtwo_exercise_round::MODNAME);
} else if ($courseid) {
    $page_url = \mod_stratumtwo\urls\urls::createCategory($courseid, true);
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
stratumtwo_edit_course_navbar_add($PAGE, $courseid,
        get_string('editcategory', mod_stratumtwo_exercise_round::MODNAME),
        $page_url, 'editcategory');

// Output starts here.
// gotcha: moodle forms should be initialized before $OUTPUT->header
$form = new \mod_stratumtwo\form\edit_category_form($courseid, $id, $form_action);
if ($form->is_cancelled()) {
    // Handle form cancel operation, if cancel button is present on form
    redirect(\mod_stratumtwo\urls\urls::editCourse($courseid, true));
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
        
        if ($cat->getStatus() != $category->getStatus()) {
            // changing to/from hidden status affects the visible max points of exercise rounds
            $roundRecords = $DB->get_records_select(mod_stratumtwo_exercise_round::TABLE,
                    'id IN (SELECT DISTINCT roundid FROM {'. mod_stratumtwo_exercise::TABLE .'} WHERE categoryid = ?)',
                    array($id));
            foreach ($roundRecords as $roundrec) {
                $round = new mod_stratumtwo_exercise_round($roundrec);
                $round->updateMaxPoints();
            }
        }
        
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
    echo '<p>'.
            html_writer::link(\mod_stratumtwo\urls\urls::editCourse($courseid, true),
              get_string('backtocourseedit', mod_stratumtwo_exercise_round::MODNAME)) .
         '</p>';
    
} else {
    if ($id && !$form->is_submitted()) { // if editing, fill the form with old values
        $form->set_data($catRecord);
    }
    $form->display();
}

// Finish the page.
echo $output->footer();
