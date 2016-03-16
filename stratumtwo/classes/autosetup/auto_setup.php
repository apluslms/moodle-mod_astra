<?php
namespace mod_stratumtwo\autosetup;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot .'/course/lib.php');
require_once(dirname(dirname(dirname(__FILE__))) .'/teachers/editcourse_lib.php');

/**
 * Automatic configuration of course content based on the configuration
 * downloaded from the exercise service.
 *
 * Derived from A+ (a-plus/edit_course/operations/configure.py).
 */
class auto_setup {
    
    public function __construct() {
    }
    
    /**
     * Configure course content (exercises, rounds and categories) based on
     * the configuration downloaded from the URL. Creates new content
     * and updates existing content in Moodle, depending on what already exists
     * in Moodle. Hides content that exists in Moodle but is not listed in the
     * configuration.
     * @param int $courseid Moodle course ID
     * @param int $sectionNumber Moodle course section number (0-N) which new rounds are added to
     * @param string $url URL which the configuration is downloaded from
     * @param string $api_key API key used with the URL, null if not used
     * @return array of error strings, empty if there were no errors
     */
    public static function configure_content_from_url($courseid, $sectionNumber, $url, $api_key = null) {
        $setup = new self();
        try {
            $response = \mod_stratumtwo\protocol\remote_page::request($url, false, null, null, $api_key);
        } catch (\mod_stratumtwo\protocol\remote_page_exception $e) {
            return array($e->getMessage());
        }
        // save API key and config URL
        \mod_stratumtwo_course_config::updateOrCreate($courseid, $sectionNumber, $api_key, $url);
        
        $conf = \json_decode($response);
        if ($conf === null) {
            return array(\get_string('configjsonparseerror', \mod_stratumtwo_exercise_round::MODNAME));
        }
        
        return $setup->configure_content($courseid, $sectionNumber, $conf);
    }
    
    /**
     * Configure course content (exercises, rounds and categories) based on
     * the configuration JSON. Creates new content
     * and updates existing content in Moodle, depending on what already exists
     * in Moodle. Hides content that exists in Moodle but is not listed in the
     * configuration.
     * @param int $courseid Moodle course ID
     * @param int Moodle course section number (0-N) which new rounds are added to
     * @param stdClass $conf configuration JSON
     * @return array of error strings, empty if there were no errors
     */
    public function configure_content($courseid, $sectionNumber, $conf) {
        global $DB;

        if (!isset($conf->categories) || ! \is_object($conf->categories)) {
            return array(\get_string('configcategoriesmissing', \mod_stratumtwo_exercise_round::MODNAME));
        }
        if (!isset($conf->modules) || ! \is_array($conf->modules)) {
            return array(\get_string('configmodulesmissing', \mod_stratumtwo_exercise_round::MODNAME));
        }

        $errors = array();
    
        // parse categories
        $categories = $this->configure_categories($courseid, $conf->categories, $errors);
        
        // section 0 is always visible in the MyCourses theme (course home page)
        /* NOTE: new activities become "orphaned" and unavailable when they are added to sections that
         do not exist in the course, manually adding new sections to the course fixes it ->
         table course_format_options, field name=numsections and value=int
         */
        // if the section has activities before creating the assignments,
        // the section contents need to be sorted afterwards
        $section_row = $DB->get_record('course_sections', array(
                'course'  => $courseid,
                'section' => $sectionNumber,
        ), 'id, sequence', IGNORE_MISSING);
        if ($section_row === false || \trim($section_row->sequence) == false) {
            $must_sort = false;
        } else {
            $must_sort = true;
        }
        
        // parse course modules (exercise rounds)
        $seen_modules = array();
        $seen_exercises = array();
        $module_order = 0;
        $exercise_order = 0;
        foreach ($conf->modules as $module) {
            try {
                list($module_order, $exercise_order) = $this->configure_exercise_round(
                        $courseid, $sectionNumber, $module, $module_order, $exercise_order, $categories,
                        $seen_modules, $seen_exercises, $errors);
            } catch (\Exception $e) {
                $errors[] = $e->getMessage();
            }
        }
        
        // hide rounds and exercises that exist in Moodle but were not seen in the config
        foreach (\mod_stratumtwo_exercise_round::getExerciseRoundsInCourse($courseid) as $exround) {
            if (! \in_array($exround->getId(), $seen_modules)) {
                $exround->setStatus(\mod_stratumtwo_exercise_round::STATUS_HIDDEN);
                $exround->save();
            }
            foreach ($exround->getExercises() as $ex) {
                if (! \in_array($ex->getId(), $seen_exercises)) {
                    $ex->setStatus(\mod_stratumtwo_exercise::STATUS_HIDDEN);
                    $ex->save();
                }
            }
        }
        
        if ($must_sort && empty($errors)) {
            // Sort the activities in the section
            \stratumtwo_sort_activities_in_section($courseid, $sectionNumber);
            \rebuild_course_cache($courseid, true);
        }
        
        // clean up obsolete categories
        foreach (\mod_stratumtwo_category::getCategoriesInCourse($courseid) as $cat) {
            if ($cat->getStatus() == \mod_stratumtwo_category::STATUS_HIDDEN &&
                    $cat->countExercises() == 0) {
                $cat->delete();
            }
        }
        
        return $errors;
    }
    
