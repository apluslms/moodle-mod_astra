<?php
namespace mod_stratumtwo\summary;

defined('MOODLE_INTERNAL') || die;

/**
 * Class to gather the results of one student in a course category for templates.
 * The number of database queries is minimized to improve performance.
 *
 * Derived from A+ (a-plus/exercise/presentation/summary.py).
 */
class user_category_summary {
    
    protected $user;
    protected $category;
    protected $exerciseSummaries;
    
    /**
     * Create a summary of a user's status in a category for templates.
     * If $generate is true, the summary is generated here. Otherwise,
     * $exerciseSummaries must contain all the exercise summaries for the student
     * in this category and this method will not generate any database queries.
     * 
     * @param \mod_stratumtwo_category $category
     * @param \stdClass $user
     * @param array $exerciseSummaries array of user_exercise_summary objects
     * @param bool $generate
     */
    public function __construct(\mod_stratumtwo_category $category, $user, 
            array $exerciseSummaries = array(), $generate = true) {
        $this->user = $user;
        $this->category = $category;
        $this->exerciseSummaries = $exerciseSummaries;
        
        if ($generate) {
            $this->generate();
        }
    }
    
    protected function generate() {
        // usually this is not needed, hence we save coding work by doing 
        // database queries for each exercise separately
        $exercises = $this->category->getExercises();
        foreach ($exercises as $ex) {
            $this->exerciseSummaries[] = new user_exercise_summary($ex, $this->user);
        }
    }
    
    public function getExerciseCount() {
        return \count($this->exerciseSummaries);
    }
    
    public function getMaxPoints() {
        $max = 0;
        foreach ($this->exerciseSummaries as $exSummary) {
            $max += $exSummary->getMaxPoints();
        }
        return $max;
    }
    
    public function getTotalPoints() {
        $points = 0;
        foreach ($this->exerciseSummaries as $exSummary) {
            $points += $exSummary->getPoints();
        }
        return $points;
    }
    
    public function getRequiredPoints() {
        return $this->category->getPointsToPass();
    }
    
    public function isMissingPoints() {
        return $this->getTotalPoints() < $this->getRequiredPoints();
    }
    
    public function isPassed() {
        if ($this->isMissingPoints()) {
            return false;
        }
        foreach ($this->exerciseSummaries as $exSummary) {
            if (!$exSummary->isPassed())
                return false;
        }
        return true;
    }
    
    public function getSubmissionCount() {
        $count = 0;
        foreach ($this->exerciseSummaries as $exSummary) {
            $count += $exSummary->getSubmissionCount();
        }
        return $count;
    }
    
    public function isSubmitted() {
        return $this->getSubmissionCount() > 0;
    }
}