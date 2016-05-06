<?php

namespace mod_stratumtwo\export;

defined('MOODLE_INTERNAL') || die();

use \array_keys;
use \implode;

/** Functions for exporting course data (results/points, submitted files,
 * submitted form input, list of passed students).
 */
class export_data {
    /**
     * Return data of exercise results so that it can be encoded to JSON.
     * 
     * @param int $courseId
     *            Moodle course ID
     * @param array|null $exerciseIds
     *            exercise learning object IDs that should be included,
     *            null to include all exercises in the course (category totals also include only these exercises)
     * @param array|null $studentUserIds
     *            Moodle user IDs of the users that should be included,
     *            null for all users that have submitted
     * @param int $submittedBefore
     *            Unix timestamp. Take only submissions submitted at or before
     *            this time into account.
     * @param bool $includeAllSubmissions
     *            if true, a list of all submissions with their grades is
     *            included for each student and exercise in addition to the best grades. If false, only best
     *            grades are included for each student and exercise.
     * @return array exported data that can be encoded to JSON.
     */
    public static function export_results($courseId, array $exerciseIds = null, array $studentUserIds = null,
            $submittedBefore = 0, $includeAllSubmissions = true) {
        global $DB;
        
        $categories = \mod_stratumtwo_category::getCategoriesInCourse($courseId, true);
        $catIds = array_keys($categories);
        if (empty($catIds)) {
            return array(); // no exercises, no results
        }
        
        if (empty($exerciseIds)) {
            // all exercises in the course
            $exerciseRecords = $DB->get_records_sql(
                    \mod_stratumtwo_learning_object::getSubtypeJoinSQL(
                            \mod_stratumtwo_exercise::TABLE, 'lob.id, lob.categoryid, lob.remotekey') .
                    ' WHERE categoryid IN ('. implode(',', $catIds) .')');
            $exerciseIds = array_keys($exerciseRecords);
        } else {
            $exerciseRecords = $DB->get_records_sql(
                    \mod_stratumtwo_learning_object::getSubtypeJoinSQL(
                            \mod_stratumtwo_exercise::TABLE, 'lob.id, lob.categoryid, lob.remotekey') .
                    ' WHERE lob.id IN ('. implode(',', $exerciseIds) .')');
        }
        
        $categoriesByExRemoteKey = array(); // used later to look up categories
        foreach ($exerciseRecords as $ex) {
            foreach ($categories as $cat) {
                if ($cat->getId() == $ex->categoryid) {
                    $categoriesByExRemoteKey[$ex->remotekey] = $cat;
                    break;
                }
            }
        }
        
        // submission status to non-localized string for JSON
        $sbmsStatusToString = function ($status) {
            switch ($status) {
                case \mod_stratumtwo_submission::STATUS_INITIALIZED :
                    return 'initialized';
                case \mod_stratumtwo_submission::STATUS_WAITING :
                    return 'waiting';
                case \mod_stratumtwo_submission::STATUS_READY :
                    return 'ready';
                case \mod_stratumtwo_submission::STATUS_ERROR :
                    return 'error';
                default :
                    return 'undefined'; // should not happen
            }
        };
        
        // build SQL query for fetching all submissions to all exercises
        // (all exercises in the course or all from $exerciseIds)
        // (from all students or defined by $studentUserIds)
        $where = 's.exerciseid IN ('. implode(',', $exerciseIds) .')';
        $params = array();
        if (!empty($studentUserIds)) {
            $where .= ' AND s.submitter IN ('. implode(',', $studentUserIds) .')';
        }
        if (!empty($submittedBefore)) {
            // only count submissions submitted before the given time
            $where .= ' AND s.submissiontime <= ?';
            $params[] = $submittedBefore;
        }
        // one large DB query instead of repeating multiple small queries for each student and exercise
        $allSubmissions = $DB->get_recordset_sql(
            'SELECT s.id, s.status, s.submissiontime, s.exerciseid, s.submitter, s.grade, u.idnumber, u.username, lob.remotekey
               FROM {'. \mod_stratumtwo_submission::TABLE .'} s
               JOIN {user} u ON s.submitter = u.id
               JOIN {'. \mod_stratumtwo_learning_object::TABLE .'} lob ON s.exerciseid = lob.id
              WHERE ' . $where . 'ORDER BY s.submissiontime ASC',
                $params);
        
        $json = array(
                'students' => array(), 
        );
        foreach ($allSubmissions as $sbmsRecord) {
            // use student id or username as an identifier for the student
            if (empty($sbmsRecord->idnumber) || $sbmsRecord->idnumber === '(null)') {
                $studentId = $sbmsRecord->username;
            } else {
                $studentId = $sbmsRecord->idnumber;
            }
            
            if (!isset($json['students'][$studentId])) {
                $json['students'][$studentId] = array(
                        'exercises' => array(),
                        'categories' => array(), 
                );
            }
            
            // the exercise is completely missing for the student in the JSON if there are
            // no submissions
            if (!isset($json['students'][$studentId]['exercises'][$sbmsRecord->remotekey])) {
                // initialize
                $json['students'][$studentId]['exercises'][$sbmsRecord->remotekey] = array(
                        'points' => - 1, // replaced by any submission below (if best part) since 0 > -1
                        'submissiontime' => - 1,
                        'nth' => 0,
                        'numberofsubmissions' => 0 
                );
                if ($includeAllSubmissions) {
                    $json['students'][$studentId]['exercises'][$sbmsRecord->remotekey]['submissions'] = array();
                }
            }
            $best = &$json['students'][$studentId]['exercises'][$sbmsRecord->remotekey];
            if ($sbmsRecord->grade > $best['points']) {
                $best['points'] = (int) $sbmsRecord->grade;
                $best['submissiontime'] = (int) $sbmsRecord->submissiontime;
                $best['nth'] = $best['numberofsubmissions'] + 1; // $allSubmissions is ordered by submission time
                $best['id'] = (int) $sbmsRecord->id;
            }
            $best['numberofsubmissions'] += 1;
            unset($best);
            if ($includeAllSubmissions) {
                // list of points from all submissions to the exercise by this student
                $json['students'][$studentId]['exercises'][$sbmsRecord->remotekey]['submissions'][] = array(
                        'points' => (int) $sbmsRecord->grade,
                        'submissiontime' => (int) $sbmsRecord->submissiontime,
                        'status' => $sbmsStatusToString($sbmsRecord->status),
                        'id' => (int) $sbmsRecord->id,
                );
            }
        }
        $allSubmissions->close();
        
        foreach ($json['students'] as $studentId => $catsAndExercises) {
            $categoriesTotal = array(
                    'coursetotal' => 0,
            );
            foreach ($catsAndExercises['exercises'] as $exRemoteKey => $results) {
                $categoriesTotal['coursetotal'] += $results['points'];
                $catName = $categoriesByExRemoteKey[$exRemoteKey]->getName();
                if (isset($categoriesTotal[$catName])) {
                    $categoriesTotal[$catName] += $results['points'];
                } else {
                    $categoriesTotal[$catName] = $results['points'];
                }
            }
            $json['students'][$studentId]['categories'] = $categoriesTotal;
        }
        
        $json['numberofstudents'] = \count($json['students']);
        
        return $json;
        
        /* JSON structure:
        {
            "students": {
                "<student_id>" (idnumber or username): {
                    "exercises": {
                        "<remotekey>" (each exercise): {
                            "points": 10, (points of the best submission)
                            "submissiontime": Unix timestamp,
                            "nth": 1, (best submission was the first submission)
                            "numberofsubmissions": 5,
                            "id": 1, (database ID of the submission)
                            "submissions": [ (each submission listed if all submissions are included)
                                {
                                "points": 5,
                                "submissiontime": timestamp,
                                "status": "Ready",
                                "id": 1 (database ID of the submission)
                                }
                            ]
                        },
                    },
                    "categories": {
                        "<category1 name>": 50,
                        "coursetotal" (all categories summed): 100
                    }
                }
            },
            "numberofstudents": 5
        }
        */
    }
    
