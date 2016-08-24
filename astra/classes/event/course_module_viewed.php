<?php

namespace mod_astra\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The mod_astra instance viewed event class
 *
 * If the view mode needs to be stored as well, you may need to
 * override methods get_url() and get_legacy_log_data(), too.
 *
 * @package    mod_astra
 * @copyright  2016 Aalto SCI CS dept.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_module_viewed extends \core\event\course_module_viewed {

    /**
     * Initialize the event
     */
    protected function init() {
        $this->data['objecttable'] = \mod_astra_exercise_round::TABLE;
        parent::init();
    }
    
    public static function get_objectid_mapping() {
        return array('db' => \mod_astra_exercise_round::TABLE, 'restore' => 'astra');
    }
}
