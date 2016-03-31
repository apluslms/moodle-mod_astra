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
}