    /**
     * Return a list of students that have passed the course exercises (gained at least
     * minimum required points in all exercises, rounds and categories).
     * 
     * @param int $courseId
     *            Moodle course ID
     * @return string[] array of student ids (Moodle user idnumber if it exists or username otherwise)
     */
    public static function course_passed_list($courseId) {
        global $DB;
        
        // compute best points for each student in each exercise with minimal number of DB queries
        $results = self::export_results($courseId, null, null, 0, false);
        
        $categories = \mod_stratumtwo_category::getCategoriesInCourse($courseId);
        if (empty($categories)) {
            return array(); // no exercises, no results
        }
        
        $visibleExrounds = \mod_stratumtwo_exercise_round::getExerciseRoundsInCourse($courseId);
        $exrounds = array(); // organize by round id
        foreach ($visibleExrounds as $exround) {
            $exrounds[$exround->getId()] = $exround;
        }
        
        // all non-hidden exercises in the course
        $exerciseRecords = $DB->get_records_sql(\mod_stratumtwo_learning_object::getSubtypeJoinSQL(\mod_stratumtwo_exercise::TABLE) .
                ' WHERE categoryid IN ('. implode(',', array_keys($categories)) .') AND status != ?', array(
                \mod_stratumtwo_learning_object::STATUS_HIDDEN,
        ));
        $exercises = array();
        foreach ($exerciseRecords as $ex) {
            // check that the category and round are not hidden
            if (isset($categories[$ex->categoryid]) && isset($exrounds[$ex->roundid])) {
                $exercises[$ex->lobjectid] = new \mod_stratumtwo_exercise($ex);
            }
        }
        
        $passedAllExercisesAndRounds = function ($studentResults) use($exercises, $exrounds) {
            $roundTotals = array();
            foreach ($exercises as $ex) {
                if (isset($studentResults[$ex->getRemoteKey()])) {
                    // student's best points in the exercise
                    $points = $studentResults[$ex->getRemoteKey()]['points'];
                } else {
                    $points = 0;
                }
                if ($points < $ex->getPointsToPass()) {
                    return false; // one exercise failed
                }
                
                // add exercise points to round total
                $roundId = $ex->getRecord()->roundid;
                if (isset($roundTotals[$roundId])) {
                    $roundTotals[$roundId] += $points;
                } else {
                    $roundTotals[$roundId] = $points;
                }
            }
            
            foreach ($exrounds as $exround) {
                if ($roundTotals[$exround->getId()] < $exround->getPointsToPass()) {
                    return false; // one round failed
                }
            }
            return true;
        };
        $passedAllCategories = function ($studentResults) use($categories) {
            foreach ($categories as $cat) {
                if (isset($studentResults[$cat->getName()])) {
                    // student's best total points in the category
                    $points = $studentResults[$cat->getName()];
                } else {
                    $points = 0;
                }
                if ($points < $cat->getPointsToPass()) {
                    return false; // one category failed (category total points requirement failed)
                }
            }
            return true;
        };
        
        $passedStudents = array();
        foreach ($results['students'] as $studentId => $catsAndExercises) {
            if ($passedAllExercisesAndRounds($catsAndExercises['exercises']) &&
                    $passedAllCategories($catsAndExercises['categories'])) {
                $passedStudents[] = $studentId; // student id or username if no id exists
            }
        }
        
        return $passedStudents;
    }
    
