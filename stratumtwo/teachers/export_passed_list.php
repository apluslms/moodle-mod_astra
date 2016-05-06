<?php
/** Script that sends a list of students who passed course exercises as a file to the client.
 */
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php'); // defines MOODLE_INTERNAL for libraries
require_once($CFG->libdir .'/filelib.php');

$cid = required_param('course', PARAM_INT); // Course ID
$course = get_course($cid);

require_login($course, false);
$context = context_course::instance($cid);
require_capability('mod/stratumtwo:addinstance', $context); // editing teacher

$PAGE->set_pagelayout('incourse');
$PAGE->set_url(\mod_stratumtwo\urls\urls::exportPassedList($cid, true));
//$PAGE->set_title('Download course passed list');
//$PAGE->set_heading(format_string($course->fullname));

$passed_list = \mod_stratumtwo\export\export_data::course_passed_list($cid);
$passed_str = '';
foreach ($passed_list as $student) {
    $passed_str .= $student ."\n";
}

// force the user to download the file
$date_now = date('d-m-Y\TH-i-s');
$filename = "passed_list_$date_now.txt";
send_temp_file($passed_str, $filename, true);
