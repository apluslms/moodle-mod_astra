<?php

namespace mod_stratumtwo\event;
defined('MOODLE_INTERNAL') || die();
require_once(dirname(dirname(dirname(__FILE__))).'/constants.php');

/* Event class: student submitted a new solution to a Stratum2 exercise.
 */
/*
An event is created like this:
$event = \mod_stratumtwo\event\stratum_solution_submitted::create(array(
    'context' => context_module::instance($cm->id),
    'objectid' => $stratum->id,
    'relateduserid' => $user->id, // if needed
));
$event->trigger();
*/
class stratum_solution_submitted extends \core\event\base {

    protected function init() {
        $this->data['crud'] = 'r'; // c(reate), r(ead), u(pdate), d(elete)
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = STRATUMTWO_PLUGINNAME; // DB table. TODO change to submissions or exercises table?
    }

    /* Return localised name of the event, it is the same for all instances.
     */
    public static function get_name() {
        return get_string('eventstratumsubmitted', STRATUMTWO_MODNAME);
    }

    /* Returns non-localised description of one particular event.
     */
    public function get_description() {
        return "The user with the id '$this->userid' submitted a new solution to ".
            "Stratum2 activity with course module id '$this->contextinstanceid'.";
    }

    /* Returns Moodle URL where the event can be observed afterwards.
     * Can be null, if no valid location is present.
     */
    public function get_url() {
        return new \moodle_url('/mod/'. STRATUMTWO_PLUGINNAME .'/view.php', //TODO update
            array('s' => $this->objectid)); // stratum instance id
    }
}
