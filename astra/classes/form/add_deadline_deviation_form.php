<?php

namespace mod_astra\form;

defined('MOODLE_INTERNAL') || die();
require_once("$CFG->libdir/formslib.php");

use get_string;

/**
 * Form for adding new deadline deviations.
 */
class add_deadline_deviation_form extends \moodleform {
    
    protected $courseid;
    
    public function __construct($courseid, $action = null) {
        $this->courseid = $courseid;
        parent::__construct($action);
    }
    
    public function definition() {
        $mod = \mod_astra_exercise_round::MODNAME;
        $mform = $this->_form;
        
        self::add_common_deviation_fields($mform, $this->courseid);
        
        // extra minutes
        $mform->addElement('text', 'extraminutes', get_string('extraminutes', $mod));
        $mform->setType('extraminutes', \PARAM_INT);
        $mform->addHelpButton('extraminutes', 'extraminutes', $mod);
        $mform->addRule('extraminutes', null, 'numeric', null, 'client');
        $mform->addRule('extraminutes', null, 'required', null, 'client');
        $mform->addRule('extraminutes', null, 'maxlength', 9, 'client');
        
        // without late penalty
        // 4th argument is the label displayed after checkbox, 5th arg: HTML attributes,
        // 6th: unchecked/checked values
        $mform->addElement('advcheckbox', 'withoutlatepenalty',
                get_string('withoutlatepenalty', $mod),
                '', null, array(0, 1));
        $mform->addHelpButton('withoutlatepenalty', 'withoutlatepenalty', $mod);
        $mform->setDefault('withoutlatepenalty', 1);
        
        $this->add_action_buttons(true);
    }
    
    // add fields that are used by both deadline and submission limit deviations
    public static function add_common_deviation_fields($mform, $courseid) {
        $mod = \mod_astra_exercise_round::MODNAME;
        
        // all exercises in the course
        $allExerciseRounds = \mod_astra_exercise_round::getExerciseRoundsInCourse($courseid);
        $exerciseOptions = array();
        foreach ($allExerciseRounds as $exround) {
            foreach ($exround->getExercises() as $ex) {
                $exerciseOptions[$ex->getId()] = $ex->getName();
            }
        }
        $exercise_select = $mform->addElement('select', 'exerciseid', get_string('exercisename', $mod),
                $exerciseOptions, array('class' => 'search-select'));
        $exercise_select->setMultiple(true);
        
        // students are entered in a text field or in the multiselect
        $mform->addElement('static', 'submitternote', get_string('note', $mod),
                get_string('adddeviationsubmitternote', $mod));
        $mform->addElement('text', 'submittertext', get_string('submitters', $mod));
        $mform->setType('submittertext', \PARAM_RAW);
        
        // all enrolled students in the course
        $enrolled_users = \get_enrolled_users(\context_course::instance($courseid), 'mod/astra:submit',
                0, 'u.*', 'idnumber, username');
        $submitterOptions = array();
        foreach ($enrolled_users as $user) {
            $submitterOptions[$user->id] = \mod_astra_deviation_rule::submitterName($user);
        }
        $submitter_select = $mform->addElement('select', 'submitter', get_string('submitter', $mod),
                $submitterOptions, array('class' => 'search-select'));
        $submitter_select->setMultiple(true);
    }
    
    /**
     * Parse a comma-separated textual list of student ids or usernames.
     * @param string $textInput
     * @return array of two arrays, the first is a list of Moodle user IDs and
     * the second is a list of input identifiers that could not be found in the database.
     */
    public static function parseSubmittersText($textInput) {
        global $DB;
        
        $submitterIds = array();
        $errors = array();
        
        foreach (\explode(',', $textInput) as $studentId) {
            $studentId = \trim($studentId);
            if (\strpos($studentId, '@') === false) { // student id, 123456
                $user = $DB->get_record('user', array('idnumber' => $studentId), 'id');
                // idnumber = student id should be unique in the database
            } else {
                // username@domain.com
                $user = $DB->get_record('user', array('username' => $studentId), 'id');
            }
            if ($user === false) {
                $errors[] = $studentId;
            } else {
                $submitterIds[] = $user->id;
            }
        }
        
        return array($submitterIds, $errors);
    }
    
    public static function common_validation($data, $files) {
        if (!empty($data['submittertext'])) {
            list($ids, $errors) = self::parseSubmittersText($data['submittertext']);
            if (!empty($errors)) {
                return array('submittertext' => get_string('idsnotfound', \mod_astra_exercise_round::MODNAME,
                        \implode(', ', $errors)));
            }
        }
        
        return array();
        // submitter and exerciseid should be unique together in the database,
        // but they can be checked and skipped when committing changes to the database
    }
    
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        
        $errors = \array_merge($errors, self::common_validation($data, $files));
        
        // extra minutes must be at least 1
        if ($data['extraminutes'] !== '' && $data['extraminutes'] < 1) {
            $errors['extraminutes'] = get_string('negativeerror', \mod_astra_exercise_round::MODNAME);
        }
        
        return $errors;
    }
}