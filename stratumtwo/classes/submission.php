<?php
defined('MOODLE_INTERNAL') || die();

/**
 * One submission to an exercise. Once the submission is graded, it has
 * feedback and a grade.
 */
class mod_stratumtwo_submission extends mod_stratumtwo_database_object {
    const TABLE = 'stratumtwo_submissions'; // database table name
    const SUBMITTED_FILES_FILEAREA = 'submittedfile'; // file area for Moodle file API
    const STATUS_INITIALIZED = 0; // not sent to the exercise service
    const STATUS_WAITING     = 1; // sent for grading
    const STATUS_READY       = 2; // graded
    const STATUS_ERROR       = 3;
    
    const SAFE_FILENAME_CHARS = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ._-0123456789';
    
    // cache of references to other records, used in corresponding getter methods
    protected $exercise = null;
    protected $submitter = null;
    protected $grader = null;
    
    public function getStatus($asString = false) {
        if ($asString) {
            switch ((int) $this->record->status) {
                case self::STATUS_INITIALIZED:
                    return get_string('statusinitialized', mod_stratumtwo_exercise_round::MODNAME);
                    break;
                case self::STATUS_WAITING:
                    return get_string('statuswaiting', mod_stratumtwo_exercise_round::MODNAME);
                    break;
                case self::STATUS_READY:
                    return get_string('statusready', mod_stratumtwo_exercise_round::MODNAME);
                    break;
                case self::STATUS_ERROR:
                    return get_string('statuserror', mod_stratumtwo_exercise_round::MODNAME);
                    break;
            }
        }
        return (int) $this->record->status;
    }
    
    public function getSubmissionTime() {
        return $this->record->submissiontime; // int, Unix timestamp
    }
    
    public function getHash() {
        return $this->record->hash;
    }
    
    public function getExercise() {
        if (is_null($this->exercise)) {
            $this->exercise = mod_stratumtwo_exercise::createFromId($this->record->exerciseid);
        }
        return $this->exercise; 
    }
    
    public function getSubmitter() {
        global $DB;
        if (is_null($this->submitter)) {
            $this->submitter = $DB->get_record('user', array('id' => $this->record->submitter), '*', MUST_EXIST);
        }
        return $this->submitter;
    }
    
    public function getSubmitterName() {
        $user = $this->getSubmitter();
        $name = fullname($user);
        if (empty($user->idnumber) || $user->idnumber === '(null)') {
            $name .= " ({$user->username})";
        } else {
            $name .= " ({$user->idnumber})";
        }
        return $name;
    }
    
    public function getGrader() {
        global $DB;
        if (empty($this->record->grader)) {
            return null;
        }
        if (is_null($this->grader)) {
            $this->grader = $DB->get_record('user', array('id' => $this->record->grader), '*', MUST_EXIST);
        }
        return $this->grader;
    }
    
    public function getFeedback() {
        return $this->record->feedback;
    }
    
    public function getAssistantFeedback() {
        return $this->record->assistfeedback;
    }
    
    public function getGrade() {
        return $this->record->grade; // points given to the submission
    }
    
    public function getGradingTime() {
        return $this->record->gradingtime; // int, Unix timestamp
    }
    
    public function getLatePenaltyApplied() {
        if (isset($this->record->latepenaltyapplied))
            return $this->record->latepenaltyapplied;
        return null;
    }
    
    public function getServicePoints() {
        return $this->record->servicepoints;
    }
    
    public function getServiceMaxPoints() {
        return $this->record->servicemaxpoints;
    }
    
    /**
     * Try to decode string $data as JSON.
     * @param string $data
     * @return string|mixed decoded JSON, or string if decoding fails, or
     * null if $data is empty.
     */
    public static function tryToDecodeJSON($data) {
        if (is_null($data) || $data === '') {
            // empty() considers "0" empty too, so avoid it
            return null;
        }
        // try to decode JSON
        $jsonObj = json_decode($data);
        if (is_null($jsonObj)) {
            // cannot decode, return the original string
            return $data;
        }
        return $jsonObj;
    }
    
    public function getSubmissionData() {
        return self::tryToDecodeJSON($this->record->submissiondata);
        
        /* If submissiondata is flattened before storing in the DB
        $data = self::tryToDecodeJSON($this->record->submissiondata);
        if (is_array($data)) {
            // flattened key-value pairs, convert back to nested arrays
            $nestedData = array();
            foreach ($data as $pair) {
                $key = $pair[0];
                $val = $pair[1];
                
                if (array_key_exists($key, $nestedData)) {
                    $nestedData[$key][] = $val;
                } else {
                    $nestedData[$key] = array($val);
                }
            }
            return $nestedData;
        }
        return $data;
        */
    }
    