    /**
     * Configure one exercise round with its exercises. Updates an existing round
     * or creates a new one, similarly for the exercises.
     * @param int $courseid Moodle course ID where the exercise round is added/modified
     * @param int $sectionNumber Moodle course section number (0-N) which a new round is added to
     * @param \stdClass $module configuration JSON
     * @param int $module_order ordering number for modules, if config JSON does not specify it
     * @param int $exercise_order ordering number for exercises, if exercises
     * are numerated ignoring modules
     * @param array $categories associative array of categories in the course, indexed by keys
     * @param array $seen_modules exercise round IDs that have been seen in the config JSON
     * @param array $seen_exercises exercise IDs that have been seen in the config JSON
     * @param array $errors
     */
    protected function configure_exercise_round($courseid, $sectionNumber, \stdClass $module,
            $module_order, $exercise_order, array &$categories,
            array &$seen_modules, array &$seen_exercises, array &$errors) {
        global $DB;
        
        if (!isset($module->key)) {
            $errors[] = \get_string('configmodkeymissing', \mod_stratumtwo_exercise_round::MODNAME);
            return;
        }
        // either update existing exercise round or create new
        $roundRecord = $DB->get_record(\mod_stratumtwo_exercise_round::TABLE, array(
                'course' => $courseid,
                'remotekey' => $module->key,
        ));
        if ($roundRecord == false) { // create new
            $roundRecord = new \stdClass();
            $roundRecord->course = $courseid;
            $roundRecord->remotekey = $module->key;
        }
        
        if (isset($module->order)) {
            $order = $this->parseInt($module->order, $errors);
            if ($order !== null) {
                $roundRecord->ordernum = $order;
            } else {
                $roundRecord->ordernum = 1;
            }
        } else {
            $module_order += 1;
            $roundRecord->ordernum = $module_order;
        }
        $moduleName = null;
        if (isset($module->title)) {
            $moduleName = (string) $module->title;
        } else if (isset($module->name)) {
            $moduleName = (string) $module->name;
        }
        if (!isset($moduleName)) {
            $moduleName = '-';
        }
        $roundRecord->name = "{$roundRecord->ordernum}. $moduleName";
        // In order to show the ordinal number of the exercise round in the Moodle course page,
        // the number must be stored in the name.
        
        if (isset($module->status))
            $roundRecord->status = $this->parseModuleStatus($module->status, $errors);
        if (!isset($roundRecord->status))
            $roundRecord->status = \mod_stratumtwo_exercise_round::STATUS_READY;
        
        if (isset($module->points_to_pass)) {
            $p = $this->parseInt($module->points_to_pass, $errors);
            if ($p !== null)
                $roundRecord->pointstopass = $p;
        }
        
        if (isset($module->open)) {
            $d = $this->parseDate($module->open, $errors);
            if ($d !== null)
                $roundRecord->openingtime = $d;
        }
        if (!isset($roundRecord->openingtime)) {
            $roundRecord->openingtime = \time();
        }
        
        if (isset($module->close)) {
            $d = $this->parseDate($module->close, $errors);
            if ($d !== null)
                $roundRecord->closingtime = $d;
        } else if (isset($module->duration)) {
            $d = $this->parseDuration($roundRecord->openingtime, $module->duration, $errors);
            if ($d !== null)
                $roundRecord->closingtime = $d;
        }
        if (!isset($roundRecord->closingtime))
            $roundRecord->closingtime = \time() + 1;
        
        if (isset($module->late_close)) {
            $d = $this->parseDate($module->late_close, $errors);
            if ($d !== null) {
                $roundRecord->latesbmsdl = $d;
                $roundRecord->latesbmsallowed = 1;
            }
        } else if (isset($module->late_duration)) {
            $d = $this->parseDuration($roundRecord->closingtime, $module->late_duration, $errors);
            if ($d !== null) {
                $roundRecord->latesbmsdl = $d;
                $roundRecord->latesbmsallowed = 1;
            }
        }
        
        if (isset($module->late_penalty)) {
            $f = $this->parseFloat($module->late_penalty, $errors);
            if ($f !== null)
                $roundRecord->latesbmspenalty = $f;
        }
        
        if (isset($module->introduction)) {
            $introText = (string) $module->introduction;
        } else {
            $introText = '';
        }
        $roundRecord->introeditor = array(
                'text' => $introText,
                'format' => \FORMAT_HTML,
                'itemid' => 0,
        );
        
        // Moodle course module visibility
        $roundRecord->visible = ($roundRecord->status != \mod_stratumtwo_exercise_round::STATUS_HIDDEN) ? 1 : 0;
        
        if (isset($roundRecord->id)) {
            // update existing exercise round
            // settings for the Moodle course module
            $exround = new \mod_stratumtwo_exercise_round($roundRecord);
            $cm = $exround->getCourseModule();
            $roundRecord->coursemodule = $cm->id; // Moodle course module ID
            $roundRecord->cmidnumber = $cm->idnumber; // keep the old Moodle course module idnumber
            
            \update_module($roundRecord); // throws moodle_exception
            
        } else {
            // create new exercise round
            // settings for the Moodle course module
            $roundRecord->modulename = \mod_stratumtwo_exercise_round::TABLE;
            $roundRecord->section = $sectionNumber;
            $roundRecord->cmidnumber = ''; // Moodle course module idnumber, unused
            
            $moduleinfo = \create_module($roundRecord); // throws moodle_exception
            $exround = \mod_stratumtwo_exercise_round::createFromId($moduleinfo->instance);
        }
        
        $seen_modules[] = $exround->getId();
        
        if (!(isset($module->numerate_ignoring_modules) && $this->parseBool($module->numerate_ignoring_modules, $errors))) {
            $exercise_order = 0;
        }
        
        // parse exercises in the exercise round
        if (isset($module->children)) {
            $exercise_order = $this->configure_exercises($categories, $exround, $module->children,
                    $seen_exercises, $errors, null, $exercise_order);
        }
        
        return array($module_order, $exercise_order);
    }
    
