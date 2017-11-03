<?php

namespace mod_astra\form;

defined('MOODLE_INTERNAL') || die();
require_once("$CFG->libdir/formslib.php");

/**
 * Form for filtering the course participants list.
 * 
 * When calling the constructor, the custom data argument should include the course id:
 * $form = new filter_participants_form($action, array('courseid', $courseid));
 * The custom data may also include the key 'sort' whose value is an array.
 * This is used to remember the columns that were sorted before the form was submitted.
 * The nested array has keys and values in the format of the sort URL parameters
 * of the participants page, e.g., array('sort_idnumber' = '0_1').
 */
class filter_participants_form extends \moodleform {
    
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
        
        // email
        $mform->addElement('text', 'email', get_string('email', $mod));
        $mform->setType('email', PARAM_EMAIL);
        
        // user role (show participants with the role)
        $context = \context_course::instance($this->_customdata['courseid']);
        $rolenames = role_fix_names(get_profile_roles($context), $context, ROLENAME_ALIAS, true);
        // guess the student role if the user does not choose anything else
        $multiroles = array();
        $multiroles['0'] = get_string('selectuserrole', $mod);
        $multiroles['-1'] = get_string('allparticipants');
        $rolenames = $multiroles + $rolenames;
        
        $roleselect = $mform->addElement('select', 'roleid', get_string('currentrole', 'role'), $rolenames);
        $roleselect->setSelected('0');
        
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
}