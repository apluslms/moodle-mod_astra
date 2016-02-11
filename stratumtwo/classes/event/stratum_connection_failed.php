<?php

namespace mod_stratumtwo\event;
defined('MOODLE_INTERNAL') || die();
require_once(dirname(dirname(dirname(__FILE__))).'/constants.php');

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
        'objtable' => 'stratumtwo', // or 'stratumtwo_exercises'
        'objid' => 1, // id of the module instance (DB row), zero means none
    )
));
$event->trigger();
*/
class stratum_connection_failed extends \core\event\base {

    protected function init() {
        $this->data['crud'] = 'r'; // c(reate), r(ead), u(pdate), d(elete)
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        // do not set objecttable so that we can use the event for both
        // exercise rounds and exercises
    }

    /* Return localised name of the event, it is the same for all instances.
     */
    public static function get_name() {
        return get_string('eventstratumconnectionfailed', STRATUMTWO_MODNAME);
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
        return new \moodle_url("/mod/{$this->other['objtable']}/view.php", //TODO must update
            array('s' => $this->other['objid'])); // stratum instance id
    }
}