    public function getGradingData() {
        return self::tryToDecodeJSON($this->record->gradingdata);
    }
    
    public function isGraded() {
        return $this->getStatus() === self::STATUS_READY;
    }
    
    public function setWaiting() {
        $this->record->status = self::STATUS_WAITING;
    }
    
    public function setReady() {
        $this->record->status = self::STATUS_READY;
    }
    
    public function setError() {
        $this->record->status = self::STATUS_ERROR;
    }
    
    public function setFeedback($newFeedback) {
        $this->record->feedback = $newFeedback;
    }
    
    public function setAssistantFeedback($newFeedback) {
        $this->record->assistfeedback = $newFeedback;
    }
    
    public function setGrader(\stdClass $user) {
        $this->record->grader = $user->id;
    }
    
    /**
     * Set points without setting any service points, scaling the value or
     * checking deadline and submission limit.
     * @param int $grade new grade for the submission
     */
    public function setRawGrade($grade) {
        $this->record->grade = $grade;
    }
    
    /**
     * Create a new submission to an exercise.
     * @param mod_stratumtwo_exercise $ex
     * @param int $submitterId ID of a Moodle user
     * @param array $submissionData associative array of submission data, e.g.,
     * form input (not files) from the user. Keys should be strings (form input names).
     * Null if there is no data.
     * @param int $status
     * @return int ID of the new submission record, zero on failure
     */
    public static function createNewSubmission(mod_stratumtwo_exercise $ex, $submitterId,
            $submissionData = null, $status = self::STATUS_INITIALIZED) {
        global $DB;
        $row = new stdClass();
        $row->status = $status;
        $row->submissiontime = time();
        $row->hash = self::getRandomString();
        $row->exerciseid = $ex->getId();
        $row->submitter = $submitterId;
        if ($submissionData === null) {
            $row->submissiondata = null;
        } else {
            $row->submissiondata = self::submissionDataToString($submissionData);
        }
        
        $id = $DB->insert_record(self::TABLE, $row);
        return $id; // 0 if failed
    }
    
    public static function safeFileName($filename) {
        $safechars = str_split(self::SAFE_FILENAME_CHARS);
        $safename = '';
        $len = strlen($filename);
        if ($len > 80) $len = 80;
        for ($i = 0; $i < $len; $i++) {
            if (in_array($filename[$i], $safechars)) {
                $safename .=  $filename[$i];
            }
        }
        if (empty($safename))
            return 'file';
        if ($safename[0] == '-') { // do not allow starting -
            return '_'. (substr($safename, 1) ?: '');
        }
        return $safename;
    }
    
    /**
     * Add a file (defined by $filePath, for example the file could first exist in /tmp/)
     * to this submission, i.e.,
     * create a new file in the Moodle file storage (for permanent storage).
     * @param string $fileName base name of the file without path (filename
     * that the user should see).
     * @param string $fileKey key to the file, e.g., name attribute in HTML form input.
     * The key should be unique within the files of this submission.
     * @param string $filePath full path to the file in the file system. This is
     * the file that is added to the submission.
     */
    public function addSubmittedFile($fileName, $fileKey, $filePath) {
        if (empty($fileName) || empty($fileKey)) {
            return; // sanity check, Moodle API checks that the file ($filePath) exists
        }
        $fs = \get_file_storage();
        // Prepare file record object
        $fileinfo = array(
                'contextid' => \context_module::instance($this->getExercise()->getExerciseRound()->getCourseModule()->id)->id,
                'component' => \mod_stratumtwo_exercise_round::MODNAME,
                'filearea'  => self::SUBMITTED_FILES_FILEAREA,
                'itemid'    => $this->getId(),
                'filepath'  => "/$fileKey/", // any path beginning and ending in /
                'filename'  => $fileName, // base name without path
        );
        
        // Create Moodle file from a file in the file system
        $fs->create_file_from_pathname($fileinfo, $filePath);
    }
    
    /**
     * Return an array of the files in this submission.
     * @return stored_file[] array of stored_files indexed by path name hash
     */
    public function getSubmittedFiles() {
        $fs = \get_file_storage();
        $files = $fs->get_area_files(\context_module::instance($this->getExercise()->getExerciseRound()->getCourseModule()->id)->id,
                \mod_stratumtwo_exercise_round::MODNAME,
                self::SUBMITTED_FILES_FILEAREA,
                $this->getId(), 'filepath, filename', false);
        return $files;
    }
    
