<?php
defined('MOODLE_INTERNAL') || die();

/* Notes about Moodle database
tables (default prefix to table names is mdl_)
course_sections  visible topic sections in the course page
course_modules  activity added to the course
modules  activity module name mapping to ids (e.g. astra -> 27)

Tables starting with astra are defined in mod/astra/db/install.xml
*/

/** Sort Astra exercise round activities in the given Moodle course section.
 * The exercise rounds are sorted according to Astra settings
 * (smaller ordernum comes first).
 * Non-Astra activities are kept before all Astra activities.
 * @param int $courseid Moodle course ID
 * @param int $course_section section number (0-N) in the course,
 * sorting affects only the activities in this section
 * @return void
 */
function astra_sort_activities_in_section($courseid, $course_section) {
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
    // Astra exercise round records in the course
    $astras = $DB->get_records(\mod_astra_exercise_round::TABLE, array('course' => $courseid));

    // sorting callback function for sorting an array of course module ids
    // (only Astra modules allowed in the array)
    // Order: assignment 1 is less than asgn 2, asgn 1 subassignments follow asgn 1
    // immediately before asgn 2, in alphabetical order of subasgn names
    $sortfunc = function($cmid1, $cmid2) use ($course_modinfo, $astras) {
        $cm1 = $course_modinfo[$cmid1];
        $cm2 = $course_modinfo[$cmid2];
        // figure out Astra round order numbers
        $order1 = $astras[$cm1->instance]->ordernum;
        $order2 = $astras[$cm2->instance]->ordernum;

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

    $non_astra_modules = array(); // cm ids
    $astra_modules = array();
    // cm ids in the section
    $course_module_ids = explode(',', trim($section_row->sequence));
    foreach ($course_module_ids as $cm_id) {
        $cm = $course_modinfo[$cm_id];
        if ($cm->modname == \mod_astra_exercise_round::TABLE) {
            $astra_modules[] = $cm_id;
        } else {
            $non_astra_modules[] = $cm_id;
        }
    }
    usort($astra_modules, $sortfunc); // sort Astra exercise round activities
    // add non-astra modules to the beginning
    $section_cm_ids = array_merge($non_astra_modules, $astra_modules);
    // write the new section ordering (sequence) to DB
    $new_section_sequence = implode(',', $section_cm_ids);
    $DB->set_field('course_sections', 'sequence', $new_section_sequence,
        array('id' => $section_row->id));
}

/**
 * Renumber (visible) exercise rounds and exercises.
 * @param int $courseid Moodle course ID
 * @param int $moduleNumberingStyle module numbering constant from mod_astra_course_config
 * @param bool $numberExercisesIgnoringModules if true, exercises are numbered from 1 to N over
 * all rounds instead of starting each round with 1
 */
function astra_renumber_rounds_and_exercises($courseid,
        $moduleNumberingStyle, $numberExercisesIgnoringModules = false) {
    // derived from A+ (a-plus/course/tree.py)
    $traverse = function($lobject, $start) use (&$traverse) {
        $n = $start;
        foreach ($lobject->getChildren() as $child) {
            $n += 1;
            $child->setOrder($n);
            $child->save();
            $traverse($child, 0);
        }
        return $n;
    };
    
    $roundOrder = 0;
    $exerciseOrder = 0;
    foreach (\mod_astra_exercise_round::getExerciseRoundsInCourse($courseid) as $exround) {
        $roundOrder += 1;
        $exround->setOrder($roundOrder);
        $name = \mod_astra_exercise_round::updateNameWithOrder($exround->getName(null, true),
                $roundOrder, $moduleNumberingStyle);
        $exround->setName($name);
        $exround->save();
        if (!$numberExercisesIgnoringModules) {
            $exerciseOrder = 0;
        }
        foreach ($exround->getLearningObjects() as $ex) {
            if (!$ex->getParentId()) { // top-level learning object
                $exerciseOrder += 1;
                $ex->setOrder($exerciseOrder);
                $ex->save();
                $traverse($ex, 0);
            }
        }
    }
}

/**
 * Rename exercise rounds using their ordinal numbers and the given numbering style.
 * @param int $courseid Moodle course ID
 * @param int $moduleNumberingStyle module numbering constant from mod_astra_course_config
 */
function astra_rename_rounds_with_numbers($courseid, $moduleNumberingStyle) {
    foreach (\mod_astra_exercise_round::getExerciseRoundsInCourse($courseid) as $exround) {
        $name = \mod_astra_exercise_round::updateNameWithOrder($exround->getName(null, true),
                $exround->getOrder(), $moduleNumberingStyle);
        $exround->setName($name);
        $exround->save();
    }
}

/**
 * Update exercise gradebook item names with the current exercise settings stored
 * in the database.
 * @param int $courseid Moodle course ID in which exercises are updated
 */
function astra_update_exercise_gradebook_item_names($courseid) {
    foreach (mod_astra_exercise_round::getExerciseRoundsInCourse($courseid) as $exround) {
        foreach ($exround->getExercises(false, false) as $ex) {
            // exercise name and order is read from the database,
            // gradebook item is updated and the new name is then visible in the gradebook
            $ex->updateGradebookItem();
        }
    }
}

/**
 * Sort Astra items in the Moodle gradebook corresponding to the hierarchical
 * order of the course content.
 * @param int $courseid
 */
function astra_sort_gradebook_items($courseid) {
    global $CFG;
    require_once($CFG->libdir . '/grade/grade_item.php');
    
    // retrieve gradebook grade items in the course 
    $params = array( // keys are column names of the grade_items table
            'courseid' => $courseid,
    );
    $gradeitems = grade_item::fetch_all($params);
    // fetch_all returns an array of grade_item instances or false if none found
    if (empty($gradeitems)) {
        return;
    }
    
    // retrieve the rounds and exercises of the course in the sorted order
    // store them in a different format that helps with sorting grade_items
    $rounds = mod_astra_exercise_round::getExerciseRoundsInCourse($courseid, true);
    $order = 1;
    $courseOrder = array();
    foreach ($rounds as $round) {
        $roundItems = array(0 => $order++);
        // round itself comes before the exercises, the round always has grade itemnumber zero
        foreach ($round->getExercises(true, true) as $ex) {
            $roundItems[$ex->getGradebookItemNumber()] = $order++;
        }
        $courseOrder[$round->getId()] = $roundItems;
        // $courseOrder[round ID][grade item number] = order (int) of the grade item
    }
    
    // callback functions for sorting the $gradeitems array
    $compare = function($x, $y) {
      if ($x < $y) {
          return -1;
      } else if ($x > $y) {
          return 1;
      }
      return 0;
    };
    
    $sortfunc = function($a, $b) use ($courseOrder, &$compare) {
        if ($a->itemtype === 'mod' && $a->itemmodule === mod_astra_exercise_round::TABLE
                && $b->itemtype === 'mod' && $b->itemmodule === mod_astra_exercise_round::TABLE) {
            // both grade_items are for Astra
            // $grade_item->iteminstance is the round id and itemnumber is set by the plugin
            // (zero for rounds and greater for exercises)
            return $compare($courseOrder[$a->iteminstance][$a->itemnumber],
                    $courseOrder[$b->iteminstance][$b->itemnumber]);
        }
        // at least one grade item originates from outside Astra:
        // sort them according to the old sort order
        return $compare($a->sortorder, $b->sortorder);
    };
    
    // sort $gradeitems array and then renumber the sortorder fields
    usort($gradeitems, $sortfunc);
    $sortorder = 1;
    foreach ($gradeitems as $item) {
        $item->set_sortorder($sortorder);
        $sortorder += 1;
    }
}

/**
 * Return an array of course section numbers (0-N) that contain Astra exercise rounds.
 * @param int $courseid Moodle course ID
 * @return array of section numbers
 */
function astra_find_course_sections_with_astra_ex($courseid) {
    global $DB;
    
    $sql = "SELECT DISTINCT section FROM {course_sections} WHERE id IN (" .
            "SELECT DISTINCT section FROM {course_modules} WHERE course = ? AND module = " .
           "(SELECT id FROM {modules} WHERE name = '". \mod_astra_exercise_round::TABLE ."'))";
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
function astra_edit_course_navbar(moodle_page $page, $courseid, $active = true) {
    $courseNav = $page->navigation->find($courseid, navigation_node::TYPE_COURSE);
    $editNav = $courseNav->add(get_string('editcourse', mod_astra_exercise_round::MODNAME),
            \mod_astra\urls\urls::editCourse($courseid, true),
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
function astra_edit_course_navbar_add(moodle_page $page, $courseid, $title, moodle_url $url, $navkey) {
    $editCourseNav = astra_edit_course_navbar($page, $courseid, false);
    $nav = $editCourseNav->add($title, $url, navigation_node::TYPE_CUSTOM, null, $navkey);
    $nav->make_active();
    return $nav;
}

/**
 * Add deviations list page to the navbar.
 * @param moodle_page $page $PAGE
 * @param int $courseid
 * @param bool $active
 * @return navigation_node
 */
function astra_deviations_navbar(moodle_page $page, $courseid, $active = true) {
    $courseNav = $page->navigation->find($courseid, navigation_node::TYPE_COURSE);
    $deviNav = $courseNav->add(get_string('deviations', mod_astra_exercise_round::MODNAME),
            \mod_astra\urls\urls::deviations($courseid, true),
            navigation_node::TYPE_CUSTOM, null, 'deviations');
    if ($active) {
        $deviNav->make_active();
    }
    return $deviNav;
}

/**
 * Add both deviations list page and another page to the navbar.
 * @param moodle_page $page $PAGE
 * @param int $courseid
 * @param string $title
 * @param moodle_url $url
 * @param string $navkey
 */
function astra_deviations_navbar_add(moodle_page $page, $courseid, $title, moodle_url $url, $navkey) {
    $deviNav = astra_deviations_navbar($page, $courseid, false);
    $nav = $deviNav->add($title, $url, navigation_node::TYPE_CUSTOM, null, $navkey);
    $nav->make_active();
    return $nav;
}
