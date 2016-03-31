<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Student-specific deadline deviation (extension) to an exercise.
 */
class mod_stratumtwo_deadline_deviation extends mod_stratumtwo_deviation_rule {
    const TABLE = 'stratumtwo_dl_deviations';
    
    public function getNormalDeadline() {
        return $this->getExercise()->getExerciseRound()->getClosingTime(); // Unix timestamp
    }
    
    public function getNormalLateSubmissionDeadline() {
        return $this->getExercise()->getExerciseRound()->getLateSubmissionDeadline(); // Unix timestamp
    }
    
    public function getExtraTime() {
        return (int) $this->record->extraminutes; // in minutes
    }
    
    public function getNewDeadline() {
        return self::addMinutesToTimestamp($this->getNormalDeadline(), $this->getExtraTime());
    }
    
    public function getNewLateSubmissionDeadline() {
        return self::addMinutesToTimestamp($this->getNormalLateSubmissionDeadline(), $this->getExtraTime());
    }
    
    protected static function addMinutesToTimestamp($timestamp, $minutes) {
        $time = new \DateTime('@'. $timestamp); // DateTime from a Unix timestamp
        $newTime = $time->add(new \DateInterval("PT{$minutes}M"));
        return $newTime->getTimestamp(); // int
    }
    
    /**
     * Return true if late penalty should be applied in this deviation rule,
     * false otherwise. If true, late penalty is applied when the student submits
     * after the original deadline. Otherwise, the late penalty is applied when the
     * student submits after the extended deadline and before the extended late submission
     * deadline. Submitting after the extended late submission deadline yields zero points.
     */
    public function useLatePenalty() {
        return !((bool) $this->record->withoutlatepenalty);
    }
}