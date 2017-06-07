<?php

namespace mod_astra\export;

defined('MOODLE_INTERNAL') || die();

use \implode;
use \array_keys;

class all_students_course_summary {
    
    // options for choosing which submissions are included in the summary
    const SUBMISSIONS_BEST       = 0; // the best submission of each student in each exercise
    const SUBMISSIONS_LATEST     = 1; // the latest submission of each student in each exercise
    const SUBMISSIONS_ALL        = 2; // all submissions in included exercises
    const SUBMISSIONS_ONLY_ERROR = 3; // only submissions with status error
    
    protected $courseId;
    protected $exerciseIds;
    protected $exercises; // mod_astra_exercise objects
    protected $studentUserIds;
    protected $submittedBefore;
    protected $includeSubmissions;
    protected $categories; // mod_astra_category objects
    
    protected $submissionsByExercise;
    protected $categoryTotalsByStudent;
    protected $roundTotalsByStudent;
    
    /**
     * Construct a new summary object with the given filters.
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
     *            one of the SUBMISSIONS_* constants in this class
     * @param bool $includeHidden if true and $exerciseIds is null, hidden exercises are included
     * @param bool $includeSbmsData if true, submission objects include the submissiondata field
     */
    public function __construct($courseId, array $exerciseIds = null, array $studentUserIds = null,
            $submittedBefore = 0, $includeSubmissions = self::SUBMISSIONS_BEST, $includeHidden = false,
            $includeSbmsData = false) {
        global $DB;
        
        $this->courseId = $courseId;
        $this->exerciseIds = $exerciseIds;
        $this->studentUserIds = $studentUserIds;
        $this->submittedBefore = $submittedBefore;
        $this->includeSubmissions = $includeSubmissions;
        
        // fetch exercises and categories from the database
        if ($exerciseIds === null) {
            // all exercises in the course (including hidden if $includeHidden is true)
            // get categories first
            $this->categories = \mod_astra_category::getCategoriesInCourse($courseId, $includeHidden);
            if (empty($this->categories)) {
                $exerciseRecords = array();
            } else {
                // category visibility was checked, check exercise status here if necessary
                $exerciseRecords = $DB->get_records_sql(
                    \mod_astra_learning_object::getSubtypeJoinSQL(\mod_astra_exercise::TABLE) .
                    ' WHERE lob.categoryid IN ('. implode(',', array_keys($this->categories)) .')' .
                        ($includeHidden ? '' : ' AND lob.status != :exstatus'),
                        array('exstatus' => \mod_astra_learning_object::STATUS_HIDDEN));
            }
            $roundRecords = $DB->get_records(\mod_astra_exercise_round::TABLE, array('course' => $courseId));
            
            $this->exerciseIds = array();
            $this->exercises = array();
            foreach ($exerciseRecords as $rec) {
                // check that the round is not hidden, if necessary
                if ($includeHidden || $roundRecords[$rec->roundid]->status != \mod_astra_exercise_round::STATUS_HIDDEN) {
                    $this->exerciseIds[] = $rec->lobjectid;
                    $this->exercises[$rec->lobjectid] = new \mod_astra_exercise($rec);
                }
            }
            
        } else if (!empty($exerciseIds)) {
            // cannot have an empty array with the SQL IN operator
            $exerciseRecords = $DB->get_records_sql(
                    \mod_astra_learning_object::getSubtypeJoinSQL(\mod_astra_exercise::TABLE) .
                    ' WHERE lob.id IN ('. implode(',', $exerciseIds) .')');
            $this->exercises = array();
            foreach ($exerciseRecords as $rec) {
                $this->exercises[$rec->lobjectid] = new \mod_astra_exercise($rec);
            }
            
            $catIds = \array_map(function($ex) {
                return $ex->categoryid;
            }, $exerciseRecords);
            $catRecords = $DB->get_records_list(\mod_astra_category::TABLE, 'id', $catIds);
            $this->categories = array();
            foreach ($catRecords as $id => $rec) {
                $this->categories[$id] = new \mod_astra_category($rec);
            }
        } else {
            $this->exercises = array();
            $this->categories = array();
        }
        
        if (!empty($this->exerciseIds)) {
            $this->generate($includeSbmsData);
        }
    }
    
