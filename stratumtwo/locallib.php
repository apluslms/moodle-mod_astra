<?php

defined('MOODLE_INTERNAL') || die();

/**
 * Require custom Javascript and CSS on a Moodle page.
 * @param moodle_page $page supply $PAGE on a Moodle page
 */
function stratumtwo_page_require($page) {
    // Moodle has jQuery 1.11.3 bundled as AMD module, but some parts of Twitter Bootstrap
    // do not work if jQuery is not defined globally
    $page->requires->js(new moodle_url('https://code.jquery.com/jquery-1.12.0.js'));
    // Bootstrap CSS (hosted here because not all components are included)
    $page->requires->css(new moodle_url('/mod/'. mod_stratumtwo_exercise_round::TABLE .'/assets/bootstrap/css/bootstrap.min.css'));
    // require Bootstrap JS globally since it does not always work if it is only used as AMD module
    $page->requires->js(new moodle_url('/mod/'. mod_stratumtwo_exercise_round::TABLE .'/assets/bootstrap/js/bootstrap.min.js'));
    // custom CSS
    $page->requires->css(new moodle_url('/mod/'. mod_stratumtwo_exercise_round::TABLE .'/assets/css/main.css'));
    
    // highlight.js for source code syntax highlighting
    $page->requires->css(new moodle_url('https://cdnjs.cloudflare.com/ajax/libs/highlight.js/9.2.0/styles/default.min.css'));
    // Highligh.js Javascript is included only as an AMD module, not here
    //$page->requires->js(new moodle_url('https://cdnjs.cloudflare.com/ajax/libs/highlight.js/9.2.0/highlight.min.js'));
    
    // custom JS, use the AMD module version
    //$page->requires->js(new moodle_url('/mod/'. mod_stratumtwo_exercise_round::TABLE .'/assets/js/stratum2.js'));
}

/**
 * Convert a number to a roman numeral. Number should be between 0--1999.
 * 
 * Derived from A+ (a-plus/lib/helpers.py).
 * @param int $number
 * @return string
 */
function stratumtwo_roman_numeral($number) {
    $numbers = array(1000,900,500,400,100,90,50,40,10,9,5,4,1);
    $letters = array("M","CM","D","CD","C","XC","L","XL","X","IX","V","IV","I");
    $roman = '';
    $lenNumbers = count($numbers);
    for ($i = 0; $i < $lenNumbers; $i++) {
        while ($number >= $numbers[$i]) {
            $roman .= $letters[$i];
            $number -= $numbers[$i];
        }
    }
    return $roman;
}

/**
 * Add a learning object with its parent objects to the page navbar after the exercise round node.
 * @param moodle_page $page
 * @param int $cmid Moodle course module ID of the exercise round
 * @param mod_stratumtwo_learning_object $exercise
 * @return navigation_node the new navigation node of the given exercise
 */
function stratumtwo_navbar_add_exercise(moodle_page $page, $cmid, mod_stratumtwo_learning_object $exercise) {
    $roundNav = $page->navigation->find($cmid, navigation_node::TYPE_ACTIVITY);
    
    $parents = array($exercise);
    $ex = $exercise;
    while ($ex = $ex->getParentObject()) {
        $parents[] = $ex;
    }
    
    $previousNode = $roundNav;
    // leaf child comes last in the navbar
    for ($i = count($parents) - 1; $i >= 0; --$i) {
        $previousNode = stratumtwo_navbar_add_one_exercise($previousNode, $parents[$i]);
    }
    
    return $previousNode;
}

/**
 * Add a single learning object navbar node after the given node.
 * @param navigation_node $previousNode
 * @param mod_stratumtwo_learning_object $learningObject
 */
function stratumtwo_navbar_add_one_exercise(navigation_node $previousNode, mod_stratumtwo_learning_object $learningObject) {
    return $previousNode->add($learningObject->getName(),
            \mod_stratumtwo\urls\urls::exercise($learningObject, true),
            navigation_node::TYPE_CUSTOM,
            null, 'ex'.$learningObject->getId());
}

function stratumtwo_navbar_add_submission(navigation_node $prevNode, mod_stratumtwo_submission $submission) {
    $submissionNav = $prevNode->add(get_string('submissionnumber', mod_stratumtwo_exercise_round::MODNAME, $submission->getId()),
            \mod_stratumtwo\urls\urls::submission($submission, true),
            navigation_node::TYPE_CUSTOM, null, 'sub'.$submission->getId());
    return $submissionNav;
}

