<?php

require_once(dirname(__FILE__) .'/backup_astra_stepslib.php');
//require_once(dirname(__FILE__) .'/backup_astra_settingslib.php');

/**
 * Astra backup task that provides all the settings and steps to perform one
 * complete backup of the activity.
 */
class backup_astra_activity_task extends backup_activity_task {

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
        // astra only has one structure step
        $this->add_step(new backup_astra_activity_structure_step('astra_structure', 'astra.xml'));
    }

    /**
     * Code the transformations to perform in the activity in
     * order to get transportable (encoded) links
     */
    public static function encode_content_links($content) {
        global $CFG;
        
        $base = preg_quote($CFG->wwwroot, "/");
        
        // Link to the list of exercise rounds
        $search = "/(". $base ."\/mod\/". mod_astra_exercise_round::TABLE ."\/index.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@ASTRAINDEX*$2@$', $content);
        
        // Link to round view by moduleid
        $search = "/(". $base ."\/mod\/". mod_astra_exercise_round::TABLE ."\/view.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@ASTRAVIEWBYID*$2@$', $content);
        
        // Link to round view by astra id
        $search = "/(". $base ."\/mod\/". mod_astra_exercise_round::TABLE ."\/view.php\?s\=)([0-9]+)/";
        $content = preg_replace($search, '$@ASTRAVIEWBYS*$2@$', $content);
        
        return $content;
    }
}

/*
Planning (example in https://docs.moodle.org/dev/Backup_2.0_for_developers#Schema)

exercise round (no user info)
    id (attr)
    course (not needed)
    name
    intro (file area for intro editor, but ignore it since we expect to have only an HTML string here without attachments)
    introformat
    timecreated
    timemodified
    ordernum
    status
    grade
    remotekey
    pointstopass
    openingtime
    closingtime
    latesbmsallowed
    latesbmsdl
    latesbmspenalty
    
categories (no user info)
    id (attr)
    course (not needed)
    status
    name
    pointstopass
    
learning objects (no user info)
    id (attr)
    status
    categoryid (not needed)
    roundid (not needed)
    parentid (requires special care since it is a foreign key to the same table)
    ordernum
    remotekey
    name
    serviceurl
    
exercises (no user info)
    id (attr)
    lobjectid (not needed)
    maxsubmissions
    pointstopass
    maxpoints
    gradeitemnumber
    maxsbmssize
    allowastviewing
    allowastgrading
    
chapters (no user info)
    id (attr)
    lobjectid (not needed)
    generatetoc
    
submissions (user info)
    id (attr)
    status
    submissiontime
    hash
    exerciseid (not needed)
    submitter (annotation)
    grader (annotation)
    feedback (text, no file area)
    assistfeedback (text, no file area)
    grade
    gradingtime
    latepenaltyapplied
    servicepoints
    servicemaxpoints
    submissiondata (text, no file area)
    gradingdata (text, no file area)
file area for user submitted files, uses submission id

course settings (no user info)
    id (attr)
    course (not needed)
    apikey
    configurl
    sectionnum
    modulenumbering
    contentnumbering
    
deadline deviations (user info)
    id (attr)
    submitter (annotation)
    exerciseid (not needed)
    extraminutes
    withoutlatepenalty
    
submit limit deviations (user info)
    id (attr)
    submitter (annotation)
    exerciseid (not needed)
    extrasubmissions
*/