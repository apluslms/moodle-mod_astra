<?php

defined('MOODLE_INTERNAL') || die();

/**
 * One exercise in an exercise round. Each exercise belongs to one exercise round
 * and one category. An exercise has a service URL that is used to connect to
 * the exercise service. An exercise has max points and minimum points to pass.
 */
class mod_astra_exercise extends mod_astra_learning_object {
    const TABLE = 'astra_exercises'; // database table name
    
    public function getMaxSubmissions() {
        return $this->record->maxsubmissions;
    }
    
    public function getPointsToPass() {
        return $this->record->pointstopass;
    }
    
    public function getMaxPoints() {
        return $this->record->maxpoints;
    }
    
    public function getGradebookItemNumber() {
        return $this->record->gradeitemnumber;
    }
    
    public function getSubmissionFileMaxSize() {
        return (int) $this->record->maxsbmssize;
    }
    
    public function isSubmittable() {
        return true;
    }
    
    public function isAssistantViewingAllowed() {
        return (bool) $this->record->allowastviewing;
    }
    
    public function isAssistantGradingAllowed() {
        return (bool) $this->record->allowastgrading;
    }
    
    /**
     * Check whether the uploaded files obey the submission file size constraint.
     * @param array $uploadedFiles supply the $_FILES superglobal or an array that
     * has the same structure and includes the file sizes.
     * @return boolean true if all files obey the limit, false otherwise
     */
    public function checkSubmissionFileSizes(array $uploadedFiles) {
        $maxSize = $this->getSubmissionFileMaxSize();
        if ($maxSize == 0) {
            return true; // no limit
        }
        foreach ($uploadedFiles as $formInputName => $farray) {
            if ($farray['size'] > $maxSize) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Delete this exercise instance from the database, and possible child
     * learning objects. All submissions to this exercise are also deleted.
     * @param bool $updateRoundMaxPoints if true, the max points of the 
     * exercise round are updated here
     */
    public function deleteInstance($updateRoundMaxPoints = true) {
        global $DB;
        
        // all submitted files to this exercise (in Moodle file API) (file itemid is a submission id)
        $fs = \get_file_storage();
        $fs->delete_area_files_select(context_module::instance($this->getExerciseRound()->getCourseModule()->id)->id,
                mod_astra_exercise_round::MODNAME, mod_astra_submission::SUBMITTED_FILES_FILEAREA,
                'IN (SELECT id FROM {'. mod_astra_submission::TABLE .'} WHERE exerciseid = :astraexerciseid)',
                array('astraexerciseid' => $this->getId()));
        // all submissions to this exercise
        $DB->delete_records(mod_astra_submission::TABLE, array(
            'exerciseid' => $this->getId(),
        ));
        
        // delete exercise gradebook item
        $this->deleteGradebookItem();
        
        // this exercise (both lobject and exercise tables) and children
        $res = parent::deleteInstance();
        
        // update round max points (this exercise must have been deleted from the DB before this)
        if ($updateRoundMaxPoints) {
            $this->getExerciseRound()->updateMaxPoints();
        }
        
        return $res;
    }
    
    /**
     * Delete Moodle gradebook item for this exercise.
     * @return int GRADE_UPDATE_OK or GRADE_UPDATE_FAILED (or GRADE_UPDATE_MULTIPLE)
     */
    public function deleteGradebookItem() {
        global $CFG;
        require_once($CFG->libdir.'/gradelib.php');
        return grade_update('mod/'. mod_astra_exercise_round::TABLE,
                $this->getExerciseRound()->getCourse()->courseid,
                'mod',
                mod_astra_exercise_round::TABLE,
                $this->getExerciseRound()->getId(),
                $this->getGradebookItemNumber(),
                null, array('deleted' => 1));
    }
    
    /**
     * Return the best submission of the student to this exercise.
     * @param int $userid Moodle user ID of the student
     * @return mod_astra_submission the best submission, or null if there is
     * no submission
     */
    public function getBestSubmissionForStudent($userid) {
        global $DB;

        $submissions = $this->getSubmissionsForStudent($userid);
        // order by submissiontime, earlier first
        $bestSubmission = null;
        foreach ($submissions as $s) {
            $sbms = new mod_astra_submission($s);
            // assume that the grade of a submission is zero if it was not accepted
            // due to submission limit or deadline
            if ($bestSubmission === null || $sbms->getGrade() > $bestSubmission->getGrade()) {
                $bestSubmission = $sbms;
            }
        }
        $submissions->close();
        
        return $bestSubmission;
    }

    /**
     * Return the number of submissions a student has made in this exercise.
     * @param int $userid
     * @param bool $excludeErrors if true, the submissions with status error are not counted
     * @return int
     */
    public function getSubmissionCountForStudent($userid, $excludeErrors = false) {
        global $DB;
        
        if ($excludeErrors) {
            // exclude submissions with status error
            $count = $DB->count_records_select(mod_astra_submission::TABLE,
                    'exerciseid = ? AND submitter = ? AND status != ?', array(
                            $this->getId(),
                            $userid,
                            mod_astra_submission::STATUS_ERROR,
                    ), "COUNT('id')");
        } else {
            $count = $DB->count_records(mod_astra_submission::TABLE, array(
                    'exerciseid' => $this->getId(),
                    'submitter'  => $userid,
            ));
        }
        return $count;
    }
    
    /**
     * Return the submissions of a student in this exercise.
     * @param int $userid
     * @param bool $excludeErrors if true, the submissions with status error are not returned
     * @param string $orderBy SQL ORDER BY argument
     * @return Moodle recordset (iterator) of database records (stdClass).
     * The caller of this method must call the close() method.
     */
    public function getSubmissionsForStudent($userid, $excludeErrors = false, $orderBy = 'submissiontime ASC') {
        global $DB;
        
        if ($excludeErrors) {
            // exclude submissions with status error
            $submissions = $DB->get_recordset_select(mod_astra_submission::TABLE,
                    'exerciseid = ? AND submitter = ? AND status != ?', array(
                            $this->getId(),
                            $userid,
                            mod_astra_submission::STATUS_ERROR,
                    ), $orderBy);
        } else {
            $submissions = $DB->get_recordset(mod_astra_submission::TABLE, array(
                'exerciseid' => $this->getId(),
                'submitter'  => $userid,
            ), $orderBy);
        }
        return $submissions;
    }
    
    /**
     * Return all submissions to this exercise. Name and idnumber fields of
     * the submitter are included in the records.
     * @param bool $excludeErrors if true, submissions with status error are excluded
     * @param array $filter queries for filtering the submissions. Possible keys:
     * idnumber, firstname, lastname (user data of the submitter);
     * status (one of submission status constants), submissiontimebef, submissiontimeaft,
     * gradeless, gradegreater (greater than or equal to),
     * hasassistfeedback (supports values from the filter submissions form)
     * @param array $sort which attributes are used to sort the results?
     * Outer array is indexed from zero and shows the order of the columns
     * to sort (primary column first). The nested arrays contain two elements: field names
     * (like 'idnumber' in the all_submissions_page::allowedFilterFields method)
     * and boolean values (true for ascending sort, false for descending).
     * @param number $limitfrom limit the number of records returned, starting from this point
     * @param number $limitnum number of records to return (zero for no limit)
     * @return list($submissions, $count), $submissions is a Moodle recordset (iterator)
     * of database records (stdClass). The caller of this method must call the close()
     * method on the recordset. $count is the total number of records that match the
     * query (useful if $limitnum is used to limit the number of records returned).
     */
    public function getAllSubmissions($excludeErrors = false, array $filter = null,
            array $sort = null, $limitfrom = 0, $limitnum = 0) {
        global $DB;
        
        // SELECT fields
        // exclude fields feedback, submissiondata, and gradingdata from submissions
        $sbmsFieldsArray = array('id','status','submissiontime','hash','exerciseid','submitter',
                'grader','assistfeedback','grade','gradingtime','latepenaltyapplied',
                'servicepoints','servicemaxpoints');
        $sbmsFieldsArray = array_map(function ($attr) {
            return 's.' . $attr; // prepend the table alias prefix
        }, $sbmsFieldsArray);
        // name and idnumber from the Moodle user table
        $userFields = get_all_user_name_fields(true, 'u') . ',u.idnumber';
        $fields = implode(',', $sbmsFieldsArray) . ',' . $userFields;
        
        // WHERE conditions from the filter parameter
        $params = array();
        $wheres = array();
        $wheres[] = "s.exerciseid = :exerciseid";
        $params['exerciseid'] = $this->getId();
        
        if ($excludeErrors) {
            $wheres[] = 's.status <> :error';
            $params['error'] = mod_astra_submission::STATUS_ERROR;
        }
        
        // some fields use SQL LIKE comparison in the where clause
        $likeCompareFields = array('idnumber', 'firstname', 'lastname');
        foreach ($likeCompareFields as $field) {
            if (isset($filter[$field])) {
                $wheres[] = $DB->sql_like($field, ":$field", false, false);
                $params[$field] = '%' . $DB->sql_like_escape($filter[$field]) . '%';
            }
        }
        
        if (isset($filter['status']) && $filter['status'] >= 0) {
            $wheres[] = 's.status = :status';
            $params['status'] = $filter['status'];
        }
        if (isset($filter['submissiontimebef'])) {
            $wheres[] = 's.submissiontime <= :submissiontimebef';
            $params['submissiontimebef'] = $filter['submissiontimebef'];
        }
        if (isset($filter['submissiontimeaft'])) {
            $wheres[] = 's.submissiontime >= :submissiontimeaft';
            $params['submissiontimeaft'] = $filter['submissiontimeaft'];
        }
        if (isset($filter['gradeless'])) {
            $wheres[] = 's.grade <= :gradeless';
            $params['gradeless'] = $filter['gradeless'];
        }
        if (isset($filter['gradegreater'])) {
            $wheres[] = 's.grade >= :gradegreater';
            $params['gradegreater'] = $filter['gradegreater'];
        }
        if (isset($filter['hasassistfeedback'])) {
            if ($filter['hasassistfeedback'] === 'yes') {
                $wheres[] = '(s.assistfeedback IS NOT NULL AND '
                    . $DB->sql_isnotempty(mod_astra_submission::TABLE, 's.assistfeedback', true, true)
                    . ')';
            } else if ($filter['hasassistfeedback'] === 'no') {
                $wheres[] = '(s.assistfeedback IS NULL OR '
                    . $DB->sql_isempty(mod_astra_submission::TABLE, 's.assistfeedback', true, true)
                    . ')';
            }
        }
        
        $sqlEnd =
            "FROM {". mod_astra_submission::TABLE ."} s
             JOIN {user} u ON u.id = s.submitter";
        
        $sql = "SELECT $fields " . $sqlEnd;
        $sqlCount = "SELECT COUNT(*) " . $sqlEnd;
        
        if (!empty($wheres)) {
            $where = ' WHERE '. implode(' AND ', $wheres);
            $sql .= $where;
            $sqlCount .= $where;
        }
        if (!empty($sort)) {
            $sortfields = array();
            foreach ($sort as $fieldASC) {
                $f = $fieldASC[0];
                if (!$fieldASC[1]) {
                    $f .= ' DESC';
                }
                $sortfields[] = $f;
            }
            
            $sql .= ' ORDER BY ' . implode(',', $sortfields);
        }
        
        $submissions = $DB->get_recordset_sql($sql, $params, $limitfrom, $limitnum);
        $count = $DB->count_records_sql($sqlCount, $params);
        
        return array($submissions, $count);
    }
    
    /**
     * Create or update the Moodle gradebook item for this exercise.
     * (In order to add grades for students, use the method updateGrades.)
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
        
        // update exercise grading information ($item)
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
        
        $courseid = $this->getExerciseRound()->getCourse()->courseid;
        
        // create gradebook item
        $res = grade_update('mod/'. mod_astra_exercise_round::TABLE, $courseid, 'mod',
                mod_astra_exercise_round::TABLE, $this->record->roundid,
                $this->getGradebookItemNumber(), null, $item);
        
        // parameters to find the grade item from DB
        $grade_item_params = array(
                'itemtype'     => 'mod',
                'itemmodule'   => mod_astra_exercise_round::TABLE,
                'iteminstance' => $this->record->roundid,
                'itemnumber'   => $this->getGradebookItemNumber(),
                'courseid'     => $courseid,
        );
        // set min points to pass
        $DB->set_field('grade_items', 'gradepass', $this->getPointsToPass(), $grade_item_params);
        $gi = grade_item::fetch($grade_item_params);
        if ($gi) {
            $gi->update('mod/'. mod_astra_exercise_round::TABLE);
        }
        
        return $res;
    }
    
    /**
     * Return the grade of this exercise for the given user from the Moodle gradebook. 
     * @param int $userid
     * @param numeric the grade
     */
    public function getGradeFromGradebook($userid) {
        global $CFG;
        require_once($CFG->libdir.'/gradelib.php');
        // The Moodle API returns the exercise round and exercise grades all at once
        // since they use different item numbers with the same Moodle course module.
        $grades = grade_get_grades($this->getExerciseRound()->getCourse()->courseid, 'mod',
                mod_astra_exercise_round::TABLE,
                $this->getExerciseRound()->getId(),
                $userid);
        $itemNum = $this->getGradebookItemNumber();
        if (isset($grades->items[$itemNum]->grades[$userid])) {
            return (int) $grades->items[$itemNum]->grades[$userid]->grade;
        }
        return 0;
    }
    
    /**
     * Update the grades of students in the gradebook for this exercise.
     * The gradebook item must have been created earlier.
     * @param array $grades student grades of this exercise, indexed by Moodle user IDs.
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
        
        if ($this->getMaxPoints() == 0) {
            // skip if the max points are zero (no grading)
            return GRADE_UPDATE_OK;
        }
        
        // transform integer grades to objects (if the first array value is integer)
        if (is_int(reset($grades))) {
            $grades = mod_astra_exercise_round::gradeArrayToGradeObjects($grades);
        }
        
        return grade_update('mod/'. mod_astra_exercise_round::TABLE,
                $this->getExerciseRound()->getCourse()->courseid, 'mod',
                mod_astra_exercise_round::TABLE,
                $this->getExerciseRound()->getId(),
                $this->getGradebookItemNumber(), $grades, null);
    }
    
    /**
     * Write grades for each student in this exercise to Moodle gradebook.
     * The grades are read from the database tables of the plugin.
     * 
     * @return array grade objects written to gradebook, indexed by user IDs
     */
    public function writeAllGradesToGradebook() {
        global $DB;
        
        if ($this->getMaxPoints() == 0) {
            // skip if the max points are zero (no grading)
            return array();
        }
        
        // get all user IDs of the students that have submitted to this exercise
        $table = mod_astra_submission::TABLE;
        $submitters = $DB->get_recordset_sql('SELECT DISTINCT submitter FROM {'. $table .'} WHERE exerciseid = ?',
                array($this->getId()));
        $grades = array(); // grade objects indexed by user IDs
        foreach ($submitters as $row) {
            // get the best points of each student
            $sbms = $this->getBestSubmissionForStudent($row->submitter);
            if ($sbms !== null) {
                $grades[$row->submitter] = $sbms->getGradeObject();
            }
        }
        $submitters->close();
        
        $this->updateGrades($grades);
        
        return $grades;
    }
    
    public function save($skipGradebook = false) {
        if (!$skipGradebook) {
            $this->updateGradebookItem();
        }
        
        return parent::save();
    }
    
    /**
     * Return the number of users that have submitted to this exercise.
     * @return int
     */
    public function getTotalSubmitterCount() {
        global $DB;
        return $DB->count_records_select(mod_astra_submission::TABLE,
                'exerciseid = ?',
                array($this->getId()),
                'COUNT(DISTINCT submitter)');
    }
    
    /**
     * Return the template context of all submissions from a user.
     * @param int $userid
     * @return stdClass[]
     */
    public function getSubmissionsTemplateContext($userid) {
        // latest submission first
        $submissions = $this->getSubmissionsForStudent($userid, false, 'submissiontime DESC');
        $objects = array();
        foreach ($submissions as $record) {
            $objects[] = new mod_astra_submission($record);
        }
        $submissions->close();
        
        return self::submissionsTemplateContext($objects);
    }
    
    /**
     * Return the template context objects for the given submissions.
     * The submissions should be submitted by the same user to the same exercise
     * and the array should be sorted by the submission time (latest submission first).
     * 
     * @param array $submissions \mod_astra_submission objects
     * @return stdClass[] array of context objects
     */
    public static function submissionsTemplateContext(array $submissions) {
        $ctx = array();
        $nth = count($submissions);
        foreach ($submissions as $sbms) {
            $obj = $sbms->getTemplateContext();
            $obj->nth = $nth;
            $nth--;
            $ctx[] = $obj;
        }
        
        return $ctx;
    }
    
    public function getExerciseTemplateContext(stdClass $user = null,
            $includeTotalSubmitterCount = true, $includeCourseModule = true,
            $includeSiblings = false) {
        $ctx = parent::getTemplateContext($includeCourseModule, $includeSiblings);
        $ctx->submissionlisturl = \mod_astra\urls\urls::submissionList($this);
        $ctx->infourl = \mod_astra\urls\urls::exerciseInfo($this);
        
        $ctx->max_points = $this->getMaxPoints();
        $ctx->max_submissions = $this->getMaxSubmissions();
        if ($user !== null) {
            $ctx->max_submissions_for_user = $this->getMaxSubmissionsForStudent($user);
            if ($ctx->max_submissions_for_user > 0) {
                // number of extra submissions given to the student
                $ctx->submit_limit_deviation = $ctx->max_submissions_for_user - $ctx->max_submissions;
            } else {
                $ctx->submit_limit_deviation = 0;
            }
            
            $dl_deviation = mod_astra_deadline_deviation::findDeviation($this->getId(), $user->id);
            if ($dl_deviation !== null) {
                $ctx->deadline = $dl_deviation->getNewDeadline();
                $ctx->dl_extended_minutes = $dl_deviation->getExtraTime();
            } else {
                $ctx->deadline = $this->getExerciseRound()->getClosingTime();
                $ctx->dl_extended_minutes = 0;
            }
        }
        
        $ctx->points_to_pass = $this->getPointsToPass();
        if ($includeTotalSubmitterCount) {
            $ctx->total_submitter_count = $this->getTotalSubmitterCount(); // heavy DB query
        }
        $ctx->allow_assistant_grading = $this->isAssistantGradingAllowed();
        $ctx->allow_assistant_viewing = $this->isAssistantViewingAllowed();
        $context = context_module::instance($this->getExerciseRound()->getCourseModule()->id);
        $ctx->can_view_submissions = ($ctx->allow_assistant_viewing && 
                has_capability('mod/astra:viewallsubmissions', $context)) || 
            has_capability('mod/astra:addinstance', $context); // editing teacher can always view
        
        return $ctx;
    }
    
    /**
     * Return the URL used for loading the exercise page from the exercise service or
     * for uploading a submission for grading
     * (service URL with GET query parameters).
     * @param string $submissionUrl value for the submission_url GET query argument
     * @param string|int $uid user ID, if many users form a group, the IDs should
     * be given in the format "1-2-3" (separated by dash)
     * @param int $submissionOrdinalNumber ordinal number of the submission that is
     * uploaded for grading or the submission for which the exercise description is downloaded
     * @param string $language language of the content of the page, e.g., 'en' for English
     * (lang query parameter in the grader protocol)
     * @return string
     */
    protected function buildServiceUrl($submissionUrl, $uid, $submissionOrdinalNumber, $language) {
        $query_data = array(
                'submission_url' => $submissionUrl,
                'post_url' => \mod_astra\urls\urls::newSubmissionHandler($this),
                'max_points' => $this->getMaxPoints(),
                'uid' => $uid,
                'ordinal_number' => $submissionOrdinalNumber,
                'lang' => $language,
        );
        return $this->getServiceUrl() .'?'. http_build_query($query_data, 'i_', '&');
    }
    
    public function getLoadUrl($userid, $submissionOrdinalNumber, $language) {
        return $this->buildServiceUrl(\mod_astra\urls\urls::asyncNewSubmission($this, $userid),
                $userid, $submissionOrdinalNumber, $language);
    }
    
    /**
     * Upload the submission to the exercise service for grading and store the results
     * if the submission is graded synchronously.
     * @param \mod_astra_submission $submission
     * @param bool $noPenalties
     * @param array $files submitted files. Associative array of stdClass objects that have fields
     * filename (original base name), filepath (full file path in Moodle, e.g. under /tmp)
     * and mimetype (e.g. "text/plain"). The array keys are the keys used in HTTP POST data.
     * If $files is null, this method reads the submission files from the database and
     * adds them to the upload automatically.
     * @param bool $deleteFiles if true and $files is a non-empty array, the files are
     * deleted here from the file system
     * @throws mod_astra\protocol\remote_page_exception if there are errors
     * in connecting to the server
     * @throws Exception if there are errors in handling the files
     * @return \mod_astra\protocol\exercise_page the feedback page
     */
    public function uploadSubmissionToService(\mod_astra_submission $submission, $noPenalties = false,
            array $files = null, $deleteFiles = false) {
        $sbmsData = $submission->getSubmissionData();
        if ($sbmsData !== null)
            $sbmsData = (array) $sbmsData;
        
        if (is_null($files)) {
            $deleteFiles = true;
            $files = $submission->prepareSubmissionFilesForUpload();
        }
        
        $courseConfig = mod_astra_course_config::getForCourseId(
                $submission->getExercise()->getExerciseRound()->getCourse()->courseid);
        $api_key = ($courseConfig ? $courseConfig->getApiKey() : null);
        if (empty($api_key)) {
            $api_key = null; // $courseConfig gives an empty string if not set
        }
        
        $language = current_language();
        
        $serviceUrl = $this->buildServiceUrl(\mod_astra\urls\urls::asyncGradeSubmission($submission),
                $submission->getRecord()->submitter, $submission->getCounter(), $language);
        try {
            $remotePage = new \mod_astra\protocol\remote_page(
                    $serviceUrl, true, $sbmsData, $files, $api_key);
        } catch (\mod_astra\protocol\remote_page_exception $e) {
            if ($deleteFiles) {
                foreach ($files as $f) {
                    @unlink($f->filepath);
                }
            }
            // error logging
            if ($e instanceof \mod_astra\protocol\service_connection_exception) {
                $event = \mod_astra\event\service_connection_failed::create(array(
                        'context' => context_module::instance($this->getExerciseRound()->getCourseModule()->id),
                        'other' => array(
                                'error' => $e->getMessage(),
                                'url' => $serviceUrl,
                                'objtable' => \mod_astra_submission::TABLE,
                                'objid' => $submission->getId(),
                        )
                ));
                $event->trigger();
            } else if ($e instanceof \mod_astra\protocol\exercise_service_exception) {
                $event = \mod_astra\event\exercise_service_failed::create(array(
                        'context' => context_module::instance($this->getExerciseRound()->getCourseModule()->id),
                        'other' => array(
                                'error' => $e->getMessage(),
                                'url' => $serviceUrl,
                                'objtable' => \mod_astra_submission::TABLE,
                                'objid' => $submission->getId(),
                        )
                ));
                $event->trigger();
            }
            throw $e;
        } // PHP 5.4 has no finally block
        
        $remotePage->setLearningObject($this);
        $page = $remotePage->loadFeedbackPage($this, $submission, $noPenalties);
        
        if ($deleteFiles) {
            foreach ($files as $f) {
                @unlink($f->filepath);
            }
        }
        return $page;
    }
    
    public function getMaxSubmissionsForStudent(stdClass $user) {
        $max = $this->getMaxSubmissions(); // zero means no limit
        $deviation = mod_astra_submission_limit_deviation::findDeviation($this->getId(), $user->id);
        if ($deviation !== null && $max !== 0) {
            return $max + $deviation->getExtraSubmissions();
        }
        return $max;
    }
    
    public function studentHasSubmissionsLeft(stdClass $user) {
        if ($this->getMaxSubmissions() == 0)
            return true;
        return $this->getSubmissionCountForStudent($user->id) < $this->getMaxSubmissionsForStudent($user);
    }
    
    public function studentHasAccess(stdClass $user, $when = null) {
        // check deadlines
        if ($when === null)
            $when = time();
        $exround = $this->getExerciseRound();
        if ($exround->isOpen($when) || $exround->isLateSubmissionOpen($when))
            return true;
        if ($exround->hasStarted($when)) {
            // check deviations
            $deviation = mod_astra_deadline_deviation::findDeviation($this->getId(), $user->id);
            if ($deviation !== null && $when <= $deviation->getNewDeadline()) {
                return true;
            }
        }
        return false;
    }
    
    public function isSubmissionAllowed(stdClass $user) {
        $context = context_module::instance($this->getExerciseRound()->getCourseModule()->id);
        if (has_capability('mod/astra:addinstance', $context, $user) ||
                has_capability('mod/astra:viewallsubmissions', $context, $user)) {
            // allow always for teachers
            return true;
        }
        if (!$this->studentHasAccess($user)) {
            return false;
        }
        if (!$this->studentHasSubmissionsLeft($user)) {
            return false;
        }
        return true;
    }
    
    /**
     * Generate a hash of this exercise for the user. The hash is based on
     * a secret key.
     * @param int $userid Moodle user ID of the user for whom the hash is generated
     * @return string
     */
    public function getAsyncHash($userid) {
        $secretkey = get_config(mod_astra_exercise_round::MODNAME, 'secretkey');
        if (empty($secretkey)) {
            throw new moodle_exception('nosecretkeyset', mod_astra_exercise_round::MODNAME);
        }
        $identifier = "$userid." . $this->getId();
        return \hash_hmac('sha256', $identifier, $secretkey);
    }
}