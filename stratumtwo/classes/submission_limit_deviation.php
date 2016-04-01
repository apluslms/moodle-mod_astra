<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Student-specific submission limit deviation (extension) to an exercise.
 */
class mod_stratumtwo_submission_limit_deviation extends mod_stratumtwo_deviation_rule {
    const TABLE = 'stratumtwo_maxsbms_devs';
    
    public function getExtraSubmissions() {
        return (int) $this->record->extrasubmissions;
    }
    
    public function getTemplateContext() {
        $ctx = parent::getTemplateContext();
        $ctx->extra_submissions = $this->getExtraSubmissions();
        $ctx->remove_url = \mod_stratumtwo\urls\urls::deleteSubmissionLimitDeviation($this);
        return $ctx;
    }
    
    public static function createNew($exerciseId, $userId, $extraSubmissions) {
        global $DB;
    
        if (self::findDeviation($exerciseId, $userId) === null) {
            // does not exist yet
            $record = new stdClass();
            $record->submitter = $userId;
            $record->exerciseid = $exerciseId;
            $record->extrasubmissions = $extraSubmissions;
            return $DB->insert_record(self::TABLE, $record);
        } else {
            // user already has a deviation in the exercise
            return null;
        }
    }
}