<?php

namespace mod_astra\form;

defined('MOODLE_INTERNAL') || die();
require_once("$CFG->libdir/formslib.php");

use get_string;

/**
 * Form for editing or creating a new exercise round.
 */
class edit_round_form extends \moodleform {

    protected $courseid;
    protected $editRoundId;
    protected $introItemId;
    
    /**
     * Constructor.
     * @param int $courseid Moodle course ID of the category
     * @param int $introItemId itemid for the intro HTML editor
     * @param int $editRoundId ID of the exercise round if editing, zero if creating new
     * @param string $action form action URL
     */
    public function __construct($courseid, $introItemId, $editRoundId = 0, $action = null) {
        $this->courseid = $courseid;
        $this->editRoundId = $editRoundId;
        $this->introItemId = $introItemId;
        parent::__construct($action); // calls definition()
    }
    
    /**
     * Add fields for editing an exercise round that should be listed before the introeditor.
     * This method can be used by mod_form and this class to reuse the same code.
     * @param $mform form instance
     */
    public static function add_fields_before_intro($mform) {
        global $CFG;
        
        $mod = \mod_astra_exercise_round::MODNAME; // for get_string()
        
        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('roundname', $mod),
                array('size' => '64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', \PARAM_TEXT);
        } else {
            $mform->setType('name', \PARAM_CLEAN);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'roundname', $mod);
    }
    
    /**
     * Add fields for editing an exercise round that should be listed after the introeditor.
     * This method can be used by mod_form and this class to reuse the same code.
     * @param $mform form instance
     */
    public static function add_fields_after_intro($mform) {
        $mod = \mod_astra_exercise_round::MODNAME; // for get_string()
        
        // exercise round status
        $mform->addElement('select', 'status', get_string('status', $mod), array(
                \mod_astra_exercise_round::STATUS_READY => get_string('statusready', $mod),
                \mod_astra_exercise_round::STATUS_HIDDEN => get_string('statushidden', $mod),
                \mod_astra_exercise_round::STATUS_MAINTENANCE => get_string('statusmaintenance', $mod),
                \mod_astra_exercise_round::STATUS_UNLISTED => get_string('statusunlisted', $mod),
        ));
        
        // order amongst rounds
        $mform->addElement('text', 'ordernum', get_string('ordernum', $mod));
        $mform->setType('ordernum', \PARAM_INT);
        $mform->addHelpButton('ordernum', 'ordernum', $mod);
        $mform->addRule('ordernum', null, 'required', null, 'client');
        $mform->addRule('ordernum', null, 'maxlength', 4, 'client');
        $mform->addRule('ordernum', null, 'numeric', null, 'client');
        
        // remote key (URL component in A+)
        $mform->addElement('text', 'remotekey', get_string('remotekey', $mod));
        $mform->setType('remotekey', \PARAM_NOTAGS);
        $mform->addHelpButton('remotekey', 'remotekey', $mod);
        $mform->addRule('remotekey', null, 'required', null, 'client');
        $mform->addRule('remotekey', null, 'maxlength', 128, 'client');
        
        // points to pass
        $mform->addElement('text', 'pointstopass', get_string('pointstopass', $mod));
        $mform->setType('pointstopass', \PARAM_INT);
        $mform->addHelpButton('pointstopass', 'pointstopass', $mod);
        $mform->addRule('pointstopass', null, 'numeric', null, 'client');
        $mform->addRule('pointstopass', null, 'maxlength', 7, 'client');
        $mform->addRule('pointstopass', null, 'required', null, 'client');
        $mform->setDefault('pointstopass', 0);
        
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
        $mform->setDefault('openingtime', \time());
        
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
        $mform->setDefault('closingtime', \time());
        
        
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
        $mform->setType('latesbmspenalty', \PARAM_FLOAT); // requires dot as decimal separator
        $mform->addHelpButton('latesbmspenalty', 'latesbmspenalty', $mod);
        //$mform->addRule('latesbmspenalty', null, 'required', null, 'client');
        $mform->addRule('latesbmspenalty', null, 'numeric', null, 'client');
        $mform->addRule('latesbmspenalty', null, 'maxlength', 5, 'client');
    }
    
    public function definition() {
        global $CFG;
        
        $mform = $this->_form;
        $mod = \mod_astra_exercise_round::MODNAME; // for get_string()
        // All the addRule validation rules must match the limits in the DB schema !!!
        // (table astra in the file astra/db/install.xml)
        
        self::add_fields_before_intro($mform);
        
        // Adding the "intro" and "introformat" fields (HTML editor)
        if ($this->editRoundId) {
            list($course, $cm) = \get_course_and_cm_from_instance($this->editRoundId, \mod_astra_exercise_round::TABLE);
            $context = \context_module::instance($cm->id);
        } else {
            $context = \context_course::instance($this->courseid);
        }
        $mform->addElement('editor', 'introeditor', \get_string('moduleintro'),
                array('rows' => 10),
                array(
                    'maxfiles' => 0,
                    'maxbytes' => 0,
                    'noclean' => true,
                    'context' => $context,
                    'subdirs' => false,
                    'enable_filemanagement' => false,
                    'changeformat' => 0,
                    'trusttext' => 1,
                ));
        $mform->setType('introeditor', \PARAM_RAW);
        $mform->addElement('hidden', 'introeditor[itemid]', $this->introItemId);
        
        self::add_fields_after_intro($mform);
        
        // course section number, required if creating a new round, ignored if editing
        if ($this->editRoundId == 0) {
            $mform->addElement('text', 'sectionnumber', get_string('sectionnum', $mod));
            $mform->setType('sectionnumber', \PARAM_INT);
            $mform->addHelpButton('sectionnumber', 'sectionnum', $mod);
            $mform->addRule('sectionnumber', null, 'numeric', null, 'client');
            $mform->addRule('sectionnumber', null, 'maxlength', 2, 'client');
            $mform->addRule('sectionnumber', null, 'required', null, 'client');
        }
        
        // Add standard buttons, common to all modules.
        $this->add_action_buttons();
    }
    
    /**
     * Validate form fields that are added in the other reusable static methods.
     * This method can be used by mod_form and this class to reuse the same code.
     * @param array $data user input in the form
     * @param $files files in the form
     * @param int $courseid Moodle course ID of the exercise round
     * @param int $editRoundId ID of the exercise round, or zero if creating a new round
     * @return errors array indexed by form field names
     */
    public static function common_validation($data, $files, $courseid, $editRoundId = 0) {
        $mod = \mod_astra_exercise_round::MODNAME; // for get_string()
        $errors = array();
        
        // if point values are given, they cannot be negative
        if ($data['pointstopass'] !== '' && $data['pointstopass'] < 0) {
            $errors['pointstopass'] = get_string('negativeerror', $mod);
        }
        
        // closing time must be later than opening time
        if ($data['closingtime'] !== '' && $data['openingtime'] !== '' &&
                $data['closingtime'] < $data['openingtime']) {
            $errors['closingtime'] = get_string('closingbeforeopeningerror', $mod);
        }
        
        if ($data['latesbmsallowed'] == 1) {
            if (empty($data['latesbmsdl'])) {
                $errors['latesbmsdl'] = get_string('mustbesetwithlate', $mod);
            } else {
                // late submission deadline must be later than the closing time
                if ($data['closingtime'] !== '' &&
                        $data['latesbmsdl'] <= $data['closingtime']) {
                    $errors['latesbmsdl'] = get_string('latedlbeforeclosingerror', $mod);
                }
            }

            if (!isset($data['latesbmspenalty']) || $data['latesbmspenalty'] === '') {
                $errors['latesbmspenalty'] = get_string('mustbesetwithlate', $mod);
            } else {
                // late submission penalty must be between 0 and 1
                if ($data['latesbmspenalty'] < 0 || $data['latesbmspenalty'] > 1) {
                    $errors['latesbmspenalty'] = get_string('zerooneerror', $mod);
                }
            }
        }

        // check that remote keys of exercise rounds are unique within a course
        foreach (\mod_astra_exercise_round::getExerciseRoundsInCourse($courseid) as $exround) {
            if ($editRoundId != $exround->getId() && $data['remotekey'] == $exround->getRemoteKey()) {
                $errors['remotekey'] = get_string('duplicateroundremotekey', $mod);
            }
        }
        
        return $errors;
    }
    
    public function validation($data, $files) {
        $mod = \mod_astra_exercise_round::MODNAME; // for get_string()
        $errors = parent::validation($data, $files);
    
        $errors = \array_merge($errors, self::common_validation($data, $files, $this->courseid, $this->editRoundId));
        
        // require section number if creating a new round, must be non-negative
        if ($this->editRoundId == 0 && ($data['sectionnumber'] === '' || $data['sectionnumber'] < 0)) {
            $errors['sectionnumber'] = get_string('negativeerror', $mod);
        }
        return $errors;
    }
}