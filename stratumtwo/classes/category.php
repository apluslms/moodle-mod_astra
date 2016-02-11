<?php
defined('MOODLE_INTERNAL') || die();

class mod_stratumtwo_category extends mod_stratumtwo_database_object {
    const TABLE = 'stratumtwo_categories'; // database table name
    const STATUS_READY  = 0;
    const STATUS_HIDDEN = 1;
    
    public function getId() {
        return $this->record->id;
    }
    
    public function getCourse() {
        // return course_modinfo object
        return get_fast_modinfo($this->record->course);
    }
    
    public function getStatus() {
        //TODO return number or string?
        switch ($this->record->status) {
            case self::STATUS_READY:
                return 'ready';
                break;
            case self::STATUS_HIDDEN:
                return 'hidden';
                break;
            default:
                throw new coding_exception('Stratum2 exercise category has unknown status.');
        }
    }
    
    public function getName() {
        return $this->record->name;
    }
    
    public function getPointsToPass() {
        return $this->record->pointstopass;
    }
}