<?php
/** Page for manual editing/creation of a Stratum2 exercise.
 */
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php'); // defines MOODLE_INTERNAL for libraries
require_once(dirname(__FILE__) .'/editcourse_lib.php');

$id       = optional_param('id', 0, PARAM_INT); // exercise ID, edit existing
$roundid  = optional_param('round', 0, PARAM_INT); // exercise round ID, if creating new

if ($id) {
    $exerciseRecord = $DB->get_record(mod_stratumtwo_exercise::TABLE, array('id' => $id), '*', MUST_EXIST);
    $exercise = new mod_stratumtwo_exercise($exerciseRecord);
    $exround = $exercise->getExerciseRound();
    $page_url = \mod_stratumtwo\urls\urls::editExercise($exercise, true);
    $form_action = 'edit_exercise.php?id='. $id;
    $heading = get_string('editexercise', mod_stratumtwo_exercise_round::MODNAME);
} else if ($roundid) {
    $exround = mod_stratumtwo_exercise_round::createFromId($roundid);
    $page_url = \mod_stratumtwo\urls\urls::createExercise($exround, true);
    $form_action = 'edit_exercise.php?round='. $roundid;
    $heading = get_string('createexercise', mod_stratumtwo_exercise_round::MODNAME);
} else {
    // missing parameter: cannot create new or modify existing
    print_error('missingparam', '', '', 'id');
}

$courseid = $exround->getCourse()->courseid;
$course = get_course($courseid);

require_login($course, false);
$context = context_course::instance($courseid);
require_capability('mod/stratumtwo:addinstance', $context);

// Print the page header.
$PAGE->set_pagelayout('incourse');
$PAGE->set_url($page_url);
$PAGE->set_title(format_string(get_string('editexercise', mod_stratumtwo_exercise_round::MODNAME)));
$PAGE->set_heading(format_string($course->fullname));

// navbar
stratumtwo_edit_course_navbar_add($PAGE, $courseid,
        get_string('editexercise', mod_stratumtwo_exercise_round::MODNAME),
        $page_url, 'editexercise');

// Output starts here.
// gotcha: moodle forms should be initialized before $OUTPUT->header
$form = new \mod_stratumtwo\form\edit_exercise_form($exround, $id, $form_action);
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
    if (isset($fromform->parentid) && $fromform->parentid == 0) {
        $fromform->parentid = null; // use null in DB for no parent
    }
    
    if ($id) { // edit
        $fromform->id = $id;
        // if the round of the exercise changes, gradebook requires additional changes
        
        if ($fromform->roundid == $exerciseRecord->roundid) { // round not changed 
            $fromform->gradeitemnumber = $exerciseRecord->gradeitemnumber; // keep the old value
            $updatedExercise = new mod_stratumtwo_exercise($fromform);
            $updatedExercise->save();
            
            // update round max points
            $updatedExercise->getExerciseRound()->updateMaxPoints($updatedExercise->getMaxPoints() - $exerciseRecord->maxpoints);
        } else {
            // round changed, delete old gradebook item, modify max points of both rounds
            $exercise->deleteGradebookItem();
            // reduce max points of previous round (using the old max points of the exercise)
            $exercise->getExerciseRound()->updateMaxPoints(- $exercise->getMaxPoints());
            // gradeitemnumber must be unique in the new round
            $newRound = mod_stratumtwo_exercise_round::createFromId($fromform->roundid);
            $fromform->gradeitemnumber = $newRound->getNewGradebookItemNumber();
            $newExercise = new mod_stratumtwo_exercise($fromform);
            $newExercise->save(); // updates gradebook item (creates new item)
            $newRound->updateMaxPoints($newExercise->getMaxPoints());
        }
        
        $message = get_string('exerciseeditsuccess', mod_stratumtwo_exercise_round::MODNAME);
        
    } else { // create new
        $category = mod_stratumtwo_category::createFromId($fromform->categoryid);
        $exround = mod_stratumtwo_exercise_round::createFromId($fromform->roundid);
        $exercise = $exround->createNewExercise($fromform, $category);
        if ($exercise !== null) {
            // success
            $message = get_string('excreatesuccess', mod_stratumtwo_exercise_round::MODNAME);
        } else {
            $message = get_string('excreatefailure', mod_stratumtwo_exercise_round::MODNAME);
        }
    }
    
    echo '<p>'. $message .'</p>';
    echo '<p>'.
            html_writer::link(\mod_stratumtwo\urls\urls::editCourse($courseid, true),
              get_string('backtocourseedit', mod_stratumtwo_exercise_round::MODNAME)) .
         '</p>';
    
} else {
    if ($id && !$form->is_submitted()) { // if editing, fill the form with old values
        $form->set_data($exerciseRecord);
    }
    $form->display();
}

// Finish the page.
echo $output->footer();
