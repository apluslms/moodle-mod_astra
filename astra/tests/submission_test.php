<?php

require_once(dirname(__FILE__) .'/exercise_test_data.php');

/**
 * Unit tests for submission.
 * @group mod_astra
 */
class mod_astra_submission_testcase extends advanced_testcase {
    
    use exercise_test_data;
    
    private $tmpFiles = array();
    protected $timenow;

    public function setUp(): void {
        $this->timenow = time();
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
    
    public function tearDown(): void {
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
        $this->assertEquals(
            $expectedBestGrade,
            $sbms->getExercise()->getBestSubmissionForStudent($sbms->getSubmitter()->id)->getGrade()
        );
        // gradebook
        $grade_items = grade_get_grades($this->course->id, 'mod', mod_astra_exercise_round::TABLE,
                $sbms->getExercise()->getExerciseRound()->getId(), $sbms->getSubmitter()->id)->items;
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
                $grade_items[0]->grades[$sbms->getSubmitter()->id]->grade);
        
        
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
        $this->assertEquals(9, $grade_items[0]->grades[$sbms->getSubmitter()->id]->grade);
        // delete the best submission
        $sbms->delete();
        
        $fetchedSbms = $DB->get_record(mod_astra_submission::TABLE, array('id' => $sbms->getId()));
        $this->assertFalse($fetchedSbms);
        // gradebook should show the first points as the best
        $grade_items = grade_get_grades($this->course->id, 'mod', mod_astra_exercise_round::TABLE,
                $sbms->getExercise()->getExerciseRound()->getId(), $sbms->getSubmitter()->id)->items;
        $this->assertEquals(5, $grade_items[0]->grades[$sbms->getSubmitter()->id]->grade);
    }

