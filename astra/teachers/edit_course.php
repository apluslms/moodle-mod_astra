<?php
/** Page that lets the user edit/add/delete Astra exercises in a Moodle course.
 * The user can modify settings manually or create and update exercises automatically
 * based on the configuration in the Astra exercise service.
 */
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php'); // defines MOODLE_INTERNAL for libraries
require_once($CFG->libdir . '/gradelib.php');
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
        // renumerate rounds or learning objects
        \mod_astra_course_config::updateOrCreate($cid, null, null, null, $module_numbering, $content_numbering);
        
        if (isset($_POST['save'])) {
            astra_rename_rounds_with_numbers($cid, $module_numbering);
        } else if (isset($_POST['renumbermodule'])) {
            astra_renumber_rounds_and_exercises($cid, $module_numbering, false);
        } else if (isset($_POST['renumbercourse'])) {
            astra_renumber_rounds_and_exercises($cid, $module_numbering, true);
        }
        // sort Astra activities (Moodle course modules) in the course page
        foreach (astra_find_course_sections_with_astra_ex($cid) as $sectionNumber) {
            astra_sort_activities_in_section($cid, $sectionNumber);
        }
        // clear (Moodle) cache so that course main page shows the updated exercise round names (Moodle course modules)
        rebuild_course_cache($cid);

    } else if (isset($_POST['cache'])) {
        // clear the Astra exercise/learning object description cache for the course
        \mod_astra\cache\exercise_cache::invalidate_course($cid);
        // use the Moodle redirect function to show a message to the user (caches successfully cleared)
        // It is clunky and old-fashioned, but Moodle does not have other built-in method for such flash messages.
        // Moodle 3.1 seems to have a new notification API for this purpose.
        redirect(\mod_astra\urls\urls::editCourse($cid, true), get_string('cachescleared', mod_astra_exercise_round::MODNAME));
        exit(0);
    } else if (isset($_POST['syncgradebook'])) {
        // Delete Astra grade items from the gradebook and recreate them.
        $exrounds = mod_astra_exercise_round::getExerciseRoundsInCourse($cid, true);
        $errors = array();
        foreach ($exrounds as $exround) {
            $res1 = $exround->deleteGradebookItem();
            $res2 = $exround->updateGradebookItem();
            $res3 = false;
            if ($res2 === GRADE_UPDATE_OK) {
                $res3 = $exround->writeAllGradesToGradebook();
            }

            if ($res1 !== GRADE_UPDATE_OK) {
                $errors[] = get_string('errorgradesyncdelete', mod_astra_exercise_round::MODNAME) . $exround->getName();
            }
            if ($res2 !== GRADE_UPDATE_OK) {
                $errors[] = get_string('errorgradesyncupdate', mod_astra_exercise_round::MODNAME) . $exround->getName();
            }
            if ($res3 !== GRADE_UPDATE_OK) {
                $errors[] = get_string('errorgradesyncwrite', mod_astra_exercise_round::MODNAME) . $exround->getName();
            }
        }
        if (empty($errors)) {
            $msg = get_string('gradesyncsuccess', mod_astra_exercise_round::MODNAME);
            $type = \core\output\notification::NOTIFY_SUCCESS;
        } else {
            $msg = html_writer::alist($errors);
            $type = \core\output\notification::NOTIFY_ERROR;
        }
        redirect(\mod_astra\urls\urls::editCourse($cid, true), $msg, null, $type);
        exit(0);
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