    protected static function get_all_submissions_by_exercise($courseId, $exerciseIds,
            $studentUserIds, $submittedBefore, $includeAllSubmissions, $includeSubmissionData = false) {
        global $DB;
        
        $categories = \mod_stratumtwo_category::getCategoriesInCourse($courseId, true);
        $catIds = array_keys($categories);
        if (empty($catIds)) {
            return false; // no exercises, no results
        }
        
        if (empty($exerciseIds)) {
            // all exercises in the course
            $exerciseRecords = $DB->get_records_sql(
                    \mod_stratumtwo_learning_object::getSubtypeJoinSQL(\mod_stratumtwo_exercise::TABLE,
                            'lob.id, lob.categoryid, lob.remotekey') .
                    ' WHERE categoryid IN ('. implode (',', $catIds) .')');
            $exerciseIds = array_keys($exerciseRecords);
        } else {
            $exerciseRecords = $DB->get_records_sql(
                    \mod_stratumtwo_learning_object::getSubtypeJoinSQL(\mod_stratumtwo_exercise::TABLE,
                            'lob.id, lob.categoryid, lob.remotekey') .
                    ' WHERE lob.id IN ('. implode(',', $exerciseIds) .')');
        }
        
        // build SQL query for fetching all submissions to all exercises
        // (all exercises in the course or all from $exerciseIds)
        // (from all students or defined by $studentUserIds)
        $where = 's.exerciseid IN ('. implode(',', $exerciseIds) .')';
        $params = array();
        if (!empty($studentUserIds)) {
            $where .= ' AND s.submitter IN ('. implode(',', $studentUserIds) .')';
        }
        if (!empty($submittedBefore)) {
            // only count submissions submitted before the given time
            $where .= ' AND s.submissiontime <= ?';
            $params[] = $submittedBefore;
        }
        $submissionDataColumn = '';
        if ($includeSubmissionData) {
            $submissionDataColumn = ',s.submissiondata';
        }
        // one large DB query instead of repeating multiple small queries for each student and exercise
        $allSubmissions = $DB->get_recordset_sql(
            "SELECT s.id, s.status, s.submissiontime, s.exerciseid, s.submitter, s.grade, u.idnumber, u.username, lob.remotekey $submissionDataColumn 
               FROM {". \mod_stratumtwo_submission::TABLE .'} s
               JOIN {user} u ON s.submitter = u.id
               JOIN {'. \mod_stratumtwo_learning_object::TABLE .'} lob ON s.exerciseid = lob.id
              WHERE '. $where .'ORDER BY s.submissiontime ASC', $params);
        
        $submissionsByExercise = array(); // organize submissions
        foreach ($allSubmissions as $sbmsRecord) {
            if (empty($sbmsRecord->idnumber) || $sbmsRecord->idnumber === '(null)') {
                $studentId = $sbmsRecord->username;
            } else {
                $studentId = $sbmsRecord->idnumber;
            }
        
            if (!isset($submissionsByExercise[$sbmsRecord->remotekey])) {
                $submissionsByExercise[$sbmsRecord->remotekey] = array();
            }
            if (!isset($submissionsByExercise[$sbmsRecord->remotekey][$studentId])) {
                $submissionsByExercise[$sbmsRecord->remotekey][$studentId] = array();
            }
        
            $sbms = new \mod_stratumtwo_submission($sbmsRecord);
            if ($includeAllSubmissions) {
                $submissionsByExercise[$sbmsRecord->remotekey][$studentId][] = $sbms;
            } else if (empty($submissionsByExercise[$sbmsRecord->remotekey][$studentId])) { // only keep the best submission
                $submissionsByExercise[$sbmsRecord->remotekey][$studentId][] = $sbms;
            } else if ($submissionsByExercise[$sbmsRecord->remotekey][$studentId][0]->getGrade() < $sbms->getGrade()) {
                $submissionsByExercise[$sbmsRecord->remotekey][$studentId][0] = $sbms;
            }
        }
        $allSubmissions->close();
        
        return array($submissionsByExercise, $exerciseRecords);
    }
    
    /**
     * Create a zip archive file of the submitted files in the course.
     * 
     * @param int $courseId
     *            Moodle course ID
     * @param array|null $exerciseIds
     *            exercise learning object IDs that should be included,
     *            null to include all exercises in the course
     * @param array|null $studentUserIds
     *            Moodle user IDs of the users that should be included,
     *            null for all users that have submitted
     * @param int $submittedBefore
     *            Unix timestamp. Take only submissions submitted at or before
     *            this time into account.
     * @param bool $includeAllSubmissions
     *            if true, files from all submissions are included.
     *            If false, only files from the best submission for each student and exercise are included.
     * @return path of a temp file (the zip archive) - note this returned file does
     *         not have a .zip extension - it is a temp file.
     */
    public static function export_submitted_files($courseId, array $exerciseIds = null,
            array $studentUserIds = null, $submittedBefore = 0, $includeAllSubmissions = false) {
        global $CFG;
        require_once ($CFG->libdir . '/filestorage/zip_packer.php');
        
        list($submissionsByExercise, $exerciseRecords) = self::get_all_submissions_by_exercise(
                $courseId, $exerciseIds, $studentUserIds, $submittedBefore, $includeAllSubmissions);
        
        // fetch and organize submitted files that are included in the zip archive
        $filesForZipping = array();
        // gather all submitted files to the array
        // (key = file path inside archive, value = Moodle stored_file instance)
        foreach ($exerciseRecords as $ex) {
            $exRemoteKey = $ex->remotekey;
            if (isset($submissionsByExercise[$exRemoteKey])) {
                foreach ($submissionsByExercise[$exRemoteKey] as $studentId => $submissions) {
                    foreach ($submissions as $sbms) {
                        $sbmsId = $sbms->getId();
                        $submittedFiles = $sbms->getSubmittedFiles();
                        foreach ($submittedFiles as $file) {
                            $pathInArchive = "submitted_files/$exRemoteKey/$studentId/sbms$sbmsId/" . $file->get_filename();
                            if (isset($filesForZipping[$pathInArchive])) {
                                // if multiple files with the same name were submitted in the submission
                                $pathInArchive = "submitted_files/$exRemoteKey/$studentId/sbms$sbmsId/" .
                                        \trim($file->get_filepath(), '/') .'_'. $file->get_filename();
                            }
                            $filesForZipping[$pathInArchive] = $file; // Moodle stored_file
                        }
                    }
                }
            }
        }
        
        // Create path for new zip file.
        $tempzip = \tempnam($CFG->tempdir, 'stsbms');
        // Zip files.
        $zipper = new \zip_packer();
        if ($zipper->archive_to_pathname($filesForZipping, $tempzip)) {
            return $tempzip;
        }
        @\unlink($tempzip);
        return false;
        
        /*
        Directory structure: (varying parts in < >)
        submitted_files/
            <exercise_remote_key>/
                <student_id>/
                    sbms<ID>/
                        <file1>, <file2>
        */
    }
    
    /**
     * Create a JSON object of the submitted form input in the course.
     *
     * @param int $courseId
     *            Moodle course ID
     * @param array|null $exerciseIds
     *            exercise learning object IDs that should be included,
     *            null to include all exercises in the course
     * @param array|null $studentUserIds
     *            Moodle user IDs of the users that should be included,
     *            null for all users that have submitted
     * @param int $submittedBefore
     *            Unix timestamp. Take only submissions submitted at or before
     *            this time into account.
     * @param bool $includeAllSubmissions
     *            if true, files from all submissions are included.
     *            If false, only files from the best submission for each student and exercise are included.
     * @return array exported data that can be encoded as JSON
     */
    public static function export_submitted_form_input($courseId, array $exerciseIds = null,
            array $studentUserIds = null, $submittedBefore = 0, $includeAllSubmissions = false) {
        
        list($submissionsByExercise, $exerciseRecords) = self::get_all_submissions_by_exercise(
                $courseId, $exerciseIds, $studentUserIds, $submittedBefore, $includeAllSubmissions, true);
        
        $submittedFormInput = array();
        
        foreach ($submissionsByExercise as $exRemoteKey => $submissionsByStudent) {
            $submittedFormInput[$exRemoteKey] = array();
            foreach ($submissionsByStudent as $studentId => $submissions) {
                $submittedFormInput[$exRemoteKey][$studentId] = array();
                foreach ($submissions as $sbms) {
                    $submittedFormInput[$exRemoteKey][$studentId]['sbms'. $sbms->getId()] =
                            $sbms->getSubmissionData();
                }
            }
        }
        
        return $submittedFormInput;
        /* JSON structure:
        {
            <exercise remote key>: {
                <student id>: { (idnumber or username)
                    "sbms<id>": submission data (form input)
                }
            }
        }
        */
    }
    
    /**
     * Wrapper around standard library function json_last_error_msg,
     * which is not available in PHP 5.4 and earlier.
     */
    public static function json_last_error_msg() {
        if (!function_exists('json_last_error_msg')) { // added in PHP 5.5
            // Source: http://php.net/manual/en/function.json-last-error-msg.php#117393
            static $ERRORS = array(
                    \JSON_ERROR_NONE => 'No error',
                    \JSON_ERROR_DEPTH => 'Maximum stack depth exceeded',
                    \JSON_ERROR_STATE_MISMATCH => 'State mismatch (invalid or malformed JSON)',
                    \JSON_ERROR_CTRL_CHAR => 'Control character error, possibly incorrectly encoded',
                    \JSON_ERROR_SYNTAX => 'Syntax error',
                    \JSON_ERROR_UTF8 => 'Malformed UTF-8 characters, possibly incorrectly encoded',
            );
    
            $error = \json_last_error();
            return isset($ERRORS[$error]) ? $ERRORS[$error] : 'Unknown error';
        } else {
            return \json_last_error_msg();
        }
    }

}