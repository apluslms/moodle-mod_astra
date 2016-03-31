<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Abstract class for student-specific submission deviations to an exercise.
 */
abstract class mod_stratumtwo_deviation_rule extends mod_stratumtwo_database_object {
    // subclasses must define constant TABLE
    
    // cache
    protected $exercise = null;
    protected $submitter = null;
    
    public static function findDeviation(mod_stratumtwo_exercise $exercise, $userid) {
        global $DB;
        
        $record = $DB->get_record(static::TABLE, array(
                'submitter'  => $userid,
                'exerciseid' => $exercise->getId(),
        ));
        if ($record === false) {
            return null;
        } else {
            return new static($record);
            // creates an instance of the class that is used to call this method statically
        }
    }
    
    public function getExercise() {
        if ($this->exercise === null) {
            $this->exercise = mod_stratumtwo_exercise::createFromId($this->record->exerciseid);
        }
        return $this->exercise;
    }
    
    public function getSubmitter() {
        global $DB;
        if ($this->submitter === null) {
            $this->submitter = $DB->get_record('user', array('id' => $this->record->submitter), '*', MUST_EXIST);
        }
        return $this->submitter;
    }
    
}