    /**
     * Configure exercise categories in the course using the configuration JSON.
     * @param int $courseid Moodle course ID
     * @param stdClass $categoriesConf configuration JSON
     * @param array $errors possible errors are added here
     * @return array of mod_stratumtwo_category objects indexed by category keys
     */
    protected function configure_categories($courseid, \stdClass $categoriesConf, &$errors) {
        $categories = array();
        $seen_cats = array();
        foreach ($categoriesConf as $key => $cat) {
            if (!isset($cat->name)) {
                $errors[] = \get_string('configcatnamemissing', \mod_stratumtwo_exercise_round::MODNAME);
                continue;
            }
            $catRecord = new \stdClass();
            $catRecord->course = $courseid;
            $catRecord->name = $cat->name;
            if (isset($cat->status)) {
                $catRecord->status = $this->parseCategoryStatus($cat->status, $errors);
            }
            if (isset($cat->points_to_pass)) {
                $catRecord->pointstopass = $this->parseInt($cat->points_to_pass, $errors);
                if ($catRecord->pointstopass === null)
                    unset($catRecord->pointstopass);
            }
            $category = \mod_stratumtwo_category::createFromId(\mod_stratumtwo_category::updateOrCreate($catRecord));
            $categories[$key] = $category;
            $seen_cats[] = $category->getId();
        }
        
        // hide categories that exist in Moodle but were not seen in the config
        foreach (\mod_stratumtwo_category::getCategoriesInCourse($courseid) as $id => $cat) {
            if (! \in_array($id, $seen_cats)) {
                $cat->setHidden();
                $cat->save();
            }
        }
        
        return $categories;
    }
    
