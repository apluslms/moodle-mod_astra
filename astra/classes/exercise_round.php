<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Exercise round in a course. An exercise round consists of learning objects
 * (exercises and chapters) and the
 * round has a starting date and a closing date. The round can have required
 * points to pass that a student should earn in total in the exercises of the round.
 * The maximum points in a round is defined by the sum of the exercise maximum
 * points.
 */
class mod_astra_exercise_round extends mod_astra_database_object {
    const TABLE   = 'astra'; // database table name
    const MODNAME = 'mod_astra'; // module name for get_string
    
    const STATUS_READY       = 0;
    const STATUS_HIDDEN      = 1;
    const STATUS_MAINTENANCE = 2;
    const STATUS_UNLISTED    = 3;
    
    // calendar event types
    const EVENT_DL_TYPE = 'deadline';
    
    private $cm; // Moodle course module as cm_info instance
    private $courseConfig;
    
    public function __construct($astra) {
        parent::__construct($astra);
        $this->cm = $this->findCourseModule();
    }
    
    /**
     * Find the Moodle course module corresponding to this astra activity instance.
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
     * Return the Moodle course module corresponding to this astra activity instance.
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
    
    /**
     * Return the (Astra) course configuration object of the course.
     * May return null if there is no configuration.
     * @return NULL|mod_astra_course_config
     */
    public function getCourseConfig() {
        if (is_null($this->courseConfig)) {
            $this->courseConfig = mod_astra_course_config::getForCourseId($this->record->course);
        }
        return $this->courseConfig;
    }
    
    /**
     * Check if the given language is configured for the course.
     * Return the language code that should be used with the backend.
     * The given language is used if it is available to the course.
     * @param string $selected_lang the preferred language code
     * @return string the language to use
     */
    public function checkCourseLang(string $selected_lang) {
        $courseConf = $this->getCourseConfig();
        if (!$courseConf) {
            return $selected_lang;
        }
        $courseLanguages = $courseConf->getLanguages();
        if (in_array($selected_lang, $courseLanguages)) {
            return $selected_lang;
        }
        return $courseLanguages[0];
    }
    
