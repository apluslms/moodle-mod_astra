<?php
//TODO restore not implemented
/**
 * Define the complete stratumtwo structure for backup, with file and id annotations.
 */     
class backup_stratumtwo_activity_structure_step extends backup_activity_structure_step {
 
    protected function define_structure() {
 
        // To know if we are including userinfo
        $userinfo = $this->get_setting_value('userinfo');
 
        // Define each element separated
        // exercise round
        $stratumtwo = new backup_nested_element('stratumtwo', array('id'),
                array(
                        'name', 'intro', 'introformat', 'timecreated', 'timemodified', 'ordernum', 'status',
                        'grade', 'remotekey', 'pointstopass', 'openingtime', 'closingtime', 'latesbmsallowed',
                        'latesbmsdl', 'latesbmspenalty',
                ));
        $categories = new backup_nested_element('categories');
        $category = new backup_nested_element('category', array('id'),
                array(
                        'status', 'name', 'pointstopass',
                ));
        $learningObjects = new backup_nested_element('learningobjects');
        $learningObject = new backup_nested_element('learningobject', array('id'),
                array( //TODO parentid
                        'status', 'ordernum', 'remotekey', 'name', 'serviceurl',
                ));
        $exercises = new backup_nested_element('exercises');
        $exercise = new backup_nested_element('exercise', array('id'),
                array(
                        'maxsubmissions', 'pointstopass', 'maxpoints', 'gradeitemnumber',
                        'maxsbmssize', 'allowastviewing', 'allowastgrading',
                ));
        $chapters = new backup_nested_element('chapters');
        $chapter = new backup_nested_element('chapter', array('id'),
                array(
                        'generatetoc',
                ));
        $submissions = new backup_nested_element('submissions');
        $submission = new backup_nested_element('submission', array('id'),
                array(
                        'status', 'submissiontime', 'hash', 'submitter', 'grader',
                        'feedback', 'assistfeedback', 'grade', 'gradingtime', 'latepenaltyapplied',
                        'servicepoints', 'servicemaxpoints', 'submissiondata', 'gradingdata',
                ));
        $courseSettings = new backup_nested_element('coursesettings');
        $courseSetting = new backup_nested_element('coursesetting', array('id'),
                array(
                        'apikey', 'configurl', 'sectionnum', 'modulenumbering', 'contentnumbering',
                ));
        $deadlineDeviations = new backup_nested_element('deadlinedeviations');
        $deadlineDeviation = new backup_nested_element('deadlinedeviation', array('id'),
                array(
                        'submitter', 'extraminutes', 'withoutlatepenalty',
                ));
        $submitLimitDeviations = new backup_nested_element('submitlimitdeviations');
        $submitLimitDeviation = new backup_nested_element('submitlimitdeviation', array('id'),
                array(
                        'submitter', 'extrasubmissions',
                ));

        // Build the tree
        $stratumtwo->add_child($categories);
        $categories->add_child($category);
        $category->add_child($learningObjects);
        $learningObjects->add_child($learningObject);
        $learningObject->add_child($exercises);
        $exercises->add_child($exercise);
        $learningObject->add_child($chapters);
        $chapters->add_child($chapter);
        $exercise->add_child($submissions);
        $submissions->add_child($submission);
        $stratumtwo->add_child($courseSettings);
        $courseSettings->add_child($courseSetting);
        $exercise->add_child($deadlineDeviations);
        $deadlineDeviations->add_child($deadlineDeviation);
        $exercise->add_child($submitLimitDeviations);
        $submitLimitDeviations->add_child($submitLimitDeviation);
        //TODO this stores all categories under each round repeatedly, and if it
        // creates them all when restoring, there will be multiple copies of a category
        // (and course settings too)

        // Define sources
        $stratumtwo->set_source_table('stratumtwo', array('id' => backup::VAR_ACTIVITYID));
        $category->set_source_table('stratumtwo_categories', array('course' => backup::VAR_COURSEID));
        $learningObject->set_source_table('stratumtwo_lobjects', array('roundid' => backup::VAR_ACTIVITYID));
        $exercise->set_source_table('stratumtwo_exercises', array('lobjectid' => backup::VAR_PARENTID));
        $chapter->set_source_table('stratumtwo_chapters', array('lobjectid' => backup::VAR_PARENTID));
        $courseSetting->set_source_table('stratumtwo_course_settings', array('course' => backup::VAR_COURSEID));
        
        if ($userinfo) {
            $submission->set_source_table('stratumtwo_submissions', array('exerciseid' => '../../../../id'));
            $deadlineDeviation->set_source_table('stratumtwo_dl_deviations', array('exerciseid' => '../../../../id'));
            $submitLimitDeviation->set_source_table('stratumtwo_maxsbms_devs', array('exerciseid' => '../../../../id'));
        }

        // Define id annotations
        $submission->annotate_ids('user', 'submitter');
        $deadlineDeviation->annotate_ids('user', 'submitter');
        $submitLimitDeviation->annotate_ids('user', 'submitter');

        // Define file annotations
        $submission->annotate_files(\mod_stratumtwo_exercise_round::MODNAME,
                \mod_stratumtwo_submission::SUBMITTED_FILES_FILEAREA, 'id');

        // Return the root element (stratumtwo), wrapped into standard activity structure
        return $this->prepare_activity_structure($stratumtwo);
    }
}