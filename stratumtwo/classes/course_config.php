<?php
defined('MOODLE_INTERNAL') || die();

class mod_stratumtwo_course_config extends mod_stratumtwo_database_object {
    const TABLE = 'stratumtwo_course_settings';
    
    public static function updateOrCreate($courseid, $sectionNumber, $api_key = null, $config_url = null) {
        global $DB;
        
        $row = $DB->get_record(self::TABLE, array('course' => $courseid), '*', IGNORE_MISSING);
        if ($row === false) {
            // create new
            $newRow = new stdClass();
            $newRow->course = $courseid;
            $newRow->sectionnum = $sectionNumber;
            $newRow->apikey = $api_key;
            $newRow->configurl = $config_url;
            $id = $DB->insert_record(self::TABLE, $newRow);
            return $id != 0;
        } else {
            // update row
            $row->sectionnum = $sectionNumber;
            $row->apikey = $api_key;
            $row->configurl = $config_url;
            return $DB->update_record(self::TABLE, $row);
        }
    }
}