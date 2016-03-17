<?php

namespace mod_stratumtwo\output;

defined('MOODLE_INTERNAL') || die();

class edit_course_page implements \renderable, \templatable {
    
    protected $courseid;
    protected $moduleNumbering;
    protected $contentNumbering;
    
    public function __construct($courseid) {
        global $DB;
        
        $this->courseid = $courseid;
        
        $course_settings = $DB->get_record(\mod_stratumtwo_course_config::TABLE, array('course' => $courseid));
        if ($course_settings === false) {
            $this->moduleNumbering = \mod_stratumtwo_course_config::MODULE_NUMBERING_ARABIC;
            $this->contentNumbering = \mod_stratumtwo_course_config::CONTENT_NUMBERING_ARABIC;
        } else {
            $conf = new \mod_stratumtwo_course_config($course_settings);
            $this->moduleNumbering = $conf->getModuleNumbering();
            $this->contentNumbering = $conf->getContentNumbering();
        }
    }
    
    public function export_for_template(\renderer_base $output) {
        $ctx = new \stdClass();
        $ctx->autosetupurl = \mod_stratumtwo\urls\urls::autoSetup($this->courseid);
        $ctx->categories = array();
        foreach (\mod_stratumtwo_category::getCategoriesInCourse($this->courseid) as $cat) {
            $ctx->categories[] = $cat->getTemplateContext();
        }
        $ctx->create_category_url = \mod_stratumtwo\urls\urls::createCategory($this->courseid);
        $ctx->course_modules = array();
        foreach (\mod_stratumtwo_exercise_round::getExerciseRoundsInCourse($this->courseid) as $exround) {
            $ctx->course_modules[] = $exround->getTemplateContextWithExercises(true);
        }
        $ctx->create_module_url = \mod_stratumtwo\urls\urls::createExerciseRound($this->courseid);
        $ctx->renumber_action_url = \mod_stratumtwo\urls\urls::editCourse($this->courseid);
        
        $ctx->module_numbering_options = function($mustacheHelper) {
            $options = array(
                \mod_stratumtwo_course_config::MODULE_NUMBERING_NONE => 
                    \get_string('nonumbering', \mod_stratumtwo_exercise_round::MODNAME),
                \mod_stratumtwo_course_config::MODULE_NUMBERING_ARABIC => 
                    \get_string('arabicnumbering', \mod_stratumtwo_exercise_round::MODNAME),
                \mod_stratumtwo_course_config::MODULE_NUMBERING_ROMAN => 
                    \get_string('romannumbering', \mod_stratumtwo_exercise_round::MODNAME),
                \mod_stratumtwo_course_config::MODULE_NUMBERING_HIDDEN_ARABIC => 
                    \get_string('hiddenarabicnum', \mod_stratumtwo_exercise_round::MODNAME),
            );
            $result = '';
            foreach ($options as $val => $text) {
                $selected = '';
                if ($val === $this->moduleNumbering) {
                    $selected = ' selected="selected"';
                }
                $result .= "<option value=\"$val\"$selected>$text</option>";
            }
            return $result;
        };
        $ctx->content_numbering_options = function($mustacheHelper) {
            $options = array(
                \mod_stratumtwo_course_config::CONTENT_NUMBERING_NONE => 
                    \get_string('nonumbering', \mod_stratumtwo_exercise_round::MODNAME),
                \mod_stratumtwo_course_config::CONTENT_NUMBERING_ARABIC => 
                    \get_string('arabicnumbering', \mod_stratumtwo_exercise_round::MODNAME),
                \mod_stratumtwo_course_config::CONTENT_NUMBERING_ROMAN => 
                    \get_string('romannumbering', \mod_stratumtwo_exercise_round::MODNAME),
            );
            $result = '';
            foreach ($options as $val => $text) {
                $selected = '';
                if ($val === $this->contentNumbering) {
                    $selected = ' selected="selected"';
                }
                $result .= "<option value=\"$val\"$selected>$text</option>";
            }
            return $result;
        };
        return $ctx;
    }
}