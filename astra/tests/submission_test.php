<?php

require_once(dirname(__FILE__) .'/exercise_test_data.php');

/**
 * Unit tests for submission.
 * @group mod_astra
 */
class mod_astra_submission_testcase extends advanced_testcase {
    
    use exercise_test_data;
    
    private $tmpFiles = array();
    
    public function setUp() {
        $this->add_test_data();
    }
    
    public function test_createNewSubmission() {
        global $DB;
        
        $this->resetAfterTest(true);
        
        $sId1 = mod_astra_submission::createNewSubmission($this->exercises[1], $this->student->id);
        $sId2 = mod_astra_submission::createNewSubmission($this->exercises[1], $this->student2->id, array(
                'somekey' => 17,
        ));
        
        $this->assertNotEquals(0, $sId1);
        $this->assertNotEquals(0, $sId2);
        $sbms1 = $DB->get_record(mod_astra_submission::TABLE, array('id' => $sId1));
        $sbms2 = $DB->get_record(mod_astra_submission::TABLE, array('id' => $sId2));
        $this->assertTrue($sbms1 !== false);
        $this->assertTrue($sbms2 !== false);
        
        $sbms1 = new mod_astra_submission($sbms1);
        $sbms2 = new mod_astra_submission($sbms2);
        $this->assertEquals(mod_astra_submission::STATUS_INITIALIZED, $sbms1->getStatus());
        $this->assertEquals(mod_astra_submission::STATUS_INITIALIZED, $sbms2->getStatus());
        $this->assertNotEquals($sbms1->getHash(), $sbms2->getHash());
        $this->assertEquals($this->exercises[1]->getId(), $sbms1->getExercise()->getId());
        $this->assertEquals($this->exercises[1]->getId(), $sbms2->getExercise()->getId());
        $this->assertEquals($this->student->id, $sbms1->getSubmitter()->id);
        $this->assertEquals($this->student2->id, $sbms2->getSubmitter()->id);
        $this->assertEquals(0, $sbms1->getGrade());
        $this->assertEquals(0, $sbms2->getGrade());
        $this->assertNull($sbms1->getFeedback());
        $this->assertNull($sbms2->getFeedback());
        
        $this->assertNull($sbms1->getSubmissionData());
        $s2_sbms_data = $sbms2->getSubmissionData();
        $this->assertNotEmpty($s2_sbms_data);
        $this->assertEquals(17, $s2_sbms_data->somekey);
    }
    
    public function test_safeFileName() {
        $this->resetAfterTest(true);
        
        $this->assertEquals('myfile.txt', mod_astra_submission::safeFileName('myfile.txt'));
        $this->assertEquals('myfile.txt', mod_astra_submission::safeFileName('ÄÄÄööömyfile.txt'));
        $this->assertEquals('_myfile.txt', mod_astra_submission::safeFileName('-myfile.txt'));
        $this->assertEquals('myfile.txt.', mod_astra_submission::safeFileName('myfile.txt.ååå'));
        $this->assertEquals('myfile4567.txt', mod_astra_submission::safeFileName('myfile4567.txt'));
        $this->assertEquals('file', mod_astra_submission::safeFileName('ääööö'));
        $this->assertEquals('_myfile.txt', mod_astra_submission::safeFileName('äää-myfile.txt'));
        $this->assertEquals('myFile.txt', mod_astra_submission::safeFileName('myFile.txt'));
        $this->assertEquals('myfile.txt', mod_astra_submission::safeFileName('ääämyfileöööö.txt'));
    }
    
    public function test_addSubmittedFile() {
        $this->resetAfterTest(true);
        
        $this->tmpFiles = array(); // create temp files in the filesystem, they must be removed later
        for ($i = 1; $i <= 3; ++$i) {
            $tmpFilePath = tempnam(sys_get_temp_dir(), 'tmp');
            file_put_contents($tmpFilePath, 'Some submission file content '. $i);
            $this->tmpFiles[] = $tmpFilePath;
            
            $this->submissions[0]->addSubmittedFile("mycode$i.java", "exercise$i", $tmpFilePath);
        }
        
        $fs = get_file_storage();
        $files = $fs->get_area_files(context_module::instance($this->submissions[0]->getExercise()->getExerciseRound()->getCourseModule()->id)->id,
                mod_astra_exercise_round::MODNAME, mod_astra_submission::SUBMITTED_FILES_FILEAREA,
                $this->submissions[0]->getId(),
                'itemid, filepath, filename', false);
        
        $this->assertEquals(3, count($files));
        $files = array_values($files);
        $i = 1;
        while ($i <= 3) {
            $this->assertEquals("mycode$i.java", $files[$i - 1]->get_filename());
            $this->assertEquals("Some submission file content $i", $files[$i - 1]->get_content());
            $this->assertEquals("/exercise$i/", $files[$i - 1]->get_filepath());
            ++$i;
        }
        
        
        // remove temp files
        foreach ($this->tmpFiles as $tmpFile) {
            @unlink($tmpFile);
        }
        $this->tmpFiles = array();
        // method tearDown removes the files if this method is interrupted by an assertion error
    }
    
