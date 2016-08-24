<?php

namespace mod_astra\export;

defined('MOODLE_INTERNAL') || die();

/** Functions for exporting course data (results/points, submitted files,
 * submitted form input, list of passed students).
 */
class export_data {
    
    protected $courseId;
    protected $exerciseIds;
    protected $studentUserIds;
    protected $submittedBefore;
    protected $includeSubmissions;
    
    protected $courseSummary; // all_students_course_summary object
    
    /**
     * Construct object for exporting course data with the given filters.
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
     * @param bool $includeSubmissions
     *            one of the SUBMISSIONS_* constants in all_students_course_summary class
     */
    public function __construct($courseId, array $exerciseIds = null, array $studentUserIds = null,
            $submittedBefore = 0, $includeSubmissions = all_students_course_summary::SUBMISSIONS_BEST) {
        $this->courseId = $courseId;
        $this->exerciseIds = $exerciseIds;
        $this->studentUserIds = $studentUserIds;
        $this->submittedBefore = $submittedBefore;
        $this->includeSubmissions = $includeSubmissions;
    }
    
    /**
     * Return data of exercise results so that it can be encoded to JSON.
     * @param bool $includeHiddenExercises if true and $exerciseIds in the constructor is null,
     * hidden exercises are included in the results
     * @return array exported data that can be encoded to JSON.
     */
    public function export_results($includeHiddenExercises = false) {
        
        $this->courseSummary = new all_students_course_summary($this->courseId, $this->exerciseIds,
                $this->studentUserIds, $this->submittedBefore, $this->includeSubmissions,
                $includeHiddenExercises, false);
        
        // submission status to non-localized string for JSON
        $sbmsStatusToString = function ($status) {
            switch ($status) {
                case \mod_astra_submission::STATUS_INITIALIZED :
                    return 'initialized';
                case \mod_astra_submission::STATUS_WAITING :
                    return 'waiting';
                case \mod_astra_submission::STATUS_READY :
                    return 'ready';
                case \mod_astra_submission::STATUS_ERROR :
                    return 'error';
                default :
                    return 'undefined'; // should not happen
            }
        };
        
        $json = array(
                'students' => array(), 
        );
        foreach ($this->courseSummary->getSubmissionsByExercise() as $exRemoteKey => $students) {
            foreach ($students as $userId => $results) {
                $studentId = $results['student_id'];
                if (!isset($json['students'][$studentId])) {
                    $json['students'][$studentId] = array(
                            'exercises' => array(),
                            'categories' => array(),
                            'rounds' => array(),
                    );
                }
                // the exercise is completely missing for the student in the JSON if there are
                // no submissions
                
                $numSubmissions = \count($results['submissions']);
                if ($this->includeSubmissions == all_students_course_summary::SUBMISSIONS_LATEST) {
                    $bestSbms = $results['submissions'][$numSubmissions - 1];
                } else {
                    $bestSbms = $results['best'];
                }
                
                $json['students'][$studentId]['exercises'][$exRemoteKey] = array(
                        'points' => $bestSbms->getGrade(),
                        'submissiontime' => $bestSbms->getSubmissionTime(),
                        'id' => $bestSbms->getId(),
                        'numberofsubmissions' => $numSubmissions,
                        'roundkey' => $results['roundkey'],
                );
                // include a list of all submissions in JSON
                if ($this->includeSubmissions == all_students_course_summary::SUBMISSIONS_ALL ||
                        $this->includeSubmissions == all_students_course_summary::SUBMISSIONS_ONLY_ERROR) {
                    $json['students'][$studentId]['exercises'][$exRemoteKey]['submissions'] = array();
                    foreach ($results['submissions'] as $sbms) {
                        $json['students'][$studentId]['exercises'][$exRemoteKey]['submissions'][] = array(
                                'points' => $sbms->getGrade(),
                                'submissiontime' => $sbms->getSubmissionTime(),
                                'status' => $sbmsStatusToString($sbms->getStatus()),
                                'id' => $sbms->getId(),
                        );
                    }
                }
            }
        }
        
        foreach ($this->courseSummary->getCategoryTotalsByStudent() as $studentId => $categoryPoints) {
            foreach ($categoryPoints as $catName => $points) {
                // includes cat name "coursetotal" = sum of all real categories
                $json['students'][$studentId]['categories'][$catName] = $points;
            }
        }
        
        $json['numberofstudents'] = \count($json['students']);
        
        // round total points
        foreach ($this->courseSummary->getRoundTotalsByStudent() as $studentId => $roundPoints) {
            foreach ($roundPoints as $roundKey => $points) {
                $json['students'][$studentId]['rounds'][$roundKey] = $points;
            }
        }
        
        return $json;
        
        /* JSON structure:
        {
            "students": {
                "<student_id>" (idnumber or username): {
                    "exercises": {
                        "<remotekey>" (each exercise): {
                            "points": 10, (points of the best submission)
                            "submissiontime": Unix timestamp,
                            "numberofsubmissions": 5,
                            "id": 1, (database ID of the submission)
                            "roundkey": "someround",
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
                        // total points include only the submissions/exercises that match the filters
                    },
                    "rounds": {
                        "<round remotekey>": 50, (total points in the round)
                    },
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
     * @return string[] array of student ids (Moodle user idnumber if it exists or username otherwise)
     */
    public function course_passed_list() {
        // compute best points for each student in each exercise with minimal number of DB queries
        $results = $this->export_results();
        
        $categories = $this->courseSummary->getCategories();
        if (empty($categories)) {
            return array(); // no exercises, no results
        }
        
        $visibleExrounds = \mod_astra_exercise_round::getExerciseRoundsInCourse($this->courseId);
        $exrounds = array(); // organize by round id
        foreach ($visibleExrounds as $exround) {
            $exrounds[$exround->getId()] = $exround;
        }
        
        // all non-hidden exercises in the course (assuming $this->exerciseIds is null)
        $exercises = $this->courseSummary->getExercises();
        
        $passedAllExercisesAndRounds = function ($studentResults) use ($exercises, $exrounds) {
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
        $passedAllCategories = function ($studentResults) use ($categories) {
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
    
    /**
     * Create a zip archive file of the submitted files in the course.
     * 
     * @param bool $includeHiddenExercises if true and $exerciseIds in the constructor is null,
     * hidden exercises are included in the archive
     * @return path of a temp file (the zip archive) - note this returned file does
     *         not have a .zip extension - it is a temp file.
     */
    public function export_submitted_files($includeHiddenExercises = false) {
        global $CFG;
        require_once ($CFG->libdir . '/filestorage/zip_packer.php');
        
        $this->courseSummary = new all_students_course_summary($this->courseId, $this->exerciseIds,
                $this->studentUserIds, $this->submittedBefore, $this->includeSubmissions,
                $includeHiddenExercises, false);
        
        $submissionsByExercise = $this->courseSummary->getSubmissionsByExercise();
        $exercises = $this->courseSummary->getExercises();
        
        // fetch and organize submitted files that are included in the zip archive
        $filesForZipping = array();
        // gather all submitted files to the array
        // (key = file path inside archive, value = Moodle stored_file instance)
        foreach ($exercises as $ex) {
            $exRemoteKey = $ex->getRemoteKey();
            if (isset($submissionsByExercise[$exRemoteKey])) {
                foreach ($submissionsByExercise[$exRemoteKey] as $userId => $results) {
                    if ($this->includeSubmissions == all_students_course_summary::SUBMISSIONS_BEST) {
                        $submissions = array($results['best']);
                    } else if ($this->includeSubmissions == all_students_course_summary::SUBMISSIONS_LATEST) {
                        $submissions = array($results['submissions'][ \count($results['submissions']) - 1 ]);
                    } else {
                        $submissions = $results['submissions'];
                    }
                    $studentId = $results['student_id'];
                    
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
     * @param bool $includeHiddenExercises if true and $exerciseIds in the constructor is null,
     * hidden exercises are included in the archive
     * @return array exported data that can be encoded as JSON
     */
    public function export_submitted_form_input($includeHiddenExercises = false) {
        
        $this->courseSummary = new all_students_course_summary($this->courseId, $this->exerciseIds,
                $this->studentUserIds, $this->submittedBefore, $this->includeSubmissions,
                $includeHiddenExercises, true);
        
        $submissionsByExercise = $this->courseSummary->getSubmissionsByExercise();
        $exercises = $this->courseSummary->getExercises();
        
        $submittedFormInput = array();
        
        foreach ($submissionsByExercise as $exRemoteKey => $submissionsByStudent) {
            $submittedFormInput[$exRemoteKey] = array();
            foreach ($submissionsByStudent as $userId => $results) {
                $studentId = $results['student_id'];
                $submittedFormInput[$exRemoteKey][$studentId] = array();
                
                if ($this->includeSubmissions == all_students_course_summary::SUBMISSIONS_BEST) {
                    $submissions = array($results['best']);
                } else if ($this->includeSubmissions == all_students_course_summary::SUBMISSIONS_LATEST) {
                    $submissions = array($results['submissions'][ \count($results['submissions']) - 1 ]);
                } else {
                    $submissions = $results['submissions'];
                }
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