    /**
     * Copy the submitted files of this submission to a temporary directory and
     * return the full file paths to those files (with original file base names and
     * mime types).
     * @throws \Exception if there are errors in file operations
     * @return stdClass[] array of objects, each has fields filename, filepath and
     * mimetype
     */
    public function prepareSubmissionFilesForUpload() {
        $stored_files = $this->getSubmittedFiles();
        $files = array();
        $error = null;
        foreach ($stored_files as $stored_file) {
            $obj = new stdClass();
            $obj->filename = $stored_file->get_filename(); // original name that user sees
            $obj->mimetype = $stored_file->get_mimetype();
    
            // to obtain a full path to the file in the file system, the Moodle
            // stored file has to be first copied to a temp directory
            $tempPath = $stored_file->copy_content_to_temp();
            if (empty($tempPath)) {
                $error = 'Copying Moodle stored file to a temporary path failed';
                break;
            }
            $obj->filepath = $tempPath;
    
            $key = substr($stored_file->get_filepath(), 1, -1); // remove the slashes / from the start and end
            if (empty($key)) {
                // this should not happen, since the path is always defined in method addSubmittedFile
                $error = 'No POST data key for file '. $obj->filename;
                break;
            }
            
            $files[$key] = $obj;
        }
        
        if (isset($error)) {
            // remove temp files created thus far
            foreach ($files as $f) {
                @unlink($f->filepath);
            }
            throw new \Exception($error);
        }
        
        return $files;
    }
    
    /**
     * Flatten an array. Returns a numerically indexed array of key-value pairs,
     * for example [[key1, val1], [key2, val2], [key1, val3]]. If the input data
     * uses the same key more than once (nested in inner arrays), the output
     * contains several pairs with the same key.
     * @param array $data
     * @return array
     */
    private static function flattenData(array $data, $outerKey = null) {
        $flat = array();
        foreach ($data as $key => $val) {
            if (is_array($val)) {
                $flat = array_merge($flat, self::flattenData($val, $key));
            } else {
                if (is_numeric($key) && is_string($outerKey)) {
                    $key = $outerKey;
                }
                $flat[] = array($key, $val);
            }
        }
        return $flat;
    }
    
    public static function submissionDataToString(array $submissionData) {
        //$flatData = self::flattenData($submissionData);
        // flattening is not a good idea due to the differences on how PHP and Django (mooc-grader)
        // handle arrays in POST data
        $json = json_encode($submissionData);
        if ($json === false)
            return null; // failed to encode
        return $json;
    }
    
    public static function getRandomString($length = 32) {
        // digits 0-9, alphabets a-z, A-Z
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $rmax = strlen($chars) - 1; // max value for rand, inclusive
        $res = '';
        for ($i = 0; $i < $length; $i++) {
            // randomly pick one character at a time
            $res .= substr($chars, mt_rand(0, $rmax), 1);
        }
        return $res;
    }
    
    public function delete() {
        global $DB;
        // delete submitted files from Moodle file API
        delete_area_files(context_module::instance($this->getExercise()->getExerciseRound()->getCourseModule()->id),
                mod_stratumtwo_exercise_round::MODNAME, self::SUBMITTED_FILES_FILEAREA,
                $this->record->id);
        
        $DB->delete_records(self::TABLE, array('id' => $this->record->id));
    }
    
    /**
     * Grade this submission (with machine-generated feedback).
     * @param int $servicePoints points from the exercise service
     * @param int $serviceMaxPoints max points used by the exercise service
     * @param string $feedback feedback to student in HTML
     * @param array $gradingData associative array of extra data about grading
     * @param bool $noPenalties if true, no deadline penalties are used
     */
    public function grade($servicePoints, $serviceMaxPoints, $feedback, $gradingData = null, $noPenalties = false) {
        $this->record->status = self::STATUS_READY;
        $this->record->feedback = $feedback;
        $this->record->gradingtime = time();
        $this->setPoints($servicePoints, $serviceMaxPoints);
        if ($gradingData === null) {
            $this->record->gradingdata = null;
        } else {
            $this->record->gradingdata = self::submissionDataToString($gradingData);
        }
        
        $this->save();
    }
    
