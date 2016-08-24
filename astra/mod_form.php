<?php

/**
 * The main astra configuration form for Moodle.
 * This form exists because Moodle requires it from any activity module.
 * In the case of Astra plugin, the user should not use this form but
 * instead the edit course functionality offered by the Astra block plugin.
 *
 * It uses the standard core Moodle formslib. For more info about them, please
 * visit: http://docs.moodle.org/en/Development:lib/formslib.php
 *
 * @package    mod_astra
 * @copyright  2016 Aalto SCI CS dept.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');

/**
 * Module instance settings form
 *
 * @package    mod_astra
 */
class mod_astra_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG;
        
        $mform = $this->_form;
        $mod = mod_astra_exercise_round::MODNAME; // for get_string()
        // All the addRule validation rules must match the limits in the DB schema !!!
        // (table astra in the file astra/db/install.xml)

        // Adding the "general" fieldset, where all the common settings are showed.
        $mform->addElement('header', 'general', get_string('general', 'form'));
        
        // warning to the user that this form should not be used
        $mform->addElement('static', 'astradonotuse',
                get_string('note', $mod),
                get_string('donotusemodform', $mod));

        \mod_astra\form\edit_round_form::add_fields_before_intro($mform);

        // Adding the standard "intro" and "introformat" fields.
        $this->standard_intro_elements();

        \mod_astra\form\edit_round_form::add_fields_after_intro($mform);
        
        // Add standard elements, common to all modules.
        $this->standard_coursemodule_elements();

        // Add standard buttons, common to all modules.
        $this->add_action_buttons();
    }

    function validation($data, $files) {
        global $COURSE;
        
        $errors = parent::validation($data, $files);

        $editRoundId = 0;
        if (!empty($this->_instance)) {
            $editRoundId = $this->_instance;
        }
        
        $errors = \array_merge($errors,
            \mod_astra\form\edit_round_form::common_validation($data, $files, $COURSE->id, $editRoundId));
        
        return $errors;
    }
}