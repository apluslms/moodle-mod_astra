<?php

namespace mod_stratumtwo\event;
defined('MOODLE_INTERNAL') || die();

/* Event class that represents an error in the connection to
 * the external Stratum server. (For example, when curl cannot 
 * connect to the server.)
 */
/*
An event is created like this:
$event = \mod_stratumtwo\event\stratum_connection_failed::create(array(
    'context' => context_module::instance($cm->id),
    'relateduserid' => $user->id, // optional user that is related to the action,
    // may be different than the user taking the action
    'other' => array(
        'error' => curl_error($ch),
        'url' => 'https://tried.to.connect.here.com',
        'objtable' => 'stratumtwo_exercises', // or 'stratumtwo_submissions', used if relevant
        'objid' => 1, // id of the module instance (DB row), zero means none
    )
));
$event->trigger();
*/
class stratum_connection_failed extends \core\event\base {

    protected function init() {
        $this->data['crud'] = 'r'; // c(reate), r(ead), u(pdate), d(elete)
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        // do not set objecttable here so that we can use the event for many types
        // (exercise rounds, exercises,...)
    }

    /* Return localised name of the event, it is the same for all instances.
     */
    public static function get_name() {
        return get_string('eventstratumconnectionfailed', \mod_stratumtwo_exercise_round::MODNAME);
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
        if ($this->other['objtable'] == \mod_stratumtwo_exercise::TABLE) {
            return new \moodle_url('/mod/'. \mod_stratumtwo_exercise_round::TABLE .'/exercise.php',
                    array('id' => $this->other['objid'])); // stratum2 exercise ID
        }
        if ($this->other['objtable'] == \mod_stratumtwo_submission::TABLE) {
            return new \moodle_url('/mod/'. \mod_stratumtwo_exercise_round::TABLE .'/submission.php',
                    array('id' => $this->other['objid'])); // stratum2 submission ID
        }
        return null;
    }
}
