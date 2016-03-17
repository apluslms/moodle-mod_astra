<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Exercise round in a course. An exercise round consists of exercises and the
 * round has a starting date and a closing date. The round can have required
 * points to pass that a student should earn in total in the exercises of the round.
 * The maximum points in a round is defined by the sum of the exercise maximum
 * points.
 */
class mod_stratumtwo_exercise_round extends mod_stratumtwo_database_object {
    const TABLE   = 'stratumtwo'; // database table name
    const MODNAME = 'mod_stratumtwo'; // module name for get_string
    
    const STATUS_READY       = 0;
    const STATUS_HIDDEN      = 1;
    const STATUS_MAINTENANCE = 2;
    
    // calendar event types
    const EVENT_DL_TYPE = 'deadline';
    
    private $cm; // Moodle course module as cm_info instance
    
    public function __construct($stratumtwo) {
        parent::__construct($stratumtwo);
        $this->cm = $this->findCourseModule();
    }
    
    /**
     * Find the Moodle course module corresponding to this stratumtwo activity instance.
     * @return cm_info|null the Moodle course module. Null if it does not exist.
     */
    protected function findCourseModule() {
        // the Moodle course module may not exist yet if the exercise round is being created
        if (isset($this->getCourse()->instances[self::TABLE][$this->record->id])) {
            return $this->getCourse()->instances[self::TABLE][$this->record->id];
        } else {
            return null;
        }
    }
    
    /**
     * Return the Moodle course module corresponding to this stratumtwo activity instance.
     * @return cm_info|null the Moodle course module. Null if it does not exist.
     */
    public function getCourseModule() {
        if (is_null($this->cm)) {
            $this->cm = $this->findCourseModule();
        }
        return $this->cm;
    }
    
    public function getCourse() {
        // return course_modinfo object
        return get_fast_modinfo($this->record->course);
    }
    
    public function getName() {
        return $this->record->name;
    }
    
    public function getIntro($format = false) {
        if ($format) {
            // use Moodle filters for safe HTML output or other intro format types
            return format_module_intro(self::TABLE, $this->record, $this->cm->id);
        }
        return $this->record->intro;
    }
    
    public function getStatus($asString = false) {
        if ($asString) {
            switch ((int) $this->record->status) {
                case self::STATUS_READY:
                    return get_string('statusready', self::MODNAME);
                    break;
                case self::STATUS_MAINTENANCE:
                    return get_string('statusmaintenance', self::MODNAME);
                    break;
                default:
                    return get_string('statushidden', self::MODNAME);
            }
        }
        return (int) $this->record->status;
    }
    
    public function getMaxPoints() {
        return $this->record->grade;
    }
    
    public function getRemoteKey() {
        return $this->record->remotekey;
    }
    
    public function getOrder() {
        return $this->record->ordernum;
    }
    
    public function getPointsToPass() {
        return $this->record->pointstopass;
    }
    
    public function getOpeningTime() {
        return $this->record->openingtime; // int, Unix timestamp
    }
    
    public function getClosingTime() {
        return $this->record->closingtime; // int, Unix timestamp
    }
    
    public function isLateSubmissionAllowed() {
        return (bool) $this->record->latesbmsallowed;
    }
    
    public function getLateSubmissionDeadline() {
        return $this->record->latesbmsdl; // int, Unix timestamp
    }
    
    public function getLateSubmissionPenalty() {
        return $this->record->latesbmspenalty; // float number between 0--1
    }
    
    /**
     * Return the percentage (0-100) that late submission points are worth.
     * @return int percentage 0-100
     */
    public function getLateSubmissionPointWorth() {
        $pointWorth = 0;
        if ($this->isLateSubmissionAllowed()) {
            $pointWorth = (int) ((1.0 - $this->getLateSubmissionPenalty()) * 100.0);
        }
        return $pointWorth;
    }
    
    /**
     * Return true if this exercise round has closed (not open and the opening time
     * has passed).
     * @param int|null $when time to check, null for current time
     * @return boolean
     */
    public function hasExpired($when = null) {
        if (is_null($when)) {
            $when = time();
        }
        return $when > $this->getClosingTime();
    }
    
    public function isOpen($when = null) {
        if (is_null($when)) {
            $when = time();
        }
        return $this->getOpeningTime() <= $when && $when <= $this->getClosingTime();
    }
    