    /**
     * Set the points for this submission. If the given maximum points are
     * different than the ones for the exercise this submission is for,
     * the points will be scaled.
     * 
     * The method also checks if the submission is late and if it is, by
     * default applies the late_submission_penalty set for the 
     * exercise round. If $noPenalties is true, the penalty is not applied.
     * 
     * The updated database record is not saved here.
     * 
     * @param int $points
     * @param int $maxPoints
     * @param bool $noPenalties
     */
    public function setPoints($points, $maxPoints, $noPenalties = false) {
        $exercise = $this->getExercise();
        $this->record->servicepoints = $points;
        $this->record->servicemaxpoints = $maxPoints;
        
        // Scale the given points to the maximum points for the exercise
        if ($maxPoints > 0) {
            $adjustedGrade = ($exercise->getMaxPoints() * $points / $maxPoints);
        } else {
            $adjustedGrade = 0.0;
        }
        
        // Check if this submission was done late. If it was, reduce the points
        // with late submission penalty. No less than 0 points are given. This
        // is not done if $noPenalties is true.
        if (!$noPenalties && $this->isLate()) {
            $exround = $exercise->getExerciseRound();
            if ($exround->isLateSubmissionAllowed() && !$this->isLateFromLateDeadline()) {
                $this->record->latepenaltyapplied = $exround->getLateSubmissionPenalty();
            } else {
                $this->record->latepenaltyapplied = 1; // zero points
            }
            $adjustedGrade -= ($adjustedGrade * $this->record->latepenaltyapplied);
        } else {
            $this->record->latepenaltyapplied = null;
        }
        
        $adjustedGrade = round($adjustedGrade);
        
        // check submit limit
        $submissions = $this->getExercise()->getSubmissionsForStudent($this->record->submitter);
        $count = 0;
        $thisIsBest = true;
        foreach ($submissions as $record) {
            if ($record->id != $this->record->id) {
                // count the ordinal number for this submission ("how many'th submission")
                if ($record->submissiontime <= $this->getSubmissionTime()) {
                    $count += 1;
                }
                // check if this submission is the best one
                if ($record->grade >= $adjustedGrade) {
                    $thisIsBest = false;
                }
            }
        }
        $submissions->close();
        $count += 1;
        $maxSubmissions = $this->getExercise()->getMaxSubmissions();
        if ($maxSubmissions > 0 && $count > $maxSubmissions) {
            // this submission exceeded the submission limit
            $this->record->grade = 0;
            if ($count == 1)
                $thisIsBest = true; // the only submission
            else
                $thisIsBest = false; // earlier submissions must be better, at least they were submitted earlier
        } else {
            $this->record->grade = $adjustedGrade;
        }
        // write to gradebook if this is the best submission
        if ($thisIsBest) {
            $this->writeToGradebook();
        }
    }
    
    /**
     * Check if this submission was submitted after the exercise round closing time.
     */
    public function isLate() {
        if ($this->getSubmissionTime() <= $this->getExercise()->getExerciseRound()->getClosingTime()) {
            return false;
        }
        //TODO deadline deviations/extensions for specific students
        return true;
    }
    
    /**
     * Check if this submission was submitted after the exercise round
     * late submission deadline (which should be later than the closing time).
     * @return boolean
     */
    public function isLateFromLateDeadline() {
        $lateSbmsDl = $this->getExercise()->getExerciseRound()->getLateSubmissionDeadline();
        if ($lateSbmsDl == 0) { // not set
            return false;
        }
        return $this->getSubmissionTime() > $lateSbmsDl;
        //TODO deviations
    }
    
    /**
     * Return a Moodle gradebook compatible grade object describing the grade
     * given to this submission.
     * @return stdClass grade object
     */
    public function getGradeObject() {
        $grade = new stdClass();
        $grade->rawgrade = $this->getGrade();
        $grade->userid = $this->record->submitter; // student
        // user ID of the grader: use the student's ID if the submission was graded only automatically
        $grade->usermodified = empty($this->record->grader) ? $this->record->submitter : $this->record->grader;
        $grade->dategraded = $this->getGradingTime(); // timestamp
        $grade->datesubmitted = $this->getSubmissionTime(); // timestamp
        return $grade;
    }
    
    /**
     * Write the grade of this submission to the Moodle gradebook.
     * @param bool $updateRoundGrade if true, the grade of the exercise round is updated too
     * @return int grade_update return value (one of GRADE_UPDATE_OK, GRADE_UPDATE_FAILED, 
     * GRADE_UPDATE_MULTIPLE or GRADE_UPDATE_ITEM_LOCKED)
     */
    public function writeToGradebook($updateRoundGrade = true) {
        global $CFG;
        require_once($CFG->libdir.'/gradelib.php');

        $ret =  grade_update('mod/'. mod_stratumtwo_exercise_round::TABLE,
                $this->getExercise()->getExerciseRound()->getCourse()->courseid,
                'mod',
                mod_stratumtwo_exercise_round::TABLE,
                $this->getExercise()->getExerciseRound()->getId(),
                $this->getExercise()->getGradebookItemNumber(),
                $this->getGradeObject(), null);
        
        if ($updateRoundGrade) {
            $this->getExercise()->getExerciseRound()->updateGradeForOneStudent($this->record->submitter);
        }
        
        return $ret;
    }
    