function stratumtwo_navbar_add_inspect_submission(navigation_node $prevNode, mod_stratumtwo_exercise $exercise,
        mod_stratumtwo_submission $submission) {
    $allSbmsNav = $prevNode->add(get_string('allsubmissions', mod_stratumtwo_exercise_round::MODNAME),
            \mod_stratumtwo\urls\urls::submissionList($exercise, true),
            navigation_node::TYPE_CUSTOM,
            null, 'allsubmissions');
    $submissionNav = $allSbmsNav->add(get_string('submissionnumber', mod_stratumtwo_exercise_round::MODNAME, $submission->getId()),
            \mod_stratumtwo\urls\urls::submission($submission, true),
            navigation_node::TYPE_CUSTOM,
            null, 'sub'.$submission->getId());
    $inspectNav = $submissionNav->add(get_string('inspectsubmission', mod_stratumtwo_exercise_round::MODNAME),
            \mod_stratumtwo\urls\urls::inspectSubmission($submission, true),
            navigation_node::TYPE_CUSTOM,
            null, 'inspect');
    return $inspectNav;
}

/**
 * Send a Moodle message to a user about new assistant feedback in a submission.
 * @param mod_stratumtwo_submission $submission
 * @param stdClass $fromUser assistant
 * @param stdClass $toUser student
 */
function stratumtwo_send_assistant_feedback_notification(mod_stratumtwo_submission $submission,
        stdClass $fromUser, stdClass $toUser) {
    $str = new stdClass();
    $str->exname = $submission->getExercise()->getName();
    $str->exurl = \mod_stratumtwo\urls\urls::exercise($submission->getExercise());
    $str->sbmsurl = \mod_stratumtwo\urls\urls::submission($submission);
    $str->sbmsid = $submission->getId();
    $msg_start = get_string('youhavenewfeedback', \mod_stratumtwo_exercise_round::MODNAME, $str);
    $full_msg = "<p>$msg_start</p>". $submission->getAssistantFeedback();
    
    $message = new \core\message\message();
    $message->component = \mod_stratumtwo_exercise_round::MODNAME;
    $message->name = 'assistant_feedback_notification';
    $message->userfrom = $fromUser;
    $message->userto = $toUser;
    $message->subject = get_string('feedbackto', \mod_stratumtwo_exercise_round::MODNAME, $str->exname);
    $message->fullmessage = $full_msg;
    $message->fullmessageformat = FORMAT_HTML;
    $message->fullmessagehtml = $full_msg;
    $message->smallmessage = $msg_start;
    $message->notification = 1;
    $message->contexturl = \mod_stratumtwo\urls\urls::submission($submission);
    $message->contexturlname = get_string('submissionnumber', \mod_stratumtwo_exercise_round::MODNAME, $submission->getId());
    
    return message_send($message);
}

/**
 * Return data of exercise results so that it can be encoded to JSON.
 * @param int $courseId Moodle course ID
 * @param array|null $exerciseIds exercise learning object IDs that should be included,
 * null to include all exercises in the course (category totals also include only these exercises)
 * @param array|null $studentUserIds Moodle user IDs of the users that should be included,
 * null for all users that have submitted
 * @param int $submittedBefore Unix timestamp. Take only submissions submitted at or before
 * this time into account.
 * @param bool $includeAllSubmissions if true, a list of all submissions with their grades is
 * included for each student and exercise in addition to the best grades. If false, only best
 * grades are included for each student and exercise.
 * @return array exported data that can be encoded to JSON.
 */
