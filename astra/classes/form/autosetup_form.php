<?php

namespace mod_astra\form;

defined('MOODLE_INTERNAL') || die();
require_once("$CFG->libdir/formslib.php");

/**
 * Form for input that is needed to automatically create and update
 * Astra exercises in a course.
 */
class autosetup_form extends \moodleform {

    private $default_values; // stdClass with keys apikey, configurl and sectionnum

    function __construct($default_val, $action=null) {
        $this->default_values = $default_val; // should be defined before calling definition()
        parent::__construct($action); // calls definition()
    }

    public function definition() {
        $mform = $this->_form;

        // exercise service URL for configuration JSON
        $mform->addElement('text', 'configurl', get_string('configurl', \mod_astra_exercise_round::MODNAME));
        $mform->setType('configurl', PARAM_NOTAGS);
        $mform->addHelpButton('configurl', 'configurl', \mod_astra_exercise_round::MODNAME);
        $mform->addRule('configurl', null, 'required', null, 'client');
        $mform->addRule('configurl', null, 'maxlength', 255, 'client');

        // server API key
        $mform->addElement('text', 'apikey', get_string('apikey', \mod_astra_exercise_round::MODNAME));
        $mform->setType('apikey', PARAM_NOTAGS);
        $mform->addHelpButton('apikey', 'apikey', \mod_astra_exercise_round::MODNAME);
        //$mform->addRule('apikey', null, 'required', null, 'client');
        $mform->addRule('apikey', null, 'maxlength', 100, 'client');

        // Moodle course section number (0-N), to which the new activities are added
        $mform->addElement('text', 'sectionnum',
                get_string('sectionnum', \mod_astra_exercise_round::MODNAME));
        $mform->setType('sectionnum', PARAM_INT);
        $mform->addHelpButton('sectionnum', 'sectionnum', \mod_astra_exercise_round::MODNAME);
        $mform->addRule('sectionnum', null, 'required', null, 'client');
        $mform->addRule('sectionnum', null, 'numeric', null, 'client');
        $mform->addRule('sectionnum', null, 'maxlength', 2, 'client');

        // set default values to form fields
        if (!is_null($this->default_values)) {
            $mform->setDefault('configurl', $this->default_values->configurl);
            $mform->setDefault('apikey', $this->default_values->apikey);
            $mform->setDefault('sectionnum', $this->default_values->sectionnum);
        }

        $this->add_action_buttons(true,
            get_string('apply', \mod_astra_exercise_round::MODNAME));
    }
}
