<?php

/**
 * stratumtwo restore task that provides all the settings and steps to perform one
 * complete restore of the activity
 */
require_once(dirname(__FILE__) .'/restore_stratumtwo_stepslib.php');

class restore_stratumtwo_activity_task extends restore_activity_task {

    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        // No particular settings for this activity
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        // Choice only has one structure step
        $this->add_step(new restore_stratumtwo_activity_structure_step('stratumtwo_structure', 'stratumtwo.xml'));
    }

    /**
     * Define the contents in the activity that must be
     * processed by the link decoder
     */
    public static function define_decode_contents() {
        $contents = array();
        // we do not expect any plugin content (e.g., descriptions in rounds) to include links to
        // other plugin pages in Moodle, so that we would need to decode them in restoring to update
        // new IDs in the links
        //$contents[] = new restore_decode_content(mod_stratumtwo_exercise_round::TABLE, array('intro'), 'stratumtwo');

        return $contents;
    }

    /**
     * Define the decoding rules for links belonging
     * to the activity to be executed by the link decoder
     */
    public static function define_decode_rules() {
        $rules = array();
        
        $rules[] = new restore_decode_rule('STRATUMTWOINDEX',
                '/mod/'. mod_stratumtwo_exercise_round::TABLE .'/index.php?id=$1', 'course');
        $rules[] = new restore_decode_rule('STRATUMTWOVIEWBYID',
                '/mod/'. mod_stratumtwo_exercise_round::TABLE .'/view.php?id=$1', 'course_module');
        $rules[] = new restore_decode_rule('STRATUMTWOVIEWBYS',
                '/mod/'. mod_stratumtwo_exercise_round::TABLE .'/view.php?s=$1', 'stratumtwo');

        return $rules;
    }

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * stratumtwo logs. It must return one array
     * of {@link restore_log_rule} objects
     */
    public static function define_restore_log_rules() {
        $rules = array();
        
        $rules[] = new restore_log_rule(mod_stratumtwo_exercise_round::TABLE, 'view',
                'view.php?id={course_module}', '{stratumtwo}');
        $rules[] = new restore_log_rule(mod_stratumtwo_exercise_round::TABLE, 'view exercise',
                'exercise.php?id={learningobject}', '{learningobject}');
        $rules[] = new restore_log_rule(mod_stratumtwo_exercise_round::TABLE, 'submit solution',
                'submission.php?id={submission}', '{submission}');

        return $rules;
    }

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * course logs. It must return one array
     * of {@link restore_log_rule} objects
     *
     * Note this rules are applied when restoring course logs
     * by the restore final task, but are defined here at
     * activity level. All them are rules not linked to any module instance (cmid = 0)
     */
    public static function define_restore_log_rules_for_course() {
        $rules = array();
        
        $rules[] = new restore_log_rule(mod_stratumtwo_exercise_round::TABLE, 'view all', 'index.php?id={course}', null);

        return $rules;
    }

}