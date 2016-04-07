<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Exercise category in a course. Each exercise (learning object) belongs to one category
 * and the category counts the total points in the category. A category can have
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
    
    public function getStatus($asString = false) {
        if ($asString) {
            switch ((int) $this->record->status) {
                case self::STATUS_READY:
                    return get_string('statusready', mod_stratumtwo_exercise_round::MODNAME);
                    break;
                //case self::STATUS_HIDDEN:
                default:
                    return get_string('statushidden', mod_stratumtwo_exercise_round::MODNAME);
            }
        }
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
    
    private function getLearningObjects_sql($subtypeTable, $includeHidden = false, $fields = null) {
        if ($fields === null) {
            // use default fields (all)
            $sql = mod_stratumtwo_learning_object::getSubtypeJoinSQL($subtypeTable) . ' WHERE lob.categoryid = ?';
        } else {
            $sql = mod_stratumtwo_learning_object::getSubtypeJoinSQL($subtypeTable, $fields) . ' WHERE lob.categoryid = ?';
        }
        $params = array($this->getId());
        
        if (!$includeHidden) {
            $sql .= ' AND status != ?';
            $params[] = mod_stratumtwo_learning_object::STATUS_HIDDEN;
        }
        
        return array($sql, $params);
    }
    
    /**
     * Return all learning objects in this category.
     * @param bool $includeHidden if true, hidden learning objects are included
     * @return mod_stratumtwo_learning_object[], indexed by learning object IDs
     */
    public function getLearningObjects($includeHidden = false) {
        global $DB;
        
        list($chapters_sql, $ch_params) = $this->getLearningObjects_sql(mod_stratumtwo_chapter::TABLE, $includeHidden);
        $chapterRecords = $DB->get_records_sql($chapters_sql, $ch_params);
        
        $learningObjects = $this->getExercises($includeHidden);
        
        foreach ($chapterRecords as $rec) {
            $learningObjects[$rec->id] = new mod_stratumtwo_chapter($rec);
        }
        
        return $learningObjects;
    }
    
    /**
     * Return all exercises in this category.
     * @param bool $includeHidden if true, hidden exercises are included
     * @return mod_stratumtwo_exercise[], indexed by exercise/learning object IDs
     */
    public function getExercises($includeHidden = false) {
        global $DB;
        
        list($sql, $params) = $this->getLearningObjects_sql(mod_stratumtwo_exercise::TABLE, $includeHidden);
        
        $exerciseRecords = $DB->get_records_sql($sql, $params);
        
        $exercises = array();
        
        foreach ($exerciseRecords as $rec) {
            $exercises[$rec->id] = new mod_stratumtwo_exercise($rec);
        }
        
        return $exercises;
    }
    
    /**
     * Return the count of exercises in this category.
     * @return int
     */
    public function countExercises($includeHidden = false) {
        global $DB;
        
        list($sql, $params) = $this->getLearningObjects_sql(mod_stratumtwo_exercise::TABLE,
                $includeHidden, 'COUNT(lob.id)');
        
        return $DB->count_records_sql($sql, $params);
    }
    
    /**
     * Return the count of learning objects in this category.
     * @return int
     */
    public function countLearningObjects($includeHidden = false) {
        global $DB;
    
        list($ch_sql, $ch_params) = $this->getLearningObjects_sql(mod_stratumtwo_chapter::TABLE,
                $includeHidden, 'COUNT(lob.id)');

        return $this->countExercises($includeHidden) + $DB->count_records_sql($ch_sql, $ch_params);
    }
    
    /**
     * Return all categories in a course.
     * @param int $courseid
     * @param bool $includeHidden if true, hidden categories are included
     * @return array of mod_stratumtwo_category objects, indexed by category IDs
     */
    public static function getCategoriesInCourse($courseid, $includeHidden = false) {
        global $DB;
        if ($includeHidden) {
            $records = $DB->get_records(self::TABLE, array('course' => $courseid));
        } else {
            $records = $DB->get_records(self::TABLE, array(
                    'course' => $courseid,
                    'status' => self::STATUS_READY,
            ));
        }
        
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
        
        // delete learning objects in this category
        foreach ($this->getLearningObjects(true) as $lobject) {
            $lobject->deleteInstance();
        }
        
        return $DB->delete_records(self::TABLE, array('id' => $this->getId()));
    }
    
    public function getTemplateContext() {
        $ctx = new stdClass();
        $ctx->name = $this->getName();
        $ctx->editurl = \mod_stratumtwo\urls\urls::editCategory($this);
        //$ctx->has_exercises = ($this->countExercises() > 0); // unneeded
        $ctx->has_learning_objects = ($this->countLearningObjects() > 0);
        $ctx->removeurl = \mod_stratumtwo\urls\urls::deleteCategory($this);
        $ctx->status_ready = ($this->getStatus() === self::STATUS_READY);
        $ctx->status_str = $this->getStatus(true);
        return $ctx;
    }
}