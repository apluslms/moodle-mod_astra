<?php
namespace mod_stratumtwo\urls;

defined('MOODLE_INTERNAL') || die;

class urls {
    public static function baseURL() {
        global $CFG;
        return $CFG->wwwroot .'/mod/'. \mod_stratumtwo_exercise_round::TABLE;
    }
    
    public static function exerciseRound(\mod_stratumtwo_exercise_round $exround) {
        $query = array('id' => $exround->getCourseModule()->id);
        return self::baseURL() .'/view.php?'. \http_build_query($query, 'i_', '&');
    }
    
    public static function newSubmissionHandler(\mod_stratumtwo_exercise $ex) {
        // form POST target for new submissions
        return self::exercise($ex); // POST to the exercise page
    }
    
    public static function exercise(\mod_stratumtwo_exercise $ex) {
        $query = array('id' => $ex->getId());
        return self::baseURL() .'/exercise.php?'. \http_build_query($query, 'i_', '&');
    }
    
    public static function editExercise(\mod_stratumtwo_exercise $ex) {
        $query = array('id' => $ex->getId());
        return self::baseURL() .'/edit_exercise.php?'. \http_build_query($query, 'i_', '&');
    }
    
    public static function submission(\mod_stratumtwo_submission $sbms) {
        $query = array('id' => $sbms->getId());
        return self::baseURL() .'/submission.php?'. \http_build_query($query, 'i_', '&');
    }
    
    public static function inspectSubmission(\mod_stratumtwo_submission $sbms) {
        $query = array('id' => $sbms->getId());
        return self::baseURL() .'/inspect.php?'. \http_build_query($query, 'i_', '&');
    }
    
    public static function submissionList(\mod_stratumtwo_exercise $ex) {
        $query = array('id' => $ex->getId());
        return self::baseURL() .'/submission_list.php?'. \http_build_query($query, 'i_', '&');
    }
    
    public static function asyncGradeSubmission(\mod_stratumtwo_submission $sbms) {
        // exercise service HTTP POSTs grading results asynchronously to this URL
        $query = array(
                'id' => $sbms->getId(),
                'hash' => $sbms->getHash(),
        );
        return self::baseURL() .'/async/grade_submission.php?'. \http_build_query($query, 'i_', '&');
    }
    
    public static function asyncNewSubmission(\mod_stratumtwo_exercise $ex, $userid) {
        // URL for asynchronously creating a new graded submission
        $query = array(
                'id' => $ex->getId(),
                'hash' => $ex->getAsyncHash($userid),
                'userid' => $userid,
        );
        return self::baseURL() .'/async/new_submission.php?'. \http_build_query($query, 'i_', '&');
    }
}