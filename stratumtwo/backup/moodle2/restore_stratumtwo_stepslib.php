<?php

/**
 * Structure step to restore one stratumtwo activity
 */
class restore_stratumtwo_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('stratumtwo', '/activity/stratumtwo');
        $paths[] = new restore_path_element('category', '/activity/stratumtwo/categories/category');
        $paths[] = new restore_path_element('learningobject',
                '/activity/stratumtwo/categories/category/learningobjects/learningobject');
        $paths[] = new restore_path_element('exercise',
                '/activity/stratumtwo/categories/category/learningobjects/learningobject/exercise');
        $paths[] = new restore_path_element('chapter',
                '/activity/stratumtwo/categories/category/learningobjects/learningobject/chapter');
        $paths[] = new restore_path_element('coursesetting',
                '/activity/stratumtwo/coursesetting');
        
        if ($userinfo) {
            $paths[] = new restore_path_element('submission',
                    '/activity/stratumtwo/categories/category/learningobjects/learningobject/exercise/submissions/submission');
            $paths[] = new restore_path_element('deadlinedeviation',
                    '/activity/stratumtwo/categories/category/learningobjects/learningobject/exercise/deadlinedeviations/deadlinedeviation');
            $paths[] = new restore_path_element('submitlimitdeviation',
                    '/activity/stratumtwo/categories/category/learningobjects/learningobject/exercise/submitlimitdeviations/submitlimitdeviation');
        }

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    protected function process_stratumtwo($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();
        
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);
        $data->openingtime = $this->apply_date_offset($data->openingtime);
        $data->closingtime = $this->apply_date_offset($data->closingtime);
        $data->latesbmsdl = $this->apply_date_offset($data->latesbmsdl);
        
        // New exercise rounds and learning objects are created during restore even if
        // objects with the same remote keys already exist in the Moodle course.
        // If the course is empty before restoring, that can not happen of course.
        // The teacher should check remote keys (and exercise service configuration) after
        // restoring if existing rounds/learning objects are duplicated in the restore process.
        
        // insert the stratumtwo (exercise round) record
        $newitemid = $DB->insert_record(mod_stratumtwo_exercise_round::TABLE, $data);
        // immediately after inserting "activity" record, call this
        $this->apply_activity_instance($newitemid);
        
        if ($DB->count_records(mod_stratumtwo_exercise_round::TABLE,
                array('course' => $data->course, 'remotekey' => $data->remotekey)) > 1) {
            $this->get_logger()->process(
                'The course probably was not empty before restoring and now there are multiple exercise rounds with the same remote key. '.
                    'You should check and update them manually. The same applies to learning objects (exercises/chapters).',
                backup::LOG_INFO);
        }
    }
    
    protected function process_category($data) {
        global $DB;
        
        $data = (object) $data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();
        // each exercise round XML tree contains all categories in the course, thus
        // we cannot create a new category every time we find a category XML element
        $existingCat = $DB->get_record(mod_stratumtwo_category::TABLE, array('course' => $data->course, 'name' => $data->name));
        if ($existingCat === false) { // does not yet exist
            $newitemid = $DB->insert_record(mod_stratumtwo_category::TABLE, $data);
        } else {
            // do not modify the existing category
            $newitemid = $existingCat->id;
        }
        
        $this->set_mapping('category', $oldid, $newitemid);
    }
    
    protected function process_learningobject($data) {
        global $DB;
        
        $data = (object) $data;
        $oldid = $data->id;
        $data->categoryid = $this->get_new_parentid('category');
        $data->roundid = $this->get_new_parentid('stratumtwo');
        if ($data->parentid !== null) {
            $data->parentid = $this->get_mappingid('learningobject', $data->parentid, null);
            if ($data->parentid === null) {
                // mapping not found because the parent was not defined before the child in the XML
                $this->get_logger()->process(
                    "Parent id of a learning object (name={$data->name}) could not be recovered, setting the object to top level.",
                    backup::LOG_ERROR);
                //TODO parentids in some other XML tree to get correct mapping?
            }
        }
        
        $newitemid = $DB->insert_record(mod_stratumtwo_learning_object::TABLE, $data);
        $this->set_mapping('learningobject', $oldid, $newitemid);
    }
    
    protected function process_exercise($data) {
        global $DB;
        
        $data = (object) $data;
        $oldid = $data->id;
        $data->lobjectid = $this->get_new_parentid('learningobject');
        $newitemid = $DB->insert_record(mod_stratumtwo_exercise::TABLE, $data);
    }
    
    protected function process_chapter($data) {
        global $DB;
        
        $data = (object) $data;
        $oldid = $data->id;
        $data->lobjectid = $this->get_new_parentid('learningobject');
        $newitemid = $DB->insert_record(mod_stratumtwo_chapter::TABLE, $data);
    }
    
    protected function process_coursesetting($data) {
        global $DB;
        
        $data = (object) $data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();
        // each exercise round XML tree contains one course settings element, thus
        // we only create a new settings row if it does not yet exist in the course
        $existingSetting = $DB->get_record(mod_stratumtwo_course_config::TABLE, array('course' => $data->course));
        if ($existingSetting === false) {
            $newitemid = $DB->insert_record(mod_stratumtwo_course_config::TABLE, $data);
        }
    }
    
    protected function process_submission($data) {
        global $DB;
        
        $data = (object) $data;
        $oldid = $data->id;
        
        $data->submissiontime = $this->apply_date_offset($data->submissiontime);
        $data->exerciseid = $this->get_new_parentid('learningobject');
        $data->submitter = $this->get_mappingid('user', $data->submitter);
        $data->grader = $this->get_mappingid('user', $data->grader);
        $data->gradingtime = $this->apply_date_offset($data->gradingtime);
        
        $newitemid = $DB->insert_record(mod_stratumtwo_submission::TABLE, $data);
        // set mapping for restoring submitted files
        $this->set_mapping('submission', $oldid, $newitemid, true);
    }
    
    protected function process_deadlinedeviation($data) {
        global $DB;
        
        $data = (object) $data;
        $oldid = $data->id;
        $data->submitter = $this->get_mappingid('user', $data->submitter);
        $data->exerciseid = $this->get_new_parentid('learningobject');
        
        $newitemid = $DB->insert_record(mod_stratumtwo_deadline_deviation::TABLE, $data);
    }
    
    protected function process_submitlimitdeviation($data) {
        global $DB;
        
        $data = (object) $data;
        $oldid = $data->id;
        $data->submitter = $this->get_mappingid('user', $data->submitter);
        $data->exerciseid = $this->get_new_parentid('learningobject');
        
        $newitemid = $DB->insert_record(mod_stratumtwo_submission_limit_deviation::TABLE, $data);
    }

    protected function after_execute() {
        // Restore submitted files
        $this->add_related_files(mod_stratumtwo_exercise_round::MODNAME,
                mod_stratumtwo_submission::SUBMITTED_FILES_FILEAREA, 'submission');
    }
}