<?php
defined('MOODLE_INTERNAL') || die();

/* Notes about Moodle database
tables (default prefix to table names is mdl_)
course_sections  visible topic sections in the course page
course_modules  activity added to the course
modules  activity module name mapping to ids (e.g. stratumtwo -> 27)

Tables starting with stratumtwo are defined in mod/stratumtwo/db/install.xml
*/

/** Sort Stratum2 exercise round activities in the given Moodle course section.
 * The exercise rounds are sorted according to Stratum2 settings
 * (smaller ordernum comes first).
 * Non-Stratum2 activities are kept before all Stratum2 activities.
 * @param int $courseid Moodle course ID
 * @param int $course_section section number (0-N) in the course,
 * sorting affects only the activities in this section
 * @return void
 */
function stratumtwo_sort_activities_in_section($courseid, $course_section) {
    global $DB;
    $section_row = $DB->get_record('course_sections', array(
        'course'  => $courseid,
        'section' => $course_section,
    ), 'id, sequence', IGNORE_MISSING);
    if ($section_row === false) {
        return;
    }
    // course module records
    $course_modinfo = get_fast_modinfo($courseid)->cms; // indexes are course module ids
    // Stratum2 exercise round records in the course
    $stratumtwos = $DB->get_records(\mod_stratumtwo_exercise_round::TABLE, array('course' => $courseid));

    // sorting callback function for sorting an array of course module ids
    // (only Stratum modules allowed in the array)
    // Order: assignment 1 is less than asgn 2, asgn 1 subassignments follow asgn 1
    // immediately before asgn 2, in alphabetical order of subasgn names
    $sortfunc = function($cmid1, $cmid2) use ($course_modinfo, $stratumtwos) {
        $cm1 = $course_modinfo[$cmid1];
        $cm2 = $course_modinfo[$cmid2];
        // figure out Stratum2 round order numbers
        $order1 = $stratumtwos[$cm1->instance]->ordernum;
        $order2 = $stratumtwos[$cm2->instance]->ordernum;

        // must return an integer less than, equal to, or greater than zero if the
        // first argument is considered to be respectively less than, equal to, or
        // greater than the second
        if ($order1 < $order2) {
            return -1;
        } else if ($order2 < $order1) {
            return 1;
        } else { // same order number
            if ($cm1->instance < $cm2->instance) // compare IDs
                return -1;
            else if ($cm1->instance > $cm2->instance)
                return 1;
            else
                return 0;
        }
    };

    $non_stratum_modules = array(); // cm ids
    $stratum_modules = array();
    // cm ids in the section
    $course_module_ids = explode(',', trim($section_row->sequence));
    foreach ($course_module_ids as $cm_id) {
        $cm = $course_modinfo[$cm_id];
        if ($cm->modname == \mod_stratumtwo_exercise_round::TABLE) {
            $stratum_modules[] = $cm_id;
        } else {
            $non_stratum_modules[] = $cm_id;
        }
    }
    usort($stratum_modules, $sortfunc); // sort Stratum2 exercise round activities
    // add non-stratum modules to the beginning
    $section_cm_ids = array_merge($non_stratum_modules, $stratum_modules);
    // write the new section ordering (sequence) to DB
    $new_section_sequence = implode(',', $section_cm_ids);
    $DB->set_field('course_sections', 'sequence', $new_section_sequence,
        array('id' => $section_row->id));
}

/**
 * Renumber (visible) exercise rounds and exercises.
 * @param int $courseid Moodle course ID
 * @param int $moduleNumberingStyle module numbering constant from mod_stratumtwo_course_config
 * @param bool $numberExercisesIgnoringModules if true, exercises are numbered from 1 to N over
 * all rounds instead of starting each round with 1
 */
function stratumtwo_renumber_rounds_and_exercises($courseid,
        $moduleNumberingStyle, $numberExercisesIgnoringModules = false) {
    $roundOrder = 0;
    $exerciseOrder = 0;
    foreach (\mod_stratumtwo_exercise_round::getExerciseRoundsInCourse($courseid) as $exround) {
        $roundOrder += 1;
        $exround->setOrder($roundOrder);
        $name = \mod_stratumtwo_exercise_round::updateNameWithOrder($exround->getName(),
                $roundOrder, $moduleNumberingStyle);
        $exround->setName($name);
        $exround->save();
        if (!$numberExercisesIgnoringModules) {
            $exerciseOrder = 0;
        }
        foreach ($exround->getExercises() as $ex) {
            $exerciseOrder += 1;
            $ex->setOrder($exerciseOrder);
            $ex->save();
        }
    }
}

/**
 * Rename exercise rounds using their ordinal numbers and the given numbering style.
 * @param int $courseid Moodle course ID
 * @param int $moduleNumberingStyle module numbering constant from mod_stratumtwo_course_config
 */
function stratumtwo_rename_rounds_with_numbers($courseid, $moduleNumberingStyle) {
    foreach (\mod_stratumtwo_exercise_round::getExerciseRoundsInCourse($courseid) as $exround) {
        $name = \mod_stratumtwo_exercise_round::updateNameWithOrder($exround->getName(),
                $exround->getOrder(), $moduleNumberingStyle);
        $exround->setName($name);
        $exround->save();
    }
}

/**
 * Return an array of course section numbers (0-N) that contain Stratum2 exercise rounds.
 * @param int $courseid Moodle course ID
 * @return array of section numbers
 */
function stratumtwo_find_course_sections_with_stratum_ex($courseid) {
    global $DB;
    
    $sql = "SELECT DISTINCT section FROM {course_sections} WHERE id IN (" .
            "SELECT DISTINCT section FROM {course_modules} WHERE course = ? AND module = " .
           "(SELECT id FROM {modules} WHERE name = '". \mod_stratumtwo_exercise_round::TABLE ."'))";
    $section_records = $DB->get_records_sql($sql, array($courseid));
    $sections = array();
    foreach ($section_records as $sec) {
        $sections[] = $sec->section;
    }
    return $sections;
}

/**
 * Add edit course page to the navbar.
 * @param moodle_page $page $PAGE
 * @param int $courseid
 * @param bool $active
 * @return navigation_node
 */
function stratumtwo_edit_course_navbar(moodle_page $page, $courseid, $active = true) {
    $courseNav = $page->navigation->find($courseid, navigation_node::TYPE_COURSE);
    $editNav = $courseNav->add(get_string('editcourse', mod_stratumtwo_exercise_round::MODNAME),
            \mod_stratumtwo\urls\urls::editCourse($courseid, true),
            navigation_node::TYPE_CUSTOM, null, 'editcourse');
    if ($active) {
        $editNav->make_active();
    }
    return $editNav;
}

/**
 * Add both edit course and another page to the navbar.
 * @param moodle_page $page
 * @param int $courseid
 * @param string $title title for the new page
 * @param moodle_url $url URL of the new page
 * @param string $navkey navbar key for the new page
 * @return navigation_node
 */
function stratumtwo_edit_course_navbar_add(moodle_page $page, $courseid, $title, moodle_url $url, $navkey) {
    $editCourseNav = stratumtwo_edit_course_navbar($page, $courseid, false);
    $nav = $editCourseNav->add($title, $url, navigation_node::TYPE_CUSTOM, null, $navkey);
    $nav->make_active();
    return $nav;
}
