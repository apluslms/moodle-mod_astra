<?php

require_once(dirname(__FILE__) .'/exercise_test_data.php');

/**
 * Unit tests for exercise.
 * @group mod_astra
 */
class mod_astra_exercise_testcase extends advanced_testcase {
    
    use exercise_test_data;
    
    public function setUp() {
        $this->add_test_data();
    }
    
    public function test_isSubmissionAllowed() {
        $this->resetAfterTest(true);
        
        $this->assertTrue($this->exercises[0]->isSubmissionAllowed($this->student));
    }
    
    public function test_studentHasSubmissionsLeft() {
        $this->resetAfterTest(true);
        
        $this->assertTrue($this->exercises[0]->studentHasSubmissionsLeft($this->student));
        
        $third_sbms = mod_astra_submission::createFromId(
                mod_astra_submission::createNewSubmission($this->exercises[0], $this->student->id));
        
        $this->assertFalse($this->exercises[0]->studentHasSubmissionsLeft($this->student));
        
        // add submit limit deviation
        mod_astra_submission_limit_deviation::createNew($this->exercises[0]->getId(), $this->student->id, 1);
        
        $this->assertTrue($this->exercises[0]->studentHasSubmissionsLeft($this->student));
    }
    
    public function test_studentHasAccess() {
        $this->resetAfterTest(true);
        
        $this->assertTrue($this->exercises[0]->studentHasAccess($this->student, $this->round1->getOpeningTime() + 3600));
        $this->assertFalse($this->exercises[0]->studentHasAccess($this->student, $this->round1->getOpeningTime() - 3600));
        $this->assertTrue($this->exercises[0]->studentHasAccess($this->student, $this->round1->getClosingTime() + 3600));
        $this->assertFalse($this->exercises[0]->studentHasAccess($this->student, $this->round1->getLateSubmissionDeadline() + 3600));
        $this->assertTrue($this->exercises[0]->studentHasAccess($this->student, $this->round1->getLateSubmissionDeadline() - 3600));
        
        // add deadline deviation
        mod_astra_deadline_deviation::createNew($this->exercises[0]->getId(), $this->student->id, 60 * 24 * 8, true);
        // 8-day extension exceeds the original late submission deadline too since it was 7 days from the closing time
        
        $this->assertFalse($this->exercises[0]->studentHasAccess($this->student, $this->round1->getOpeningTime() - 3600));
        $this->assertTrue($this->exercises[0]->studentHasAccess($this->student, $this->round1->getClosingTime() + 3600));
        $this->assertTrue($this->exercises[0]->studentHasAccess($this->student, $this->round1->getLateSubmissionDeadline() + 3600 * 23));
        $this->assertTrue($this->exercises[0]->studentHasAccess($this->student, $this->round1->getLateSubmissionDeadline() - 3600));
        $this->assertFalse($this->exercises[0]->studentHasAccess($this->student, $this->round1->getLateSubmissionDeadline() + 3600 * 25));
        $this->assertTrue($this->exercises[0]->studentHasAccess($this->student, $this->round1->getLateSubmissionDeadline() + 3600 * 24));
        // The previous line should hit the last second of allowed access.
        $this->assertFalse($this->exercises[0]->studentHasAccess($this->student, $this->round1->getLateSubmissionDeadline() + 3600 * 24 + 1));
    }
    
    public function test_uploadSubmissionToService() {
        $this->resetAfterTest(true);
        //TODO this depends on the exercise service and could maybe be moved to a separate testcase class
    }
    
    public function test_getSubmissionsForStudent() {
        $this->resetAfterTest(true);
        
        $toSbms = function($record) {
            return new mod_astra_submission($record);
        };
        
        $submissions = $this->exercises[0]->getSubmissionsForStudent($this->student->id, false, 'submissiontime ASC, status ASC');
        // the submissions probably have the same submissiontime when they are created in setUp()
        $submissions_array = array_map($toSbms, iterator_to_array($submissions, false));
        $this->assertEquals(2, count($submissions_array));
        $this->assertEquals($this->submissions[0]->getId(), $submissions_array[0]->getId());
        $this->assertEquals($this->submissions[1]->getId(), $submissions_array[1]->getId());
        $submissions->close();
        
        $submissions = $this->exercises[0]->getSubmissionsForStudent($this->student->id, true);
        $submissions_array = array_map($toSbms, iterator_to_array($submissions, false));
        $this->assertEquals(1, count($submissions_array));
        $this->assertEquals($this->submissions[0]->getId(), $submissions_array[0]->getId());
        $submissions->close();
    }
    
    public function test_getSubmissionCountForStudent() {
        $this->resetAfterTest(true);
        
        $this->assertEquals(2, $this->exercises[0]->getSubmissionCountForStudent($this->student->id, false));
        $this->assertEquals(1, $this->exercises[0]->getSubmissionCountForStudent($this->student->id, true));
    }
    
