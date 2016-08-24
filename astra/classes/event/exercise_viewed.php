<?php

namespace mod_stratumtwo\event;
defined('MOODLE_INTERNAL') || die();

/* Event class: user viewed a Stratum2 exercise.
 */
/*
An event is created like this:
$event = \mod_stratumtwo\event\exercise_viewed::create(array(
    'context' => context_module::instance($cm->id),
    'objectid' => $exercise->id, // learning object ID
    'relateduserid' => $user->id, // if needed
));
$event->trigger();
*/
class exercise_viewed extends \core\event\base {

    protected function init() {
        $this->data['crud'] = 'r'; // c(reate), r(ead), u(pdate), d(elete)
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = \mod_stratumtwo_learning_object::TABLE; // DB table
    }

    /* Return localised name of the event, it is the same for all instances.
     */
    public static function get_name() {
        return get_string('eventexerciseviewed', \mod_stratumtwo_exercise_round::MODNAME);
    }

    /* Returns non-localised description of one particular event.
     */
    public function get_description() {
        return "The user with the id '$this->userid' viewed a Stratum2 exercise (id = {$this->objectid}).";
    }

    /* Returns Moodle URL where the event can be observed afterwards.
     * Can be null, if no valid location is present.
     */
    public function get_url() {
        return new \moodle_url('/mod/'. \mod_stratumtwo_exercise_round::TABLE .'/exercise.php',
            array('id' => $this->objectid));
    }
    
    public static function get_objectid_mapping() {
        return array('db' => \mod_stratumtwo_learning_object::TABLE, 'restore' => 'learningobject');
    }
}
