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

function stratumtwo_navbar_add_exercise(moodle_page $page, $cmid, mod_stratumtwo_exercise $exercise) {
    $roundNav = $page->navigation->find($cmid, navigation_node::TYPE_ACTIVITY);
    $exerciseNav = $roundNav->add($exercise->getName(),
            \mod_stratumtwo\urls\urls::exercise($exercise, true),
            navigation_node::TYPE_CUSTOM,
            null, 'ex'.$exercise->getId());
    return $exerciseNav;
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
