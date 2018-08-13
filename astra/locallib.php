<?php

defined('MOODLE_INTERNAL') || die();

/**
 * Require custom Javascript and CSS on a Moodle page.
 * @param moodle_page $page supply $PAGE on a Moodle page
 */
function astra_page_require($page) {
    // custom CSS
    $page->requires->css(new moodle_url('/mod/'. mod_astra_exercise_round::TABLE .'/assets/css/main.css'));
    
    // highlight.js for source code syntax highlighting
    $page->requires->css(new moodle_url('https://cdnjs.cloudflare.com/ajax/libs/highlight.js/9.2.0/styles/default.min.css'));
    // JS code is included as AMD module
}

/**
 * Filter/format exercise page content so that Moodle filters are activated, e.g.,
 * the Moodle MathJax loader renders Latex math formulas.
 * This function may be used to filter exercise descriptions and submission feedbacks
 * that originate from an exercise service.
 * 
 * @param string $content (HTML) content to filter
 * @param context|int $ctx Moodle context object or context ID of the exercise (round)
 */
function astra_filter_exercise_content($content, $ctx) {
    return format_text($content, FORMAT_HTML, array(
            'trusted' => true,
            // $content is trusted and its dangerous elements are not removed, e.g., <input>
            'noclean' => true,
            'filter' => true, // activate Moodle filters
            'para' => false, // no extra <div> wrapping
            'context' => $ctx,
            'allowid' => true, // retain HTML element IDs
    ));
}

/**
 * Convert a number to a roman numeral. Number should be between 0--1999.
 * 
 * Derived from A+ (a-plus/lib/helpers.py).
 * @param int $number
 * @return string
 */
