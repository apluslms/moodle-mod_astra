<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Abstract base class for the classes that represent database records.
 * Database schemas are defined in the file db/install.xml.
 */
abstract class mod_astra_database_object {
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
        // mod_astra_submission::createFromId() returns instance of 
        // mod_astra_submission
    }
    
    /**
     * Create object from the given database record. The instance should already
     * exist in the database and have valid id.
     * @param stdClass $record
     */
    public function __construct(stdClass $record) {
        $this->record = $record;
    }
    
    public function getId() {
        return (int) $this->record->id;
    }
    
    /**
     * Save the updated record to the database. It must exist in the database
     * before calling this method (has valid id).
     * @return boolean success/failure (should always be true)
     * @throws dml_exception for any errors
     */
    public function save() {
        global $DB;
        return $DB->update_record(static::TABLE, $this->record);
    }
    
    /**
     * Return the database record of the object (as stdClass).
     * Please do not use this method to change the state of the object by
     * modifying the record; use this when Moodle requires data as stdClass.
     * @return stdClass 
     */
    public function getRecord() {
        return $this->record;
    }
}