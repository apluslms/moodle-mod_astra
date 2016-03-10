<?php

/**
 * Displays a Stratum2 exercise.
 *
 * @package    mod_stratumtwo
 * @copyright  2016 Aalto SCI CS dept.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/locallib.php');

$id = required_param('id', PARAM_INT); // exercise ID

$exerciseRecord = $DB->get_record(mod_stratumtwo_exercise::TABLE, array('id' => $id), '*', MUST_EXIST);
$exroundRecord  = $DB->get_record(mod_stratumtwo_exercise_round::TABLE, array('id' => $exerciseRecord->roundid), '*', MUST_EXIST);
list($course, $cm) = get_course_and_cm_from_instance($exroundRecord->id, mod_stratumtwo_exercise_round::TABLE);

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

$errorMsg = null;
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // user submitted a new solution, create a database record
    // check if submission is allowed (deadline, submit limit)
    if ($exercise->isSubmissionAllowed($USER)) {
        $sbmsId = mod_stratumtwo_submission::createNewSubmission($exercise, $USER->id, $_POST);
        if ($sbmsId == 0) {
            // error: the new submission was not stored in the database
            $errorMsg = get_string('submissionfailed', mod_stratumtwo_exercise_round::MODNAME);
        }
        
        $event = \mod_stratumtwo\event\solution_submitted::create(array(
                'context' => $context,
                'objectid' => $sbmsId,
        ));
        $event->trigger();
        
        if ($sbmsId != 0) {
            $sbms = mod_stratumtwo_submission::createFromId($sbmsId);
            $tmpFiles = array();
            // add files
            try {
                foreach ($_FILES as $formInputName => $farray) {
                    if (isset($farray['tmp_name'])) {
                        // the user uploaded a file, i.e., the form input was not left blank
                        // sanitize original file name
                        $fobj = new stdClass();
                        $fobj->filename = mod_stratumtwo_submission::safeFileName($farray['name']);
                        $fobj->filepath = $farray['tmp_name'];
                        $fobj->mimetype = $farray['type'];
                        
                        $sbms->addSubmittedFile($fobj->filename, $formInputName, $fobj->filepath);
                        
                        $tmpFiles[$formInputName] = $fobj;
                    }
                }
                
                // send the new submission to the exercise service
                $exercise->uploadSubmissionToService($sbms, false, $tmpFiles, false);
                
            } catch (Exception $e) {
                $errorMsg = get_string('uploadtoservicefailed', mod_stratumtwo_exercise_round::MODNAME);
            }
            
            // delete temp files
            foreach ($tmpFiles as $f) {
                unlink($f->filepath);
            }
            
            if (empty($errorMsg)) {
                // Redirect the client to the submission page: 
                // there must be no output before this (echo HTML, whitespace outside php tags)
                header('Location: '. \mod_stratumtwo\urls\urls::submission($sbms));
                exit(0);
            }
        }
    } else {
        $errorMsg = get_string('youmaynotsubmit', mod_stratumtwo_exercise_round::MODNAME);
    }
}

// Event for logging (viewing the page)
$event = \mod_stratumtwo\event\exercise_viewed::create(array(
        'objectid' => $id,
        'context' => $PAGE->context,
));
$event->trigger();

// Print the page header.
// add CSS and JS
stratumtwo_page_require($PAGE);

// add Moodle navbar item for the exercise, round is already there
$page_url = new moodle_url('/mod/'. mod_stratumtwo_exercise_round::TABLE .'/exercise.php', array('id' => $id));

$roundNav = $PAGE->navigation->find($cm->id, navigation_node::TYPE_ACTIVITY);
$exerciseNav = $roundNav->add($exercise->getName(), $page_url, navigation_node::TYPE_CUSTOM,
        null, 'ex'.$id);
$exerciseNav->make_active();

$PAGE->set_url($page_url);
$PAGE->set_title(format_string($exerciseRecord->name));
$PAGE->set_heading(format_string($course->fullname));

// render page content
$output = $PAGE->get_renderer(mod_stratumtwo_exercise_round::TABLE); //TODO MODNAME

echo $output->header();

$renderable = new \mod_stratumtwo\output\exercise_page($exround, $exercise, $USER, $errorMsg);
echo $output->render($renderable);

echo $output->footer();