    public function isLateSubmissionOpen($when = null) {
        if ($when === null)
            $when = time();
        return $this->isLateSubmissionAllowed() && 
            $this->getClosingTime() <= $when && $when <= $this->getLateSubmissionDeadline();
    }
    
    /**
     * Return true if this exercise round has opened at or before timestamp $when.
     * @param int|null $when time to check, null for current time
     * @return boolean
     */
    public function hasStarted($when = null) {
        if (is_null($when)) {
            $when = time();
        }
        return $when >= $this->getOpeningTime();
    }
    
    public function isHidden() {
        return $this->getStatus() === self::STATUS_HIDDEN;
    }
    
    public function isUnderMaintenance() {
        return $this->getStatus() === self::STATUS_MAINTENANCE;
    }
    
    public function setOrder($order) {
        $this->record->ordernum = $order;
    }
    
    public function setName($name) {
        $this->record->name = $name;
    }
    
    /**
     * Return a new name based on the old name using the given ordinal number and
     * numbering style.
     * @param string $oldName old name with a possible old number
     * @param int $order new ordinal number to use
     * @param int $numberingStyle module numbering constant from mod_stratumtwo_course_config
     * @return string
     */
    public static function updateNameWithOrder($oldName, $order, $numberingStyle) {
        require_once(dirname(dirname(__FILE__)) .'/locallib.php');
        
        // remove possible old ordinal number
        $name = preg_replace('/^(\d+\.)|([IVXCML]+ )/', '', $oldName, 1);
        // require space after the roman numeral, or it catches words like "Very"
        if ($name !== null) {
            $name = trim($name);
            switch ($numberingStyle) {
                case mod_stratumtwo_course_config::MODULE_NUMBERING_ARABIC:
                    $name = "$order. $name";
                    break;
                case mod_stratumtwo_course_config::MODULE_NUMBERING_ROMAN:
                    $name = stratumtwo_roman_numeral($order) .' '. $name;
                    break;
                //case mod_stratumtwo_course_config::MODULE_NUMBERING_HIDDEN_ARABIC:
                //case mod_stratumtwo_course_config::MODULE_NUMBERING_NONE:
                default:
                    // do not add anything to the name
            }
            return $name;
            
        } else {
            return $oldName;
        }
    }
    
    public function setPointsToPass($points) {
        $this->record->pointstopass = $points;
    }
    
    public function setIntro($intro) {
        $this->record->intro = $intro;
        $this->record->introformat = FORMAT_HTML;
    }
    
    public function setStatus($status) {
        require_once($CFG->dirroot .'/course/lib.php');
        
        $cm = $this->getCourseModule();
        if ($status === self::STATUS_HIDDEN && $cm->visible) {
            // hide the Moodle course module
            \set_coursemodule_visible($cm->id, 0);
        } else if ($status !== self::STATUS_HIDDEN && !$cm->visible) {
            // show the Moodle course module
            \set_coursemodule_visible($cm->id, 1);
        }
        $this->record->status = $status;
    }
    
    public function setOpeningTime($open) {
        $this->record->openingtime = $open;
    }
    
    public function setClosingTime($close) {
        $this->record->closingtime = $close;
    }
    
    public function setLateSubmissionDeadline($dl) {
        $this->record->latesbmsdl = $dl;
    }
    
    public function setLateSubmissionAllowed($isAllowed) {
        $this->record->latesbmsallowed = (int) $isAllowed;
    }
    
    public function setLateSubmissionPenalty($penalty) {
        $this->record->latesbmspenalty = (float) $penalty;
    }
    
    /** Create or update the course calendar event for the deadline (closing time) 
     * of this exercise round.
     */
    public function update_calendar() {
        // deadline event
        $dl          = $this->getClosingTime(); // zero if no dl
        $title       = get_string('deadline', self::MODNAME) .': '. $this->getName();
        switch($this->getStatus()) {
            case self::STATUS_READY:
            case self::STATUS_MAINTENANCE:
                $visible = 1;
                break;
            case self::STATUS_HIDDEN:
                $visible = 0;
                break;
            default:
                throw new coding_exception('Stratum2 exercise round has unknown status.');
        }

        $this->update_event(self::EVENT_DL_TYPE, $dl, $title, $visible);
    }
    
