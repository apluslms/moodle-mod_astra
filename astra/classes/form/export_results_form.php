<?php

namespace mod_stratumtwo\form;

defined('MOODLE_INTERNAL') || die();
require_once("$CFG->libdir/formslib.php");

use get_string;

/**
 * Form for choosing what to include in the course results JSON export.
 */
class export_results_form extends \moodleform {
    
    protected $courseid;
    protected $submitButtonLabel;
    
    public function __construct($courseid, $submitButtonLabel, $action = null) {
        $this->courseid = $courseid;
        $this->submitButtonLabel = $submitButtonLabel; // string key for the submit button
        parent::__construct($action);
    }
    
    public function definition() {
        $mform = $this->_form;
        $mod = \mod_stratumtwo_exercise_round::MODNAME; // for get_string()
        
        // Include all exercises checkbox
        $mform->addElement('advcheckbox', 'inclallexercises',
                get_string('exportinclallexercises', $mod),
                '', null, array(0, 1));
        $mform->addHelpButton('inclallexercises', 'exportinclallexercises', $mod);
        $mform->setDefault('inclallexercises', 1);
        
        // all exercises in the course
        $allExerciseRounds = \mod_stratumtwo_exercise_round::getExerciseRoundsInCourse($this->courseid, true);
        $exerciseOptions = array();
        $roundOptions = array();
        foreach ($allExerciseRounds as $exround) {
            foreach ($exround->getExercises(true) as $ex) {
                $exerciseOptions[$ex->getId()] = $ex->getName();
            }
            $roundOptions[$exround->getId()] = $exround->getName();
        }
        // all categories
        $allCategories = \mod_stratumtwo_category::getCategoriesInCourse($this->courseid, true);
        $categoryOptions = array();
        foreach ($allCategories as $cat) {
            $categoryOptions[$cat->getId()] = $cat->getName();
        }
        
        // select exercises
        $exercise_select = $mform->addElement('select', 'selectexercises',
                get_string('exportselectexercises', $mod), $exerciseOptions);
        $exercise_select->setMultiple(true);
        
        // select categories (include all exercises in those categories)
        $cat_select = $mform->addElement('select', 'selectcategories',
                get_string('exportselectcats', $mod), $categoryOptions);
        $cat_select->setMultiple(true);
        
        // select exercise rounds (include all exercises in those rounds)
        $round_select = $mform->addElement('select', 'selectrounds',
                get_string('exportselectrounds', $mod), $roundOptions);
        $round_select->setMultiple(true);
        
        // all students in the course
        $studentOptions = array();
        $enrolled_users = \get_enrolled_users(\context_course::instance($this->courseid), 'mod/stratumtwo:submit',
                0, 'u.*', 'idnumber, username');
        foreach ($enrolled_users as $user) {
            $studentOptions[$user->id] = \mod_stratumtwo_deviation_rule::submitterName($user);
        }
        
        // select students (if none selected, all students are included)
        $student_select = $mform->addElement('select', 'selectstudents',
                get_string('exportselectstudents', $mod), $studentOptions);
        $student_select->setMultiple(true);
        $mform->addHelpButton('selectstudents', 'exportselectstudents', $mod);
        
        // include only submissions submitted before this time
        $mform->addElement('date_time_selector', 'submittedbefore',
                get_string('exportsubmittedbefore', $mod),
                array(
                        'step' => 1, // minutes step in the drop-down menu
                        'optional' => true, // allow disabling the date
                ));
        $mform->addHelpButton('submittedbefore', 'exportsubmittedbefore', $mod);
        $mform->setDefault('submittedbefore', array()); // disable by default -> not set
        
        // select which submissions are included in the regrading
        $submissionOptions = array(
                \mod_stratumtwo\export\all_students_course_summary::SUBMISSIONS_BEST =>
                    get_string('massregrsbmsbest', $mod),
                \mod_stratumtwo\export\all_students_course_summary::SUBMISSIONS_LATEST =>
                    get_string('massregrsbmslatest', $mod),
                \mod_stratumtwo\export\all_students_course_summary::SUBMISSIONS_ALL =>
                    get_string('massregrsbmsall', $mod),
                \mod_stratumtwo\export\all_students_course_summary::SUBMISSIONS_ONLY_ERROR =>
                    get_string('massregrsbmserror', $mod),
        );
        $mform->addElement('select', 'selectsubmissions',
                get_string('massregrinclsbms', $mod), $submissionOptions);
        $mform->addHelpButton('selectsubmissions', 'massregrinclsbms', $mod);
        
        $this->add_action_buttons(true, get_string($this->submitButtonLabel, $mod));
    }
    
    public function validation($data, $files) {
        $mod = \mod_stratumtwo_exercise_round::MODNAME; // for get_string()
        $errors = parent::validation($data, $files);
        
        $exerciseSelectMethods = 0;
        if ($data['inclallexercises'] == 1) {
            $exerciseSelectMethods++;
        }
        if (!empty($data['selectexercises'])) {
            $exerciseSelectMethods++;
        }
        if (!empty($data['selectcategories'])) {
            $exerciseSelectMethods++;
        }
        if (!empty($data['selectrounds'])) {
            $exerciseSelectMethods++;
        }
        if ($exerciseSelectMethods == 0) {
            // exercises must be defined
            $errors['inclallexercises'] = get_string('exportmustdefineexercises', $mod);
        } else if ($exerciseSelectMethods > 1) {
            // only one method should be used to select exercises
            $errors['inclallexercises'] = get_string('exportuseonemethodtoselectexercises', $mod);
        }
        
        return $errors;
    }
    
    /**
     * Parse exercises from the form input.
     * @param \stdClass $fromform submitted form data
     * @return NULL|array array of exercise learning object IDs, or null if all exercises should be included
     */
    public static function parse_exercises(\stdClass $fromform) {
        global $DB;
    
        if (isset($fromform->inclallexercises) && $fromform->inclallexercises) {
            $exerciseIds = null; // all exercises
        } else if (!empty($fromform->selectexercises)) {
            // only these exercises
            $exerciseIds = $fromform->selectexercises;
        } else if (!empty($fromform->selectcategories)) {
            // all exercises in these categories
            $exerciseIds = \array_keys(
                    $DB->get_records_list(\mod_stratumtwo_learning_object::TABLE, 'categoryid', $fromform->selectcategories, '', 'id'));
        } else { // (!empty($fromform['selectrounds']))
            // all exercises in these rounds
            $exerciseIds = \array_keys(
                    $DB->get_records_list(\mod_stratumtwo_learning_object::TABLE, 'roundid', $fromform->selectrounds, '', 'id'));
        }
        return $exerciseIds;
    }
}