    public function tearDown() {
        foreach ($this->tmpFiles as $tmpFile) {
            @unlink($tmpFile);
        }
    }
    
    public function test_getSubmittedFiles() {
        $this->resetAfterTest(true);
        
        $this->tmpFiles = array(); // create temp files in the filesystem, they must be removed later
        for ($i = 1; $i <= 3; ++$i) {
            $tmpFilePath = tempnam(sys_get_temp_dir(), 'tmp');
            file_put_contents($tmpFilePath, 'Some submission file content '. $i);
            $this->tmpFiles[] = $tmpFilePath;
        
            $this->submissions[0]->addSubmittedFile("mycode$i.java", "exercise$i", $tmpFilePath);
        }
        
        $files = $this->submissions[0]->getSubmittedFiles();
        
        $this->assertEquals(3, count($files));
        $files = array_values($files);
        $i = 1;
        while ($i <= 3) {
            $this->assertEquals("mycode$i.java", $files[$i - 1]->get_filename());
            $this->assertEquals("Some submission file content $i", $files[$i - 1]->get_content());
            $this->assertEquals("/exercise$i/", $files[$i - 1]->get_filepath());
            ++$i;
        }


        // remove temp files
        foreach ($this->tmpFiles as $tmpFile) {
            @unlink($tmpFile);
        }
        $this->tmpFiles = array();
        // method tearDown removes the files if this method is interrupted by an assertion error
    }
    
    public function test_grade() {
        global $CFG;
        require_once($CFG->libdir .'/gradelib.php');
        
        $this->resetAfterTest(true);
        
        $this->submissions[0]->grade(80, 100, 'Good feedback', array('extra' => 8));
        $sbms = mod_astra_submission::createFromId($this->submissions[0]->getId());
        //$this->assertEquals(8, $sbms->getGrade()); // in helper method
        //$this->assertEquals('Good feedback', $sbms->getFeedback());
        $this->assertNotEmpty($sbms->getGradingData());
        $this->assertEquals(8, $sbms->getGradingData()->extra);
        $this->assertEquals($this->student->id, $sbms->getSubmitter()->id);
        $this->assertEmpty($sbms->getAssistantFeedback());
        $this->assertEquals(80, $sbms->getServicePoints());
        $this->assertEquals(100, $sbms->getServiceMaxPoints());
        $this->grade_test_helper($sbms, 8, 8, 8, 'Good feedback');
        
        // new third submission
        $sbms = mod_astra_submission::createFromId(
                mod_astra_submission::createNewSubmission($this->exercises[0], $this->student->id, null,
                        mod_astra_submission::STATUS_INITIALIZED,
                        $this->exercises[0]->getExerciseRound()->getClosingTime() - 3600 * 24));
        $sbms->grade(15, 15, 'Some feedback');
        $this->grade_test_helper($sbms, 10, 10, 10, 'Some feedback');
        
        // new fourth submission, exceeds submission limit
        $sbms = mod_astra_submission::createFromId(
                mod_astra_submission::createNewSubmission($this->exercises[0], $this->student->id, null,
                        mod_astra_submission::STATUS_INITIALIZED,
                        $this->exercises[0]->getExerciseRound()->getClosingTime() - 3600 * 12));
        $sbms->grade(17, 17, 'Some other feedback');
        $this->grade_test_helper($sbms, 0, 10, 10, 'Some other feedback');
        
        // grade again, ignore deadline but submission limit is still active
        $sbms->grade(10, 10, 'Some feedback', null, true);
        $this->grade_test_helper($sbms, 0, 10, 10, 'Some feedback');
        
        // add submission limit deviation
        mod_astra_submission_limit_deviation::createNew($sbms->getExercise()->getId(), $this->student->id, 1);
        $sbms->grade(17, 17, 'Some feedback 2');
        $this->grade_test_helper($sbms, 10, 10, 10, 'Some feedback 2');
        
        // new submission, different exercise
        $sbms = mod_astra_submission::createFromId(
                mod_astra_submission::createNewSubmission($this->exercises[1], $this->student->id, null,
                        mod_astra_submission::STATUS_INITIALIZED,
                        $this->exercises[1]->getExerciseRound()->getLateSubmissionDeadline() + 3600)); // late from late deadline
        $sbms->grade(10, 10, 'Some feedback 3');
        $this->grade_test_helper($sbms, 0, 0, 10, 'Some feedback 3');
        
        // different student, late
        $sbms = mod_astra_submission::createFromId(
                mod_astra_submission::createNewSubmission($this->exercises[0], $this->student2->id, null,
                        mod_astra_submission::STATUS_INITIALIZED,
                        $this->exercises[0]->getExerciseRound()->getClosingTime() + 3600)); // late
        $sbms->grade(10, 10, 'Some feedback');
        $this->grade_test_helper($sbms, 6, 6, 6, 'Some feedback');
        
        // another exercise, check round total grade
        $sbms = mod_astra_submission::createFromId(
                mod_astra_submission::createNewSubmission($this->exercises[1], $this->student2->id, null,
                        mod_astra_submission::STATUS_INITIALIZED,
                        $this->exercises[0]->getExerciseRound()->getClosingTime() - 3600 * 24));
        $sbms->grade(20, 20, 'Some new feedback');
        $this->grade_test_helper($sbms, 10, 10, 16, 'Some new feedback');
    }
    
