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
    
    public function isHidden() {
        return $this->getStatus() === self::STATUS_HIDDEN;
    }
    
    public function setHidden() {
        $this->record->status = self::STATUS_HIDDEN;
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
     * Return the count of exercises in this category.
     * @return int
     */
    public function countExercises() {
        global $DB;
        return $DB->count_records(mod_stratumtwo_exercise::TABLE, array(
                'categoryid' => $this->getId(),
        ));
    }
    
    /**
     * Return all categories in a course.
     * @param int $courseid
     * @return array of mod_stratumtwo_category objects, indexed by category IDs
     */
    public static function getCategoriesInCourse($courseid) {
        global $DB;
        $records = $DB->get_records(self::TABLE, array('course' => $courseid));
        $categories = array();
        foreach ($records as $id => $record) {
            $categories[$id] = new self($record);
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
        return $DB->insert_record(self::TABLE, $categoryRecord);
    }
    
    /**
     * Update an existing category record or create a new one if it does not
     * yet exist (based on course and the name).
     * @param stdClass $newRecord must have at least course and name fields as
     * they are used to look up the record. Course and name are not modified in
     * an existing record.
     * @return int ID of the new/modified record
     */
    public static function updateOrCreate(stdClass $newRecord) {
        global $DB;
        
        $catRecord = $DB->get_record(self::TABLE, array(
                'course' => $newRecord->course,
                'name' => $newRecord->name,
        ), '*', IGNORE_MISSING);
        if ($catRecord === false) {
            // create new
            return $DB->insert_record(self::TABLE, $newRecord);
        } else {
            // update
            if (isset($newRecord->status))
                $catRecord->status = $newRecord->status;
            if (isset($newRecord->pointstopass))
                $catRecord->pointstopass = $newRecord->pointstopass;
            $DB->update_record(self::TABLE, $catRecord);
            return $catRecord->id;
        }
    }
    
    public function delete() {
        global $DB;
        // delete exercises in this category
        $DB->delete_records(mod_stratumtwo_exercise::TABLE, array(
                'categoryid' => $this->getId(),
        ));
        $DB->delete_records(self::TABLE, array('id' => $this->getId()));
    }
    
    public function getTemplateContext() {
        $ctx = new stdClass();
        $ctx->name = $this->getName();
        $ctx->editurl = \mod_stratumtwo\urls\urls::editCategory($this);
        $ctx->has_exercises = ($this->countExercises() > 0);
        $ctx->removeurl = 'TODO'; //TODO \mod_stratumtwo\urls\urls::deleteCategory($this);
        return $ctx;
    }
}