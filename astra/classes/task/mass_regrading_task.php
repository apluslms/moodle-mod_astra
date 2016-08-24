<?php

namespace mod_astra\task;

defined('MOODLE_INTERNAL') || die;

use \mod_astra\export\all_students_course_summary;

/**
 * Adhoc task (background job that runs immediately after being set up) for
 * uploading all/many submissions to the exercise service for regrading.
 */
class mass_regrading_task extends \core\task\adhoc_task {
    
    /**
     * Do the job.
     * Throw exceptions on errors (the job will be retried).
     */
    public function execute() {
        global $DB;
        
        $params = $this->get_custom_data();
        $exerciseIds       = $params->exercise_ids;
        $userIds           = $params->student_user_ids;
        $selectSubmissions = $params->submissions;
        $courseId          = $params->course_id;
        $submittedBefore   = $params->submitted_before;
        
        $courseSummary = new all_students_course_summary($courseId, $exerciseIds,
                $userIds, $submittedBefore, $selectSubmissions, false, true);
        $exercises = $courseSummary->getExercises();
        $submissionsByExercise = $courseSummary->getSubmissionsByExercise();
        
        foreach ($submissionsByExercise as $exRemoteKey => $students) {
            foreach ($students as $results) {
                if ($selectSubmissions == all_students_course_summary::SUBMISSIONS_BEST) {
                    $submissions = array($results['best']);
                } else if ($selectSubmissions == all_students_course_summary::SUBMISSIONS_LATEST) {
                    $submissions = array($results['submissions'][ \count($results['submissions']) - 1 ]);
                } else {
                    $submissions = $results['submissions'];
                }
                
                foreach ($submissions as $sbms) {
                    try {
                        // regrading - upload the submission to the exercise service
                        $exercises[$sbms->getRecord()->exerciseid]->uploadSubmissionToService($sbms);
                    } catch (\Exception $e) {
                        // ignore and move on to the next submission
                    }
                }
                
            }
        }
    }
}