<?php
namespace mod_astra\summary;

defined('MOODLE_INTERNAL') || die;

/**
 * Class to gather the results of one student in an exercise round for templates.
 * The number of database queries is minimized to improve performance.
 * 
 * Derived from A+ (a-plus/exercise/presentation/summary.py).
 */
class user_module_summary {
    
    protected $exround;
    protected $user;
    protected $exerciseSummaries;
    protected $learningObjects;
    protected $latestSubmissionTime;
    
    /**
     * Create a summary of a user's status in one exercise round.
     * If $generate is true, the summary is generated here. Otherwise,
     * $exerciseSummaries must contain all the exercise summaries for the student
     * in this round and
     * this method will not generate any database queries. Likewise,
     * $learningObjects must contain the visible learning objects if $generate is false.
     *
     * @param \mod_astra_exercise_round $exround
     * @param \stdClass $user
     * @param array $exerciseSummaries array of user_exercise_summary objects
     * @param array $learningObjects array of \mod_astra_learning_object objects, i.e.,
     *        the visible exercises and chapters of the round
     * @param bool $generate
     */
    public function __construct(\mod_astra_exercise_round $exround, $user,
            array $exerciseSummaries = array(), array $learningObjects = array(),
            $generate = true) {
        $this->exround = $exround;
        $this->user = $user;
        $this->exerciseSummaries = $exerciseSummaries;
        $this->learningObjects = (empty($learningObjects) ?
                $learningObjects :
                \mod_astra_exercise_round::sortRoundLearningObjects($learningObjects));
        $this->latestSubmissionTime = 0; // time of the latest submission in the round from the user
        foreach ($exerciseSummaries as $exsummary) {
            // the best submissions are not necessarily the latest, but it does not matter
            // since the latest submission time is only used when the round summary is generated
            // here and this loop is then not used
            $best = $exsummary->getBestSubmission();
            if ($best !== null && $best->getSubmissionTime() > $this->latestSubmissionTime) {
                $this->latestSubmissionTime = $best->getSubmissionTime();
            }
        }
        
        if ($generate) {
            $this->generate();
        }
    }
    
    protected function generate() {
        global $DB;
        
        // all visible learning objects (exercises and chapters) in the round
        $lobjects = $this->exround->getLearningObjects(false, true);
        $this->learningObjects = $lobjects;
        $submissionsByExerciseId = array();
        $exerciseIds = array();
        foreach ($lobjects as $ex) {
            if ($ex->isSubmittable()) {
                $submissionsByExerciseId[$ex->getId()] = array(
                        'count' => 0, // number of submissions
                        'best'  => null, // best submission
                        'all'   => array(),
                );
                $exerciseIds[] = $ex->getId();
            }
        }
        
        // all submissions from the user in any visible exercise in the exercise round
        $sql = 
            'SELECT id, status, submissiontime, exerciseid, submitter, grader,
                 assistfeedback, grade, gradingtime, latepenaltyapplied, servicepoints, servicemaxpoints
             FROM {'. \mod_astra_submission::TABLE .'} '
           .'WHERE submitter = ? AND exerciseid IN ('. implode(',', $exerciseIds) .')
             ORDER BY submissiontime DESC';
        
        if (!empty($exerciseIds)) {
            $submissions = $DB->get_recordset_sql($sql, array($this->user->id));
            // find best submission for each exercise
            foreach ($submissions as $record) {
                $sbms = new \mod_astra_submission($record);
                $exerciseBest = &$submissionsByExerciseId[$record->exerciseid];
                $best = $exerciseBest['best'];
                if ($best === null || $sbms->getGrade() > $best->getGrade() || 
                        ($sbms->getGrade() == $best->getGrade() && 
                         $sbms->getSubmissionTime() < $best->getSubmissionTime())) {
                    $exerciseBest['best'] = $sbms;
                }
                $exerciseBest['count'] += 1;
                $exerciseBest['all'][] = $sbms;
                
                if ($sbms->getSubmissionTime() > $this->latestSubmissionTime) {
                    $this->latestSubmissionTime = $sbms->getSubmissionTime();
                }
            }
            
            $submissions->close();
        }
        
        // create exercise summary objects
        $this->exerciseSummaries = array();
        foreach ($lobjects as $ex) {
            if ($ex->isSubmittable()) {
                $this->exerciseSummaries[] = new user_exercise_summary($ex, $this->user,
                        $submissionsByExerciseId[$ex->getId()]['count'],
                        $submissionsByExerciseId[$ex->getId()]['best'],
                        $submissionsByExerciseId[$ex->getId()]['all'],
                        null, false);
            }
        }
    }
    
