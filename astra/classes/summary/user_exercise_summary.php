<?php
namespace mod_astra\summary;

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
    protected $submissions;
    protected $category;
    
    /**
     * Create a summary of a user's status in one exercise. 
     * If $generate is true, the summary is generated here. Otherwise, 
     * $submissionCount, $bestSubmission, and $submissions must have correct values and 
     * this method will not generate any database queries.
     * 
     * @param \mod_astra_exercise $ex
     * @param \stdClass $user
     * @param int $submissionCount
     * @param \mod_astra_submission $bestSubmission
     * @param array $submissions array of \mod_astra_submission objects,
     *        sorted by submission time (latest first)
     * @param \mod_astra_category $category category of the exercise, to avoid querying the database
     * @param bool $generate
     */
    public function __construct(\mod_astra_exercise $ex, $user, $submissionCount = 0,
            \mod_astra_submission $bestSubmission = null, array $submissions = null,
            \mod_astra_category $category = null, $generate = true) {
        $this->user = $user;
        $this->exercise = $ex;
        $this->submissionCount = $submissionCount;
        $this->bestSubmission = $bestSubmission;
        $this->submissions = $submissions;
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
        $submissions = $DB->get_recordset(\mod_astra_submission::TABLE, array(
                'submitter'  => $this->user->id,
                'exerciseid' => $this->exercise->getId(),
            ),
            'submissiontime DESC',
            'id, status, submissiontime, exerciseid, submitter, grader,' .
            'assistfeedback, grade, gradingtime, latepenaltyapplied, servicepoints, servicemaxpoints');

        $this->submissionCount = 0;
        $this->bestSubmission = null;
        $this->submissions = array();
        // find best submission and count
        foreach ($submissions as $record) {
            $sbms = new \mod_astra_submission($record);
            if ($this->bestSubmission === null || $sbms->getGrade() > $this->bestSubmission->getGrade() ||
                    ($sbms->getGrade() == $this->bestSubmission->getGrade() &&
                     $sbms->getSubmissionTime() < $this->bestSubmission->getSubmissionTime())) {
                $this->bestSubmission = $sbms;
            }
            $this->submissionCount += 1;
            $this->submissions[] = $sbms;
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
        return $this->getPoints() < $this->exercise->getPointsToPass();
    }
    
    public function isPassed() {
        return !$this->isMissingPoints();
    }
    
    public function getBestSubmission() {
        return $this->bestSubmission;
    }
    
    public function getSubmissions() {
        return $this->submissions;
    }
    
    public function getMaxPoints() {
        return $this->exercise->getMaxPoints();
    }
    
    public function getRequiredPoints() {
        return $this->exercise->getPointsToPass();
    }
    
    public function getPenalty() {
        if ($this->bestSubmission === null)
            return null;
        else
            return $this->bestSubmission->getLatePenaltyApplied();
    }
    
    public function getPenaltyPercentage() {
        $penalty = $this->getPenalty();
        if ($penalty === null)
            return null;
        else
            return (int) round($penalty * 100);
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

    public function hasAnySubmissionAssistantFeedback() {
        foreach ($this->submissions as $submission) {
            if ($submission->hasAssistantFeedback()) {
                return true;
            }
        }
        return false;
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
        $ctx->required = $this->getRequiredPoints();
        if ($ctx->max > 0) {
            $ctx->percentage = round(100 * $ctx->points / $ctx->max);
            $ctx->required_percentage = round(100 * $ctx->required / $ctx->max);
        } else {
            $ctx->percentage = 0;
            $ctx->required_percentage = 0;
        }
        $ctx->penaltyapplied = $this->getPenalty();
        $ctx->penaltyappliedpercent = $this->getPenaltyPercentage();
        $ctx->submission_count = $this->getSubmissionCount();
        $ctx->has_any_sbms_assist_feedback = $this->hasAnySubmissionAssistantFeedback();

        return $ctx;
    }
}