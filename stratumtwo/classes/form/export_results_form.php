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
    
    public function __construct($courseid, $action = null) {
        $this->courseid = $courseid;
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
            foreach ($exround->getExercises() as $ex) {
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
        
        // include all submissions
        $mform->addElement('advcheckbox', 'inclallsubmissions',
                get_string('exportinclallsubmissions', $mod),
                '', null, array(0, 1));
        $mform->addHelpButton('inclallsubmissions', 'exportinclallsubmissions', $mod);
        $mform->setDefault('inclallsubmissions', 1);
        
        $this->add_action_buttons(true, get_string('exportresults', $mod));
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
}