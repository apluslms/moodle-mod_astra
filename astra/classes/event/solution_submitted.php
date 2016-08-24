<?php

namespace mod_astra\event;
defined('MOODLE_INTERNAL') || die();

/* Event class: student submitted a new solution to an Astra exercise.
 */
/*
An event is created like this:
$event = \mod_astra\event\solution_submitted::create(array(
    'context' => context_module::instance($cm->id),
    'objectid' => $submission->id,
    'relateduserid' => $user->id, // if needed
));
$event->trigger();
*/
class solution_submitted extends \core\event\base {

    protected function init() {
        $this->data['crud'] = 'c'; // c(reate), r(ead), u(pdate), d(elete)
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = \mod_astra_submission::TABLE; // DB table
    }

    /* Return localised name of the event, it is the same for all instances.
     */
    public static function get_name() {
        return get_string('eventsubmitted', \mod_astra_exercise_round::MODNAME);
    }

    /* Returns non-localised description of one particular event.
     */
    public function get_description() {
        return "The user with the id '$this->userid' submitted a new solution (id = {$this->objectid}) to ".
            "Astra exercise. (Round course module id '$this->contextinstanceid'.)";
    }

    /* Returns Moodle URL where the event can be observed afterwards.
     * Can be null, if no valid location is present.
     */
    public function get_url() {
        return new \moodle_url('/mod/'. \mod_astra_exercise_round::TABLE .'/submission.php',
            array('id' => $this->objectid));
    }
    
    public static function get_objectid_mapping() {
        return array('db' => \mod_astra_submission::TABLE, 'restore' => 'submission');
    }
}