    public function test_gradebook() {
        global $DB, $CFG;
        require_once($CFG->libdir .'/gradelib.php');

        $this->resetAfterTest(true);

        $category2 = mod_astra_category::createFromId(mod_astra_category::createNew((object) array(
                'course' => $this->course->id,
                'status' => mod_astra_category::STATUS_READY,
                'name' => 'Another category',
                'pointstopass' => 0,
        )));

        // Create exercises.
        $exercisesround2 = array();
        $exercisesround2[] = $this->add_exercise(array('maxpoints' => 15), $this->round2, $category2);
        $exercisesround2[] = $this->add_exercise(array('parentid' => $exercisesround2[0]->getId()), $this->round2, $this->category);
        $exercisesround2[] = $this->add_exercise(array('parentid' => $exercisesround2[0]->getId()), $this->round2, $this->category);
        $exercisesround2[] = $this->add_exercise(array('parentid' => $exercisesround2[1]->getId()), $this->round2, $this->category);
        $exercisesround2[] = $this->add_exercise(array(), $this->round2, $this->category);
        $exercisesround2[] = $this->add_exercise(array(), $this->round2, $category2);

        $now = time();
        $submissionids = array();

        // Create more submissions.
        $submissionids[] = $DB->insert_record(mod_astra_submission::TABLE, (object) array(
            'status' => mod_astra_submission::STATUS_READY,
            'submissiontime' => $now + 1,
            'hash' => mod_astra_submission::getRandomString(),
            'exerciseid' => $this->exercises[0]->getId(),
            'submitter' => $this->student->id,
            'feedback' => 'test feedback',
            'grade' => 7,
            'gradingtime' => $now + 1,
            'servicepoints' => 7,
            'servicemaxpoints' => 10,
        ));
        $submissionids[] = $DB->insert_record(mod_astra_submission::TABLE, (object) array(
            'status' => mod_astra_submission::STATUS_READY,
            'submissiontime' => $now + 2,
            'hash' => mod_astra_submission::getRandomString(),
            'exerciseid' => $this->exercises[0]->getId(),
            'submitter' => $this->student2->id,
            'feedback' => 'test feedback',
            'grade' => 8,
            'gradingtime' => $now + 2,
            'servicepoints' => 8,
            'servicemaxpoints' => 10,
        ));
        $submissionids[] = $DB->insert_record(mod_astra_submission::TABLE, (object) array(
            'status' => mod_astra_submission::STATUS_READY,
            'submissiontime' => $now + 3,
            'hash' => mod_astra_submission::getRandomString(),
            'exerciseid' => $this->exercises[0]->getId(),
            'submitter' => $this->student2->id,
            'feedback' => 'test feedback',
            'grade' => 1,
            'gradingtime' => $now + 3,
            'servicepoints' => 1,
            'servicemaxpoints' => 10,
        ));
        // student 7, student2 8
        // $this->exercises[1]
        $submissionids[] = $DB->insert_record(mod_astra_submission::TABLE, (object) array(
            'status' => mod_astra_submission::STATUS_READY,
            'submissiontime' => $now + 4,
            'hash' => mod_astra_submission::getRandomString(),
            'exerciseid' => $this->exercises[1]->getId(),
            'submitter' => $this->student->id,
            'feedback' => 'test feedback',
            'grade' => 0,
            'gradingtime' => $now + 4,
            'servicepoints' => 0,
            'servicemaxpoints' => 10,
        ));
        $submissionids[] = $DB->insert_record(mod_astra_submission::TABLE, (object) array(
            'status' => mod_astra_submission::STATUS_READY,
            'submissiontime' => $now + 5,
            'hash' => mod_astra_submission::getRandomString(),
            'exerciseid' => $this->exercises[1]->getId(),
            'submitter' => $this->student->id,
            'feedback' => 'test feedback',
            'grade' => 10,
            'gradingtime' => $now + 5,
            'servicepoints' => 10,
            'servicemaxpoints' => 10,
        ));
        $submissionids[] = $DB->insert_record(mod_astra_submission::TABLE, (object) array(
            'status' => mod_astra_submission::STATUS_READY,
            'submissiontime' => $now + 6,
            'hash' => mod_astra_submission::getRandomString(),
            'exerciseid' => $this->exercises[1]->getId(),
            'submitter' => $this->student->id,
            'feedback' => 'test feedback',
            'grade' => 9,
            'gradingtime' => $now + 6,
            'servicepoints' => 9,
            'servicemaxpoints' => 10,
        ));
        $submissionids[] = $DB->insert_record(mod_astra_submission::TABLE, (object) array(
            'status' => mod_astra_submission::STATUS_READY,
            'submissiontime' => $now + 7,
            'hash' => mod_astra_submission::getRandomString(),
            'exerciseid' => $this->exercises[1]->getId(),
            'submitter' => $this->student2->id,
            'feedback' => 'test feedback',
            'grade' => 6,
            'gradingtime' => $now + 7,
            'servicepoints' => 6,
            'servicemaxpoints' => 10,
        ));
        $submissionids[] = $DB->insert_record(mod_astra_submission::TABLE, (object) array(
            'status' => mod_astra_submission::STATUS_READY,
            'submissiontime' => $now + 8,
            'hash' => mod_astra_submission::getRandomString(),
            'exerciseid' => $this->exercises[1]->getId(),
            'submitter' => $this->student2->id,
            'feedback' => 'test feedback',
            'grade' => 5,
            'gradingtime' => $now + 8,
            'servicepoints' => 5,
            'servicemaxpoints' => 10,
        ));
        // student 17, student2 14
        // $this->exercises[3]
        $submissionids[] = $DB->insert_record(mod_astra_submission::TABLE, (object) array(
            'status' => mod_astra_submission::STATUS_READY,
            'submissiontime' => $now + 9,
            'hash' => mod_astra_submission::getRandomString(),
            'exerciseid' => $this->exercises[3]->getId(),
            'submitter' => $this->student->id,
            'feedback' => 'test feedback',
            'grade' => 2,
            'gradingtime' => $now + 9,
            'servicepoints' => 2,
            'servicemaxpoints' => 10,
        ));
        $submissionids[] = $DB->insert_record(mod_astra_submission::TABLE, (object) array(
            'status' => mod_astra_submission::STATUS_READY,
            'submissiontime' => $now + 10,
            'hash' => mod_astra_submission::getRandomString(),
            'exerciseid' => $this->exercises[3]->getId(),
            'submitter' => $this->student->id,
            'feedback' => 'test feedback',
            'grade' => 3,
            'gradingtime' => $now + 10,
            'servicepoints' => 3,
            'servicemaxpoints' => 10,
        ));
        $submissionids[] = $DB->insert_record(mod_astra_submission::TABLE, (object) array(
            'status' => mod_astra_submission::STATUS_READY,
            'submissiontime' => $now + 11,
            'hash' => mod_astra_submission::getRandomString(),
            'exerciseid' => $this->exercises[3]->getId(),
            'submitter' => $this->student2->id,
            'feedback' => 'test feedback',
            'grade' => 4,
            'gradingtime' => $now + 11,
            'servicepoints' => 4,
            'servicemaxpoints' => 10,
        ));
        // student 20, student2 18
        // $exercisesround2[0]
        $submissionids[] = $DB->insert_record(mod_astra_submission::TABLE, (object) array(
            'status' => mod_astra_submission::STATUS_READY,
            'submissiontime' => $now + 12,
            'hash' => mod_astra_submission::getRandomString(),
            'exerciseid' => $exercisesround2[0]->getId(),
            'submitter' => $this->student->id,
            'feedback' => 'test feedback',
            'grade' => 11,
            'gradingtime' => $now + 12,
            'servicepoints' => 11,
            'servicemaxpoints' => 15,
        ));
        $submissionids[] = $DB->insert_record(mod_astra_submission::TABLE, (object) array(
            'status' => mod_astra_submission::STATUS_READY,
            'submissiontime' => $now + 13,
            'hash' => mod_astra_submission::getRandomString(),
            'exerciseid' => $exercisesround2[0]->getId(),
            'submitter' => $this->student->id,
            'feedback' => 'test feedback',
            'grade' => 10,
            'gradingtime' => $now + 13,
            'servicepoints' => 10,
            'servicemaxpoints' => 15,
        ));
        $submissionids[] = $DB->insert_record(mod_astra_submission::TABLE, (object) array(
            'status' => mod_astra_submission::STATUS_READY,
            'submissiontime' => $now + 14,
            'hash' => mod_astra_submission::getRandomString(),
            'exerciseid' => $exercisesround2[0]->getId(),
            'submitter' => $this->student2->id,
            'feedback' => 'test feedback',
            'grade' => 3,
            'gradingtime' => $now + 14,
            'servicepoints' => 3,
            'servicemaxpoints' => 15,
        ));
        // student 11, student2 3
        // $exercisesround2[1]
        $submissionids[] = $DB->insert_record(mod_astra_submission::TABLE, (object) array(
            'status' => mod_astra_submission::STATUS_READY,
            'submissiontime' => $now + 15,
            'hash' => mod_astra_submission::getRandomString(),
            'exerciseid' => $exercisesround2[1]->getId(),
            'submitter' => $this->student->id,
            'feedback' => 'test feedback',
            'grade' => 7,
            'gradingtime' => $now + 15,
            'servicepoints' => 7,
            'servicemaxpoints' => 10,
        ));
        // student 18, student2 3
        // $exercisesround2[2]
        $submissionids[] = $DB->insert_record(mod_astra_submission::TABLE, (object) array(
            'status' => mod_astra_submission::STATUS_READY,
            'submissiontime' => $now + 16,
            'hash' => mod_astra_submission::getRandomString(),
            'exerciseid' => $exercisesround2[2]->getId(),
            'submitter' => $this->student2->id,
            'feedback' => 'test feedback',
            'grade' => 0,
            'gradingtime' => $now + 16,
            'servicepoints' => 0,
            'servicemaxpoints' => 10,
        ));
        // student 18, student2 3
        // $exercisesround2[3]
        $submissionids[] = $DB->insert_record(mod_astra_submission::TABLE, (object) array(
            'status' => mod_astra_submission::STATUS_READY,
            'submissiontime' => $now + 17,
            'hash' => mod_astra_submission::getRandomString(),
            'exerciseid' => $exercisesround2[3]->getId(),
            'submitter' => $this->student2->id,
            'feedback' => 'test feedback',
            'grade' => 6,
            'gradingtime' => $now + 17,
            'servicepoints' => 6,
            'servicemaxpoints' => 10,
        ));
        $submissionids[] = $DB->insert_record(mod_astra_submission::TABLE, (object) array(
            'status' => mod_astra_submission::STATUS_READY,
            'submissiontime' => $now + 18,
            'hash' => mod_astra_submission::getRandomString(),
            'exerciseid' => $exercisesround2[3]->getId(),
            'submitter' => $this->student2->id,
            'feedback' => 'test feedback',
            'grade' => 4,
            'gradingtime' => $now + 18,
            'servicepoints' => 4,
            'servicemaxpoints' => 10,
        ));
        $submissionids[] = $DB->insert_record(mod_astra_submission::TABLE, (object) array(
            'status' => mod_astra_submission::STATUS_WAITING,
            'submissiontime' => $now + 19,
            'hash' => mod_astra_submission::getRandomString(),
            'exerciseid' => $exercisesround2[3]->getId(),
            'submitter' => $this->student->id,
            'feedback' => null,
            'grade' => 0,
            'gradingtime' => $now + 19,
            'servicepoints' => 0,
            'servicemaxpoints' => 0,
        ));
        // student 18, student2 9

        // Update gradebook.
        $this->round1->writeAllGradesToGradebook($this->student->id);
        $this->round2->writeAllGradesToGradebook($this->student->id);
        $this->round1->writeAllGradesToGradebook($this->student2->id);
        $this->round2->writeAllGradesToGradebook($this->student2->id);

        // Check that the gradebook has correct grades.
        $gradinginfo1 = grade_get_grades(
            $this->course->id,
            'mod',
            mod_astra_exercise_round::TABLE,
            $this->round1->getId(),
            array(
                $this->student->id,
                $this->student2->id
            )
        );
        $gradinginfo2 = grade_get_grades(
            $this->course->id,
            'mod',
            mod_astra_exercise_round::TABLE,
            $this->round2->getId(),
            array(
                $this->student->id,
                $this->student2->id
            )
        );
        $items1 = $gradinginfo1->items;
        $items2 = $gradinginfo2->items;
        $this->assertEquals(1, count($items1)); // Only the exercise round has a grade item.
        $this->assertEquals(1, count($items2));
        $this->assertEquals(20, $items1[0]->grades[$this->student->id]->grade);
        $this->grade_test_helper(
            mod_astra_submission::createFromId($submissionids[0]), // student
            7, // expected submission grade
            7, // expected best exercise grade
            20, // expected exercise round grade
            'test feedback'
        );
        $this->grade_test_helper(
            mod_astra_submission::createFromId($submissionids[2]), // student2
            1,
            8,
            18,
            'test feedback'
        );
        $this->grade_test_helper(
            mod_astra_submission::createFromId($submissionids[11]),
            11,
            11,
            18,
            'test feedback'
        );
        $this->grade_test_helper(
            mod_astra_submission::createFromId($submissionids[17]), // student2
            4,
            6,
            9,
            'test feedback'
        );

        // Delete grades from the gradebook and write grades again for everyone.
        grade_update(
            'mod/'. mod_astra_exercise_round::TABLE,
            $this->course->id,
            'mod',
            mod_astra_exercise_round::TABLE,
            $this->round1->getId(),
            0,
            null,
            array('reset' => true)
        );
        grade_update(
            'mod/'. mod_astra_exercise_round::TABLE,
            $this->course->id,
            'mod',
            mod_astra_exercise_round::TABLE,
            $this->round2->getId(),
            0,
            null,
            array('reset' => true)
        );
        $this->round1->writeAllGradesToGradebook();
        $this->round2->writeAllGradesToGradebook();
        $gradinginfo1 = grade_get_grades(
            $this->course->id,
            'mod',
            mod_astra_exercise_round::TABLE,
            $this->round1->getId(),
            array(
                $this->student->id,
                $this->student2->id
            )
        );
        $gradinginfo2 = grade_get_grades(
            $this->course->id,
            'mod',
            mod_astra_exercise_round::TABLE,
            $this->round2->getId(),
            array(
                $this->student->id,
                $this->student2->id
            )
        );
        $items1 = $gradinginfo1->items;
        $items2 = $gradinginfo2->items;
        $this->assertEquals(1, count($items1)); // Only the exercise round has a grade item.
        $this->assertEquals(1, count($items2));
        $this->assertEquals(20, $items1[0]->grades[$this->student->id]->grade);
        $this->grade_test_helper(
            mod_astra_submission::createFromId($submissionids[0]), // student
            7, // expected submission grade
            7, // expected best exercise grade
            20, // expected exercise round grade
            'test feedback'
        );
        $this->grade_test_helper(
            mod_astra_submission::createFromId($submissionids[2]), // student2
            1,
            8,
            18,
            'test feedback'
        );
        $this->grade_test_helper(
            mod_astra_submission::createFromId($submissionids[11]),
            11,
            11,
            18,
            'test feedback'
        );
        $this->grade_test_helper(
            mod_astra_submission::createFromId($submissionids[17]), // student2
            4,
            6,
            9,
            'test feedback'
        );
    }

    public function test_gradebook_enrolled() {
        global $DB, $CFG;
        require_once($CFG->libdir .'/gradelib.php');

        $this->resetAfterTest(true);

        $user1 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user1->id, $this->course->id);
        // Create submissions.
        $submissions = array();
        $submissions[] = $this->add_submission($this->exercises[0], $user1, array(
            'grade' => 5,
        ));
        $this->round1->writeAllGradesToGradebook($user1->id);
        $this->grade_test_helper($submissions[0], 5, 5, 5, 'test feedback');

        $submissions[] = $this->add_submission($this->exercises[0], $user1, array(
            'grade' => 7,
        ));
        $this->round1->writeAllGradesToGradebook();
        $this->grade_test_helper($submissions[1], 7, 7, 7, 'test feedback');

        $submissions[] = $this->add_submission($this->exercises[0], $user1, array(
            'grade' => 6,
        ));
        $this->round1->writeAllGradesToGradebook($user1->id);
        $this->grade_test_helper($submissions[2], 6, 7, 7, 'test feedback');
    }
}