    /** Helper method for creating/updating a calendar event.
     * @param string $type one of the EVENT_*_TYPE constants in this class
     * @param int $dl the time of the event (deadline), seconds since epoch.
     * If zero, no new event is created, and an existing event is removed.
     * @param string $title title for the event.
     * @param int $visible 0 or 1 for not visible or visible. If not visible,
     * no new event is created, but an existing event is updated.
     */
    protected function update_event($type, $dl, $title, $visible) {
        // see moodle/mod/assign/locallib.php for hints
        global $CFG, $DB;
        require_once($CFG->dirroot .'/calendar/lib.php');
    
        $event             = new stdClass();
        $event->id         = $DB->get_field('event', 'id', array(
                'modulename' => self::TABLE,
                'instance'   => $this->record->id,
                'eventtype'  => $type,
        )); // if the event already exists, there should be one hit

        $event->name       = $title;
        $event->timestart  = $dl; // seconds since epoch
        $event->visible    = $visible;

        if ($event->id) {
            // update existing
            $calendarevent = calendar_event::load($event->id);
            if ($dl) {
                $calendarevent->update($event);
            } else {
                // deadline removed, delete event
                $calendarevent->delete();
            }
        } else if ($dl && $visible) {
            // create new, unless no deadline is set for the assignment or not visible
            unset($event->id);
            if (is_null($this->cm)) {
                $event->description  = array(
                        'text'   => '', // no description in calendar
                        'format' => $this->record->introformat,
                );
            } else {
                // format_module_intro uses the Moodle description from mod_form
                $event->description  = format_module_intro(self::TABLE, $this->record, $this->cm->id);
            }
            $event->courseid     = $this->record->course;
            $event->groupid      = 0;
            $event->userid       = 0; // course event, no user
            $event->modulename   = self::TABLE;
            $event->instance     = $this->record->id;
            $event->eventtype    = $type;
            // eventtype: For activity module's events, this can be used to set the alternative text of the event icon.
            // Set it to 'pluginname' unless you have a better string.
    
            $event->timeduration = 0; // duration in seconds
    
            calendar_event::create($event);
        }
    }
    
    /** Delete the calendar event(s) for this assignment */
    public function delete_calendar_event() {
        global $DB;
        $DB->delete_records('event', array(
                'modulename' => self::TABLE,
                'instance'   => $this->record->id,
        ));
    }
    
    /**
     * Return an array of the exercises in this round (as mod_stratumtwo_exercise
     * instances).
     * @param bool $includeHidden if true, hidden exercises are included
     * @return array of mod_stratumtwo_exercise instances
     */
    public function getExercises($includeHidden = false) {
        global $DB;
        
        if ($includeHidden) {
            $exerciseRecords = $DB->get_records(mod_stratumtwo_exercise::TABLE, array(
                'roundid' => $this->record->id,
            ), 'ordernum ASC, id ASC');
        } else {
            $exerciseRecords = $DB->get_records_select(mod_stratumtwo_exercise::TABLE,
                    'roundid = ? AND status != ?',
                    array($this->getId(), mod_stratumtwo_exercise::STATUS_HIDDEN),
                    'ordernum ASC, id ASC');
        }
        
        $exercises = array();
        foreach ($exerciseRecords as $ex) {
            $exercises[] = new mod_stratumtwo_exercise($ex);
        }
        
        // sort again in case some exercises have parent exercises, output array should be in
        // the order that is used to print the exercises under the round
        // Sorting and flattening the exercise tree is derived from A+ (a-plus/course/tree.py).
        
        // $parentid may be null to get top-level exercises
        $children = function($parentid) use ($exercises) {
            $child_exs = array();
            foreach ($exercises as $ex) {
                if ($ex->getParentId() == $parentid)
                    $child_exs[] = $ex;
            }
            // the children are ordered by ordernum since $exercises array was sorted
            // by the database API, and we take only exercises with the same parent here
            return $child_exs;
        };
        
        $traverse = function($parentid) use (&$children, &$traverse) {
            $container = array();
            foreach ($children($parentid) as $child) {
                $container[] = $child;
                $container = array_merge($container, $traverse($child->getId()));
            }
            return $container;
        };
        
        return $traverse(null);
    }
    
