<?php

namespace mod_astra\event;
defined('MOODLE_INTERNAL') || die();

/* Event class that represents an error in receiving asynchronous grading results.
 */
/*
An event is created like this:
$event = \mod_astra\event\async_grading_failed::create(array(
    'context' => context_module::instance($cm->id),
    'relateduserid' => $user->id, // optional user that is related to the action,
    // may be different than the user taking the action
    'other' => array(
        'error' => '...',
    )
));
$event->trigger();
*/
class async_grading_failed extends \core\event\base {

    protected function init() {
        $this->data['crud'] = 'u'; // c(reate), r(ead), u(pdate), d(elete)
        $this->data['edulevel'] = self::LEVEL_TEACHING;
    }

    /* Return localised name of the event, it is the same for all instances.
     */
    public static function get_name() {
        return get_string('eventasyncgradingfailed', \mod_astra_exercise_round::MODNAME);
    }

    /* Returns non-localised description of one particular event.
     */
    public function get_description() {
        return isset($this->other['error']) ? $this->other['error'] : '';
    }

    /* Returns Moodle URL where the event can be observed afterwards.
     * Can be null, if no valid location is present.
     */
    public function get_url() {
        return null;
    }
    
    public static function get_other_mapping() {
        // Backup/restore: no database IDs need to mapped in other data
        return false;
    }
}