    public function getName(string $lang = null, bool $includeAllLang = false) {
        require_once(dirname(dirname(__FILE__)) .'/locallib.php');

        if ($includeAllLang) {
            // do not parse multilang values
            return $this->record->name;
        }

        return astra_parse_multilang_filter_localization($this->record->name, $lang);
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
                case self::STATUS_UNLISTED:
                    return get_string('statusunlisted', self::MODNAME);
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
     * Return true if this exercise round has closed (not open and the closing time
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
     * @param int $numberingStyle module numbering constant from mod_astra_course_config
     * @return string
     */
    public static function updateNameWithOrder($oldName, $order, $numberingStyle) {
        require_once(dirname(dirname(__FILE__)) .'/locallib.php');
        
        // remove possible old ordinal number
        $name = preg_replace('/^(\d+\.)|^([IVXCML]+ )/', '', $oldName, 1);
        // require space after the roman numeral, or it catches words like "Very"
        if ($name !== null) {
            $name = trim($name);
            switch ($numberingStyle) {
                case mod_astra_course_config::MODULE_NUMBERING_ARABIC:
                    $name = "$order. $name";
                    break;
                case mod_astra_course_config::MODULE_NUMBERING_ROMAN:
                    $name = astra_roman_numeral($order) .' '. $name;
                    break;
                //case mod_astra_course_config::MODULE_NUMBERING_HIDDEN_ARABIC:
                //case mod_astra_course_config::MODULE_NUMBERING_NONE:
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
        global $CFG;
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
            case self::STATUS_UNLISTED:
                $visible = 1;
                break;
            case self::STATUS_HIDDEN:
                $visible = 0;
                break;
            default:
                throw new coding_exception('Astra exercise round has unknown status.');
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
     * Return an array of the learning objects in this round (as mod_astra_learning_object
     * instances).
     * @param bool $includeHidden if true, hidden learning objects are included
     * @param bool $sort if true, the result array is sorted
     * @return (sorted) array of mod_astra_learning_object instances
     */
    public function getLearningObjects($includeHidden = false, $sort = true) {
        global $DB;
        
        $where = ' WHERE lob.roundid = ?';
        $params = array($this->getId());
        
        if (!$includeHidden) {
            $notHiddenCats = mod_astra_category::getCategoriesInCourse($this->getCourse()->courseid, false);
            $notHiddenCatIds = array_keys($notHiddenCats);
            
            $where .= ' AND status != ? AND categoryid IN ('. implode(',', $notHiddenCatIds) .')';
            $params[] = mod_astra_learning_object::STATUS_HIDDEN;
        }
        
        $exerciseRecords = array();
        $chapterRecords = array();
        if ($includeHidden || !empty($notHiddenCatIds)) {
            $exerciseRecords = $DB->get_records_sql(
                mod_astra_learning_object::getSubtypeJoinSQL(mod_astra_exercise::TABLE) . $where,
                $params);
            $chapterRecords = $DB->get_records_sql(
                mod_astra_learning_object::getSubtypeJoinSQL(mod_astra_chapter::TABLE) . $where,
                $params);
        }
        // gather all learning objects of the round in one array
        $learningObjects = array();
        foreach ($exerciseRecords as $ex) {
            $learningObjects[] = new mod_astra_exercise($ex);
        }
        foreach ($chapterRecords as $ch) {
            $learningObjects[] = new mod_astra_chapter($ch);
        }
        
        // Sort again since some learning objects may have parent objects, and combining
        // chapters and exercises messes up the order from the database.
        // Output array should be in
        // the order that is used to print the exercises under the round
        // Sorting and flattening the exercise tree is derived from A+ (a-plus/course/tree.py).
        if ($sort) {
            return self::sortRoundLearningObjects($learningObjects);
        } else {
            return $learningObjects; // no sorting
        }
    }
    
    /**
     * Sort the given learning objects that should all belong to the same round.
     * The sorting uses ordernums of the objects and places child objects after their parent.
     * @param array $learningObjects array of mod_astra_learning_objects that are to be sorted
     * @param int|null $startParentId only include objects starting from this level.
     * This is an ID of an object that is parent to other objects. Only the children and their
     * children etc. of the object are included. Give null for top level (include all).
     * @return mod_astra_learning_object[] a new array of the sorted objects
     */
    public static function sortRoundLearningObjects(array $learningObjects, $startParentId = null) {
        $orderSortCallback = function($obj1, $obj2) {
            $ord1 = $obj1->getOrder();
            $ord2 = $obj2->getOrder();
            if ($ord1 < $ord2) {
                return -1;
            } else if ($ord1 == $ord2) {
                return 0;
            } else {
                return 1;
            }
        };
        
        // $parentid may be null to get top-level learning objects
        $children = function($parentid) use ($learningObjects, &$orderSortCallback) {
            $child_objs = array();
            foreach ($learningObjects as $obj) {
                if ($obj->getParentId() == $parentid)
                    $child_objs[] = $obj;
            }
            // sort children by ordernum, they all have the same parent
            usort($child_objs, $orderSortCallback);
            return $child_objs;
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
     * Return an array of the exercises in this round.
     * @param bool $includeHidden if true, hidden exercises are included
     * @param bool $sort if true, the result array is sorted
     * @return mod_astra_exercise[]
     */
    public function getExercises($includeHidden = false, $sort = true) {
        // array_filter keeps the old indexes/keys, so a numerically indexed array may
        // have discontinuous indexes
        return array_filter($this->getLearningObjects($includeHidden, $sort), function($lobj) {
            return $lobj->isSubmittable();
        });
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
        $item['itemname'] = $this->getName(null, true);

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
            
            $grade_item = $DB->get_record('grade_items', $grade_item_params);
            $update = new \stdClass();
            $update->id = $grade_item->id;
            // set min points to pass
            $update->gradepass = $this->getPointsToPass();
            // set zero coefficient so that the course total is not affected by rounds
            // (round grades are only sums of the exercise grades in the round)
            $update->aggregationcoef2 = 0.0;
            $update->weightoverride = 1;
            $DB->update_record('grade_items', $update);
            
            // must run the update method of the grade item to see changes
            $gi = grade_item::fetch($grade_item_params);
            $gi->update('mod/'. self::TABLE);
            
            // If some students already have grades for the round in the gradebook,
            // the changed coefficient may not be taken into account unless
            // the course final grades are computed again. The API function
            // grade_regrade_final_grades($this->record->course);
            // is very heavy and should not be called here since the call must not
            // be repeated for each round when automatically updating course configuration
            // (auto setup). For now, we assume that the grade_regrade_final_grades
            // call is not needed since nobody has grades when the grade item is
            // initially created and its coefficient is then set to zero.
        }

        return $res;
    }
    
    /**
     * Update the max points of this exercise round (based on the max points of exercises).
     * (Updates the database and gradebook item.)
     * @return boolean success/failure
     */
    public function updateMaxPoints() {
        global $DB;
        
        $this->record->timemodified = time();
        $max = 0;
        foreach ($this->getExercises(false, false) as $lobj) {
            // chapters have no grading, ignore them
            // only non-hidden exercises, but must check categories too
            if (!$lobj->getCategory()->isHidden()) {
                $max += $lobj->getMaxPoints();
            }
        }
        $this->record->grade = $max;
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
     * Delete Moodle gradebook item for this astra (exercise round) instance.
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
        foreach ($grades->items as $gradeItemNumber => $grade) {
            if ($gradeItemNumber != 0 && isset($grade->grades[$userid]->grade)) { // do not add the old exercise round points
                $sum += $grade->grades[$userid]->grade;
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
     * The grades are read from the database tables of the plugin.
     * @param int $userid update grade of a specific user only, 0 means all participants
     * @param bool $nullifnone If a single user is specified, $nullifnone is true and
     *     the user has no grade then a grade item with a null rawgrade should be inserted
     */
    public function writeAllGradesToGradebook($userid = 0, $nullifnone = false) {
        global $DB;
        if ($userid != 0) {
            // one student
            foreach ($this->getLearningObjects() as $ex) {
                if ($ex->isSubmittable()) {
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
            }
            $this->updateGradeForOneStudent($userid);
        } else {
            // all users in the course
            $roundGrades = array();
            foreach ($this->getLearningObjects() as $ex) {
                if ($ex->isSubmittable()) {
                    $exerciseGrades = $ex->writeAllGradesToGradebook();
                    foreach ($exerciseGrades as $student => $grade) {
                        if (isset($roundGrades[$student])) {
                            $roundGrades[$student] += $grade->rawgrade;
                        } else {
                            $roundGrades[$student] = $grade->rawgrade;
                        }
                    }
                }
            }
            $this->updateGrades($roundGrades);
        }
    }
    
    /**
     * Save a new instance of astra into the database 
     * (a new empty exercise round).
     * @param stdClass $astra
     * @return int The id of the newly inserted record, 0 if failed
     */
    public static function addInstance(stdClass $astra) {
        global $DB;
        
        $astra->timecreated = time();
        // Round max points depend on the max points of the exercises. A new round has
        // no exercises yet. The auto setup should compute the max points since
        // it knows the exercises that will be added to the round.
        if (!isset($astra->grade)) {
            $astra->grade = 0;
        }
        
        $astra->id = $DB->insert_record(self::TABLE, $astra);
        
        if ($astra->id) {
            $exround = new self($astra);
            if (!$exround->isHidden()) {
                $exround->updateGradebookItem();
                // NOTE: the course module does not usually yet exist in the DB at this stage
                $exround->update_calendar();
            }
        }
        
        return $astra->id;
    }
    
    /**
     * Update an instance of the astra (exercise round) in the database.
     * @param stdClass $astra record with id field and updated values for
     * any other field
     * @return bool true on success, false on failure
     */
    public static function updateInstance(stdClass $astra) {
        global $DB;
        // do not modify the Moodle course module here, since this function is called
        // (from lib.php) as a part of standard Moodle course module creation/modification
        
        $astra->timemodified = time();
        $result = $DB->update_record(self::TABLE, $astra);
        
        if ($result) {
            if (!isset($astra->grade)) {
                // $astra does not have grade field set since it comes from the Moodle mod_form
                $astra->grade = $DB->get_field(self::TABLE, 'grade', array(
                        'id' => $astra->id,
                ), MUST_EXIST);
            }
            
            $exround = new self($astra);
            $exround->updateGradebookItem(); // uses visibility of the Moodle course module
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
     * Remove this instance of the astra (exercise round) from the database.
     * @return boolean true on success, false on failure
     */
    public function deleteInstance() {
        global $DB;
        
        // Delete all learning objects of the round, since their foreign key roundid would become invalid
        $learningObjects = $this->getLearningObjects(true);
        foreach ($learningObjects as $lobj) {
            // If some learning objects have child objects, deleting the parent should
            // already delete the child. However, there is no harm in calling delete again
            // here for the already deleted child objects.
            if ($lobj->isSubmittable()) { // exercise
                $lobj->deleteInstance(false);
            } else {
                $lobj->deleteInstance();
            }
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
     * Return an array of the exercise rounds (as mod_astra_exercise_round objects)
     * in a course.
     * @param int $courseid
     * @param bool $includeHidden if true, hidden rounds are included
     * @return array of mod_astra_exercise_round objects
     */
    public static function getExerciseRoundsInCourse($courseid, $includeHidden = false) {
        global $DB;
        $sort = 'ordernum ASC, openingtime ASC, closingtime ASC, id ASC';
        if ($includeHidden) {
            $records = $DB->get_records(self::TABLE, array('course' => $courseid),
                $sort);
        } else {
            // exclude hidden rounds
            $astraRecords = $DB->get_records_select(self::TABLE, 'course = ? AND status != ?',
                array($courseid, self::STATUS_HIDDEN), $sort);
            // check course_module visibility too since the status may be ready,
            // but the course_module is not visible
            $records = array();
            $courseModules = get_fast_modinfo($courseid)->instances[self::TABLE];
            foreach ($astraRecords as $id => $rec) {
                if ($courseModules[$id]->visible) {
                    $records[$id] = $rec;
                }
            }
        }
        
        $rounds = array();
        foreach ($records as $record) {
            $rounds[] = new self($record);
        }
        return $rounds;
    }
    
    /**
     * Create a new exercise to this exercise round.
     * @param stdClass $exercise settings for the nex exercise: object with fields
     * status, parentid, ordernum, remotekey, name, serviceurl,
     * maxsubmissions, pointstopass, maxpoints
     * @param mod_astra_category $category category of the exercise
     * @param bool $updateRoundMaxPoints if true, the max points of the round are
     * updated here. Use false if the round is handled elsewhere in order to
     * reduce database operations.
     * @return mod_astra_exercise the new exercise, or null if failed
     */
    public function createNewExercise(stdClass $exercise, mod_astra_category $category,
            bool $updateRoundMaxPoints = true) {
        global $DB;

        $exercise->categoryid = $category->getId();
        $exercise->roundid = $this->getId();
        $exercise->gradeitemnumber = $this->getNewGradebookItemNumber();
        
        $exercise->lobjectid = $DB->insert_record(mod_astra_learning_object::TABLE, $exercise);
        $ex = null;
        if ($exercise->lobjectid) {
            $exercise->id = $DB->insert_record(mod_astra_exercise::TABLE, $exercise);
            
            try {
                $ex = mod_astra_exercise::createFromId($exercise->lobjectid);
            } catch (dml_exception $e) {
                // learning object row was created but not the exercise row, remove learning object
                $DB->delete_records(mod_astra_learning_object::TABLE, array('id' => $exercise->lobjectid));
                return null;
            }
            
            if (!$ex->isHidden() && !$this->isHidden() && !$category->isHidden()) {
                // create gradebook item
                $ex->updateGradebookItem();

                // update the max points of the round
                if ($updateRoundMaxPoints) {
                    $this->updateMaxPoints();
                }
            }
        }
        
        return $ex;
    }
    
    /**
     * Create a new chapter to this exercise round.
     * @param stdClass $chapter settings for the nex chapter: object with fields
     * status, parentid, ordernum, remotekey, name, serviceurl, generatetoc
     * @param mod_astra_category $category category of the chapter
     * @return mod_astra_chapter the new chapter, or null if failed
     */
    public function createNewChapter(stdClass $chapter, mod_astra_category $category) {
        global $DB;
        
        $chapter->categoryid = $category->getId();
        $chapter->roundid = $this->getId();
        
        $chapter->lobjectid = $DB->insert_record(mod_astra_learning_object::TABLE, $chapter);
        $ch = null;
        if ($chapter->lobjectid) {
            $chapter->id = $DB->insert_record(mod_astra_chapter::TABLE, $chapter);
            
            try {
                $ch = mod_astra_chapter::createFromId($chapter->lobjectid);
            } catch (dml_exception $e) {
                // learning object row was created but not the chapter row, remove learning object
                $DB->delete_records(mod_astra_learning_object::TABLE, array('id' => $chapter->lobjectid));
            }
        }
        
        return $ch;
    }
    
    /**
     * Find an unused gradebook item number from the exercises of this round.
     */
    public function getNewGradebookItemNumber() {
        $exs = $this->getLearningObjects(true);
        $max = 0;
        foreach ($exs as $ex) {
            if ($ex->isSubmittable()) {
                $num = $ex->getGradebookItemNumber();
                if ($num > $max) {
                    $max = $num;
                }
            }
        }
        return $max + 1;
    }
    
    protected function getSiblingContext($next = true) {
        // if $next true, get the next sibling; if false, get the previous sibling
        global $DB;
        
        $context = context_course::instance($this->record->course);
        $isTeacher = has_capability('moodle/course:manageactivities', $context);
        $isAssistant = has_capability('mod/astra:viewallsubmissions', $context);
        
        $where = 'course = :course';
        $where .= ' AND ordernum '. ($next ? '>' : '<') .' :ordernum';
        $params = array(
                'course' => $this->record->course,
                'ordernum' => $this->getOrder(),
        );
        if ($isAssistant && !$isTeacher) {
            // assistants do not see hidden rounds
            $where .= ' AND status <> :status';
            $params['status'] = self::STATUS_HIDDEN;
        } else if (!$isTeacher) {
            // students see normally enabled rounds
            $where .= ' AND status = :status';
            $params['status'] = self::STATUS_READY;
        }
        // order the DB query so that the first record is the next/previous sibling
        $results = $DB->get_records_select(self::TABLE, $where, $params,
                'ordernum '. ($next ? 'ASC' : 'DESC'),
                '*', 0, 1);
        
        $ctx = null;
        if (!empty($results)) {
            $sibling = new self(reset($results));
            $ctx = new stdClass();
            $ctx->name = $sibling->getName();
            $ctx->link = \mod_astra\urls\urls::exerciseRound($sibling);
            $ctx->accessible = $sibling->hasStarted();
        }
        return $ctx;
    }
    
    public function getNextSiblingContext() {
        return $this->getSiblingContext(true);
    }
    
    public function getPreviousSiblingContext() {
        return $this->getSiblingContext(false);
    }
    
    public function getTemplateContext($includeSiblings = false) {
        $ctx = new stdClass();
        $ctx->id = $this->getId();
        $ctx->openingtime = $this->getOpeningTime();
        $ctx->closingtime = $this->getClosingTime();
        $ctx->name = $this->getName();
        $ctx->late_submissions_allowed = $this->isLateSubmissionAllowed();
        $ctx->late_submission_deadline = $this->getLateSubmissionDeadline();
        $ctx->late_submission_point_worth = $this->getLateSubmissionPointWorth();
        $ctx->is_late_submission_open = $this->isLateSubmissionOpen();
        $ctx->show_late_submission_point_worth = ($ctx->late_submission_point_worth < 100);
        $ctx->late_submission_penalty = (int) ($this->getLateSubmissionPenalty() * 100); // percent
        $ctx->status_ready = ($this->getStatus() === self::STATUS_READY);
        // show_lobject_points: true if the exercise round point progress panel should display the exercise points for each exercise
        $ctx->show_lobject_points = ($this->getStatus() === self::STATUS_READY || $this->getStatus() === self::STATUS_UNLISTED);
        $ctx->status_maintenance = ($this->getStatus() === self::STATUS_MAINTENANCE);
        $ctx->introduction = \format_module_intro(self::TABLE, $this->record, $this->cm->id);
        $ctx->show_required_points = ($ctx->status_ready && $this->getPointsToPass() > 0);
        $ctx->points_to_pass = $this->getPointsToPass();
        $ctx->expired = $this->hasExpired();
        $ctx->open = $this->isOpen();
        $ctx->has_started = $this->hasStarted();
        $ctx->not_started = !$ctx->has_started;
        $ctx->status_str = $this->getStatus(true);
        $ctx->editurl = \mod_astra\urls\urls::editExerciseRound($this);
        $ctx->removeurl = \mod_astra\urls\urls::deleteExerciseRound($this);
        $ctx->url = \mod_astra\urls\urls::exerciseRound($this);
        $ctx->addnewexerciseurl = \mod_astra\urls\urls::createExercise($this);
        $ctx->addnewchapterurl = \mod_astra\urls\urls::createChapter($this);
        $context = context_module::instance($this->getCourseModule()->id);
        $ctx->is_course_staff = \has_capability('mod/astra:viewallsubmissions', $context);
        
        if ($includeSiblings) {
            $ctx->next = $this->getNextSiblingContext();
            $ctx->previous = $this->getPreviousSiblingContext();
        }
        
        return $ctx;
    }
    
    public function getTemplateContextWithExercises($includeHiddenExercises = false) {
        $ctx = $this->getTemplateContext();
        $ctx->all_exercises = array();
        foreach ($this->getLearningObjects($includeHiddenExercises) as $ex) {
            if ($ex->isSubmittable()) {
                $ctx->all_exercises[] = $ex->getExerciseTemplateContext(null, false, false);
            } else {
                $ctx->all_exercises[] = $ex->getTemplateContext(false);
            }
        }
        $ctx->has_exercises = !empty($ctx->all_exercises);
        return $ctx;
    }
    
    /**
     * Get an exercise round from the database matching the given course ID and remote key,
     * or create it if it does not yet exist.
     * @param int $courseid Moodle course ID
     * @param string $remotekey
     * @return mod_astra_exercise_round|NULL null if creation fails
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