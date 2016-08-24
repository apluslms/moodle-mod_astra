<?php
/** Page for adding new student-specific submission deviations
 * (deadline or max submissions limit) in the course.
 */
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php'); // defines MOODLE_INTERNAL for libraries
require_once(dirname(__FILE__) .'/editcourse_lib.php');
//require_once(dirname(dirname(__FILE__)) .'/locallib.php');

$courseid = required_param('course', PARAM_INT); // Course ID
$type     = required_param('type', PARAM_ALPHA); // dl or submitlimit

if ($type == 'dl') {
    $page_url = \mod_astra\urls\urls::addDeadlineDeviation($courseid, true);
    $title = get_string('addnewdldeviations', mod_astra_exercise_round::MODNAME);
} elseif ($type == 'submitlimit') {
    $page_url = \mod_astra\urls\urls::addSubmissionLimitDeviation($courseid, true);
    $title = get_string('addnewsbmslimitdeviations', mod_astra_exercise_round::MODNAME);
} else {
    print_error('missingparam', '', '', 'type');
}

$course = get_course($courseid);

require_login($course, false);
$context = context_course::instance($courseid);
require_capability('mod/astra:addinstance', $context); // editing teacher

//astra_page_require($PAGE); // Bootstrap CSS etc.
// Print the page header.
$PAGE->set_pagelayout('incourse');
$PAGE->set_url($page_url);
$PAGE->set_title($title);
$PAGE->set_heading(format_string($course->fullname));

// navbar
astra_deviations_navbar_add($PAGE, $courseid, $title, $page_url, 'adddeviation');
/*
// A+ search-select widget disabled since Bootstrap CSS/JS does not work well enough in Moodle.
// require AMD Javascript for search-select widget and enable it
$amd_js = <<<'EOT'
require(['jquery', 'mod_astra/aplus_searchselect'], function($) {
    $('.search-select').aplusSearchSelect();
});
EOT;
$PAGE->requires->js_amd_inline($amd_js);
*/

$formAction = "add_deviation.php?course=$courseid&type=$type";
if ($type == 'dl') {
    $form = new \mod_astra\form\add_deadline_deviation_form($courseid, $formAction);
} else {
    /// submit limit
    $form = new \mod_astra\form\add_submit_limit_deviation_form($courseid, $formAction);
}

if ($form->is_cancelled()) {
    redirect(\mod_astra\urls\urls::deviations($courseid, true));
    exit(0);
}

$output = $PAGE->get_renderer(mod_astra_exercise_round::MODNAME);
echo $output->header();
echo $output->heading($title);

if ($fromform = $form->get_data()) {
    $existed = array();
    $errors = array();
    
    if (empty($fromform->submittertext)) {
        $submitterIds = $fromform->submitter; // multi-select
    } else {
        // parse text input of student ids/usernames and make an array of Moodle user IDs
        list($submitterIds, $ignored) = 
            \mod_astra\form\add_deadline_deviation_form::parseSubmittersText($fromform->submittertext);
    }
    
    foreach ($fromform->exerciseid as $exid) {
        foreach ($submitterIds as $submitter) {
            if ($type == 'dl') {
                $res = mod_astra_deadline_deviation::createNew($exid, $submitter,
                        $fromform->extraminutes, $fromform->withoutlatepenalty);
            } else {
                $res = mod_astra_submission_limit_deviation::createNew($exid, $submitter, $fromform->extrasubmissions);
            }
            if (!$res) {
                $exercise = mod_astra_exercise::createFromId($exid);
                $user = $DB->get_record('user', array('id' => $submitter));
                $text = $exercise->getName() .', '. mod_astra_deviation_rule::submitterName($user);
                if ($res === null) {
                    // user already had deviation in the exercise
                    $existed[] = $text;
                } else {
                    // database error, could not create
                    $errors[] = $text;
                }
            }
        }
    }
    
    if (!empty($existed)) {
        echo '<p>'. get_string('deviationsexisted', mod_astra_exercise_round::MODNAME) .'</p>';
        echo html_writer::alist($existed);
    }
    if (!empty($errors)) {
        echo '<p>'. get_string('deviationscreationerror', mod_astra_exercise_round::MODNAME) .'</p>';
        echo html_writer::alist($errors);
    }
    if (empty($existed) && empty($errors)) {
        // success
        echo '<p>'. get_string('deviationscreatesuccess', mod_astra_exercise_round::MODNAME) .'</p>';
    }
    echo '<p><a href="'.\mod_astra\urls\urls::deviations($courseid).'">'.
            get_string('back', mod_astra_exercise_round::MODNAME) .'</a></p>';
    
} else {
    $form->display();
    /*
    // A+ search select widget requires a hidden HTML definition for the widget
    $remove    = get_string('remove', mod_astra_exercise_round::MODNAME);
    $search    = get_string('search', mod_astra_exercise_round::MODNAME);
    $searchfor = get_string('searchfor', mod_astra_exercise_round::MODNAME);
    $nomatches = get_string('nomatches', mod_astra_exercise_round::MODNAME);
    
    $search_select_widget_html = <<<EOT
<div id="search-select-widget" class="hide">
  <ul class="list-inline search-selected">
    <li><button type="button"><span class="name">None</span> <span aria-label="$remove">&times;</span></button></li>
  </ul>
  <div class="input-group">
    <span class="input-group-btn dropdown-toggle" aria-haspopup="true" aria-expanded="false">
      <button class="btn btn-default" data-toggle="dropdown" type="button" aria-label="$search">
        <span class="glyphicon glyphicon-search" aria-hidden="true"></span>
      </button>
      <ul class="dropdown-menu search-options">
        <li class="not-found"><a>$nomatches</a></li>
      </ul>
    </span>
    <input type="text" class="form-control" placeholder="$searchfor" />
  </div>
</div>
EOT;
    
    echo $search_select_widget_html;
    */
}

echo $output->footer();
