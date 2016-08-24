<?php

namespace mod_astra\event;
defined('MOODLE_INTERNAL') || die();

/* Event class that represents an error in the connection to
 * the external exercise service server. (For example, when curl cannot 
 * connect to the server.)
 */
/*
An event is created like this:
$event = \mod_astra\event\service_connection_failed::create(array(
    'context' => context_module::instance($cm->id),
    'relateduserid' => $user->id, // optional user that is related to the action,
    // may be different than the user taking the action
    'other' => array(
        'error' => curl_error($ch),
        'url' => 'https://tried.to.connect.here.com',
        'objtable' => 'astra_lobjects', // or 'astra_submissions', used if relevant
        'objid' => 1, // id of the module instance (DB row), zero means none
    )
));
$event->trigger();
*/
class service_connection_failed extends \core\event\base {

    protected function init() {
        $this->data['crud'] = 'r'; // c(reate), r(ead), u(pdate), d(elete)
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        // do not set objecttable here so that we can use the event for many types
        // (exercise rounds, exercises,...)
    }

    /* Return localised name of the event, it is the same for all instances.
     */
    public static function get_name() {
        return get_string('eventserviceconnectionfailed', \mod_astra_exercise_round::MODNAME);
    }

    /* Returns non-localised description of one particular event.
     */
    public function get_description() {
        $url = isset($this->other['url']) ? $this->other['url'] : '';
        $error = isset($this->other['error']) ? $this->other['error'] : '';
        return 'Error in connecting to URL "'. $url .'" ('. $error .').';
    }

    /* Returns Moodle URL where the event can be observed afterwards.
     * Can be null, if no valid location is present.
     */
    public function get_url() {
        if (!isset($this->other['objtable']) || !isset($this->other['objid']) ||
                $this->other['objid'] == 0) {
            return null;
        }
        if ($this->other['objtable'] == \mod_astra_learning_object::TABLE) {
            return new \moodle_url('/mod/'. \mod_astra_exercise_round::TABLE .'/exercise.php',
                    array('id' => $this->other['objid'])); // astra learning object ID
        }
        if ($this->other['objtable'] == \mod_astra_submission::TABLE) {
            return new \moodle_url('/mod/'. \mod_astra_exercise_round::TABLE .'/submission.php',
                    array('id' => $this->other['objid'])); // astra submission ID
        }
        return null;
    }
    
    public static function get_other_mapping() {
        // can not map objid in other data for backup/restore since this method is static
        // and the objtable varies
        return false;
    }
}
