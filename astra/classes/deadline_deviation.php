<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Student-specific deadline deviation (extension) to an exercise.
 */
class mod_astra_deadline_deviation extends mod_astra_deviation_rule {
    const TABLE = 'astra_dl_deviations';
    
    public function getNormalDeadline() {
        return $this->getExercise()->getExerciseRound()->getClosingTime(); // Unix timestamp
    }
    
    public function getExtraTime() {
        return (int) $this->record->extraminutes; // in minutes
    }
    
    public function getNewDeadline() {
        return self::addMinutesToTimestamp($this->getNormalDeadline(), $this->getExtraTime());
    }
    
    protected static function addMinutesToTimestamp($timestamp, $minutes) {
        $time = new \DateTime('@'. $timestamp); // DateTime from a Unix timestamp
        $newTime = $time->add(new \DateInterval("PT{$minutes}M"));
        return $newTime->getTimestamp(); // int
    }
    
    /**
     * Return true if late penalty should be applied in this deviation rule,
     * false otherwise. If true, late penalty is applied when the student submits
     * after the original deadline and before the extended deadline. Otherwise,
     * the late penalty is not applied during the extension at all.
     * Submitting after the extended deadline yields zero points, unless normal
     * late submissions are still open and enabled, in which case the late
     * penalty is applied normally.
     */
    public function useLatePenalty() {
        return !((bool) $this->record->withoutlatepenalty);
    }
    
    public function getTemplateContext() {
        $ctx = parent::getTemplateContext();
        $ctx->extra_minutes = $this->getExtraTime();
        $ctx->without_late_penalty = ($this->useLatePenalty() ? 'false' : 'true');
        $ctx->remove_url = \mod_astra\urls\urls::deleteDeadlineDeviation($this);
        return $ctx;
    }
    
    public static function createNew($exerciseId, $userId, $extraMinutes, $withoutLatePenalty) {
        global $DB;
        
        if (self::findDeviation($exerciseId, $userId) === null) {
            // does not exist yet
            $record = new stdClass();
            $record->submitter = $userId;
            $record->exerciseid = $exerciseId;
            $record->extraminutes = $extraMinutes;
            $record->withoutlatepenalty = (int) $withoutLatePenalty;
            return $DB->insert_record(self::TABLE, $record);
        } else {
            // user already has a deviation in the exercise
            return null;
        }
    }
}