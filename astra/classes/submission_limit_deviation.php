<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Student-specific submission limit deviation (extension) to an exercise.
 */
class mod_astra_submission_limit_deviation extends mod_astra_deviation_rule {
    const TABLE = 'astra_maxsbms_devs';
    
    public function getExtraSubmissions() {
        return (int) $this->record->extrasubmissions;
    }
    
    public function getTemplateContext() {
        $ctx = parent::getTemplateContext();
        $ctx->extra_submissions = $this->getExtraSubmissions();
        $ctx->remove_url = \mod_astra\urls\urls::deleteSubmissionLimitDeviation($this);
        return $ctx;
    }

    /**
     * Update this deviation.
     * @param int $extrasubmissions the new value for extra submissions
     * @return boolean
     */
    public function update(int $extrasubmissions) {
        global $DB;
        $this->record->extrasubmissions = $extrasubmissions;
        return $this->save();
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

    /**
     * Add more extra submissions to an existing deviation or
     * create a new deviation with the given extra submissions.
     * @param int $exerciseid (lobjectid)
     * @param int $userid Moodle user ID
     * @param int $extrasubmissions the number of extra submissions.
     * If a deviation already exists, this is added on top of that.
     * @return mod_astra_submission_limit_deviation
     */
    public static function createOrUpdate($exerciseid, $userid, int $extrasubmissions) {
        global $DB;

        $deviation = self::findDeviation($exerciseid, $userid);
        if ($deviation === null) {
            // Create new.
            $record = new stdClass();
            $record->submitter = $userid;
            $record->exerciseid = $exerciseid;
            $record->extrasubmissions = $extrasubmissions;
            $record->id = $DB->insert_record(self::TABLE, $record);
            return new self($record);
        } else {
            // Update existing.
            $newextra = $deviation->getExtraSubmissions() + $extrasubmissions;
            $deviation->update($newextra);
            return $deviation;
        }
    }
}