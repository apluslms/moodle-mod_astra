<?php
/** Page for listing all submissions to an exercise.
 */
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php'); // defines MOODLE_INTERNAL for libraries
require_once(dirname(dirname(__FILE__)).'/locallib.php');

$id = required_param('id', PARAM_INT); // exercise learning object ID

$filter = array();
foreach (array('status', 'submissiontimebef', 'submissiontimeaft', 'gradeless', 'gradegreater') as $field) {
    // PARAM_RAW used since empty values are forced to zeros with PARAM_INT
    $val = optional_param($field, null, PARAM_RAW);
    if ($val !== null && $val !== '') {
        $filter[$field] = (int) $val;
    }
}
foreach (array('firstname', 'lastname', 'idnumber', 'hasassistfeedback') as $field) {
    $val = optional_param($field, null, PARAM_RAW);
    if ($val !== null && $val !== '') {
        $filter[$field] = $val;
    }
}
$filter = empty($filter) ? null : $filter;

$sort = array();
$sort_url_val = array();
foreach (\mod_astra\output\all_submissions_page::allowedFilterFields(true) as $field) {
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

$page = optional_param('page', null, PARAM_INT);
$pagesize = optional_param('pagesize', null, PARAM_INT);


$exercise = mod_astra_learning_object::createFromId($id);
if (!$exercise->isSubmittable()) {
    // no submissions in a chapter
    print_error('exerciselobjectexpected', mod_astra_exercise_round::MODNAME);
}
$exround = $exercise->getExerciseRound();
$cm = $exround->getCourseModule();

$course = get_course($exround->getCourse()->courseid);

require_login($course, false, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/astra:viewallsubmissions', $context);
if (!$exercise->isAssistantViewingAllowed() && !has_capability('mod/astra:addinstance', $context)) {
    // assistant viewing not allowed and the user is not an editing teacher
    throw new moodle_exception('assistviewingnotallowed', mod_astra_exercise_round::MODNAME,
            \mod_astra\urls\urls::exercise($exercise));
}


// add CSS and JS
astra_page_require($PAGE);

$url = \mod_astra\urls\urls::submissionList($exercise, true, $sort, $filter, $page, $pagesize);
$PAGE->set_url($url);
$PAGE->set_title(get_string('allsubmissions', mod_astra_exercise_round::MODNAME));
$PAGE->set_heading(format_string($course->fullname));

// navbar
$exerciseNav = astra_navbar_add_exercise($PAGE, $cm->id, $exercise);
$allSbmsNav = $exerciseNav->add(get_string('allsubmissions', mod_astra_exercise_round::MODNAME),
        \mod_astra\urls\urls::submissionList($exercise, true),
        navigation_node::TYPE_CUSTOM,
        null, 'allsubmissions');
$allSbmsNav->make_active();

// render page content
$output = $PAGE->get_renderer(mod_astra_exercise_round::MODNAME);

$form = new \mod_astra\form\filter_submissions_form('submission_list.php?id='. $id,
        array('sort' => $sort_url_val));

if ($sort === null) {
    // sort by submission time as a default
    $sort = array(array('submissiontime', false));
}

if ($form->is_cancelled()) {
    redirect($url);
    exit(0);
} else if ($fromform = $form->get_data()) {
    // form submitted, redirect to the submission list page with the given parameters
    // convert dates (arrays) to integers (timestamps)
    if (isset($fromform->submissiontimeafter) && $fromform->submissiontimeafter != 0) {
        $filter['submissiontimeaft'] = $fromform->submissiontimeafter;
    }
    if (isset($fromform->submissiontimebefore) && $fromform->submissiontimebefore != 0) {
        $filter['submissiontimebef'] = $fromform->submissiontimebefore;
    }
    $targeturl = \mod_astra\urls\urls::submissionList($exercise, true, $sort,
            $filter, null, $fromform->pagesize);
    redirect($targeturl);
    exit(0);
} else {
    // display the page normally (or display errors after submitting the form)
    // fill in the form with the currently used filters
    $filtervalues = empty($filter) ? array() : $filter;
    $defaults = array(
            'pagesize' => $pagesize,
    );
    $defaults += $filtervalues;
    if (isset($filtervalues['submissiontimeaft'])) {
        $defaults['submissiontimeafter'] = $filtervalues['submissiontimeaft'];
    }
    if (isset($filtervalues['submissiontimebef'])) {
        $defaults['submissiontimebefore'] = $filtervalues['submissiontimebef'];
    }
    $form->set_data($defaults);
    
    // Print the page header (Moodle navbar etc.).
    echo $output->header();
    
    $renderable = new \mod_astra\output\all_submissions_page($exercise, $sort,
            $filter, $page, $pagesize, $form);
    echo $output->render($renderable);
    
    echo $output->footer();
}