    /**
     * Create or update the Moodle gradebook item for this exercise round.
     * (In order to add grades for students, use the method updateGrades.) 
     * This method does not create or update the grade items for the exercises of
     * the round. 
     * @param bool $reset if true, delete all grades in the grade item
     * @return int grade_update return value (one of GRADE_UPDATE_OK, GRADE_UPDATE_FAILED, 
     * GRADE_UPDATE_MULTIPLE or GRADE_UPDATE_ITEM_LOCKED)
     */
    public function updateGradebookItem($reset = false) {
        global $CFG, $DB;
        require_once($CFG->libdir.'/gradelib.php');
        require_once($CFG->libdir .'/grade/grade_item.php');

        $item = array();
        $item['itemname'] = clean_param($this->getName(), PARAM_NOTAGS);

        // update activity grading information ($item)
        if ($this->getMaxPoints() > 0) {
            $item['gradetype'] = GRADE_TYPE_VALUE; // points
            $item['grademax']  = $this->getMaxPoints();
            $item['grademin']  = 0; // min allowed value (points cannot be below this)
            // looks like min grade to pass (gradepass) cannot be set in this API directly
        } else {
            // Moodle core does not accept zero max points
            $item['gradetype'] = GRADE_TYPE_NONE;
        }

        if ($reset) {
            $item['reset'] = true;
        }

        // set course gradebook total grade aggregation method to "natural"
        $this->setGradebookTotalAggregation();

        // create gradebook item
        $res = grade_update('mod/'. self::TABLE, $this->record->course, 'mod',
                self::TABLE, $this->record->id, 0, null, $item);

        if ($this->getMaxPoints() > 0) {
            // parameters to find the grade item from DB
            $grade_item_params = array(
                    'itemtype'     => 'mod',
                    'itemmodule'   => self::TABLE,
                    'iteminstance' => $this->record->id,
                    'itemnumber'   => 0,
                    'courseid'     => $this->record->course,
            );
            // set min points to pass
            $DB->set_field('grade_items', 'gradepass', $this->getPointsToPass(), $grade_item_params);
            $gi = grade_item::fetch($grade_item_params);
            $gi->update('mod/'. self::TABLE);
        }

        return $res;
    }
    
    /**
     * Update the max points of this exercise round.
     * (Updates the database and gradebook item.)
     * @param int $change change to the current max points (positive or negative integer)
     * @return boolean success/failure
     */
    public function updateMaxPoints($change) {
        global $DB;
        
        $this->record->timemodified = time();
        $this->record->grade += $change;
        $result = $DB->update_record(self::TABLE, $this->record);
        $this->updateGradebookItem();
        
        return $result;
    }
    
    /** Set the course gradebook total grade aggregation method to "natural" (sum) because
     * it is the only one that allows setting the subassignment coefficients to zero.
     */
    public function setGradebookTotalAggregation() {
        global $CFG, $DB;
        require_once($CFG->libdir .'/grade/constants.php');
        require_once($CFG->libdir .'/grade/grade_category.php');
    
        // set course gradebook total grade aggregation method to "natural" (sum) because
        // it is the only one that allows setting the subassignment coefficients to zero
        $grade_cat_params = array(
            'courseid' => $this->record->course,
            'depth'    => 1,
            // There may several grade categories in a course if the teacher has manually
            // added new ones in addition to the default course total category.
            // This is supposed to only affect the course total category.
        );
        $old_aggregation = $DB->get_field('grade_categories', 'aggregation', $grade_cat_params, IGNORE_MISSING);
        if ($old_aggregation !== false && $old_aggregation != GRADE_AGGREGATE_SUM) {
            // only change if the aggregation was set to something else than natural
            $DB->set_field('grade_categories', 'aggregation', GRADE_AGGREGATE_SUM, $grade_cat_params);
            // include ungraded assignments in the aggregation
            // (course total does not show 100 % with only one asgn submitted with correct solution)
            $DB->set_field('grade_categories', 'aggregateonlygraded', 0, $grade_cat_params);
            $grade_category = grade_category::fetch($grade_cat_params);
            $props = new stdClass();
            $props->aggregation = GRADE_AGGREGATE_SUM;
            $props->aggregateonlygraded = 0;
            grade_category::set_properties($grade_category, $props);
            $grade_category->update('mod/'. self::TABLE);
            // update: Moodle must make some grade re-calculations before the new value in DB becomes effective
    
            // the category has its own grade item too
            $grade_item = $grade_category->load_grade_item();
            $grade_item->update();
        }
    }
    
