<?php
namespace mod_astra\autosetup;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot .'/course/lib.php');
require_once(dirname(dirname(dirname(__FILE__))) .'/teachers/editcourse_lib.php');
require_once($CFG->dirroot .'/enrol/locallib.php');

/**
 * Automatic configuration of course content based on the configuration
 * downloaded from the exercise service.
 *
 * Derived from A+ (a-plus/edit_course/operations/configure.py).
 */
class auto_setup {
    
    protected $numerate_ignoring_modules = false;
    protected $languages;
    protected $json; // the whole parsed configuration
    
    public function __construct() {
    }
    
    /**
     * Configure course content (exercises, chapters, rounds and categories) based on
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
            list($response, $response_headers) = \mod_astra\protocol\remote_page::request($url, false, null, null, $api_key);
        } catch (\mod_astra\protocol\remote_page_exception $e) {
            return array($e->getMessage());
        }
        
        $conf = \json_decode($response);
        if ($conf === null) {
            // save API key and config URL
            \mod_astra_course_config::updateOrCreate($courseid, $sectionNumber, $api_key, $url);
            return array(\get_string('configjsonparseerror', \mod_astra_exercise_round::MODNAME));
        }
        
        $lang = isset($conf->lang) ? $conf->lang : null;
        // save API key and config URL
        \mod_astra_course_config::updateOrCreate($courseid, $sectionNumber, $api_key, $url,
                null, null, $lang);
        
        return $setup->configure_content($courseid, $sectionNumber, $conf);
    }
    
    /**
     * Configure course content (exercises, chapters, rounds and categories) based on
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

        $this->json = $conf;
        $lang = isset($conf->lang) ? $conf->lang : array('en');
        if (!is_array($lang)) {
            $lang = array($lang);
        }
        $this->languages = $lang;
        
        if (!isset($conf->categories) || ! \is_object($conf->categories)) {
            return array(\get_string('configcategoriesmissing', \mod_astra_exercise_round::MODNAME));
        }
        if (!isset($conf->modules) || ! \is_array($conf->modules)) {
            return array(\get_string('configmodulesmissing', \mod_astra_exercise_round::MODNAME));
        }

        $errors = array();
    
        // parse categories
        $categories = $this->configure_categories($courseid, $conf->categories, $errors);
        
        // section 0 should always exist and be visible by default (course home page)
        /* NOTE: new activities become "orphaned" and unavailable when they are added to sections that
         do not exist in the course, manually adding new sections to the course fixes it ->
         table course_format_options, field name=numsections and value=int
         */
        // if the section has activities before creating the assignments,
        // the section contents need to be sorted afterwards
        $course_modinfo = get_fast_modinfo($courseid, -1);
        $section_info = $course_modinfo->get_section_info($sectionNumber);
        if ($section_info === null) {
            $must_sort = false;
            $section_visible = false;
        } else {
            $must_sort = trim($section_info->sequence) != false;
            $section_visible = $section_info->visible;
        }
        
        // check whether the config defines course assistants and whether we can promote them
        // with the non-editing teacher role in Moodle
        if (isset($conf->assistants)) {
            $assistant_users = $this->parseStudentIdList($conf->assistants, $errors);
        } else {
            $assistant_users = array();
        }
        $course_ctx = \context_course::instance($courseid);
        $teacher_role_id = $DB->get_field('role', 'id', array('shortname' => 'teacher')); // non-editing teacher role
        // assume that the default Moodle role "non-editing teacher" exists in the Moodle site where
        // this plugin is installed and that it is a suitable role for course assistants
        // (other alternative would be to ask the user for the correct role)
        if ($teacher_role_id === false) {
            $assistant_users = array();
        } else if (!empty($assistant_users)) {
            if (!\has_capability('moodle/role:assign', $course_ctx) ||
                    !\array_key_exists($teacher_role_id, \get_assignable_roles($course_ctx))) {
                // ensure that the current user (teacher) is allowed to modify user roles in the course
                $errors[] = \get_string('configuserrolesdisallowed', \mod_astra_exercise_round::MODNAME);
                $assistant_users = array();
            }
        }
        // Enrol assistants to the course as non-editing teachers. Enrolment is needed so
        // that they may access the course page. They also gain non-editing teacher privileges
        // in the course. configure_exercise_round() will also give them the non-editing teacher
        // role in the course module contexts (exercise rounds), which is unnecessary if the
        // assistant has the role in the course level, but we may want to remove the course level
        // teacher role from the assistants.
        self::enrolUsersToCourse($assistant_users, $courseid, $teacher_role_id, $errors);
        