    /**
     * Configure exercises (create/update) in an exercise round based on
     * the configuration JSON.
     * @param array $categories \mod_stratumtwo_category objects indexed by keys
     * @param \mod_stratumtwo_exercise_round $exround
     * @param array $config configuration JSON of the exercises
     * @param array $seen array of exercise IDs that have been seen in the config
     * @param array $errors
     * @param \mod_stratumtwo_exercise $parent set if the exercise is listed under another exercise,
     * null if there is no parent exercise.
     * @param int $n ordering number
     * @return int new ordering number, use if exercises are numerated course-wide
     */
    protected function configure_exercises(array &$categories, \mod_stratumtwo_exercise_round $exround,
            array $config, array &$seen, array &$errors, \mod_stratumtwo_exercise $parent = null, $n = 0) {
        global $DB;
        
        foreach ($config as $o) {
            if (!isset($o->key)) {
                $errors[] = \get_string('configexercisekeymissing', \mod_stratumtwo_exercise_round::MODNAME);
                continue;
            }
            if (!isset($o->category)) {
                $errors[] = \get_string('configexercisecatmissing', \mod_stratumtwo_exercise_round::MODNAME);
                continue;
            }
            if (!isset($categories[$o->category])) {
                $errors[] = \get_string('configexerciseunknowncat', \mod_stratumtwo_exercise_round::MODNAME, $o->category);
                continue;
            }
            if (!isset($o->max_submissions)) {
                $errors[] = \get_string('configchapternotsupported', \mod_stratumtwo_exercise_round::MODNAME);
                continue;
            }
            
            // find if an exercise with the key exists in the same course as the exercise round
            $exerciseRecord = $DB->get_record_select(\mod_stratumtwo_exercise::TABLE,
                    'remotekey = ? AND roundid IN (SELECT id FROM {'. \mod_stratumtwo_exercise_round::TABLE .'} WHERE course = ?)',
                    array($o->key, $exround->getCourse()->courseid), '*', IGNORE_MISSING);
            if ($exerciseRecord === false) {
                // create new later
                $exerciseRecord = new \stdClass();
                $exerciseRecord->remotekey = $o->key;
            }

            $exerciseRecord->roundid = $exround->getId();
            $exerciseRecord->categoryid = $categories[$o->category]->getId();
            if ($parent !== null)
                $exerciseRecord->parentid = $parent->getId();
            
            if (isset($o->allow_assistant_grading)) {
                $exerciseRecord->allowastgrading = $this->parseBool($o->allow_assistant_grading, $errors);
            }
            
            $maxsbms = $this->parseInt($o->max_submissions, $errors);
            if ($maxsbms !== null)
                $exerciseRecord->maxsubmissions = $maxsbms;
            
            $oldMaxPoints = 0;
            if (isset($exerciseRecord->maxpoints))
                $oldMaxPoints = $exerciseRecord->maxpoints;
            if (isset($o->max_points)) {
                $maxpoints = $this->parseInt($o->max_points, $errors);
                if ($maxpoints !== null)
                    $exerciseRecord->maxpoints = $maxpoints;
            }
            if (!isset($exerciseRecord->maxpoints))
                $exerciseRecord->maxpoints = 100;
            
            if (isset($o->points_to_pass)) {
                $pointstopass = $this->parseInt($o->points_to_pass, $errors);
                if ($pointstopass !== null)
                    $exerciseRecord->pointstopass = $pointstopass;
            }
            if (!isset($exerciseRecord->pointstopass))
                $exerciseRecord->pointstopass = 0;
            
            if (isset($o->order)) {
                $order = $this->parseInt($o->order, $errors);
                if ($order !== null)
                    $exerciseRecord->ordernum = $order;
            } else {
                $n += 1;
                $exerciseRecord->ordernum = $n;
            }
            
            if (isset($o->url)) {
                $exerciseRecord->serviceurl = (string) $o->url;
            }
            if (isset($o->status)) {
                $exerciseRecord->status = $this->parseExerciseStatus($o->status, $errors);
            }

            if (isset($o->title)) {
                $exerciseRecord->name = (string) $o->title;
            } else if (isset($o->name)) {
                $exerciseRecord->name = (string) $o->name;
            }
            if (empty($exerciseRecord->name)) {
                $exerciseRecord->name = '-';
            }
            
            
            if (isset($exerciseRecord->id)) {
                // update existing
                $exercise = new \mod_stratumtwo_exercise($exerciseRecord);
                $exercise->save(); // updates gradebook for exercise 
                // update gradebook for exercise round (changed max points)
                $exround->updateMaxPoints($exerciseRecord->maxpoints - $oldMaxPoints);
            } else {
                // create new exercise
                $exercise = $exround->createNewExercise($exerciseRecord, $categories[$o->category]);
            }
            
            $seen[] = $exercise->getId();
            
            if (isset($o->children)) {
                $this->configure_exercises($categories, $exround, $o->children, $seen, $errors, $exercise);
            }
        }
        return $n;
    }
    
