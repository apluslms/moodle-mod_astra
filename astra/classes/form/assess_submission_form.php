<?php

namespace mod_astra\form;

defined('MOODLE_INTERNAL') || die();
require_once("$CFG->libdir/formslib.php");

/**
 * Form for manually assessing a submission.
 */
class assess_submission_form extends \moodleform {
    
    public function definition() {
        $mform = $this->_form;
        
        // points
        $mform->addElement('text', 'grade', get_string('assesspoints', \mod_astra_exercise_round::MODNAME));
        $mform->setType('grade', PARAM_INT);
        $mform->addHelpButton('grade', 'assesspoints', \mod_astra_exercise_round::MODNAME);
        $mform->addRule('grade', null, 'numeric', null, 'client');
        $mform->addRule('grade', null, 'required', null, 'client');
        $mform->addRule('grade', null, 'maxlength', 7, 'client');
        
        // assistant feedback
        $mform->addElement('textarea', 'assistfeedback', get_string('assessastfeedback', \mod_astra_exercise_round::MODNAME),
                'rows="10" cols="40"');
        $mform->setType('assistfeedback', PARAM_RAW);
        $mform->addHelpButton('assistfeedback', 'assessastfeedback', \mod_astra_exercise_round::MODNAME);
        
        // feedback from the exercise service
        $mform->addElement('textarea', 'feedback', get_string('assessfeedback', \mod_astra_exercise_round::MODNAME),
                'rows="10" cols="40"');
        $mform->setType('feedback', PARAM_RAW);
        $mform->addHelpButton('feedback', 'assessfeedback', \mod_astra_exercise_round::MODNAME);
        
        $this->add_action_buttons(true);
    }
    
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        
        if ($data['grade'] !== '' && $data['grade'] < 0) {
            $errors['grade'] = \get_string('negativeerror', \mod_astra_exercise_round::MODNAME);
        }
        
        return $errors;
    }
}