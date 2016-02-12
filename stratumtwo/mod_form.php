<?php

/**
 * The main stratumtwo configuration form for Moodle.
 * This form exists because Moodle requires it from any activity module.
 * In the case of Stratum2 plugin, the user should not use this form but
 * instead the edit course functionality offered by the Stratum2 block plugin.
 *
 * It uses the standard core Moodle formslib. For more info about them, please
 * visit: http://docs.moodle.org/en/Development:lib/formslib.php
 *
 * @package    mod_stratumtwo
 * @copyright  2016 Aalto SCI CS dept.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');

/**
 * Module instance settings form
 *
 * @package    mod_stratumtwo
 */
class mod_stratumtwo_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {
        $mform = $this->_form;
        $mod = mod_stratumtwo_exercise_round::MODNAME; // for get_string()
        // All the addRule validation rules must match the limits in the DB schema !!!
        // (table stratumtwo in the file stratumtwo/db/install.xml)

        // Adding the "general" fieldset, where all the common settings are showed.
        $mform->addElement('header', 'general', get_string('general', 'form'));
        
        // warning to the user that this form should not be used
        $mform->addElement('static', 'stratumdonotuse',
                get_string('note', $mod),
                get_string('donotusemodform', $mod));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('roundname', $mod),
            array('size' => '64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEAN);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'roundname', $mod);

        // Adding the standard "intro" and "introformat" fields.
        $this->standard_intro_elements();

        // exercise round status
        $mform->addElement('select', 'status', get_string('status', $mod), array(
                mod_stratumtwo_exercise_round::STATUS_READY => get_string('statusready', $mod),
                mod_stratumtwo_exercise_round::STATUS_HIDDEN => get_string('statushidden', $mod),
                mod_stratumtwo_exercise_round::STATUS_MAINTENANCE => get_string('statusmaintenance', $mod),
        ));
        
        // remote key (URL component in A+)
        $mform->addElement('text', 'remotekey', get_string('remotekey', $mod));
        $mform->setType('remotekey', PARAM_NOTAGS);
        $mform->addHelpButton('remotekey', 'remotekey', $mod);
        $mform->addRule('remotekey', null, 'required', null, 'client');
        $mform->addRule('remotekey', null, 'maxlength', 128, 'client');
        
        // points to pass
        $mform->addElement('text', 'pointstopass', get_string('pointstopass', $mod));
        $mform->setType('pointstopass', PARAM_INT);
        $mform->addHelpButton('pointstopass', 'pointstopass', $mod);
        $mform->addRule('pointstopass', null, 'numeric', null, 'client');
        $mform->addRule('pointstopass', null, 'maxlength', 7, 'client');
        
        // opening time
        $mform->addElement('date_time_selector', 'openingtime',
                get_string('openingtime', $mod),
                array(
                        'step' => 1, // minutes step in the drop-down menu
                        'optional' => false, // do not allow disabling the date
                ));
        $mform->addHelpButton('openingtime', 'openingtime', $mod);
        $mform->addRule('openingtime', null, 'required', null, 'client');
        //$mform->setDefault('openingtime', array()); // disable by default -> not set
        $mform->setDefault('openingtime', time());
        
        // closing time
        $mform->addElement('date_time_selector', 'closingtime',
                get_string('closingtime', $mod),
                array(
                        'step' => 1, // minutes step in the drop-down menu
                        'optional' => false, // do not allow disabling the date
                ));
        $mform->addHelpButton('closingtime', 'closingtime', $mod);
        $mform->addRule('closingtime', null, 'required', null, 'client');
        //$mform->setDefault('closingtime', array()); // disable by default -> not set
        $mform->setDefault('closingtime', time());
        
        
        // Allow late submissions after the closing time?
        // 4th argument is the label displayed after checkbox, 5th arg: HTML attributes,
        // 6th: unchecked/checked values
        $mform->addElement('advcheckbox', 'latesbmsallowed',
                get_string('latesbmsallowed', $mod),
                '', null, array(0, 1));
        $mform->addHelpButton('latesbmsallowed', 'latesbmsallowed', $mod);
        
        // late submission deadline
        $mform->addElement('date_time_selector', 'latesbmsdl',
                get_string('latesbmsdl', $mod),
                array(
                        'step' => 1, // minutes step in the drop-down menu
                        'optional' => false, // cannot be disabled
                ));
        $mform->addHelpButton('latesbmsdl', 'latesbmsdl', $mod);
        $mform->setDefault('latesbmsdl', array()); // disabled by default -> not set
        
        // late submission penalty
        $mform->addElement('text', 'latesbmspenalty', get_string('latesbmspenalty', $mod));
        $mform->setType('latesbmspenalty', PARAM_FLOAT); // requires dot as decimal separator
        $mform->addHelpButton('latesbmspenalty', 'latesbmspenalty', $mod);
        //$mform->addRule('latesbmspenalty', null, 'required', null, 'client');
        $mform->addRule('latesbmspenalty', null, 'numeric', null, 'client');
        $mform->addRule('latesbmspenalty', null, 'maxlength', 5, 'client');
        
        // Add standard elements, common to all modules.
        $this->standard_coursemodule_elements();

        // Add standard buttons, common to all modules.
        $this->add_action_buttons();
    }

    function validation($data, $files) {
        $mod = mod_stratumtwo_exercise_round::MODNAME; // for get_string()
        $errors = parent::validation($data, $files);

        // if point values are given, they cannot be negative
        if ($data['pointstopass'] !== '' && $data['pointstopass'] < 0) {
            $errors['pointstopass'] = get_string('negativeerror', $mod);
        }
        
        // closing time must be later than opening time
        if ($data['closingtime'] !== '' && $data['openingtime'] !== '' &&
                $data['closingtime'] < $data['openingtime']) {
            $errors['closingtime'] = get_string('closingbeforeopeningerror', $mod);
        }
        
        if ($data['latesbmsallowed'] == 1 ) {
            if (empty($data['latesbmsdl'])) {
                $errors['latesbmsdl'] = get_string('mustbesetwithlate', $mod);
            } else {
                // late submission deadline must be later than the closing time
                if ($data['closingtime'] !== '' &&
                        $data['latesbmsdl'] <= $data['closingtime']) {
                    $errors['latesbmsdl'] = get_string('latedlbeforeclosingerror', $mod);
                }
            }
            
            if (empty($data['latesbmspenalty'])) {
                $errors['latesbmspenalty'] = get_string('mustbesetwithlate', $mod);
            } else {
                // late submission penalty must be between 0 and 1
                if ($data['latesbmspenalty'] < 0 || $data['latesbmspenalty'] > 1) {
                    $errors['latesbmspenalty'] = get_string('zerooneerror', $mod);
                }
            }
        }
        return $errors;
    }
}