<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Exercise category in a course. Each exercise belongs to one category and
 * the category counts the total points in the category. A category can have
 * required points to pass that the student should earn in total from the
 * exercises in the category. Exercises in a category can be scattered across
 * multiple exercise rounds.
 * 
 * Each instance of this class should correspond to one record in the categories
 * database table.
 */
class mod_stratumtwo_category extends mod_stratumtwo_database_object {
    const TABLE = 'stratumtwo_categories'; // database table name
    const STATUS_READY  = 0;
    const STATUS_HIDDEN = 1;
    
    public function getCourse() {
        // return course_modinfo object
        return get_fast_modinfo($this->record->course);
    }
    
    public function getStatus() {
        //TODO return number or string?
        /*switch ($this->record->status) {
            case self::STATUS_READY:
                return 'ready';
                break;
            case self::STATUS_HIDDEN:
                return 'hidden';
                break;
            default:
                throw new coding_exception('Stratum2 exercise category has unknown status.');
        }*/
        return (int) $this->record->status;
    }
    
    public function getName() {
        return $this->record->name;
    }
    
    public function getPointsToPass() {
        return $this->record->pointstopass;
    }
    
    /**
     * Return all exercises in this category.
     * @return mod_stratumtwo_exercise[]
     */
    public function getExercises() {
        global $DB;
        $exerciseRecords = $DB->get_records(mod_stratumtwo_exercise::TABLE, array(
                'categoryid' => $this->getId(),
        ), 'roundid ASC, ordernum ASC, id ASC');
        $exercises = array();
        foreach ($exerciseRecords as $record) {
            $exercises[] = new mod_stratumtwo_exercise($record);
        }
        return $exercises;
    }
    
    /**
     * Return all categories in a course.
     * @param int $courseid
     * @return array of mod_stratumtwo_category objects, indexed by category IDs
     */
    public static function getCategoriesInCourse($courseid) {
        global $DB;
        $records = $DB->get_records(static::TABLE, array('course' => $courseid));
        $categories = array();
        foreach ($records as $id => $record) {
            $categories[$id] = new static($record);
        }
        return $categories;
    }
    
    /**
     * Create a new category in the database.
     * @param stdClass $categoryRecord object with the fields required by the database table,
     * excluding id
     * @return int ID of the new database record, zero on failure
     */
    public static function createNew(stdClass $categoryRecord) {
        global $DB;
        return $DB->insert_record(static::TABLE, $categoryRecord);
    }
}