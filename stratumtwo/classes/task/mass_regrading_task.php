<?php

namespace mod_stratumtwo\task;

defined('MOODLE_INTERNAL') || die;

/**
 * Adhoc task (background job that runs immediately after being set up) for
 * uploading all/many submissions to the exercise service for regrading.
 */
class mass_regrading_task extends \core\task\adhoc_task {
    // options for choosing which submissions are included in the mass regrading
    const SUBMISSIONS_ONLY_ERROR = 0; // only submissions with status error
    const SUBMISSIONS_ALL        = 1; // all submissions in included exercises
    const SUBMISSIONS_LATEST     = 2; // the latest submission of each student
    const SUBMISSIONS_BEST       = 3; // the best submission of each student
    
    /**
     * Do the job.
     * Throw exceptions on errors (the job will be retried).
     */
    public function execute() {
        global $DB;
        
        $params = $this->get_custom_data();
        $exerciseIds       = $params['exercise_ids'];
        $userIds           = $params['student_user_ids'];
        $selectSubmissions = $params['submissions'];
        $courseId          = $params['course_id'];
        
        if (empty($exerciseIds)) {
            // all exercises in the course
            $categories = mod_stratumtwo_category::getCategoriesInCourse($courseId, true);
            if (empty($categories)) {
                return; // no exercises, no submissions
            }
            $exerciseRecords = $DB->get_records_sql(
                    \mod_stratumtwo_learning_object::getSubtypeJoinSQL(mod_stratumtwo_exercise::TABLE) .
                    ' WHERE categoryid IN ('. \implode(',', \array_keys($categories)) .')');
        } else {
            $exerciseRecords = $DB->get_records_sql(
                    \mod_stratumtwo_learning_object::getSubtypeJoinSQL(mod_stratumtwo_exercise::TABLE) .
                    ' WHERE lob.id IN ('. \implode(',', $exerciseIds) .')');
        }
        $exercises = \array_map(function($exRecord) {
            return new \mod_stratumtwo_exercise($exRecord);
        }, $exerciseRecords);
        
        // build query for fetching relevant submissions from all students in one exercise at a time
        $where = 'exerciseid = ?';
        $params = array(0);
        if (!empty($userIds)) {
            $where .= ' AND submitter IN ('. \implode(',', $userIds) .')';
        }
        $orderBy = '';
        if ($selectSubmissions == self::SUBMISSIONS_ONLY_ERROR) {
            $where .= ' AND status = ?';
            $params[] = \mod_stratumtwo_submission::STATUS_ERROR;
        } else if ($selectSubmissions == self::SUBMISSIONS_BEST) {
            $orderBy = 'submitter ASC, grade DESC, submissiontime ASC';
        } else if ($selectSubmissions == self::SUBMISSIONS_LATEST) {
            $orderBy = 'submitter ASC, submissiontime DESC';
        } else {
            // all submissions
        }
        
        foreach ($exercises as $ex) {
            $params[0] = $ex->getId();
            $allSubmissions = $DB->get_recordset_select(\mod_stratumtwo_submission::TABLE,
                    $where, $params, $orderBy);
            $previousSubmitter = null;
            foreach ($allSubmissions as $sbmsRecord) {
                if ($selectSubmissions == self::SUBMISSIONS_BEST || $selectSubmissions == self::SUBMISSIONS_LATEST) {
                    // only regrade the first submission in the list for one student and
                    // loop forward to the next student, see $orderBy
                    if ($previousSubmitter == $sbmsRecord->submitter) {
                        continue;
                    } else {
                        $previousSubmitter = $sbmsRecord->submitter;
                    }
                }
                
                try {
                    // regrading - upload the submission to the exercise service
                    $ex->uploadSubmissionToService(new \mod_stratumtwo_submission($sbmsRecord));
                } catch (\Exception $e) {
                    // ignore and move on to the next submission
                }
            }
            
            $allSubmissions->close();
        }
    }
}