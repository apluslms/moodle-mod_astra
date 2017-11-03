<?php
/** Page for listing all participants (students) in the course.
 * Teachers access students' course overview pages from this page.
 * The student overview page is the one that displays all the exercise results
 * of one student like the mod astra index page.
 */
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php'); // defines MOODLE_INTERNAL for libraries
require_once(dirname(dirname(__FILE__)).'/locallib.php');

$courseid = required_param('course', PARAM_INT); // course ID

$filter = array();
foreach (\mod_astra\output\participants_page::allowedFilterFields() as $field) {
    $val = optional_param($field, null, PARAM_RAW);
    if (!empty($val) || $val === '0') { // "0" is empty according to PHP
        $filter[$field] = $val;
    }
}
$filter = empty($filter) ? null : $filter;

$sort = array();
$sort_url_val = array();
foreach (\mod_astra\output\participants_page::allowedFilterFields() as $field) {
    $val = optional_param("sort_$field", null, PARAM_ALPHANUMEXT);
    if (isset($val)) {
        // expect values like sort_idnumber => 0_1
        $orderASC = explode('_', $val);
        $order = isset($orderASC[0]) ? (int) $orderASC[0] : 0; // which column is the primary column to sort by
        $asc = isset($orderASC[1]) ? (bool) $orderASC[1] : true; // true: ascending, false: descending
        
        $sort[] = array($field, $asc, $order);
        
        $sort_url_val["sort_$field"] = $order .'_'. (int) $asc;
    }
}
if (empty($sort)) {
    $sort = null;
} else {
    // sort the array by the column order
    usort($sort, function($a, $b) {
        if ($a[2] === $b[2]) {
            return 0;
        }
        return ($a[2] < $b[2]) ? -1 : 1;
    });
}

$roleid = optional_param('roleid', null, PARAM_INT);
$page = optional_param('page', null, PARAM_INT);
$pagesize = optional_param('pagesize', null, PARAM_INT);

$course = get_course($courseid);

require_login($course, false);
$context = context_course::instance($courseid);
require_capability('mod/astra:viewallsubmissions', $context);

$PAGE->set_pagelayout('incourse');

// add CSS and JS
astra_page_require($PAGE);

$url = \mod_astra\urls\urls::participantList($courseid, true, $sort, $filter, $roleid, $page, $pagesize);
$PAGE->set_url($url);
$title = get_string('participants', mod_astra_exercise_round::MODNAME);
$PAGE->set_title($title);
$PAGE->set_heading(format_string($course->fullname));

// navbar
$courseNav = $PAGE->navigation->find($courseid, navigation_node::TYPE_COURSE);
$participantsList = $courseNav->add($title,
        \mod_astra\urls\urls::participantList($courseid, true), // nav link resets the query parameters to the defaults
        navigation_node::TYPE_CUSTOM, null, 'participantslist');
$participantsList->make_active();

// render page content
$output = $PAGE->get_renderer(mod_astra_exercise_round::MODNAME);

$form = new \mod_astra\form\filter_participants_form('participants.php?course='. $courseid,
        array('courseid' => $courseid, 'sort' => $sort_url_val));

if ($sort === null) {
    // sort by idnumber as a default
    $sort = array(array('idnumber', true));
}

if ($form->is_cancelled()) {
    redirect($url);
    exit(0);
} else if ($fromform = $form->get_data()) {
    // form submitted, redirect to the participants page with the given parameters
    $targeturl = \mod_astra\urls\urls::participantList($courseid, true, $sort,
            $filter, $fromform->roleid, null, $fromform->pagesize);
    redirect($targeturl);
    exit(0);
} else {
    // display the page normally (or display errors after submitting the form)
    // fill in the form with the currently used filters
    $filtervalues = empty($filter) ? array() : $filter;
    $defaults = array(
            'roleid' => $roleid,
            'pagesize' => $pagesize,
    );
    $defaults += $filtervalues;
    $form->set_data($defaults);
    
    // Print the page header (Moodle navbar etc.).
    echo $output->header();
    
    $renderable = new \mod_astra\output\participants_page($course, $sort, $filter,
            $roleid, $page, $pagesize, $form);
    echo $output->render($renderable);
    
    echo $output->footer();
}