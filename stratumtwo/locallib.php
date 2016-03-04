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
