<?php

namespace mod_astra\form;

defined('MOODLE_INTERNAL') || die();
require_once("$CFG->libdir/formslib.php");

/**
 * Form for updating or creating a submission limit deviation.
 * The user and exercise related to the deviation are assumed to be known from
 * elsewhere, for example, the action URL parameters.
 * 
 * You must supply custom data when constructing the form in the following way:
 * new \mod_astra\form\upsert_submit_limit_deviation_form($action, array(
 *     'exercise' => $exercise, // mod_astra_exercise instance
 *     'userfullname' => 'John Smith (123456)', // string, the student's name
 * ));
 * The custom data is used to display information in the rendered form.
 */
class upsert_submit_limit_deviation_form extends \moodleform {

    public function definition() {
        $mod = \mod_astra_exercise_round::MODNAME;
        $mform = $this->_form;

        $mform->addElement('static', 'infoextrasbms', get_string('note', $mod),
                get_string('addextrasbmsnote', $mod, (object) array(
                        'userfullname' => $this->_customdata['userfullname'],
                        'exercise' => $this->_customdata['exercise']->getName(),
                        'normallimit' => $this->_customdata['exercise']->getMaxSubmissions(),
        )));

        $options = array();
        for ($i = 1; $i <= 10; ++$i) {
            $options[$i] = $i;
        }
        $mform->addElement('select', 'extrasubmissions', get_string('extrasubmissions', $mod), $options);
        $mform->addRule('extrasubmissions', null, 'required', null, 'client');
        $mform->setDefault('extrasubmissions', '1');

        $this->add_action_buttons(true);
    }
}
