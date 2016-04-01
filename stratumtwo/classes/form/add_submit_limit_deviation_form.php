<?php

namespace mod_stratumtwo\form;

defined('MOODLE_INTERNAL') || die();
require_once("$CFG->libdir/formslib.php");

use get_string;

/**
 * Form for adding new submission limit deviations.
 */
class add_submit_limit_deviation_form extends \moodleform {
    
    protected $courseid;
    
    public function __construct($courseid, $action = null) {
        $this->courseid = $courseid;
        parent::__construct($action);
    }
    
    public function definition() {
        $mod = \mod_stratumtwo_exercise_round::MODNAME;
        $mform = $this->_form;
        
        \mod_stratumtwo\form\add_deadline_deviation_form::add_common_deviation_fields($mform, $this->courseid);
        
        // extra submissions
        $mform->addElement('text', 'extrasubmissions', get_string('extrasubmissions', $mod));
        $mform->setType('extrasubmissions', \PARAM_INT);
        $mform->addHelpButton('extrasubmissions', 'extrasubmissions', $mod);
        $mform->addRule('extrasubmissions', null, 'numeric', null, 'client');
        $mform->addRule('extrasubmissions', null, 'required', null, 'client');
        $mform->addRule('extrasubmissions', null, 'maxlength', 9, 'client');
        
        $this->add_action_buttons(true);
    }
    
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        
        $errors = \array_merge($errors,
                \mod_stratumtwo\form\add_deadline_deviation_form::common_validation($data, $files));
        
        // extra submissions must be at least 1
        if ($data['extrasubmissions'] !== '' && $data['extrasubmissions'] < 1) {
            $errors['extrasubmissions'] = get_string('negativeerror', \mod_stratumtwo_exercise_round::MODNAME);
        }
        
        return $errors;
    }
}