    /**
     * Delete Moodle gradebook item for this stratumtwo (exercise round) instance.
     * @return int GRADE_UPDATE_OK or GRADE_UPDATE_FAILED (or GRADE_UPDATE_MULTIPLE)
     */
    public function deleteGradebookItem() {
        global $CFG;
        require_once($CFG->libdir.'/gradelib.php');
        return grade_update('mod/'. self::TABLE, $this->record->course, 'mod',
                self::TABLE, $this->record->id, 0, null, array('deleted' => 1));
    }
    
    /**
     * Update the grades of students in the gradebook for this exercise round 
     * (only the round, not its exercises). The gradebook item must have been 
     * created earlier.
     * @param array $grades student grades of this exercise round, indexed by Moodle user IDs.
     * The grade is given either as an integer or as stdClass with fields 
     * userid and rawgrade. Do not mix these two input types in the same array!
     * 
     * For example:
     * array(userid => 100)
     * OR
     * $g = new stdClass(); $g->userid = userid; $g->rawgrade = 100;
     * array(userid => $g)
     * 
     * @return int grade_update return value (one of GRADE_UPDATE_OK, GRADE_UPDATE_FAILED, 
     * GRADE_UPDATE_MULTIPLE or GRADE_UPDATE_ITEM_LOCKED)
     */
    public function updateGrades(array $grades) {
        global $CFG;
        require_once($CFG->libdir.'/gradelib.php');
        
        // transform integer grades to objects (if the first array value is integer)
        if (is_int(reset($grades))) {
            $grades = self::gradeArrayToGradeObjects($grades);
        }
        
        return grade_update('mod/'. self::TABLE, $this->record->course, 'mod',
                self::TABLE, $this->record->id, 0, $grades, null);
    }
    
    /**
     * Update the grade of this exercise round for one student in the gradebook.
     * The new grade is the sum of the exercise grades stored in the gradebook.
     * @param int $userid Moodle user ID of the student
     * @return int grade_update return value (one of GRADE_UPDATE_OK, GRADE_UPDATE_FAILED, 
     * GRADE_UPDATE_MULTIPLE or GRADE_UPDATE_ITEM_LOCKED)
     */
    public function updateGradeForOneStudent($userid) {
        global $CFG;
        require_once($CFG->libdir.'/gradelib.php');
        // The Moodle API returns the exercise round and exercise grades all at once
        // since they use different item numbers with the same Moodle course module.
        $grades = grade_get_grades($this->getCourse()->courseid, 'mod',
                self::TABLE,
                $this->getId(),
                $userid);
        $sum = 0;
        // sum the exercise points that were stored in the gradebook (should be
        // the best points for each exercise)
        foreach ($grades as $gradeItemNumber => $grade) {
            if ($gradeItemNumber != 0 && $grade->grade !== null) { // do not add the old exercise round points
                $sum += $grade->grade;
            }
        }
        $sum = (int) round($sum);
        return $this->updateGrades(array($userid => $sum));
    }
    
    /**
     * Convert an array of grades (userid => points) to a corresponding array
     * of grade objects (userid => object) (object has fields userid and rawgrade).
     * @param array $grades
     * @return array
     */
    public static function gradeArrayToGradeObjects(array $grades) {
        $objects = array();
        foreach ($grades as $userid => $grade) {
            $obj = new stdClass();
            $obj->userid = $userid;
            $obj->rawgrade = $grade;
            $objects[$userid] = $obj;
        }
        return $objects;
    }
    
