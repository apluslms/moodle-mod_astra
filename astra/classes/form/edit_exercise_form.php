<?php

namespace mod_astra\form;

defined('MOODLE_INTERNAL') || die();
require_once("$CFG->libdir/formslib.php");

/**
 * Form for editing or creating a new exercise.
 */
class edit_exercise_form extends \mod_astra\form\edit_learning_object_form {

    public function definition() {
        $mform = $this->_form;
        
        parent::definition();
        
        // max points
        $mform->addElement('text', 'maxpoints', \get_string('maxpoints', \mod_astra_exercise_round::MODNAME));
        $mform->setType('maxpoints', PARAM_INT);
        $mform->addHelpButton('maxpoints', 'maxpoints', \mod_astra_exercise_round::MODNAME);
        $mform->addRule('maxpoints', null, 'numeric', null, 'client');
        $mform->addRule('maxpoints', null, 'required', null, 'client');
        $mform->addRule('maxpoints', null, 'maxlength', 7, 'client');
        
        // points to pass
        $mform->addElement('text', 'pointstopass', \get_string('pointstopass', \mod_astra_exercise_round::MODNAME));
        $mform->setType('pointstopass', PARAM_INT);
        $mform->addHelpButton('pointstopass', 'pointstopass', \mod_astra_exercise_round::MODNAME);
        $mform->addRule('pointstopass', null, 'numeric', null, 'client');
        $mform->addRule('pointstopass', null, 'required', null, 'client');
        $mform->addRule('pointstopass', null, 'maxlength', 7, 'client');
        $mform->setDefault('pointstopass', 0);
        
        // max submissions limit
        $mform->addElement('text', 'maxsubmissions', \get_string('maxsubmissions', \mod_astra_exercise_round::MODNAME));
        $mform->setType('maxsubmissions', PARAM_INT);
        $mform->addHelpButton('maxsubmissions', 'maxsubmissions', \mod_astra_exercise_round::MODNAME);
        $mform->addRule('maxsubmissions', null, 'numeric', null, 'client');
        $mform->addRule('maxsubmissions', null, 'required', null, 'client');
        $mform->addRule('maxsubmissions', null, 'maxlength', 4, 'client');
        $mform->setDefault('maxsubmissions', 10);
        
        // submission file size limit
        $mform->addElement('text', 'maxsbmssize', \get_string('sbmsfilemaxsize', \mod_astra_exercise_round::MODNAME));
        $mform->setType('maxsbmssize', PARAM_INT);
        $mform->addHelpButton('maxsbmssize', 'sbmsfilemaxsize', \mod_astra_exercise_round::MODNAME);
        $mform->addRule('maxsbmssize', null, 'numeric', null, 'client');
        $mform->addRule('maxsbmssize', null, 'required', null, 'client');
        $mform->addRule('maxsbmssize', null, 'maxlength', 9, 'client');
        $mform->setDefault('maxsbmssize', 1048576); // 1 MiB
        
        // Allow assistant viewing?
        $mform->addElement('advcheckbox', 'allowastviewing',
                \get_string('allowastviewing', \mod_astra_exercise_round::MODNAME),
                '', null, array(0, 1));
        $mform->addHelpButton('allowastviewing', 'allowastviewing', \mod_astra_exercise_round::MODNAME);
        
        // Allow assistant grading?
        // 4th argument is the label displayed after checkbox, 5th arg: HTML attributes,
        // 6th: unchecked/checked values
        $mform->addElement('advcheckbox', 'allowastgrading',
                \get_string('allowastgrading', \mod_astra_exercise_round::MODNAME),
                '', null, array(0, 1));
        $mform->addHelpButton('allowastgrading', 'allowastgrading', \mod_astra_exercise_round::MODNAME);
        
        $this->add_action_buttons(true);
    }
    
    function validation($data, $files) {
        $errors = parent::validation($data, $files);
        
        if ($data['pointstopass'] !== '' && $data['pointstopass'] < 0) {
            $errors['pointstopass'] = \get_string('negativeerror', \mod_astra_exercise_round::MODNAME);
        }
        if ($data['maxpoints'] !== '' && $data['maxpoints'] < 0) {
            $errors['maxpoints'] = \get_string('negativeerror', \mod_astra_exercise_round::MODNAME);
        }
        if ($data['maxsubmissions'] !== '' && $data['maxsubmissions'] < 0) {
            $errors['maxsubmissions'] = \get_string('negativeerror', \mod_astra_exercise_round::MODNAME);
        }
        
        return $errors;
    }
}