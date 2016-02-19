<?php
namespace mod_stratumtwo\summary;

defined('MOODLE_INTERNAL') || die;

/**
 * Class to gather the results of one student in an exercise for templates.
 * The number of database queries is minimized to improve performance.
 * 
 * Derived from A+ (a-plus/exercise/presentation/summary.py).
 */
class user_exercise_summary {
    
    protected $user;
    protected $exercise;
    protected $submissionCount;
    protected $bestSubmission;
    protected $category;
    
    /**
     * Create a summary of a user's status in one exercise. 
     * If $generate is true, the summary is generated here. Otherwise, 
     * $submissionCount and $bestSubmission must have correct values and 
     * this method will not generate any database queries.
     * 
     * @param \mod_stratumtwo_exercise $ex
     * @param \stdClass $user
     * @param int $submissionCount
     * @param \mod_stratumtwo_submission $bestSubmission
     * @param \mod_stratumtwo_category $category category of the exercise, to avoid querying the database
     * @param bool $generate
     */
    public function __construct(\mod_stratumtwo_exercise $ex, $user, $submissionCount = 0,
            \mod_stratumtwo_submission $bestSubmission = null, \mod_stratumtwo_category $category = null,
            $generate = true) {
        $this->user = $user;
        $this->exercise = $ex;
        $this->submissionCount = $submissionCount;
        $this->bestSubmission = $bestSubmission;
        if (is_null($category)) {
            $this->category = $ex->getCategory();
        } else {
            $this->category = $category;
        }
        if ($generate) {
            $this->generate();
        }
    }
    
    protected function generate() {
        global $DB;
        
        // all submissions from the user in the exercise
        $submissions = $DB->get_recordset(\mod_stratumtwo_submission::TABLE, array(
                'submitter'  => $this->user->id,
                'exerciseid' => $this->exercise->getId(),
        ), '', 'id, status, exerciseid, grade, submissiontime');

        $this->submissionCount = 0;
        $this->bestSubmission = null;
        // find best submission and count
        foreach ($submissions as $record) {
            $sbms = new \mod_stratumtwo_submission($record);
            if ($this->bestSubmission === null || $sbms->getGrade() > $this->bestSubmission->getGrade() ||
                    ($sbms->getGrade() == $this->bestSubmission->getGrade() &&
                     $sbms->getSubmissionTime() < $this->bestSubmission->getSubmissionTime())) {
                //TODO is the grade of late submissions zero or not? (and submit limit)
                $this->bestSubmission = $sbms;
            }
            $this->submissionCount += 1;
        }

        $submissions->close();
    }
    
    public function getSubmissionCount() {
        return $this->submissionCount;
    }
    
    public function getPoints() {
        if (is_null($this->bestSubmission)) {
            return 0;
        } else {
            return $this->bestSubmission->getGrade();
        }
    }
    
    public function isMissingPoints() {
        $this->getPoints() < $this->exercise->getPointsToPass();
    }
    
    public function isPassed() {
        return !$this->isMissingPoints();
    }
    
    public function getBestSubmission() {
        return $this->bestSubmission;
    }
    
    public function getMaxPoints() {
        $this->exercise->getMaxPoints();
    }
    
    public function getRequiredPoints() {
        $this->exercise->getPointsToPass();
    }
    
    public function getPenalty() {
        if ($this->bestSubmission === null)
            return null;
        else
            return $this->bestSubmission->getLatePenaltyApplied();
    }
    
    public function isSubmitted() {
        return $this->submissionCount > 0;
    }
    
    public function getExercise() {
        return $this->exercise;
    }
    
    public function getExerciseCategory() {
        return $this->category;
    }
    
    public function getTemplateContext() {
        $grade = $this->getPoints();
        
        $ctx = new \stdClass();
        $ctx->submitted = $this->isSubmitted();
        $ctx->full_score = ($grade >= $this->getMaxPoints());
        $ctx->passed = $this->isPassed();
        $ctx->missing_points = $this->isMissingPoints();
        $ctx->points = $grade;
        $ctx->max = $this->getMaxPoints();
        $ctx->points_to_pass = $this->getRequiredPoints();
        return $ctx;
    }
}