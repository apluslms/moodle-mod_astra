<?php
defined('MOODLE_INTERNAL') || die();

/**
 * One submission to an exercise. Once the submission is graded, it has
 * feedback and a grade.
 */
class mod_stratumtwo_submission extends mod_stratumtwo_database_object {
    const TABLE = 'stratumtwo_submissions'; // database table name
    const STATUS_INITIALIZED = 0; // not sent to the exercise service
    const STATUS_WAITING     = 1; // sent for grading
    const STATUS_READY       = 2; // graded
    const STATUS_ERROR       = 3;
    
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
        if (is_null($this->$submitter)) {
            $this->submitter = $DB->get_record('user', array('id' => $this->record->submitter), '*', MUST_EXIST);
        }
        return $this->submitter;
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
        // TODO is cleaning needed with format_text() ?
        return $this->record->feedback;
    }
    
    public function getAssistantFeedback() {
        // TODO is cleaning needed with format_text() ?
        return $this->record->assistfeedback;
    }
    
    public function getGrade() {
        return $this->record->grade; // points given to the submission
    }
    
    public function getGradingTime() {
        return $this->record->gradingtime; // int, Unix timestamp
    }
    
    public function getLatePenaltyApplied() {
        return $this->record->latepenaltyapplied;
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
     * @return string|mixed decoded JSON or string if decoding fails.
     */
    public static function tryToDecodeJSON($data) {
        if (is_null($data) || $data === '') {
            // empty() considers "0" empty too, so avoid it
            return '';
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
    }
    
    public function getGradingData() {
        return self::tryToDecodeJSON($this->record->gradingdata);
    }
    
    public function isGraded() {
        return $this->getStatus() === self::STATUS_READY;
    }
    
    /**
     * Create a new submission to an exercise.
     * @param mod_stratumtwo_exercise $ex
     * @param int $submitterId ID of a Moodle user
     * @param string $submissionData
     * @param int $status
     * @return int ID of the new submission record, zero on failure
     */
    public static function createNewSubmission(mod_stratumtwo_exercise $ex, $submitterId,
            $submissionData, $status = self::STATUS_INITIALIZED) {
        global $DB;
        $row = new stdClass();
        $row->status = $status; //TODO is this needed here? async new submissions?
        $row->submissiontime = time();
        $row->hash = static::getRandomString();
        $row->exerciseid = $ex->getId();
        $row->submitter = $submitterId;
        //TODO $submissionData
        $row->submissiondata = $submissionData;
        
        $id = $DB->insert_record(static::TABLE, $row);
        return $id; // 0 if failed
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
    
    /**
     * Grade this submission (with machine-generated feedback).
     * @param int $servicePoints points from the exercise service
     * @param int $serviceMaxPoints max points used by the exercise service
     * @param string $feedback feedback to student in HTML
     * @param string $gradingData extra data about grading (in JSON)
     */
    public function grade($servicePoints, $serviceMaxPoints, $feedback, $gradingData = null) {
        $this->record->status = self::STATUS_READY;
        $this->record->feedback = $feedback;
        $this->record->gradingtime = time();
        $this->setPoints($servicePoints, $serviceMaxPoints);
        $this->record->gradingdata = $gradingData;
        
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
        $thisIsBest = false;
        foreach ($submissions as $record) {
            // count the ordinal number for this submission ("how many'th submission")
            if ($record->submissiontime <= $this->getSubmissionTime() && $record->id != $this->getId()) {
                $count += 1;
            }
            // check if this submission is the best one
            if ($record->grade < $adjustedGrade) {
                $thisIsBest = true;
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
                $this->getExercise()->getExerciseRound()->getCourse()->id,
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
    
    public function getTemplateContext() {
        $ctx = new stdClass();
        $ctx->url = (new moodle_url('/mod/'. mod_stratumtwo_exercise_round::TABLE . 
                '/submission.php', array('id' => $this->getId())))->out();
        $ctx->submission_time = $this->getSubmissionTime();
        //$ctx->nth = 1; // counting the ordinal number here would be too expensive,
        // since it has to query all submissions from the database
        
        if ($this->isGraded()) {
            $grade = $this->getGrade();
            $ctx->submitted = true;
            $ctx->full_score = ($grade >= $this->getExercise()->getMaxPoints());
            $ctx->passed = ($grade >= $this->getExercise()->getPointsToPass());
            $ctx->missing_points = !$ctx->passed;
            $ctx->points = $grade;
            $ctx->max = $this->getExercise()->getMaxPoints();
            $ctx->points_to_pass = $this->getExercise()->getPointsToPass();
        } else {
            $ctx->status = $this->getStatus(true);
        }
        return $ctx;
    }
}