    protected function grade_test_helper(mod_astra_submission $sbms, $expectedGrade,
            $expectedBestGrade, $expectedRoundGrade, $expectedFeedback) {
        
        $this->assertEquals($expectedGrade, $sbms->getGrade());
        $this->assertEquals($expectedFeedback, $sbms->getFeedback());
        // gradebook
        $grade_items = grade_get_grades($this->course->id, 'mod', mod_astra_exercise_round::TABLE,
                $sbms->getExercise()->getExerciseRound()->getId(), $sbms->getSubmitter()->id)->items;
        $this->assertEquals($expectedBestGrade,
                $grade_items[$sbms->getExercise()->getGradebookItemNumber()]->grades[$sbms->getSubmitter()->id]->grade);
        $this->assertEquals($expectedRoundGrade,
                $grade_items[0]->grades[$sbms->getSubmitter()->id]->grade); // round total
    }
    
    public function test_delete_with_files() {
        global $DB, $CFG;
        require_once($CFG->libdir .'/gradelib.php');
        
        $this->resetAfterTest(true);
        
        // create new (the only) submission for an exercise
        $sbms = mod_astra_submission::createFromId(
                mod_astra_submission::createNewSubmission($this->exercises[1], $this->student->id, null,
                        mod_astra_submission::STATUS_INITIALIZED,
                        $this->exercises[1]->getExerciseRound()->getClosingTime() - 3600 * 24));
        $this->tmpFiles = array(); // create temp files in the filesystem, they must be removed later
        for ($i = 1; $i <= 3; ++$i) {
            $tmpFilePath = tempnam(sys_get_temp_dir(), 'tmp');
            file_put_contents($tmpFilePath, 'Some submission file content '. $i);
            $this->tmpFiles[] = $tmpFilePath;
        
            $sbms->addSubmittedFile("mycode$i.java", "exercise$i", $tmpFilePath);
        }
        
        $sbms->grade(50, 100, 'Great feedback');
        $sbms->delete();
        
        $fetchedSbms = $DB->get_record(mod_astra_submission::TABLE, array('id' => $sbms->getId()));
        $this->assertFalse($fetchedSbms);
        $fs = get_file_storage();
        $files = $fs->get_area_files(context_module::instance($sbms->getExercise()->getExerciseRound()->getCourseModule()->id)->id,
                mod_astra_exercise_round::MODNAME, mod_astra_submission::SUBMITTED_FILES_FILEAREA,
                $sbms->getId(),
                'itemid, filepath, filename', false);
        $this->assertEmpty($files);
        // gradebook
        $grade_items = grade_get_grades($this->course->id, 'mod', mod_astra_exercise_round::TABLE,
                $sbms->getExercise()->getExerciseRound()->getId(), $sbms->getSubmitter()->id)->items;
        $this->assertEquals(0,
                $grade_items[$sbms->getExercise()->getGradebookItemNumber()]->grades[$sbms->getSubmitter()->id]->grade);
        
        
        // remove temp files
        foreach ($this->tmpFiles as $tmpFile) {
            @unlink($tmpFile);
        }
        $this->tmpFiles = array();
        // method tearDown removes the files if this method is interrupted by an assertion error
    }
    
    public function test_delete() {
        global $DB, $CFG;
        require_once($CFG->libdir .'/gradelib.php');
        
        $this->resetAfterTest(true);
        
        // grade a submission
        $this->submissions[0]->grade(50, 100, 'First feedback');
        // create new submission and grade it better
        $sbms = mod_astra_submission::createFromId(
                mod_astra_submission::createNewSubmission($this->exercises[0], $this->student->id, null,
                        mod_astra_submission::STATUS_INITIALIZED,
                        $this->exercises[0]->getExerciseRound()->getClosingTime() - 3600 * 24));
        $sbms->grade(90, 100, 'Best feedback');
        // gradebook should show these points as the best
        $grade_items = grade_get_grades($this->course->id, 'mod', mod_astra_exercise_round::TABLE,
                $sbms->getExercise()->getExerciseRound()->getId(), $sbms->getSubmitter()->id)->items;
        $this->assertEquals(9,
                $grade_items[$sbms->getExercise()->getGradebookItemNumber()]->grades[$sbms->getSubmitter()->id]->grade);
        // delete the best submission
        $sbms->delete();
        
        $fetchedSbms = $DB->get_record(mod_astra_submission::TABLE, array('id' => $sbms->getId()));
        $this->assertFalse($fetchedSbms);
        // gradebook should show the first points as the best
        $grade_items = grade_get_grades($this->course->id, 'mod', mod_astra_exercise_round::TABLE,
                $sbms->getExercise()->getExerciseRound()->getId(), $sbms->getSubmitter()->id)->items;
        $this->assertEquals(5,
            $grade_items[$sbms->getExercise()->getGradebookItemNumber()]->grades[$sbms->getSubmitter()->id]->grade);
        
    }
}