    public function getTotalSubmissionCount() {
        $totalSubmissionCount = 0;
        foreach ($this->exerciseSummaries as $exSummary) {
            $totalSubmissionCount += $exSummary->getSubmissionCount();
        }
        return $totalSubmissionCount;
    }
    
    public function getTotalPoints() {
        $points = 0;
        foreach ($this->exerciseSummaries as $exSummary) {
            $points += $exSummary->getPoints();
        }
        return $points;
    }
    
    public function getMaxPoints() {
        return $this->exround->getMaxPoints();
    }
    
    public function isMissingPoints() {
        return $this->getTotalPoints() < $this->exround->getPointsToPass();
    }
    
    public function getRequiredPoints() {
        return $this->exround->getPointsToPass();
    }
    
    public function isPassed() {
        if ($this->isMissingPoints()) {
            return false;
        } else {
            foreach ($this->exerciseSummaries as $exSummary) {
                if (!$exSummary->isPassed())
                    return false;
            }
            return true;
        }
    }
    
    public function getExerciseCount() {
        return \count($this->exerciseSummaries);
    }
    
    public function isSubmitted() {
        return $this->getTotalSubmissionCount() > 0;
    }
    
    public function getLatestSubmissionTime() {
        return $this->latestSubmissionTime;
    }
    
    public function getLearningObjects() {
        return $this->learningObjects;
    }
    
    public function getTemplateContext() {
        $totalPoints = $this->getTotalPoints();
        
        $ctx = new \stdClass();
        $ctx->submitted = $this->isSubmitted();
        $ctx->full_score = ($totalPoints >= $this->getMaxPoints());
        $ctx->passed = $this->isPassed();
        $ctx->missing_points = $this->isMissingPoints();
        $ctx->points = $totalPoints;
        $ctx->max = $this->getMaxPoints();
        $ctx->points_to_pass = $this->getRequiredPoints();
        $ctx->required = $this->getRequiredPoints();
        $ctx->percentage = ($ctx->max == 0) ? 100 : round(100 * $ctx->points / $ctx->max);
        $ctx->required_percentage = ($ctx->max == 0) ? 0 : round(100 * $ctx->required / $ctx->max);
        return $ctx;
    }
    
    public function getModulePointsPanelTemplateContext() {
        // intended for the module_contents variable in exercise_round.mustache
        $ctx = array();
        $exSummariesById = array();
        foreach ($this->exerciseSummaries as $exsum) {
            $exSummariesById[$exsum->getExercise()->getId()] = $exsum;
        }
        foreach ($this->learningObjects as $lobject) {
            $data = new \stdClass();
            if ($lobject->isSubmittable()) {
                $exerciseSummary = $exSummariesById[$lobject->getId()];
                $data->exercise = $lobject->getExerciseTemplateContext($this->user, false, false);
                $data->submissions = \mod_astra_exercise::submissionsTemplateContext($exerciseSummary->getSubmissions());
                $data->exercise_summary = $exerciseSummary->getTemplateContext();
            } else {
                $data->exercise = $lobject->getTemplateContext(false);
            }
            $ctx[] = $data;
        }
        
        return $ctx;
    }
}