<?php

namespace mod_astra\form;

defined('MOODLE_INTERNAL') || die();
require_once("$CFG->libdir/formslib.php");

/**
 * Form for filtering the submission list of an exercise.
 * 
 * When calling the constructor, the custom data argument may include
 * the key 'sort' whose value is an array.
 * This is used to remember the columns that were sorted before the form was submitted.
 * The nested array has keys and values in the format of the sort URL parameters
 * of the submission list page, e.g., array('sort_idnumber' = '0_1').
 * 
 * $form = new filter_submissions_form($action, array('sort_submissiontime', '0_0'));
 */
class filter_submissions_form extends \moodleform {
    
    public function definition() {
        $mod = \mod_astra_exercise_round::MODNAME;
        $mform = $this->_form;
        
        // idnumber (student ID)
        $mform->addElement('text', 'idnumber', get_string('idnumber', $mod));
        $mform->setType('idnumber', PARAM_RAW);
        $mform->addHelpButton('idnumber', 'idnumber', $mod);
        
        // last name
        $mform->addElement('text', 'lastname', get_string('lastname', $mod));
        $mform->setType('lastname', PARAM_RAW);
        
        // first name
        $mform->addElement('text', 'firstname', get_string('firstname', $mod));
        $mform->setType('firstname', PARAM_RAW);
        
        // submission status
        $statusselect = $mform->addElement('select', 'status', get_string('status', $mod),
                self::statusValues());
        $statusselect->setSelected('-1');
        
        // submission time (start and/or end of the interval to search for)
        // the field names submissiontimeafter and submissiontimebefore are slightly different
        // than the corresponding names used elsewhere because the form POSTs the date input
        // in an array format that would mess up the parsing of the integer timestamp
        // format used otherwise when parsing URL parameters in the submission list page
        $mform->addElement('date_time_selector', 'submissiontimeafter', get_string('submittedafter', $mod), array(
                'optional' => true, // user may disable the date selector (no input)
        ));
        $mform->addElement('date_time_selector', 'submissiontimebefore', get_string('submittedbefore', $mod), array(
                'optional' => true, // user may disable the date selector (no input)
        ));
        
        // grade, lower and upper bound to search for
        $mform->addElement('text', 'gradegreater', get_string('gradegreq', $mod));
        $mform->setType('gradegreater', PARAM_INT);
        $mform->addRule('gradegreater', null, 'numeric', null, 'client');
        
        $mform->addElement('text', 'gradeless', get_string('gradeless', $mod));
        $mform->setType('gradeless', PARAM_INT);
        $mform->addRule('gradeless', null, 'numeric', null, 'client');
        
        // has the submission been given any assistant feedback?
        $assistfeedbackselect = $mform->addElement('select', 'hasassistfeedback', get_string('hasassistfeedback', $mod), array(
                'all' => get_string('allsubmissions', $mod),
                'yes' => get_string('yesassistfeedback', $mod),
                'no' => get_string('noassistfeedback', $mod),
        ));
        $assistfeedbackselect->setSelected('all');
        
        // page size for pagination if there are many results
        $pagesizeselect = $mform->addElement('select', 'pagesize', get_string('resultsperpage', $mod),
                array(
                        '20' => '20',
                        '50' => '50',
                        '100' => '100',
                        '200' => '200',
                ));
        $pagesizeselect->setSelected('100');
        
        // hidden fields for sorting columns so that the currently sorted column
        // is still selected after submitting the form
        $sort = isset($this->_customdata['sort']) ? $this->_customdata['sort'] : array();
        foreach ($sort as $field => $val) {
            $mform->addElement('hidden', $field, $val);
            $mform->setType($field, PARAM_ALPHANUMEXT);
        }
        
        $this->add_action_buttons(false, get_string('search', $mod));
        $mform->disable_form_change_checker();
    }
    
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        
        if (isset($data['gradegreater']) && $data['gradegreater'] != '' &&
                isset($data['gradeless']) && $data['gradeless'] != '' &&
                $data['gradegreater'] > $data['gradeless']) {
            // the lower bound must be less than or equal to the upper bound
            $errors['gradegreater'] = get_string('lowermustbeless', \mod_astra_exercise_round::MODNAME);
        }
        
        if (isset($data['submissiontimeafter']) && $data['submissiontimeafter'] != 0 &&
                isset($data['submissiontimebefore']) && $data['submissiontimebefore'] != 0 &&
                $data['submissiontimeafter'] > $data['submissiontimebefore']) {
            $errors['submissiontimeafter'] = get_string('lowermustbeless', \mod_astra_exercise_round::MODNAME);
        }
        
        return $errors;
    }
    
    public static function statusValues() {
        $mod = \mod_astra_exercise_round::MODNAME;
        return array(
                '-1' => get_string('anystatus', $mod), // any status
                '-2' => get_string('anystatusnoterror', $mod), // any status except error
                \mod_astra_submission::STATUS_READY => get_string('statusready', $mod),
                \mod_astra_submission::STATUS_INITIALIZED => get_string('statusinitialized', $mod),
                \mod_astra_submission::STATUS_WAITING => get_string('statuswaiting', $mod),
                \mod_astra_submission::STATUS_REJECTED => get_string('statusrejected', $mod),
                \mod_astra_submission::STATUS_ERROR => get_string('statuserror', $mod),
        );
    }
}