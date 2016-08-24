<?php
/** Page that lets the user edit/add/delete Astra exercises in a Moodle course.
 * The user can modify settings manually or create and update exercises automatically
 * based on the configuration in the Astra exercise service.
 */
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php'); // defines MOODLE_INTERNAL for libraries
require_once(dirname(__FILE__) .'/editcourse_lib.php');
require_once(dirname(dirname(__FILE__)) .'/locallib.php');

$cid = required_param('course', PARAM_INT); // Course ID
$course = get_course($cid);

require_login($course, false);
$context = context_course::instance($cid);
require_capability('mod/astra:addinstance', $context);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $module_numbering = \mod_astra_course_config::MODULE_NUMBERING_ARABIC;
    if (isset($_POST['module_numbering'])) {
        $module_numbering = (int) $_POST['module_numbering'];
    }
    $content_numbering = \mod_astra_course_config::CONTENT_NUMBERING_ARABIC;
    if (isset($_POST['content_numbering'])) {
        $content_numbering = (int) $_POST['content_numbering'];
    }
    
    $submitted = isset($_POST['save']) || isset($_POST['renumbermodule']) || isset($_POST['renumbercourse']);
    if ($submitted) {
        \mod_astra_course_config::updateOrCreate($cid, null, null, null, $module_numbering, $content_numbering);
        
        if (isset($_POST['save'])) {
            astra_rename_rounds_with_numbers($cid, $module_numbering);
            astra_update_exercise_gradebook_item_names($cid);
        } else if (isset($_POST['renumbermodule'])) {
            astra_renumber_rounds_and_exercises($cid, $module_numbering, false);
        } else if (isset($_POST['renumbercourse'])) {
            astra_renumber_rounds_and_exercises($cid, $module_numbering, true);
        }
        // sort Astra activities (Moodle course modules) in the course page
        foreach (astra_find_course_sections_with_astra_ex($cid) as $sectionNumber) {
            astra_sort_activities_in_section($cid, $sectionNumber);
        }
        // clear cache so that course main page shows the updated exercise round names (Moodle course modules)
        rebuild_course_cache($cid);
    }
}

astra_page_require($PAGE); // Bootstrap CSS etc.
// Print the page header.
$PAGE->set_pagelayout('incourse');
$PAGE->set_url(\mod_astra\urls\urls::editCourse($cid, true));
$PAGE->set_title(format_string(get_string('editcourse', mod_astra_exercise_round::MODNAME)));
$PAGE->set_heading(format_string($course->fullname));

// navbar
astra_edit_course_navbar($PAGE, $cid, true);

// Output starts here.
$output = $PAGE->get_renderer(mod_astra_exercise_round::MODNAME);

echo $output->header();

$renderable = new \mod_astra\output\edit_course_page($cid);
echo $output->render($renderable);

// Finish the page.
echo $output->footer();
