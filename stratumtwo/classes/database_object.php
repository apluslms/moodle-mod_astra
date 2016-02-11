<?php
defined('MOODLE_INTERNAL') || die();

abstract class mod_stratumtwo_database_object {
    // child classes must define constant TABLE (name of the database table)
    protected $record; // database record, stdClass
    
    /**
     * Create object of the corresponding class from an existing database ID.
     * @param int $id
     * @throws dml_exception if the record does not exist.
     */
    public static function createFromId($id) {
        global $DB;
        $rec = $DB->get_record(static::TABLE, array('id' => $id), '*', MUST_EXIST);
        return new static($rec);
        // class to instantiate is the class given in the static call: 
        // mod_stratumtwo_submission::createFromId() returns instance of 
        // mod_stratumtwo_submission
    }
    
    public function __construct(stdClass $record) {
        $this->record = $record;
    }
    
    /**
     * Save the record to the database. It must exist in the database before
     * calling this method.
     * @return boolean success/failure (should always be true)
     */
    public function save() {
        global $DB;
        return $DB->update_record(static::TABLE, $this->record);
    }
}