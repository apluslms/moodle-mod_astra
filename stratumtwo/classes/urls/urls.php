<?php
namespace mod_stratumtwo\urls;

defined('MOODLE_INTERNAL') || die;

class urls {
    public static function baseURL() {
        global $CFG;
        return $CFG->wwwroot .'/mod/'. \mod_stratumtwo_exercise_round::TABLE;
    }
    
    private static function buildUrl($path, array $query, $asMoodleUrl = false) {
        $url = new \moodle_url(self::baseURL() . $path, $query);
        if ($asMoodleUrl) {
            return $url;
        } else {
            return $url->out(); // string
        }
    }
    
    public static function exerciseRound(\mod_stratumtwo_exercise_round $exround, $asMdlUrl = false) {
        $query = array('id' => $exround->getCourseModule()->id);
        return self::buildUrl('/view.php', $query, $asMdlUrl);
    }
    
    public static function editExerciseRound(\mod_stratumtwo_exercise_round $exround, $asMdlUrl = false) {
        $query = array('id' => $exround->getId());
        return self::buildUrl('/teachers/edit_round.php', $query, $asMdlUrl);
    }
    
    public static function createExerciseRound($courseid, $asMdlUrl = false) {
        $query = array('course' => $courseid);
        return self::buildUrl('/teachers/edit_round.php', $query, $asMdlUrl);
    }
    
    public static function deleteExerciseRound(\mod_stratumtwo_exercise_round $exround, $asMdlUrl = false) {
        $query = array('id' => $exround->getId(), 'type' => 'round');
        return self::buildUrl('/teachers/delete.php', $query, $asMdlUrl);
    }
    
    public static function newSubmissionHandler(\mod_stratumtwo_exercise $ex, $asMdlUrl = false) {
        // form POST target for new submissions
        return self::exercise($ex, $asMdlUrl); // POST to the exercise page
    }
    
    public static function exercise(\mod_stratumtwo_exercise $ex, $asMdlUrl = false) {
        $query = array('id' => $ex->getId());
        return self::buildUrl('/exercise.php', $query, $asMdlUrl);
    }
    
    public static function editExercise(\mod_stratumtwo_exercise $ex, $asMdlUrl = false) {
        $query = array('id' => $ex->getId());
        return self::buildUrl('/teachers/edit_exercise.php', $query, $asMdlUrl);
    }
    
    public static function createExercise(\mod_stratumtwo_exercise_round $exround, $asMdlUrl = false) {
        $query = array('round' => $exround->getId());
        return self::buildUrl('/teachers/edit_exercise.php', $query, $asMdlUrl);
    }
    
    public static function deleteExercise(\mod_stratumtwo_exercise $ex, $asMdlUrl = false) {
        $query = array('id' => $ex->getId(), 'type' => 'exercise');
        return self::buildUrl('/teachers/delete.php', $query, $asMdlUrl);
    }
    
    public static function submission(\mod_stratumtwo_submission $sbms, $asMdlUrl = false) {
        $query = array('id' => $sbms->getId());
        return self::buildUrl('/submission.php', $query, $asMdlUrl);
    }
    
    public static function inspectSubmission(\mod_stratumtwo_submission $sbms, $asMdlUrl = false) {
        $query = array('id' => $sbms->getId());
        return self::buildUrl('/teachers/inspect.php', $query, $asMdlUrl);
    }
    
    public static function submissionList(\mod_stratumtwo_exercise $ex, $asMdlUrl = false) {
        $query = array('id' => $ex->getId());
        return self::buildUrl('/teachers/submission_list.php', $query, $asMdlUrl);
    }
    
    public static function assessSubmissionManually(\mod_stratumtwo_submission $sbms, $asMdlUrl = false) {
        $query = array('id' => $sbms->getId());
        return self::buildUrl('/teachers/assess_submission.php', $query, $asMdlUrl);
    }
    
    public static function resubmitToService(\mod_stratumtwo_submission $sbms, $asMdlUrl = false) {
        $query = array('id' => $sbms->getId());
        return self::buildUrl('/teachers/resubmit_submission.php', $query, $asMdlUrl);
    }
    
    public static function asyncGradeSubmission(\mod_stratumtwo_submission $sbms, $asMdlUrl = false) {
        // exercise service HTTP POSTs grading results asynchronously to this URL
        $query = array(
                'id' => $sbms->getId(),
                'hash' => $sbms->getHash(),
        );
        return self::buildUrl('/async/grade_submission.php', $query, $asMdlUrl);
    }
    
    public static function asyncNewSubmission(\mod_stratumtwo_exercise $ex, $userid, $asMdlUrl = false) {
        // URL for asynchronously creating a new graded submission
        $query = array(
                'id' => $ex->getId(),
                'hash' => $ex->getAsyncHash($userid),
                'userid' => $userid,
        );
        return self::buildUrl('/async/new_submission.php', $query, $asMdlUrl);
    }
    
    public static function editCategory(\mod_stratumtwo_category $cat, $asMdlUrl = false) {
        $query = array('id' => $cat->getId());
        return self::buildUrl('/teachers/edit_category.php', $query, $asMdlUrl);
    }
    
    public static function createCategory($courseid, $asMdlUrl = false) {
        $query = array('course' => $courseid);
        return self::buildUrl('/teachers/edit_category.php', $query, $asMdlUrl);
    }
    
    public static function deleteCategory(\mod_stratumtwo_category $cat, $asMdlUrl = false) {
        $query = array('id' => $cat->getId(), 'type' => 'category');
        return self::buildUrl('/teachers/delete.php', $query, $asMdlUrl);
    }
    
    public static function editCourse($courseid, $asMdlUrl = false) {
        $query = array('course' => $courseid);
        return self::buildUrl('/teachers/edit_course.php', $query, $asMdlUrl);
    }
    
    public static function autoSetup($courseid, $asMdlUrl = false) {
        $query = array('course' => $courseid);
        return self::buildUrl('/teachers/auto_setup.php', $query, $asMdlUrl);
    }
    
    public static function roundsIndex($courseid, $asMdlUrl = false) {
        $query = array('id' => $courseid);
        return self::buildUrl('/index.php', $query, $asMdlUrl);
    }
    
    public static function deviations($courseid, $asMdlUrl = false) {
        $query = array('course' => $courseid);
        return self::buildUrl('/teachers/deviations.php', $query, $asMdlUrl);
    }
    
    public static function addDeadlineDeviation($courseid, $asMdlUrl = false) {
        $query = array('course' => $courseid, 'type' => 'dl');
        return self::buildUrl('/teachers/add_deviation.php', $query, $asMdlUrl);
    }
    
    public static function addSubmissionLimitDeviation($courseid, $asMdlUrl = false) {
        $query = array('course' => $courseid, 'type' => 'submitlimit');
        return self::buildUrl('/teachers/add_deviation.php', $query, $asMdlUrl);
    }
    
    public static function deleteDeadlineDeviation(\mod_stratumtwo_deadline_deviation $dev, $asMdlUrl = false) {
        $query = array(
                'remove' => 'dl',
                'id' => $dev->getId(),
        );
        return self::buildUrl('/teachers/deviations.php', $query, $asMdlUrl);
    }
    
    public static function deleteSubmissionLimitDeviation(\mod_stratumtwo_submission_limit_deviation $dev, $asMdlUrl = false) {
        $query = array(
                'remove' => 'submitlimit',
                'id' => $dev->getId(),
        );
        return self::buildUrl('/teachers/deviations.php', $query, $asMdlUrl);
    }
}