    public function test_getBestSubmissionForStudent() {
        $this->resetAfterTest(true);
        
        $this->assertNull($this->exercises[1]->getBestSubmissionForStudent($this->student->id));
        
        $this->submissions[0]->grade(8, 10, 'Test feedback');
        
        $this->assertEquals($this->submissions[0]->getId(),
                $this->exercises[0]->getBestSubmissionForStudent($this->student->id)->getId());
    }
    
    public function test_getParentObject() {
        $this->resetAfterTest(true);
        
        $this->assertNull($this->exercises[0]->getParentObject());
        $this->assertEquals($this->exercises[0]->getId(), $this->exercises[1]->getParentObject()->getId());
        $this->assertEquals($this->exercises[2]->getId(), $this->exercises[3]->getParentObject()->getId());
        $this->assertNull($this->exercises[4]->getParentObject());
    }
    
    public function test_getChildren() {
        $this->resetAfterTest(true);
        
        $children = $this->exercises[0]->getChildren();
        $this->assertEquals(2, count($children));
        $this->assertEquals($this->exercises[1]->getId(), $children[0]->getId());
        $this->assertEquals($this->exercises[2]->getId(), $children[1]->getId());
        
        $children = $this->exercises[3]->getChildren();
        $this->assertEquals(0, count($children));
        
        $children = $this->exercises[4]->getChildren();
        $this->assertEquals(0, count($children));
    }
    
    public function test_save() {
        $this->resetAfterTest(true);
        
        $ex = $this->exercises[0];
        $rec = $ex->getRecord();
        $rec->status = mod_astra_learning_object::STATUS_MAINTENANCE;
        $rec->ordernum = 9;
        $rec->name = 'New exercise';
        $rec->maxpoints = 88;
        
        $ex->save();
        $ex = mod_astra_learning_object::createFromId($ex->getId());
        
        $this->assertEquals('1.9 New exercise', $ex->getName());
        $this->assertEquals(9, $ex->getOrder());
        $this->assertEquals(mod_astra_learning_object::STATUS_MAINTENANCE, $ex->getStatus());
        $this->assertEquals(88, $ex->getMaxPoints());
    }
    
    public function test_delete() {
        global $DB, $CFG;
        require_once($CFG->libdir .'/gradelib.php');
        
        $this->resetAfterTest(true);
        
        $this->exercises[0]->deleteInstance();
        
        // exercises (learning objects), child objects
        $this->assertFalse($DB->get_record(mod_astra_learning_object::TABLE, array('id' => $this->exercises[0]->getId())));
        $this->assertFalse($DB->get_record(mod_astra_exercise::TABLE, array('id' => $this->exercises[0]->getSubtypeId())));
        
        $this->assertEquals(0, $DB->count_records(mod_astra_learning_object::TABLE, array('parentid' => $this->exercises[0]->getId())));
        $this->assertFalse($DB->get_record(mod_astra_exercise::TABLE, array('id' => $this->exercises[1]->getSubtypeId())));
        $this->assertFalse($DB->get_record(mod_astra_exercise::TABLE, array('id' => $this->exercises[2]->getSubtypeId())));
        $this->assertFalse($DB->get_record(mod_astra_exercise::TABLE, array('id' => $this->exercises[3]->getSubtypeId())));
        $this->assertEquals(0, $DB->count_records(mod_astra_learning_object::TABLE, array('parentid' => $this->exercises[2]->getId())));
        $this->assertEquals(0, $DB->count_records(mod_astra_chapter::TABLE)); // no chapters created in setUp
        
        // submisssions, submitted files
        $exerciseIds = implode(',', array($this->exercises[0]->getId(), $this->exercises[1]->getId(),
                $this->exercises[2]->getId(), $this->exercises[3]->getId()));
        $this->assertEquals(0, $DB->count_records_select(mod_astra_submission::TABLE, "exerciseid IN ($exerciseIds)"));
        $fs = get_file_storage();
        $this->assertTrue($fs->is_area_empty(context_module::instance($this->round1->getCourseModule()->id)->id,
                mod_astra_exercise_round::MODNAME, mod_astra_submission::SUBMITTED_FILES_FILEAREA,
                false, false));
        
        // gradebook items
        $grade_items = grade_get_grades($this->course->id, 'mod', mod_astra_exercise_round::TABLE,
                $this->round1->getId(), null)->items;
        $this->assertFalse(isset($grade_items[$this->exercises[0]->getGradebookItemNumber()]));
        $this->assertFalse(isset($grade_items[$this->exercises[1]->getGradebookItemNumber()]));
        $this->assertFalse(isset($grade_items[$this->exercises[2]->getGradebookItemNumber()]));
        $this->assertFalse(isset($grade_items[$this->exercises[3]->getGradebookItemNumber()]));
        $this->assertEquals(20, $grade_items[0]->grademax); // round max points in gradebook
        
        // round max points
        $this->assertEquals(20, $DB->get_field(mod_astra_exercise_round::TABLE, 'grade', array('id' => $this->round1->getId())));
    }
}