    public function getTemplateContext($includeFeedbackAndFiles = false, $includeSbmsAndGradingData = false) {
        $ctx = new stdClass();
        $ctx->url = \mod_stratumtwo\urls\urls::submission($this);
        $ctx->inspecturl = \mod_stratumtwo\urls\urls::inspectSubmission($this);
        $ctx->submission_time = $this->getSubmissionTime();
        //$ctx->nth = 1; // counting the ordinal number here would be too expensive,
        // since it has to query all submissions from the database
        $ctx->state = $this->getStatus(true);
        $grade = $this->getGrade();
        $ctx->submitted = true;
        $ctx->full_score = ($grade >= $this->getExercise()->getMaxPoints());
        $ctx->passed = ($grade >= $this->getExercise()->getPointsToPass());
        $ctx->missing_points = !$ctx->passed;
        $ctx->points = $grade;
        $ctx->max = $this->getExercise()->getMaxPoints();
        $ctx->points_to_pass = $this->getExercise()->getPointsToPass();
        $ctx->service_points = $this->getServicePoints();
        $ctx->service_max_points = $this->getServiceMaxPoints();
        $ctx->late_penalty_applied = $this->getLatePenaltyApplied();
        if ($ctx->late_penalty_applied !== null) {
            $ctx->late_penalty_applied_percent = (int) round($ctx->late_penalty_applied * 100);
        }
        $ctx->submitter_name = $this->getSubmitterName();
        
        if ($this->isGraded()) {
            $ctx->is_graded = true;
        } else {
            $ctx->status = $this->getStatus(true); // set status only for non-graded
            $ctx->is_graded = false;
        }
        
        if ($includeFeedbackAndFiles) {
            $ctx->files = $this->getFilesTemplateContext();
            $ctx->has_files = !empty($ctx->files);
            $ctx->feedback = $this->getFeedback();
            $ctx->assistant_feedback = $this->getAssistantFeedback();
        }
        
        if ($includeSbmsAndGradingData) {
            // decode JSON and encode again to obtain pretty-printed string
            $sbmsData = $this->getSubmissionData();
            if ($sbmsData !== null) {
                $sbmsData = json_encode($sbmsData, JSON_PRETTY_PRINT);
            }
            $gradingData = $this->getGradingData();
            if ($gradingData !== null) {
                $gradingData = json_encode($gradingData, JSON_PRETTY_PRINT);
            }
            $ctx->submission_data = $sbmsData;
            $ctx->grading_data = $gradingData;
        }
        
        return $ctx;
    }
    
    /**
     * Return true if a file of the given MIME type should be passed to the user
     * (i.e., it is a binary file, e.g., image, pdf).  
     * @param string $mimetype
     */
    public static function isFilePassed($mimetype) {
        // binary (submission) file types, which are not displayed in a dialog
        $FILE_PASS_MIME_TYPES = array( 'image/jpeg', 'image/png', 'image/gif', 'application/pdf' );
        // only PHP 5.6+ allows constant expressions in the definitions of class constants
        return in_array($mimetype, $FILE_PASS_MIME_TYPES);
    }
    
    public function getFilesTemplateContext() {
        $files = array();
        $moodleFiles = $this->getSubmittedFiles();
        foreach ($moodleFiles as $file) {
            $fileCtx = new stdClass();
            $url = \moodle_url::make_pluginfile_url($file->get_contextid(),
                    \mod_stratumtwo_exercise_round::MODNAME, self::SUBMITTED_FILES_FILEAREA,
                    $file->get_itemid(), $file->get_filepath(), $file->get_filename(), false);
            $url_forcedl = \moodle_url::make_pluginfile_url($file->get_contextid(),
                    \mod_stratumtwo_exercise_round::MODNAME, self::SUBMITTED_FILES_FILEAREA,
                    $file->get_itemid(), $file->get_filepath(), $file->get_filename(), true);
            $fileCtx->absolute_url = $url->out();
            $fileCtx->absolute_url_forcedl = $url_forcedl->out();
            $fileCtx->is_passed = self::isFilePassed($file->get_mimetype());
            $fileCtx->filename = $file->get_filename(); // basename, not full path
            $fileCtx->size = $file->get_filesize(); // int, in bytes
            $files[] = $fileCtx;
        }
        return $files;
    }
}