    protected function parseExerciseStatus($value, &$errors) {
        switch ($value) {
            case 'ready':
                return \mod_stratumtwo_exercise::STATUS_READY;
                break;
            case 'hidden':
                return \mod_stratumtwo_exercise::STATUS_HIDDEN;
                break;
            case 'maintenance':
                return \mod_stratumtwo_exercise::STATUS_MAINTENANCE;
                break;
            default:
                $errors[] = \get_string('configbadstatus', \mod_stratumtwo_exercise_round::MODNAME, $value);
                return \mod_stratumtwo_exercise::STATUS_HIDDEN;
        }
    }
    
    protected function parseModuleStatus($value, &$errors) {
        switch ($value) {
            case 'ready':
                return \mod_stratumtwo_exercise_round::STATUS_READY;
                break;
            case 'hidden':
                return \mod_stratumtwo_exercise_round::STATUS_HIDDEN;
                break;
            case 'maintenance':
                return \mod_stratumtwo_exercise_round::STATUS_MAINTENANCE;
                break;
            default:
                $errors[] = \get_string('configbadstatus', \mod_stratumtwo_exercise_round::MODNAME, $value);
                return \mod_stratumtwo_exercise_round::STATUS_HIDDEN;
        }
    }
    
    protected function parseCategoryStatus($value, &$errors) {
        switch ($value) {
            case 'ready':
                return \mod_stratumtwo_category::STATUS_READY;
                break;
            case 'hidden':
                return \mod_stratumtwo_category::STATUS_HIDDEN;
                break;
            default:
                $errors[] = \get_string('configbadstatus', \mod_stratumtwo_exercise_round::MODNAME, $value);
                return \mod_stratumtwo_category::STATUS_HIDDEN;
        }
    }
    
    protected function parseInt($value, &$errors) {
        if (\is_numeric($value))
            return (int) $value;
        else {
            $errors[] = \get_string('configbadint', \mod_stratumtwo_exercise_round::MODNAME, $value);
            return null;
        }
    }
    
    protected function parseFloat($value, &$errors) {
        if (\is_numeric($value))
            return (float) $value;
        else {
            $errors[] = \get_string('configbadfloat', \mod_stratumtwo_exercise_round::MODNAME, $value);
            return null;
        }
    }
    
    protected function parseBool($value, &$errors) {
        return ($value === true || 
                (\is_string($value) && \in_array($value, array('yes', 'Yes', 'true', 'True'))));
    }
    
    protected function parseDate($value, &$errors) {
        // example: 2016-01-27T23:59:55UTC
        // literal T in the middle (\T), timezone T at the end
        $formats = array('Y-m-d\TH:i:sT', 'Y-m-d\TH:i:s', 'Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d H', 'Y-m-d');
        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, $value);
            if ($date !== false)
                return $date->getTimestamp();
        }
        $errors[] = \get_string('configbaddate', \mod_stratumtwo_exercise_round::MODNAME, $value);
        return null;
    }
    
    protected function parseDuration($openingTime, $duration, &$errors) {
        $len = \strlen($duration);
        if ($len > 1) {
            $unit = $duration[$len - 1];
            $value = \substr($duration, 0, $len - 1);
            if (\is_numeric($value)) {
                $value = (int) $value;
                if (\in_array(\strtolower($unit), array('h', 'm', 's')))
                    $intervalSpec = "PT$value". \strtoupper($unit);
                else
                    $intervalSpec = "P$value". \strtoupper($unit);
                try {
                    $interval = new \DateInterval($intervalSpec);
                    $start = new \DateTime("@$openingTime"); // from Unix timestamp
                    $start->add($interval);
                    return $start->getTimestamp();
                    
                } catch (\Exception $e) {
                    // invalid interval, error string is added below
                }
            }
        }
        $errors[] = \get_string('configbadduration', \mod_stratumtwo_exercise_round::MODNAME, $duration);
        return null;
    }
}