        // parse course modules (exercise rounds)
        $seen_modules = array();
        $seen_exercises = array();
        $module_order = 0;
        $exercise_order = 0;
        $this->numerate_ignoring_modules = isset($conf->numerate_ignoring_modules) ?
                $this->parseBool($conf->numerate_ignoring_modules, $errors) :
                false;
        foreach ($conf->modules as $module) {
            try {
                list($module_order, $exercise_order) = $this->configure_exercise_round(
                        $courseid, $sectionNumber, $section_visible, $module,
                        $module_order, $exercise_order, $categories,
                        $seen_modules, $seen_exercises, $errors, $assistant_users, $teacher_role_id);
            } catch (\Exception $e) {
                $errors[] = $e->getMessage();
            }
        }
        
        // hide rounds and exercises/chapters that exist in Moodle but were not seen in the config
        foreach (\mod_astra_exercise_round::getExerciseRoundsInCourse($courseid, true) as $exround) {
            $updateRoundMaxPoints = $exround->hideOrDeleteUnseenLearningObjects($seen_exercises);
            if (! \in_array($exround->getId(), $seen_modules)) {
                if (count($exround->getLearningObjects(true, false)) == 0) {
                    // no exercises in the round, delete the round
                    course_delete_module($exround->getCourseModule()->id);
                    $updateRoundMaxPoints = false;
                } else {
                    $exround->setStatus(\mod_astra_exercise_round::STATUS_HIDDEN);
                    $exround->save();
                    $updateRoundMaxPoints = true;
                }
            }
            if ($updateRoundMaxPoints) {
                $exround->updateMaxPoints();
            }
        }
        
        if ($must_sort && empty($errors)) {
            // Sort the activities in the section
            \astra_sort_activities_in_section($courseid, $sectionNumber);
            \rebuild_course_cache($courseid, true);
        }
        
        // sort the grade items in the gradebook
        astra_sort_gradebook_items($courseid);
        
        // clean up obsolete categories
        foreach (\mod_astra_category::getCategoriesInCourse($courseid, true) as $cat) {
            if ($cat->getStatus() == \mod_astra_category::STATUS_HIDDEN &&
                    $cat->countLearningObjects(true) == 0) {
                $cat->delete();
            }
        }
        
        // purge the exercise/learning object HTML description cache for the course
        \mod_astra\cache\exercise_cache::invalidate_course($courseid);
        
