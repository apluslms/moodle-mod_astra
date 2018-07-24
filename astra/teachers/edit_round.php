<?php
/** Page for manual editing/creation of an Astra exercise round.
 */
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php'); // defines MOODLE_INTERNAL for libraries
require_once($CFG->libdir.'/filelib.php');
require_once($CFG->dirroot .'/course/lib.php');
require_once(dirname(__FILE__) .'/editcourse_lib.php');

$id       = optional_param('id', 0, PARAM_INT); // category ID, edit existing
$courseid = optional_param('course', 0, PARAM_INT); // course ID, if creating new

if ($id) {
    $roundRecord = $DB->get_record(mod_astra_exercise_round::TABLE, array('id' => $id), '*', MUST_EXIST);
    $exround = new mod_astra_exercise_round($roundRecord);
    $courseid = $roundRecord->course;
    $page_url = \mod_astra\urls\urls::editExerciseRound($exround, true);
    $form_action = 'edit_round.php?id='. $id;
    $heading = get_string('editmodule', mod_astra_exercise_round::MODNAME);
} else if ($courseid) {
    $page_url = \mod_astra\urls\urls::createExerciseRound($courseid, true);
    $form_action = 'edit_round.php?course='. $courseid;
    $heading = get_string('createmodule', mod_astra_exercise_round::MODNAME);
} else {
    // missing parameter: cannot create new or modify existing
    print_error('missingparam', '', '', 'id');
}

$course = get_course($courseid);

require_login($course, false);
$context = context_course::instance($courseid);
require_capability('mod/astra:addinstance', $context);

// Print the page header.
$PAGE->set_pagelayout('incourse');
$PAGE->set_url($page_url);
$PAGE->set_title(format_string(get_string('editmodule', mod_astra_exercise_round::MODNAME)));
$PAGE->set_heading(format_string($course->fullname));

// navbar
astra_edit_course_navbar_add($PAGE, $courseid,
        get_string('editmodule', mod_astra_exercise_round::MODNAME),
        $page_url, 'editround');


// intro editor requires preparation
$data = new stdClass();
$draftid_editor = file_get_submitted_draft_itemid('introeditor');
if ($id) {
    $currentintro = file_prepare_draft_area($draftid_editor, $context->id, mod_astra_exercise_round::MODNAME,
            'intro', 0, array('subdirs' => false), $roundRecord->intro);
    $data->introeditor = array('text' => $currentintro, 'format' => $roundRecord->introformat, 'itemid' => $draftid_editor);
} else {
    file_prepare_draft_area($draftid_editor, null, null, null, null, array('subdirs' => false));
    $data->introeditor = array('text' => '', 'format' => FORMAT_HTML, 'itemid' => $draftid_editor);
}

// Output starts here.
// gotcha: moodle forms should be initialized before $OUTPUT->header
$form = new \mod_astra\form\edit_round_form($courseid, $draftid_editor, $id, $form_action);
if ($form->is_cancelled()) {
    // Handle form cancel operation, if cancel button is present on form
    redirect(\mod_astra\urls\urls::editCourse($courseid, true));
    exit(0);
}

$form->set_data($data); // for introeditor


$output = $PAGE->get_renderer(mod_astra_exercise_round::MODNAME);

echo $output->header();
echo $output->heading($heading);

if ($fromform = $form->get_data()) {
    // form submitted and input is valid
    $fromform->course = $courseid;
    // add settings for the Moodle course module
    $fromform->visible = ($fromform->status != \mod_astra_exercise_round::STATUS_HIDDEN) ? 1 : 0;
    $fromform->visibleoncoursepage = $fromform->visible;
    
    // update name with new ordernum
    $courseconf = mod_astra_course_config::getForCourseId($courseid);
    if ($courseconf !== null) {
        $numberingStyle = $courseconf->getModuleNumbering();
    } else {
        $numberingStyle = mod_astra_course_config::getDefaultModuleNumbering();
    }
    $fromform->name = mod_astra_exercise_round::updateNameWithOrder($fromform->name, $fromform->ordernum, $numberingStyle);

    if ($id) { // edit
        $fromform->id = $id;
        $cm = $exround->getCourseModule();
        $fromform->coursemodule = $cm->id; // Moodle course module ID
        $fromform->cmidnumber = $cm->idnumber; // keep the old Moodle course module idnumber
        try {
            \update_module($fromform); // throws moodle_exception
            $message = get_string('modeditsuccess', mod_astra_exercise_round::MODNAME);
            
            // sort the grade items in the gradebook
            astra_sort_gradebook_items($courseid);
        } catch (\Exception $e) {
            $message = get_string('modeditfailure', mod_astra_exercise_round::MODNAME);
            $message .= ' '. $e->getMessage();
        }
        
    } else { // create new
        $fromform->modulename = \mod_astra_exercise_round::TABLE;
        $fromform->section = $fromform->sectionnumber; // course section for a new round
        unset($fromform->sectionnumber);
        $fromform->cmidnumber = ''; // Moodle course module idnumber, unused
        
        try {
            \create_module($fromform); // throws moodle_exception
            $message = get_string('modcreatesuccess', mod_astra_exercise_round::MODNAME);
        } catch (\Exception $e) {
            $message = get_string('modcreatefailure', mod_astra_exercise_round::MODNAME);
        }
    }
    
    echo '<p>'. $message .'</p>';
    echo '<p>'.
            html_writer::link(\mod_astra\urls\urls::editCourse($courseid, true),
              get_string('backtocourseedit', mod_astra_exercise_round::MODNAME)) .
         '</p>';
    
} else {
    if ($id && !$form->is_submitted()) { // if editing, fill the form with old values
        $form->set_data($roundRecord);
    }
    $form->display();
}

// Finish the page.
echo $output->footer();
