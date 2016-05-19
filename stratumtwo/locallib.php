<?php

defined('MOODLE_INTERNAL') || die();

/**
 * Require custom Javascript and CSS on a Moodle page.
 * @param moodle_page $page supply $PAGE on a Moodle page
 */
function stratumtwo_page_require($page) {
    // Bootstrap CSS (hosted here because not all components are included)
    $page->requires->css(new moodle_url('/mod/'. mod_stratumtwo_exercise_round::TABLE .'/assets/bootstrap/css/bootstrap.min.css'));
    // custom CSS
    $page->requires->css(new moodle_url('/mod/'. mod_stratumtwo_exercise_round::TABLE .'/assets/css/main.css'));
    
    // highlight.js for source code syntax highlighting
    $page->requires->css(new moodle_url('https://cdnjs.cloudflare.com/ajax/libs/highlight.js/9.2.0/styles/default.min.css'));
    // JS code is included as AMD module
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
    $submissionNav = $prevNode->add(get_string('submissionnumber', mod_stratumtwo_exercise_round::MODNAME, $submission->getCounter()),
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
    $submissionNav = $allSbmsNav->add(get_string('submissionnumber', mod_stratumtwo_exercise_round::MODNAME, $submission->getCounter()),
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
    $sbmsCounter = $submission->getCounter();
    $str->sbmscounter = $sbmsCounter;
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
    $message->contexturlname = get_string('submissionnumber', \mod_stratumtwo_exercise_round::MODNAME, $sbmsCounter);
    
    return message_send($message);
}
