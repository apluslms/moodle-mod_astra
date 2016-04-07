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
    
    public static function findDeviation($exerciseid, $userid) {
        global $DB;
        
        $record = $DB->get_record(static::TABLE, array(
                'submitter'  => $userid,
                'exerciseid' => $exerciseid,
        ));
        if ($record === false) {
            return null;
        } else {
            return new static($record);
            // creates an instance of the class that is used to call this method statically
        }
    }
    
    /**
     * Return all deviations in a course as moodle_recordset (iterator of database records
     * (stdClass)).
     * @param int $courseid Moodle course ID
     * @return moodle_recordset the caller of this method must call close() method
     * on the recordset
     */
    public static function getDeviationsInCourse($courseid) {
        global $DB;
        // TABLE is either the one of deadline or submission limit deviations, depending
        // on the class used in the static method call
        return $DB->get_recordset_select(static::TABLE,
                'exerciseid IN ('.
                    mod_stratumtwo_learning_object::getSubtypeJoinSQL(mod_stratumtwo_exercise::TABLE, 'lob.id') .
                    ' WHERE lob.roundid IN ' .
                        '(SELECT id FROM {'. mod_stratumtwo_exercise_round::TABLE .'} WHERE course = ?))',
                array($courseid),
                'exerciseid ASC, submitter ASC');
    }
    
    public function delete() {
        global $DB;
        return $DB->delete_records(static::TABLE, array('id' => $this->getId()));
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
    
    public function getSubmitterName() {
        return self::submitterName($this->getSubmitter());
    }
    
    public static function submitterName(stdClass $user) {
        $name = fullname($user);
        if (empty($user->idnumber) || $user->idnumber === '(null)') {
            $name .= " ({$user->username})";
        } else {
            $name .= " ({$user->idnumber})";
        }
        return $name;
    }
    
    public function getTemplateContext() {
        $ctx = new stdClass();
        $ctx->submitter = $this->getSubmitterName();
        $ctx->exercise_name = $this->getExercise()->getName();
        return $ctx;
    }
}