    /**
     * Write grades of this exercise round and its exercises to the Moodle gradebook.
     * The grades are read from the database tables of the Stratum2 plugin.
     * @param int $userid update grade of a specific user only, 0 means all participants
     * @param bool $nullifnone If a single user is specified, $nullifnone is true and
     *     the user has no grade then a grade item with a null rawgrade should be inserted
     */
    public function writeAllGradesToGradebook($userid = 0, $nullifnone = false) {
        global $DB;
        if ($userid != 0) {
            // one student
            foreach ($this->getExercises() as $ex) {
                $sbms = $ex->getBestSubmissionForStudent($userid);
                if (is_null($sbms)) {
                    if ($nullifnone) {
                        $g = new stdClass();
                        $g->rawgrade = null;
                        $g->userid = $userid;
                        $ex->updateGrades(array($userid => $g));
                    }
                } else {
                    $sbms->writeToGradebook(false);
                }
            }
            $this->updateGradeForOneStudent($userid);
        } else {
            // all users in the course
            $roundGrades = array();
            foreach ($this->getExercises() as $ex) {
                $exerciseGrades = $ex->writeAllGradesToGradebook();
                foreach ($exerciseGrades as $student => $grade) {
                    if (isset($roundGrades[$student])) {
                        $roundGrades[$student] += $grade->rawgrade;
                    } else {
                        $roundGrades[$student] = $grade->rawgrade;
                    }
                }
            }
            $this->updateGrades($roundGrades);
        }
    }
    
    /**
     * Save a new instance of stratumtwo into the database 
     * (a new empty exercise round).
     * @param stdClass $stratumtwo
     * @return int The id of the newly inserted stratum record, 0 if failed
     */
    public static function addInstance(stdClass $stratumtwo) {
        global $DB;
        
        $stratumtwo->timecreated = time();
        // Round max points depend on the max points of the exercises. A new round has
        // no exercises yet.
        $stratumtwo->grade = 0;
        
        $stratumtwo->id = $DB->insert_record(self::TABLE, $stratumtwo);
        
        if ($stratumtwo->id) {
            $exround = new self($stratumtwo);
            $exround->updateGradebookItem();
            // NOTE: the course module does not usually yet exist in the DB at this stage
            $exround->update_calendar();
        }
        
        return $stratumtwo->id;
    }
    
    /**
     * Update an instance of the stratumtwo (exercise round) in the database.
     * @param stdClass $stratumtwo record with id field and updated values for
     * any other field
     * @return bool true on success, false on failure
     */
    public static function updateInstance(stdClass $stratumtwo) {
        global $DB;
        // do not modify the Moodle course module here, since this function is called
        // (from lib.php) as a part of standard Moodle course module creation/modification
        
        $stratumtwo->timemodified = time();
        $result = $DB->update_record(self::TABLE, $stratumtwo);
        
        if ($result) {
            if (!isset($stratumtwo->grade)) {
                // $stratumtwo does not have grade field set since it comes from the Moodle mod_form
                $stratumtwo->grade = $DB->get_field(self::TABLE, 'grade', array(
                        'id' => $stratumtwo->id,
                ), MUST_EXIST);
            }
            
            $exround = new self($stratumtwo);
            $exround->updateGradebookItem();
            $exround->update_calendar();
        }
        
        return $result;
    }
    
    public function save($skipGradebookAndCalendar = false) {
        if ($skipGradebookAndCalendar) {
            $this->record->timemodified = time();
            return parent::save();
        } else {
            return self::updateInstance($this->record);
        }
    }
    
    /**
     * Remove this instance of the stratumtwo (exercise round) from the database.
     * @return boolean true on success, false on failure
     */
    public function deleteInstance() {
        global $DB;
        
        // Delete all exercises of the round, since their foreign key roundid would become invalid
        $exercises = $this->getExercises();
        foreach ($exercises as $ex) {
            $ex->deleteInstance(false);
        }
        
        // delete calendar event for deadline
        $this->delete_calendar_event();
        
        // delete the exercise round
        $DB->delete_records(self::TABLE, array('id' => $this->record->id));
        
        // delete gradebook item
        $this->deleteGradebookItem();
        
        return true;
    }
    
    /**
     * Return an array of the exercise rounds (as mod_stratumtwo_exercise_round objects)
     * in a course.
     * @param int $courseid
     * @return array of mod_stratumtwo_exercise_round objects
     */
    public static function getExerciseRoundsInCourse($courseid) {
        global $DB;
        $rounds = array();
        $records = $DB->get_records(self::TABLE, array('course' => $courseid),
                'ordernum ASC, openingtime ASC, closingtime ASC, id ASC');
        foreach ($records as $record) {
            $rounds[] = new self($record);
        }
        return $rounds;
    }
    