        return $errors;
    }
    
    /**
     * Enrol users to the course, which allows them to access the course page.
     * Users can be enrolled with student or teacher roles.
     * @param array $users array of Moodle user records (stdClass)
     * @param int $courseid Moodle course ID
     * @param int $roleid ID of the role that is assigned to users in the enrolment.
     * If null, no role is assigned but the user is still enrolled.
     * @param array $errors error messages are appended to this array
     */
    protected static function enrolUsersToCourse(array $users, $courseid, $roleid, array &$errors) {
        global $DB, $PAGE;
        
        if (empty($users)) {
            return;
        }
        
        $enrolid = $DB->get_field('enrol', 'id', array(
                'enrol' => 'manual',
                'courseid' => $courseid,
        ), \IGNORE_MISSING);
        // if manual enrolment is not supported, no users are enrolled
        if ($enrolid === false) {
            $errors[] = \get_string('confignomanualenrol', \mod_astra_exercise_round::MODNAME);
            return;
        }
        
        $enrol_manager = new \course_enrolment_manager($PAGE, \get_course($courseid));
        $instances = $enrol_manager->get_enrolment_instances();
        $plugins = $enrol_manager->get_enrolment_plugins(true); // Do not allow actions on disabled plugins.
        if (!\array_key_exists($enrolid, $instances)) {
            $errors[] = \get_string('invalidenrolinstance', 'enrol');
            return;
        }
        $instance = $instances[$enrolid];
        if (!isset($plugins[$instance->enrol])) {
            $errors[] = \get_string('enrolnotpermitted', 'enrol');
            return;
        }
        $plugin = $plugins[$instance->enrol];
        $course_ctx = \context_course::instance($courseid);
        if ($plugin->allow_enrol($instance) && \has_capability('enrol/'.$plugin->get_name().':enrol', $course_ctx)) {
            foreach ($users as $user) {
                $plugin->enrol_user($instance, $user->id, $roleid);
            }
        } else {
            $errors[] = \get_string('enrolnotpermitted', 'enrol');
        }
    }
    
    /**
     * Configure one exercise round with its exercises. Updates an existing round
     * or creates a new one, similarly for the exercises.
     * @param int $courseid Moodle course ID where the exercise round is added/modified
     * @param int $sectionNumber Moodle course section number (0-N) which a new round is added to
     * @param int|boolean $sectionVisible true if the course section is visible
     * @param \stdClass $module configuration JSON
     * @param int $module_order ordering number for modules, if config JSON does not specify it
     * @param int $exercise_order ordering number for exercises, if exercises
     * are numerated ignoring modules
     * @param array $categories associative array of categories in the course, indexed by keys
     * @param array $seen_modules exercise round IDs that have been seen in the config JSON
     * @param array $seen_exercises exercise IDs that have been seen in the config JSON
     * @param array $errors
     * @param array $assistant_users user records (stdClass) of the users
     * that should be promoted to non-editing teachers in the exercise round if any exercise
     * has the "allow assistant grading" setting.
     * @param int Moodle role ID of the role that assistants are given (usually non-editing teacher).
     */
    protected function configure_exercise_round($courseid, $sectionNumber, $sectionVisible,
            \stdClass $module, $module_order, $exercise_order, array &$categories,
            array &$seen_modules, array &$seen_exercises, array &$errors,
            array $assistant_users, $teacher_role_id) {
        global $DB;
        
        if (!isset($module->key)) {
            $errors[] = \get_string('configmodkeymissing', \mod_astra_exercise_round::MODNAME);
            return;
        }
        // either update existing exercise round or create new
        $roundRecord = $DB->get_record(\mod_astra_exercise_round::TABLE, array(
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
            $moduleName = $this->formatLocalizationForActivityName($module->title);
        } else if (isset($module->name)) {
            $moduleName = $this->formatLocalizationForActivityName($module->name);
        }
        if (!isset($moduleName)) {
            $moduleName = '-';
        }
        $courseConfig = \mod_astra_course_config::getForCourseId($courseid);
        if ($courseConfig) {
            $numberingStyle = $courseConfig->getModuleNumbering();
        } else {
            $numberingStyle = \mod_astra_course_config::getDefaultModuleNumbering();
        }
        $roundRecord->name = \mod_astra_exercise_round::updateNameWithOrder($moduleName, $roundRecord->ordernum, $numberingStyle);
        // In order to show the ordinal number of the exercise round in the Moodle course page,
        // the number must be stored in the name.
        
        if (isset($module->status)) {
            $roundRecord->status = $this->parseModuleStatus($module->status, $errors);
        } else {
            // default
            $roundRecord->status = \mod_astra_exercise_round::STATUS_READY;
        }
        
        if (isset($module->points_to_pass)) {
            $p = $this->parseInt($module->points_to_pass, $errors);
            if ($p !== null)
                $roundRecord->pointstopass = $p;
        }
        if (!isset($roundRecord->pointstopass)) {
            $roundRecord->pointstopass = 0; // default
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
        } else {
            // late submissions are not allowed
            $roundRecord->latesbmsallowed = 0;
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
        $roundRecord->visible =
            ($roundRecord->status != \mod_astra_exercise_round::STATUS_HIDDEN && $sectionVisible)
            ? 1 : 0;
        $roundRecord->visibleoncoursepage = 1; // zero only used for stealth activities

        // the max points of the round depend on the exercises in the round
        if (isset($module->children)) {
            $roundRecord->grade = $this->get_total_max_points($module->children, $categories);
        } else {
            $roundRecord->grade = 0;
        }

        if (isset($roundRecord->id)) {
            // update existing exercise round
            // settings for the Moodle course module
            $exround = new \mod_astra_exercise_round($roundRecord);
            $cm = $exround->getCourseModule();
            $roundRecord->coursemodule = $cm->id; // Moodle course module ID
            $roundRecord->cmidnumber = $cm->idnumber; // keep the old Moodle course module idnumber
            
            \update_module($roundRecord); // throws moodle_exception
            
        } else {
            // create new exercise round
            // settings for the Moodle course module
            $roundRecord->modulename = \mod_astra_exercise_round::TABLE;
            $roundRecord->section = $sectionNumber;
            $roundRecord->cmidnumber = ''; // Moodle course module idnumber, unused
            
            $moduleinfo = \create_module($roundRecord); // throws moodle_exception
            $exround = \mod_astra_exercise_round::createFromId($moduleinfo->instance);
        }
        
        $seen_modules[] = $exround->getId();
        
        if (!$this->numerate_ignoring_modules) {
            $exercise_order = 0;
        }
        
        // parse exercises/chapters in the exercise round
        if (isset($module->children)) {
            $exercise_order = $this->configure_learning_objects($categories, $exround, $module->children,
                    $seen_exercises, $errors, null, $exercise_order);
        }

        // Add course assistants automatically to the Moodle course.
        // In Moodle, we can promote a user's role within an activity. Only exercise rounds
        // are represented as activities in this plugin, hence a user gains non-editing teacher
        // privileges in the whole exercise round if one exercise has the "allow assistant grading"
        // setting. Exercises have their own "allow assistant grading" and "allow assistant viewing"
        // settings that are used as additional access restrictions in addition to the Moodle capabilities.
        // This teacher role assignment in the course module level may be completely unnecessary if the
        // teacher role is also assigned in the course level, but we keep it here as a precaution
        // (e.g., if the responsible teacher does not want to give teacher role in the course level to
        // assistants, but only in the course module level).
        $auto_setup = $this;
        $unused_errors = array();
        $hasAllowAssistantSetting = function($children) use ($auto_setup, &$unused_errors, &$hasAllowAssistantSetting) {
            foreach ($children as $child) {
                if ((isset($child->allow_assistant_grading) && 
                        $auto_setup->parseBool($child->allow_assistant_grading, $unused_errors)) ||
                    (isset($child->allow_assistant_viewing) && 
                        $auto_setup->parseBool($child->allow_assistant_viewing, $unused_errors))) {
                    return true;
                }
                if (isset($child->children) && $hasAllowAssistantSetting($child->children)) {
                    return true;
                }
            }
            return false;
        };
        if ($hasAllowAssistantSetting($module->children)) {
            // if some exercise in the round has allow_assistant_grading/viewing, promote the user's role in the whole round
            foreach ($assistant_users as $ast_user) {
                \role_assign($teacher_role_id, $ast_user->id, \context_module::instance($exround->getCourseModule()->id));
                // this role assigned in the course module level does not provide any access to the course
                // itself (course home web page)
            }
        }
        
        return array($module_order, $exercise_order);
    }

    /**
     * Return the total summed max points from the given visible learning objects
     * (that are assumed to be in the same round).
     * @param array $conf array of objects
     * @return number the total max points
     */
    protected function get_total_max_points(array $conf, array $categories) {
        $totalMax = 0;
        $errors = array();
        foreach ($conf as $o) {
            $status = \mod_astra_learning_object::STATUS_READY;
            if (isset($o->status)) {
                $status = $this->parseLearningObjectStatus($o->status, $errors);
            }
            $catStatus = \mod_astra_category::STATUS_READY;
            if (isset($categories[$o->category])) {
                $cat = $categories[$o->category];
                $catStatus = $cat->getStatus();
            }
            $visible = ($status != \mod_astra_learning_object::STATUS_HIDDEN
                    && $catStatus != \mod_astra_category::STATUS_HIDDEN);
            if (isset($o->max_points) && $visible) {
                $maxpoints = $this->parseInt($o->max_points, $errors);
                if ($maxpoints !== null) {
                    $totalMax += $maxpoints;
                }
            }
            if (isset($o->children)) {
                $totalMax += $this->get_total_max_points($o->children, $categories);
            }
        }
        return $totalMax;
    }

    /**
     * Configure exercise categories in the course using the configuration JSON.
     * @param int $courseid Moodle course ID
     * @param stdClass $categoriesConf configuration JSON
     * @param array $errors possible errors are added here
     * @return array of mod_astra_category objects indexed by category keys
     */
    protected function configure_categories($courseid, \stdClass $categoriesConf, &$errors) {
        $categories = array();
        $seen_cats = array();
        foreach ($categoriesConf as $key => $cat) {
            if (!isset($cat->name)) {
                $errors[] = \get_string('configcatnamemissing', \mod_astra_exercise_round::MODNAME);
                continue;
            }
            $catRecord = new \stdClass();
            $catRecord->course = $courseid;
            $catRecord->name = $this->formatLocalization($cat->name);
            if (isset($cat->status)) {
                $catRecord->status = $this->parseCategoryStatus($cat->status, $errors);
            } else {
                $catRecord->status = \mod_astra_category::STATUS_READY;
            }
            if (isset($cat->points_to_pass)) {
                $catRecord->pointstopass = $this->parseInt($cat->points_to_pass, $errors);
                if ($catRecord->pointstopass === null)
                    unset($catRecord->pointstopass);
            }
            $category = \mod_astra_category::createFromId(\mod_astra_category::updateOrCreate($catRecord));
            $categories[$key] = $category;
            $seen_cats[] = $category->getId();
        }
        
        // hide categories that exist in Moodle but were not seen in the config
        foreach (\mod_astra_category::getCategoriesInCourse($courseid) as $id => $cat) {
            if (! \in_array($id, $seen_cats)) {
                $cat->setHidden();
                $cat->save();
            }
        }
        
        return $categories;
    }
    
    /**
     * Configure learning objects (exercises/chapters) (create/update) in an
     * exercise round based on the configuration JSON.
     * @param array $categories \mod_astra_category objects indexed by keys
     * @param \mod_astra_exercise_round $exround
     * @param array $config configuration JSON of the exercises
     * @param array $seen array of exercise IDs that have been seen in the config
     * @param array $errors
     * @param \mod_astra_learning_object $parent set if the object is listed under another object,
     * null if there is no parent object.
     * @param int $n ordering number
     * @return int new ordering number, use if exercises are numerated course-wide
     */
    protected function configure_learning_objects(array &$categories, \mod_astra_exercise_round $exround,
            array $config, array &$seen, array &$errors, \mod_astra_learning_object $parent = null, $n = 0) {
        global $DB;
        
        foreach ($config as $o) {
            if (!isset($o->key)) {
                $errors[] = \get_string('configexercisekeymissing', \mod_astra_exercise_round::MODNAME);
                continue;
            }
            if (!isset($o->category)) {
                $errors[] = \get_string('configexercisecatmissing', \mod_astra_exercise_round::MODNAME);
                continue;
            }
            if (!isset($categories[$o->category])) {
                $errors[] = \get_string('configexerciseunknowncat', \mod_astra_exercise_round::MODNAME, $o->category);
                continue;
            }
            
            // find if a learning object with the key exists in the exercise round
            $lobjectRecord = $DB->get_record(\mod_astra_learning_object::TABLE,
                    array('remotekey' => $o->key, 'roundid' => $exround->getId()),
                    '*', IGNORE_MISSING);
            if ($lobjectRecord === false) {
                // create new later
                $lobjectRecord = new \stdClass();
                $lobjectRecord->remotekey = $o->key;
                $oldRoundId = null;
            } else {
                $oldRoundId = $lobjectRecord->roundid;
            }

            $lobjectRecord->roundid = $exround->getId();
            $lobjectRecord->categoryid = $categories[$o->category]->getId();
            if ($parent !== null) {
                $lobjectRecord->parentid = $parent->getId();
            } else {
                $lobjectRecord->parentid = null;
            }
            
            // is it an exercise or chapter?
            if (isset($o->max_submissions)) { // exercise
                if (isset($lobjectRecord->id)) { // the exercise exists in Moodle, read old field values
                    $exerciseRecord = $DB->get_record(\mod_astra_exercise::TABLE, array('lobjectid' => $lobjectRecord->id),
                            '*', \MUST_EXIST);
                    // copy object fields
                    foreach ($exerciseRecord as $key => $val) {
                        // exercise table has its own id, keep that id here since lobjectid is the base table id
                        $lobjectRecord->$key = $val;
                    }
                }
                
                if (isset($o->allow_assistant_grading)) {
                    $lobjectRecord->allowastgrading = $this->parseBool($o->allow_assistant_grading, $errors);
                } else {
                    $lobjectRecord->allowastgrading = false;
                }
                if (isset($o->allow_assistant_viewing)) {
                    $lobjectRecord->allowastviewing = $this->parseBool($o->allow_assistant_viewing, $errors);
                } else {
                    $lobjectRecord->allowastviewing = false;
                }
                
                // max_submission is set since it was used to separate exercises and chapters
                $maxsbms = $this->parseInt($o->max_submissions, $errors);
                if ($maxsbms !== null)
                    $lobjectRecord->maxsubmissions = $maxsbms;
                
                if (isset($o->max_points)) {
                    $maxpoints = $this->parseInt($o->max_points, $errors);
                    if ($maxpoints !== null)
                        $lobjectRecord->maxpoints = $maxpoints;
                }
                if (!isset($lobjectRecord->maxpoints))
                    $lobjectRecord->maxpoints = 100;
                
                if (isset($o->points_to_pass)) {
                    $pointstopass = $this->parseInt($o->points_to_pass, $errors);
                    if ($pointstopass !== null)
                        $lobjectRecord->pointstopass = $pointstopass;
                }
                if (!isset($lobjectRecord->pointstopass))
                    $lobjectRecord->pointstopass = 0;
                
                if (isset($o->submission_file_max_size)) { // A+ does not have this setting
                    $sbmsMaxSize = $this->parseInt($o->submission_file_max_size, $errors);
                    if ($sbmsMaxSize !== null)
                        $lobjectRecord->maxsbmssize = $sbmsMaxSize;
                }
            } else {
                // chapter
                if (isset($lobjectRecord->id)) { // the chapter exists in Moodle, read old field values
                    $chapterRecord = $DB->get_record(\mod_astra_chapter::TABLE, array('lobjectid' => $lobjectRecord->id),
                            '*', \MUST_EXIST);
                    // copy object fields
                    foreach ($chapterRecord as $key => $val) {
                        // chapter table has its own id, keep that id here since lobjectid is the base table id
                        $lobjectRecord->$key = $val;
                    }
                }
                
                if (isset($o->generate_table_of_contents)) {
                    $lobjectRecord = $this->parseBool($o->generate_table_of_contents, $errors);
                }
            }
            
            if (isset($o->order)) {
                $order = $this->parseInt($o->order, $errors);
                if ($order !== null)
                    $lobjectRecord->ordernum = $order;
            } else {
                $n += 1;
                $lobjectRecord->ordernum = $n;
            }
            
            if (isset($o->url)) {
                $lobjectRecord->serviceurl = $this->formatLocalization($o->url);
            }
            if (isset($o->status)) {
                $lobjectRecord->status = $this->parseLearningObjectStatus($o->status, $errors);
            } else {
                // default
                $lobjectRecord->status = \mod_astra_learning_object::STATUS_READY;
            }
            if (isset($o->use_wide_column)) {
                $lobjectRecord->usewidecolumn = $this->parseBool($o->use_wide_column, $errors);
            }

            if (isset($o->title)) {
                $lobjectRecord->name = $this->formatLocalization($o->title);
            } else if (isset($o->name)) {
                $lobjectRecord->name = $this->formatLocalization($o->name);
            }
            if (empty($lobjectRecord->name)) {
                $lobjectRecord->name = '-';
            }
            
            
            if (isset($lobjectRecord->id)) {
                // update existing
                if (isset($o->max_submissions)) { // exercise
                    $learningObject = new \mod_astra_exercise($lobjectRecord);
                    if ($oldRoundId == $lobjectRecord->roundid) { // round not changed
                        $learningObject->save();
                        // updates gradebook for exercise 
                    } else {
                        // round changed
                        $learningObject->deleteGradebookItem();
                        // gradeitemnumber must be unique in the new round
                        $newRound = $learningObject->getExerciseRound();
                        $lobjectRecord->gradeitemnumber = $newRound->getNewGradebookItemNumber();
                        $learningObject->save();
                        // updates gradebook item (creates new item)
                    }
                } else {
                    // chapter
                    $learningObject = new \mod_astra_chapter($lobjectRecord);
                    $learningObject->save();
                }
            } else {
                // create new
                if (isset($o->max_submissions)) {
                    // create new exercise
                    $learningObject = $exround->createNewExercise($lobjectRecord, $categories[$o->category], false);
                } else {
                    // chapter
                    $learningObject = $exround->createNewChapter($lobjectRecord, $categories[$o->category]);
                }
            }
            
            $seen[] = $learningObject->getId();
            
            if (isset($o->children)) {
                $this->configure_learning_objects($categories, $exround, $o->children, $seen, $errors, $learningObject);
            }
        }
        return $n;
    }
    
    /**
     * Parse localised elements into |lang:val|lang:val| -format strings.
     * Adapted from A+ (a-plus/lib/localization_syntax.py)
     * @param object|string $elem
     * @return string
     */
    protected function formatLocalization($elem) : string {
        if (is_object($elem)) {
            $res = '';
            foreach ($elem as $lang => $val) {
                if (in_array($lang, $this->languages)) {
                    $res .= "|$lang:$val";
                }
            }
            return $res . '|';
        }
        return $elem;
    }
    
    /**
     * Parse localised elements into multilang spans used by the Moodle multilang filter.
     * (<span lang="en" class="multilang">English text</span>)
     * Adapted from A+ (a-plus/lib/localization_syntax.py)
     * @param object|string $elem
     * @return string
     */
    protected function formatLocalizationForActivityName($elem) : string {
        if (is_object($elem)) {
            $res = array();
            foreach ($elem as $lang => $val) {
                if (in_array($lang, $this->languages)) {
                    $res[] = "<span lang=\"$lang\" class=\"multilang\">$val</span>";
                }
            }
            return implode(' ', $res);
        }
        return $elem;
    }
    
    protected function parseLearningObjectStatus($value, &$errors) {
        switch ($value) {
            case 'ready':
                return \mod_astra_learning_object::STATUS_READY;
                break;
            case 'hidden':
                return \mod_astra_learning_object::STATUS_HIDDEN;
                break;
            case 'maintenance':
                return \mod_astra_learning_object::STATUS_MAINTENANCE;
                break;
            case 'unlisted':
                return \mod_astra_learning_object::STATUS_UNLISTED;
                break;
            default:
                $errors[] = \get_string('configbadstatus', \mod_astra_exercise_round::MODNAME, $value);
                return \mod_astra_learning_object::STATUS_HIDDEN;
        }
    }
    
    protected function parseModuleStatus($value, &$errors) {
        switch ($value) {
            case 'ready':
                return \mod_astra_exercise_round::STATUS_READY;
                break;
            case 'hidden':
                return \mod_astra_exercise_round::STATUS_HIDDEN;
                break;
            case 'maintenance':
                return \mod_astra_exercise_round::STATUS_MAINTENANCE;
                break;
            case 'unlisted':
                return \mod_astra_exercise_round::STATUS_UNLISTED;
                break;
            default:
                $errors[] = \get_string('configbadstatus', \mod_astra_exercise_round::MODNAME, $value);
                return \mod_astra_exercise_round::STATUS_HIDDEN;
        }
    }
    
    protected function parseCategoryStatus($value, &$errors) {
        switch ($value) {
            case 'ready':
                return \mod_astra_category::STATUS_READY;
                break;
            case 'hidden':
                return \mod_astra_category::STATUS_HIDDEN;
                break;
            case 'nototal':
                return \mod_astra_category::STATUS_NOTOTAL;
                break;
            default:
                $errors[] = \get_string('configbadstatus', \mod_astra_exercise_round::MODNAME, $value);
                return \mod_astra_category::STATUS_HIDDEN;
        }
    }
    
    protected function parseInt($value, &$errors) {
        if (\is_numeric($value))
            return (int) $value;
        else {
            $errors[] = \get_string('configbadint', \mod_astra_exercise_round::MODNAME, $value);
            return null;
        }
    }
    
    protected function parseFloat($value, &$errors) {
        if (\is_numeric($value))
            return (float) $value;
        else {
            $errors[] = \get_string('configbadfloat', \mod_astra_exercise_round::MODNAME, $value);
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
        $errors[] = \get_string('configbaddate', \mod_astra_exercise_round::MODNAME, $value);
        return null;
    }
    
    protected function parseDuration($openingTime, $duration, &$errors) {
        $len = \strlen($duration);
        if ($len > 1) {
            $unit = $duration[$len - 1];
            $value = \substr($duration, 0, $len - 1);
            if (\is_numeric($value)) {
                $value = (int) $value;
                if (\in_array(\strtolower($unit), array('h', 's')))
                    // time (hours), mooc-grader uses m for months, not minutes
                    $intervalSpec = "PT$value". \strtoupper($unit);
                else
                    // date (days, months, years)
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
        $errors[] = \get_string('configbadduration', \mod_astra_exercise_round::MODNAME, $duration);
        return null;
    }
    
    /**
     * Return an array of Moodle user records corresponding to the given student ids.
     * @param array $student_ids student ids (user idnumber in Moodle)
     * @param array $errors
     * @return \stdClass[]
     */
    protected function parseStudentIdList($student_ids, &$errors) {
        global $DB;
        
        $users = array();
        $not_found_ids = array();
        
        if (!\is_array($student_ids)) {
            $errors[] = \get_string('configassistantsnotarray', \mod_astra_exercise_round::MODNAME);
            return $users;
        }
        
        foreach ($student_ids as $student_id) {
            $user = $DB->get_record('user', array('idnumber' => $student_id));
            if ($user === false) { // not found
                $not_found_ids[] = $student_id;
            } else {
                $users[] = $user;
            }
        }
        
        if (!empty($not_found_ids)) {
            $errors[] = \get_string('configassistantnotfound', \mod_astra_exercise_round::MODNAME,
                    \implode(', ', $not_found_ids));
        }
        return $users;
    }
}