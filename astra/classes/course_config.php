<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Course-wide Astra settings.
 */
class mod_astra_course_config extends mod_astra_database_object {
    const TABLE = 'astra_course_settings';
    
    // numbering of exercise rounds
    const MODULE_NUMBERING_NONE          = 0;
    const MODULE_NUMBERING_ARABIC        = 1; // 1, 2, ...
    const MODULE_NUMBERING_ROMAN         = 2; // I, II, ...
    const MODULE_NUMBERING_HIDDEN_ARABIC = 3;
    // no number in the module name but exercise numbers start with the module number, e.g., 1.1
    
    // numbering of objects in rounds
    const CONTENT_NUMBERING_NONE   = 0;
    const CONTENT_NUMBERING_ARABIC = 1;
    const CONTENT_NUMBERING_ROMAN  = 2;
    
    const DEFAULT_LANGUAGES = array('en');
    
    public static function updateOrCreate($courseid, $sectionNumber, $api_key = null, $config_url = null,
            $module_numbering = null, $content_numbering = null, $language = null) {
        global $DB;
        
        $row = $DB->get_record(self::TABLE, array('course' => $courseid), '*', IGNORE_MISSING);
        if ($row === false) {
            // create new
            $newRow = new stdClass();
            $newRow->course = $courseid;
            $newRow->sectionnum = $sectionNumber;
            $newRow->apikey = $api_key;
            $newRow->configurl = $config_url;
            if ($module_numbering !== null) {
                $newRow->modulenumbering = $module_numbering;
            }
            if ($content_numbering !== null) {
                $newRow->contentnumbering = $content_numbering;
            }
            if ($language !== null) {
                $newRow->lang = self::prepareLangString($language);
            }
            $id = $DB->insert_record(self::TABLE, $newRow);
            return $id != 0;
        } else {
            // update row
            if ($sectionNumber !== null) {
                $row->sectionnum = $sectionNumber;
            }
            if ($api_key !== null) {
                $row->apikey = $api_key;
            }
            if ($config_url !== null) {
                $row->configurl = $config_url;
            }
            if ($module_numbering !== null) {
                $row->modulenumbering = $module_numbering;
            }
            if ($content_numbering !== null) {
                $row->contentnumbering = $content_numbering;
            }
            if ($language !== null) {
                $row->lang = self::prepareLangString($language);
            }
            return $DB->update_record(self::TABLE, $row);
        }
    }
    
    public static function prepareLangString($langs) : string {
        if (empty($langs)) {
            return '';
        } else if (is_array($langs)) {
            return '|' . implode('|', $langs) . '|';
        }
        return substr($langs, 0, 5); // at most five first characters
    }
    
    public static function getForCourseId($courseid) {
        global $DB;
        $record = $DB->get_record(self::TABLE, array('course' => $courseid));
        if ($record === false) {
            return null;
        } else {
            return new self($record);
        }
    }
    
    public static function getDefaultModuleNumbering() {
        return self::MODULE_NUMBERING_ARABIC;
    }
    
    public static function getDefaultContentNumbering() {
        return self::CONTENT_NUMBERING_ARABIC;
    }
    
    public function getSectionNumber() {
        return $this->record->sectionnum;
    }
    
    public function getApiKey() {
        return $this->record->apikey;
    }
    
    public function getConfigurationUrl() {
        return $this->record->configurl;
    }
    
    public function getModuleNumbering() {
        return (int) $this->record->modulenumbering;
    }
    
    public function getContentNumbering() {
        return (int) $this->record->contentnumbering;
    }
    
    public function getLanguages() : array {
        $langs = $this->record->lang;
        if (empty($langs)) {
            return self::DEFAULT_LANGUAGES;
        } else if (substr($langs, 0, 1) === '|') {
            // starts with the pipe |
            $arr = array_filter(explode('|', $langs));
            // filter empty values
            return empty($arr) ? self::DEFAULT_LANGUAGES : $arr;
        } else {
            return array($langs);
        }
    }
}