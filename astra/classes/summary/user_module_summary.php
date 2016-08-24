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
    protected $latestSubmissionTime;
    
    /**
     * Create a summary of a user's status in one exercise round.
     * If $generate is true, the summary is generated here. Otherwise,
     * $exerciseSummaries must contain all the exercise summaries for the student
     * in this round and
     * this method will not generate any database queries.
     *
     * @param \mod_astra_exercise_round $exround
     * @param \stdClass $user
     * @param array $exerciseSummaries array of user_exercise_summary objects
     * @param bool $generate
     */
    public function __construct(\mod_astra_exercise_round $exround, $user,
            array $exerciseSummaries = array(), $generate = true) {
        $this->exround = $exround;
        $this->user = $user;
        $this->exerciseSummaries = $exerciseSummaries;
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
        
        // all exercises in the round, no chapters
        $exercises = $this->exround->getExercises();
        $submissionsByExerciseId = array();
        foreach ($exercises as $ex) {
            $submissionsByExerciseId[$ex->getId()] = array(
                    'count' => 0, // number of submissions
                    'best'  => null, // best submission
            );
        }
        
        // all submissions from the user in any exercise in the exercise round
        $sql = 
            'SELECT id, status, exerciseid, grade, submissiontime 
             FROM {'. \mod_astra_submission::TABLE .'} '
           .'WHERE submitter = ? AND exerciseid IN (' .
                 \mod_astra_learning_object::getSubtypeJoinSQL(\mod_astra_exercise::TABLE, 'lob.id') .
                 ' WHERE lob.roundid = ?)';
        $submissions = $DB->get_recordset_sql($sql, array($this->user->id, $this->exround->getId()));
        // find best submission for each exercise
        foreach ($submissions as $record) {
            $sbms = new \mod_astra_submission($record);
            $exerciseBest = &$submissionsByExerciseId[$record->exerciseid];
            $count = $exerciseBest['count'];
            $best = $exerciseBest['best'];
            if ($best === null || $sbms->getGrade() > $best->getGrade() || 
                    ($sbms->getGrade() == $best->getGrade() && 
                     $sbms->getSubmissionTime() < $best->getSubmissionTime())) {
                $exerciseBest['best'] = $sbms;
            }
            $exerciseBest['count'] += 1;
            
            if ($sbms->getSubmissionTime() > $this->latestSubmissionTime) {
                $this->latestSubmissionTime = $sbms->getSubmissionTime();
            }
        }
        
        $submissions->close();
        
        // create exercise summary objects
        $this->exerciseSummaries = array();
        foreach ($exercises as $ex) {
            
            $this->exerciseSummaries[] = new user_exercise_summary($ex, $this->user, 
                    $submissionsByExerciseId[$ex->getId()]['count'], 
                    $submissionsByExerciseId[$ex->getId()]['best'], 
                    null, false);
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
    
    public function getExercisesByCategoriesTemplateContext() {
        $catContexts = array();
        $len = 0;
        /* The exerciseSummaries are in the order they should be displayed, but they
         * must be grouped by categories here. The same category may be repeated more than
         * once as the exercises are kept in order. For example, if three exercises have 
         * categories catA, catB, and catA in this order, then catA is repeated twice. 
         * The first catA only includes the first exercise.
         */
        foreach ($this->exerciseSummaries as $exSummary) {
            $cat = $exSummary->getExerciseCategory();
            if (!$cat->isHidden()) {
                // prepare category context (a category contains at least one exercise)
                if ($len === 0 || $cat->getId() != $catContexts[$len - 1]['id']) {
                    $catCtx = array(
                            'id' => $cat->getId(),
                            'name' => $cat->getName(),
                            'exercise_summaries' => array(),
                    );
                    $catContexts[] = $catCtx;
                    $len++;
                }
            
                // exercise context
                $exSumCtx = array(
                    'exercise' => $exSummary->getExercise()->getExerciseTemplateContext($this->user),
                    'exercise_summary' => $exSummary->getTemplateContext(),
                );
                // append under the category context
                $catContexts[$len - 1]['exercise_summaries'][] = $exSumCtx;
            }
        }
        
        return $catContexts;
    }
}