function stratumtwo_export_results($courseId, array $exerciseIds = null, array $studentUserIds = null,
        $submittedBefore = 0, $includeAllSubmissions = true) {
    global $DB;
    
    $categories = mod_stratumtwo_category::getCategoriesInCourse($courseId, true);
    $catIds = array_keys($categories);
    if (empty($catIds)) {
        return array(); // no exercises, no results
    }
    
    if (empty($exerciseIds)) {
        // all exercises in the course
        $exerciseRecords = $DB->get_records_sql(
                mod_stratumtwo_learning_object::getSubtypeJoinSQL(mod_stratumtwo_exercise::TABLE, 'lob.id, lob.categoryid, lob.remotekey') .
                ' WHERE categoryid IN ('. implode(',', $catIds) .')');
        $exerciseIds = array_keys($exerciseRecords);
    } else {
        $exerciseRecords = $DB->get_records_sql(
                mod_stratumtwo_learning_object::getSubtypeJoinSQL(mod_stratumtwo_exercise::TABLE, 'lob.id, lob.categoryid, lob.remotekey') .
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
    $sbmsStatusToString = function($status) {
        switch ($status) {
            case mod_stratumtwo_submission::STATUS_INITIALIZED:
                return 'initialized';
            case mod_stratumtwo_submission::STATUS_WAITING:
                return 'waiting';
            case mod_stratumtwo_submission::STATUS_READY:
                return 'ready';
            case mod_stratumtwo_submission::STATUS_ERROR:
                return 'error';
            default:
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
               FROM {'. mod_stratumtwo_submission::TABLE .'} s 
               JOIN {user} u ON s.submitter = u.id 
               JOIN {'. mod_stratumtwo_learning_object::TABLE .'} lob ON s.exerciseid = lob.id 
              WHERE '. $where .
             'ORDER BY s.submissiontime ASC',
            $params);
    
    $json = array('students' => array());
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
                    'points' => -1, // replaced by any submission below (if best part) since 0 > -1
                    'submissiontime' => -1,
                    'nth' => 0,
                    'numberofsubmissions' => 0,
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
        }
        $best['numberofsubmissions'] += 1;
        unset($best);
        if ($includeAllSubmissions) {
            // list of points from all submissions to the exercise by this student
            $json['students'][$studentId]['exercises'][$sbmsRecord->remotekey]['submissions'][] = array(
                    'points' => (int) $sbmsRecord->grade,
                    'submissiontime' => (int) $sbmsRecord->submissiontime,
                    'status' => $sbmsStatusToString($sbmsRecord->status),
            );
        }
    }
    $allSubmissions->close();
    
    foreach ($json['students'] as $studentId => $catsAndExercises) {
        $categoriesTotal = array('coursetotal' => 0);
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
    
    $json['numberofstudents'] = count($json['students']);
    
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
                    "submissions": [ (each submission listed if all submissions are included)
                        {
                            "points": 5,
                            "submissiontime": timestamp,
                            "status": "Ready"
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
 * @param int $courseId Moodle course ID
 * @return string[] array of student ids (Moodle user idnumber if it exists or username otherwise)
 */
function stratumtwo_course_passed_list($courseId) {
    global $DB;
    
    // compute best points for each student in each exercise with minimal number of DB queries
    $results = stratumtwo_export_results($courseId, null, null, 0, false);
    
    $categories = mod_stratumtwo_category::getCategoriesInCourse($courseId);
    if (empty($categories)) {
        return array(); // no exercises, no results
    }
    
    $visibleExrounds = mod_stratumtwo_exercise_round::getExerciseRoundsInCourse($courseId);
    $exrounds = array(); // organize by round id
    foreach ($visibleExrounds as $exround) {
        $exrounds[$exround->getId()] = $exround;
    }
    
    // all non-hidden exercises in the course
    $exerciseRecords = $DB->get_records_sql(
            mod_stratumtwo_learning_object::getSubtypeJoinSQL(mod_stratumtwo_exercise::TABLE) .
            ' WHERE categoryid IN ('. implode(',', array_keys($categories)) .') AND status != ?',
            array(mod_stratumtwo_learning_object::STATUS_HIDDEN));
    $exercises = array();
    foreach ($exerciseRecords as $ex) {
        // check that the category and round are not hidden
        if (isset($categories[$ex->categoryid]) && isset($exrounds[$ex->roundid])) {
            $exercises[$ex->lobjectid] = new mod_stratumtwo_exercise($ex);
        }
    }
    
    $passedAllExercisesAndRounds = function($studentResults) use ($exercises, $exrounds) {
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
    $passedAllCategories = function($studentResults) use ($categories) {
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
        if ($passedAllExercisesAndRounds($catsAndExercises['exercises']) && $passedAllCategories(['categories'])) {
            $passedStudents[] = $studentId; // student id or username if no id exists
        }
    }
    
    return $passedStudents;
}
