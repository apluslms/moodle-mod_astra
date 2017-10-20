<?php
/** Page for displaying the exercise results of one student in the course
 * (like the mod astra index page).
 */
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php'); // defines MOODLE_INTERNAL for libraries
require_once(dirname(dirname(__FILE__)).'/locallib.php');

$courseid = required_param('course', PARAM_INT); // course ID
$userid   = required_param('user', PARAM_INT); // user ID

$course = get_course($courseid);
$user = $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST);

require_login($course, false);
$context = context_course::instance($courseid);
require_capability('mod/astra:viewallsubmissions', $context);
require_capability('moodle/user:viewdetails', $context);

// the user must be enrolled in the course to have any results
if (!is_enrolled($context, $user)) {
    throw new moodle_exception('usernotenrolled', mod_astra_exercise_round::MODNAME,
            \mod_astra\urls\urls::participantList($courseid));
}

$PAGE->set_pagelayout('incourse');

// add CSS and JS
astra_page_require($PAGE);

$url = \mod_astra\urls\urls::userResults($courseid, $userid, true);
$PAGE->set_url($url);
$title = get_string('resultsof', mod_astra_exercise_round::MODNAME, fullname($user));
$PAGE->set_title($title);
$PAGE->set_heading(format_string($course->fullname));

// navbar
$courseNav = $PAGE->navigation->find($courseid, navigation_node::TYPE_COURSE);
$participantsList = $courseNav->add(
        get_string('participants', mod_astra_exercise_round::MODNAME),
        \mod_astra\urls\urls::participantList($courseid, true),
        navigation_node::TYPE_CUSTOM, null, 'participantslist');
$userResults = $participantsList->add($title, $url,
        navigation_node::TYPE_CUSTOM, null, 'userresults');
$userResults->make_active();

// render page content
$output = $PAGE->get_renderer(mod_astra_exercise_round::MODNAME);

// Print the page header (Moodle navbar etc.).
echo $output->header();

$renderable = new \mod_astra\output\user_results_page($course, $user);
echo $output->render($renderable);

echo $output->footer();