function astra_roman_numeral($number) {
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
 * @param mod_astra_learning_object $exercise
 * @return navigation_node the new navigation node of the given exercise
 */
function astra_navbar_add_exercise(moodle_page $page, $cmid, mod_astra_learning_object $exercise) {
    $roundNav = $page->navigation->find($cmid, navigation_node::TYPE_ACTIVITY);
    
    $parents = array($exercise);
    $ex = $exercise;
    while ($ex = $ex->getParentObject()) {
        $parents[] = $ex;
    }
    
    $previousNode = $roundNav;
    // leaf child comes last in the navbar
    for ($i = count($parents) - 1; $i >= 0; --$i) {
        $previousNode = astra_navbar_add_one_exercise($previousNode, $parents[$i]);
    }
    
    return $previousNode;
}

/**
 * Add a single learning object navbar node after the given node.
 * @param navigation_node $previousNode
 * @param mod_astra_learning_object $learningObject
 */
function astra_navbar_add_one_exercise(navigation_node $previousNode, mod_astra_learning_object $learningObject) {
    return $previousNode->add($learningObject->getName(),
            \mod_astra\urls\urls::exercise($learningObject, true, false),
            navigation_node::TYPE_CUSTOM,
            null, 'ex'.$learningObject->getId());
}

function astra_navbar_add_submission(navigation_node $prevNode, mod_astra_submission $submission) {
    $submissionNav = $prevNode->add(get_string('submissionnumber', mod_astra_exercise_round::MODNAME, $submission->getCounter()),
            \mod_astra\urls\urls::submission($submission, true),
            navigation_node::TYPE_CUSTOM, null, 'sub'.$submission->getId());
    return $submissionNav;
}

function astra_navbar_add_inspect_submission(navigation_node $prevNode, mod_astra_exercise $exercise,
        mod_astra_submission $submission) {
    $allSbmsNav = $prevNode->add(get_string('allsubmissions', mod_astra_exercise_round::MODNAME),
            \mod_astra\urls\urls::submissionList($exercise, true),
            navigation_node::TYPE_CUSTOM,
            null, 'allsubmissions');
    $submissionNav = $allSbmsNav->add(get_string('submissionnumber', mod_astra_exercise_round::MODNAME, $submission->getCounter()),
            \mod_astra\urls\urls::submission($submission, true),
            navigation_node::TYPE_CUSTOM,
            null, 'sub'.$submission->getId());
    $inspectNav = $submissionNav->add(get_string('inspectsubmission', mod_astra_exercise_round::MODNAME),
            \mod_astra\urls\urls::inspectSubmission($submission, true),
            navigation_node::TYPE_CUSTOM,
            null, 'inspect');
    return $inspectNav;
}

/**
 * Send a Moodle message to a user about new assistant feedback in a submission.
 * @param mod_astra_submission $submission
 * @param stdClass $fromUser assistant
 * @param stdClass $toUser student
 */
function astra_send_assistant_feedback_notification(mod_astra_submission $submission,
        stdClass $fromUser, stdClass $toUser) {
    global $CFG;
    // use the recipient's language
    $lang = empty($toUser->lang) ? $CFG->lang : $toUser->lang;
    $man = get_string_manager();
    
    $str = new stdClass();
    $exercise = $submission->getExercise();
    $str->exname = $exercise->getName(true, $lang);
    $str->exurl = \mod_astra\urls\urls::exercise($exercise, false, false);
    $str->sbmsurl = \mod_astra\urls\urls::submission($submission);
    $sbmsCounter = $submission->getCounter();
    $str->sbmscounter = $sbmsCounter;
    if (!($exercise->getParentObject() && $exercise->isUnlisted())) {
        $msg_start = $man->get_string('youhavenewfeedback', \mod_astra_exercise_round::MODNAME, $str, $lang);
    } else {
        // there is no direct URL to open the feedback if the exercise is embedded in a chapter
        // (the feedback is shown in a modal dialog in the chapter)
        $msg_start = $man->get_string('youhavenewfeedbacknosbmsurl', \mod_astra_exercise_round::MODNAME, $str, $lang);
    }
    $full_msg = "<p>$msg_start</p>". $submission->getAssistantFeedback();
    
    $message = new \core\message\message();
    $message->component = \mod_astra_exercise_round::MODNAME;
    $message->name = 'assistant_feedback_notification';
    $message->userfrom = $fromUser;
    $message->userto = $toUser;
    $message->subject = $man->get_string('feedbackto', \mod_astra_exercise_round::MODNAME, $str->exname, $lang);
    $message->fullmessage = $full_msg;
    $message->fullmessageformat = FORMAT_HTML;
    $message->fullmessagehtml = $full_msg;
    $message->smallmessage = $msg_start;
    $message->notification = 1;
    $message->contexturl = \mod_astra\urls\urls::submission($submission);
    $message->contexturlname = $man->get_string('submissionnumber', \mod_astra_exercise_round::MODNAME,
            $sbmsCounter, $lang);
    $message->courseid = $exercise->getExerciseRound()->getCourse()->courseid;
    
    return message_send($message);
}

/**
 * Return true if the current HTTP request was AJAX.
 * (Depends on the HTTP request header X-Requested-With.)
 */
function astra_is_ajax() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

/**
 * Return enrolled participants in the course.
 * Filtering the results based on, e.g., the user's last name is supported.
 * Users may be filtered by role as well (student, teacher, etc.).
 * The results may be paginated (small subset returned at a time).
 *
 * @param \context $context course context
 * @param array $sort array of arrays that defines the user fields to sort.
 *        Outer array is indexed from zero and shows the order of the columns
 *        to sort (primary column first). The nested arrays contain two elements: field names
 *        (like 'idnumber' in the participants_page::allowedFilterFields method)
 *        and boolean values (true for ascending sort, false for descending).
 * @param array $filter array of queries for filtering the user records;
 *        possible keys: 'idnumber', 'firstname', 'lastname', 'email'.
 *        The given query value is surrounded with wildcards so that the result
 *        includes any record containing the queried word. Multiple filters
 *        are ANDed together so that they must all match.
 * @param int $roleid include only participants with this role.
 *        Use -1 for including all roles and 0 for guessing the student role.
 *        This function has to guess which role is the student role if the id is not provided.
 * @param number $limitfrom limit the number of records returned, starting from this point
 * @param number $limitnum number of records to return
 * @return list(\stdClass[], int, int, int) user records, total number of
 *         participants with the role, total number of users after filtering,
 *         role id used for filtering participants (useful if the student role is guessed
 *         by providing the parameter $roleid 0)
 */
function astra_get_participants(\context $context, array $sort = null, array $filter = null,
        $roleid = 0, $limitfrom = 0, $limitnum = 0) {
    global $DB;

    // filtering users by role adapted from moodle/user/index.php
    list($esql, $params) = get_enrolled_sql($context);
    $joins = array("FROM {user} u");
    $wheres = array();
    
    $select = 'SELECT u.id, u.firstname, u.lastname, u.idnumber, u.email';
    $joins[] = "JOIN ($esql) e ON e.id = u.id"; // Course enrolled users only.

    if ($roleid !== -1) {
        // limit results by user role
        if ($roleid === 0) {
            // guess the student role
            $roles = get_profile_roles($context); // map of roleids to role objects
            foreach ($roles as $role) {
                if ($role->shortname === 'student') {
                    $roleid = $role->id;
                    break;
                }
            }
        }
        if ($roleid !== 0) {
            // $roleid must have a real value at this stage, or else it is ignored
            // We want to query both the current context and parent contexts.
            list($relatedctxsql, $relatedctxparams) = $DB->get_in_or_equal($context->get_parent_context_ids(true), SQL_PARAMS_NAMED, 'relatedctx');
            $wheres[] = "u.id IN (SELECT userid FROM {role_assignments} WHERE roleid = :roleid AND contextid $relatedctxsql)";
            $params = array_merge($params, array('roleid' => $roleid), $relatedctxparams);
        } else {
            $roleid = -1;
        }
    }

    // total count of participants (with the given role) before filtering
    $from = implode("\n", $joins);
    if ($wheres) {
        $where = 'WHERE ' . implode(' AND ', $wheres);
    } else {
        $where = '';
    }
    $totalcount = $DB->count_records_sql("SELECT COUNT(u.id) $from $where", $params);
    
    // possible filtering, e.g., only users whose lastnames contain a given string
    if (!empty($filter)) {
        $allowedFilterFields = \mod_astra\output\participants_page::allowedFilterFields();
        foreach ($allowedFilterFields as $i => $field) {
            if (isset($filter[$field])) {
                $wheres[] = $DB->sql_like($field, ":search$i", false, false);
                $params["search$i"] = '%'. $DB->sql_like_escape($filter[$field]) .'%';
                // wildcards before and after the given query
            }
        }
    }
    
    //$from = implode("\n", $joins);
    if ($wheres) {
        $where = 'WHERE ' . implode(' AND ', $wheres);
    } else {
        $where = '';
    }
    
    if (!empty($sort)) {
        $fields = array();
        foreach ($sort as $fieldASC) {
            $f = $fieldASC[0];
            if (!$fieldASC[1]) {
                $f .= ' DESC';
            }
            $fields[] = $f;
        }
        
        $orderby = ' ORDER BY ' . implode(',', $fields);
    } else {
        $orderby = '';
    }
    
    // the count of participants after filtering
    $matchcount = $DB->count_records_sql("SELECT COUNT(u.id) $from $where", $params);
    
    $sql = "$select $from $where $orderby";
    $users = $DB->get_records_sql($sql, $params, $limitfrom, $limitnum);
    
    return array($users, $totalcount, $matchcount, $roleid);
}

/**
 * Picks the selected language's value from |lang:value|lang:value| -format text.
 * Adapted from A+ (a-plus/lib/localization_syntax.py)
 * @param string $entry
 * @param null|string $preferred_lang the language to use. If null, the current language is used.
 * @return string
 */
function astra_parse_localization(string $text, string $preferred_lang = null) : string {
    if (strpos($text, '|') !== false) {
        $current_lang = $preferred_lang !== null ? $preferred_lang : current_language();
        $variants = explode('|', $text);
        $exercise_number = $variants[0]; // Leading numbers or an empty string
        $langs = array();
        foreach ($variants as $variant) {
            $parts = explode(':', $variant);
            if (count($parts) !== 2) {
                continue;
            }
            list($lang, $val) = $parts;
            $langs[$lang] = $val;
            
            if ($lang === $current_lang) {
                return $exercise_number . $val;
            }
        }
        
        if (isset($langs['en'])) {
            return $exercise_number . $langs['en'];
        } else if (!empty($langs)) {
            return $exercise_number . reset($langs);
        }
        
        return $exercise_number;
    }
    return $text;
}

/**
 * Pick the value for the current language from the given text that
 * may contain HTML span elements in the format of the Moodle multilang filter.
 * (<span lang="en" class="multilang">English value</span>)
 * @param string $text
 * @param null|string $preferred_lang the language to use. If null, the current language is used.
 * @return string
 */
function astra_parse_multilang_filter_localization(string $text, string $preferred_lang = null) : string {
    $offset = 0;
    $pos = stripos($text, '<span', $offset);
    if ($pos === false) {
        // no multilang values
        return $text;
    }
    $start = substr($text, 0, $pos); // substring preceding any multilang spans
    $current_lang = $preferred_lang !== null ? $preferred_lang : current_language();
    
    $multilang_span_pattern = '/<span(?:\s+lang="(?P<lang>[a-zA-Z0-9_-]+)"|\s+class="multilang"){2}\s*>(?P<value>[^<]*)<\/span>/i';
    $langs = array();
    
    while ($pos !== false) {
        $offset = $pos;
        $matches = array();
        if (preg_match($multilang_span_pattern, $text, $matches, PREG_OFFSET_CAPTURE, $offset)) {
            $lang = $matches['lang'][0];
            $value = $matches['value'][0];
            if ($lang === $current_lang) {
                return $start . $value;
            }
            $langs[$lang] = $value;
            
            // move offset over the span
            $offset = $matches[0][1] + strlen($matches[0][0]);
        }
        
        // find the next span
        $pos = stripos($text, '<span', $offset);
    }
    
    if (isset($langs['en'])) {
        return $start . $langs['en'];
    } else if (!empty($langs)) {
        return $start . reset($langs);
    }
    return $text;
}