    private function generate($includeSubmissionData = false) {
        global $DB;
        
        // build SQL query for fetching all submissions to all exercises
        $where = 's.exerciseid IN ('. implode(',', $this->exerciseIds) .')';
        $params = array();
        if (!empty($this->studentUserIds)) {
            $where .= ' AND s.submitter IN ('. implode(',', $this->studentUserIds) .')';
        }
        if (!empty($this->submittedBefore)) {
            // only count submissions submitted before the given time
            $where .= ' AND s.submissiontime <= ?';
            $params[] = $this->submittedBefore;
        }
        if ($this->includeSubmissions == self::SUBMISSIONS_ONLY_ERROR) {
            $where .= ' AND s.status = ?';
            $params[] = \mod_astra_submission::STATUS_ERROR;
        }
        $sbmsDataColumn = '';
        if ($includeSubmissionData) {
            $sbmsDataColumn = ',s.submissiondata';
        }
        
        $submissionsByExercise = array(); // organize submissions by exercise (learning object id) and submitter (user id)
        
        // one large DB query instead of repeating multiple small queries for each student and exercise
        $allSubmissions = $DB->get_recordset_sql(
            "SELECT s.id, s.status, s.submissiontime, s.exerciseid, s.submitter, s.grade, s.hash, u.idnumber, u.username, 
                    lob.remotekey, round.remotekey AS roundkey $sbmsDataColumn 
               FROM {". \mod_astra_submission::TABLE .'} s
               JOIN {user} u ON s.submitter = u.id
               JOIN {'. \mod_astra_learning_object::TABLE .'} lob ON s.exerciseid = lob.id
               JOIN {'. \mod_astra_exercise_round::TABLE .'} round ON lob.roundid = round.id
              WHERE ' . $where . 'ORDER BY s.submissiontime ASC',
                $params);
        foreach ($allSubmissions as $sbmsRecord) {
            /*
            $submissionsByExercise[exercise lobjectid][student user id] = array(
                'best' => submission object (best points),
                'student_id' => idnumber or username,
                'submissions' => array of submission objects, in the order they were submitted
                                 (latest at the end)
            */
            if (!isset($submissionsByExercise[$sbmsRecord->exerciseid])) {
                $submissionsByExercise[$sbmsRecord->exerciseid] = array();
            }
            $sbms = new \mod_astra_submission($sbmsRecord);
            
            if (!isset($submissionsByExercise[$sbmsRecord->exerciseid][$sbmsRecord->submitter])) {
                if (empty($sbmsRecord->idnumber) || $sbmsRecord->idnumber === '(null)') {
                    $studentId = $sbmsRecord->username;
                } else {
                    $studentId = $sbmsRecord->idnumber;
                }
                $submissionsByExercise[$sbmsRecord->exerciseid][$sbmsRecord->submitter] = array(
                        'best' => $sbms,
                        'student_id' => $studentId,
                        'submissions' => array(),
                        'roundkey' => $sbmsRecord->roundkey,
                        'exkey' => $sbmsRecord->remotekey, // learning object remote key
                );
            }
            
            $submissionsByExercise[$sbmsRecord->exerciseid][$sbmsRecord->submitter]['submissions'][] = $sbms;
            if ($submissionsByExercise[$sbmsRecord->exerciseid][$sbmsRecord->submitter]['best']->getGrade() <
                    $sbms->getGrade()) {
                $submissionsByExercise[$sbmsRecord->exerciseid][$sbmsRecord->submitter]['best'] = $sbms;
            }
        }
        
        $allSubmissions->close();
        $this->submissionsByExercise = $submissionsByExercise;
        
        $categoriesByExerciseId = array(); // used later to look up categories
        foreach ($this->exercises as $ex) {
            foreach ($this->categories as $cat) {
                if ($cat->getId() == $ex->getCategoryId()) {
                    $categoriesByExerciseId[$ex->getId()] = $cat;
                    break;
                }
            }
        }
        
        // compute category totals
        $categoryTotalsByStudent = array();
        foreach ($submissionsByExercise as $exerciseId => $students) {
            $catName = $categoriesByExerciseId[$exerciseId]->getName();
            foreach ($students as $userId => $results) {
                $studentId = $results['student_id'];
                if (!isset($categoryTotalsByStudent[$studentId])) {
                    $categoryTotalsByStudent[$studentId] = array(
                            'coursetotal' => 0,
                    );
                }
                if (!isset($categoryTotalsByStudent[$studentId][$catName])) {
                    $categoryTotalsByStudent[$studentId][$catName] = 0;
                }
                $points = $results['best']->getGrade();
                $categoryTotalsByStudent[$studentId][$catName] += $points;
                $categoryTotalsByStudent[$studentId]['coursetotal'] += $points;
            }
        }
        $this->categoryTotalsByStudent = $categoryTotalsByStudent;
        
        // compute round totals
        $roundTotalsByStudent = array();
        foreach ($submissionsByExercise as $exerciseId => $students) {
            foreach ($students as $userId => $results) {
                $studentId = $results['student_id'];
                $points = $results['best']->getGrade();
                $roundKey = $results['roundkey'];
                if (!isset($roundTotalsByStudent[$studentId])) {
                    $roundTotalsByStudent[$studentId] = array();
                }
                if (!isset($roundTotalsByStudent[$studentId][$roundKey])) {
                    $roundTotalsByStudent[$studentId][$roundKey] = 0;
                }
                $roundTotalsByStudent[$studentId][$roundKey] += $points;
            }
        }
        $this->roundTotalsByStudent = $roundTotalsByStudent;
    }
    
    public function getExercises() {
        return $this->exercises;
    }
    
    public function getCategories() {
        return $this->categories;
    }
    
    public function getSubmissionsByExercise() {
        return $this->submissionsByExercise;
    }
    
    public function getCategoryTotalsByStudent() {
        return $this->categoryTotalsByStudent;
    }
    
    public function getRoundTotalsByStudent() {
        return $this->roundTotalsByStudent;
    }
}