    /**
     * Create a new exercise to this exercise round.
     * @param stdClass $exercise settings for the nex exercise: object with fields
     * status, parentid, ordernum, remotekey, name, serviceurl, allowastgrading,
     * maxsubmissions, pointstopass, maxpoints, sbmsmaxbytes
     * @param mod_stratumtwo_category $category category of the exercise
     * @return mod_stratumtwo_exercise the new exercise, or null if failed
     */
    public function createNewExercise(stdClass $exercise, mod_stratumtwo_category $category) {
        global $DB;

        $exercise->categoryid = $category->getId();
        $exercise->roundid = $this->getId();
        $exercise->gradeitemnumber = $this->getNewGradebookItemNumber();
        
        $exercise->id = $DB->insert_record(mod_stratumtwo_exercise::TABLE, $exercise);
        $ex = null;
        if ($exercise->id) {
            $ex = new mod_stratumtwo_exercise($DB->get_record(
                    mod_stratumtwo_exercise::TABLE, array('id' => $exercise->id), '*', MUST_EXIST));
            // create gradebook item
            $ex->updateGradebookItem();
            
            // update the max points of the round
            $this->updateMaxPoints($ex->getMaxPoints());
        }
        
        return $ex;
    }
    
    /**
     * Find an unused gradebook item number from the exercises of this round.
     */
    protected function getNewGradebookItemNumber() {
        $exs = $this->getExercises();
        $max = 0;
        foreach ($exs as $ex) {
            $num = $ex->getGradebookItemNumber();
            if ($num > $max) {
                $max = $num;
            }
        }
        return $max + 1;
    }
    
    public function getTemplateContext() {
        $ctx = new stdClass();
        $ctx->id = $this->getId();
        $ctx->openingtime = $this->getOpeningTime();
        $ctx->closingtime = $this->getClosingTime();
        $ctx->name = $this->getName();
        $ctx->late_submissions_allowed = $this->isLateSubmissionAllowed();
        $ctx->late_submission_deadline = $this->getLateSubmissionDeadline();
        $ctx->late_submission_point_worth = $this->getLateSubmissionPointWorth();
        $ctx->show_late_submission_point_worth = ($ctx->late_submission_point_worth < 100);
        $ctx->status_ready = ($this->getStatus() === self::STATUS_READY);
        $ctx->status_maintenance = ($this->getStatus() === self::STATUS_MAINTENANCE);
        $ctx->introduction = \format_module_intro(self::TABLE, $this->record, $this->cm->id);
        $ctx->show_required_points = ($ctx->status_ready && $this->getPointsToPass() > 0);
        $ctx->points_to_pass = $this->getPointsToPass();
        $ctx->expired = $this->hasExpired();
        $ctx->open = $this->isOpen();
        $ctx->not_started = !$this->hasStarted();
        $ctx->status_str = $this->getStatus(true);
        $ctx->editurl = \mod_stratumtwo\urls\urls::editExerciseRound($this);
        $ctx->removeurl = 'TODO'; //TODO
        $ctx->url = \mod_stratumtwo\urls\urls::exerciseRound($this);
        
        return $ctx;
    }
    
    public function getTemplateContextWithExercises($includeHiddenExercises = false) {
        $ctx = $this->getTemplateContext();
        $ctx->all_exercises = array();
        foreach ($this->getExercises($includeHiddenExercises) as $ex) {
            $ctx->all_exercises[] = $ex->getTemplateContext(null, false, false);
        }
        $ctx->has_exercises = !empty($ctx->all_exercises);
        return $ctx;
    }
    
    /**
     * Get an exercise round from the database matching the given course ID and remote key,
     * or create it if it does not yet exist.
     * @param int $courseid Moodle course ID
     * @param string $remotekey
     * @return mod_stratumtwo_exercise_round|NULL null if creation fails
     */
    public static function getOrCreate($courseid, $remotekey) {
        global $DB;
        $record = $DB->get_record(self::TABLE, array(
                'course' => $courseid,
                'remotekey' => $remotekey,
        ), '*', IGNORE_MISSING);
        if ($record === false) {
            // create new
            $new = new stdClass();
            $new->course = $courseid;
            $new->name = '-';
            $new->remotekey = $remotekey;
            $new->openingtime = time();
            $new->closingtime = time();
            
            $id = self::addInstance($new);
            if ($id)
                return self::createFromId($id);
            else
                return null; // DB failure
        } else {
            // get
            return new self($record);
        }
    }
    
}