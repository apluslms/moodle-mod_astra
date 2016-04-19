<?php

/**
 * Unit tests for exercise round.
 * @group mod_stratumtwo
 */
class mod_stratumtwo_exercise_round_testcase extends advanced_testcase {
    
    private $course;
    private $round1_data;
    
    public function add_course() {
        // create a course instance for testing
        $this->setAdminUser();
        $this->course = $this->getDataGenerator()->create_course();
    }
    
    public function add_round1() {
        // create an exercise round
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_stratumtwo');
        $this->round1_data = array(
                'course' => $this->course->id,
                'name' => '1. Test round 1',
                'remotekey' => 'testround1',
                'openingtime' => time(),
                'closingtime' => time() + 3600 * 24 * 7,
                'ordernum' => 1,
                'status' => mod_stratumtwo_exercise_round::STATUS_READY,
                'pointstopass' => 0,
                'latesbmsallowed' => 1,
                'latesbmsdl' => time() + 3600 * 24 * 14,
                'latesbmspenalty' => 0.4,
        );
        return $generator->create_instance($this->round1_data); // stdClass record
    }
    
    public function test_getCourseModule() {
        $this->resetAfterTest(true);
        
        $this->add_course();
        $roundRecord1 = $this->add_round1();
        $exround = new mod_stratumtwo_exercise_round($roundRecord1);
        
        $this->assertEquals($roundRecord1->cmid, $exround->getCourseModule()->id);
        $this->assertEquals($this->course->id, $exround->getCourse()->courseid);
    }
    
    public function test_getters() {
        $this->resetAfterTest(true);
        
        $this->add_course();
        $roundRecord1 = $this->add_round1();
        $exround = new mod_stratumtwo_exercise_round($roundRecord1);
        
        $this->assertEquals($this->round1_data['name'], $exround->getName());
        $this->assertEquals($this->round1_data['status'], $exround->getStatus());
        $this->assertEquals('Ready', $exround->getStatus(true));
        $this->assertEquals(0, $exround->getMaxPoints());
        $this->assertEquals($this->round1_data['remotekey'], $exround->getRemoteKey());
        $this->assertEquals($this->round1_data['ordernum'], $exround->getOrder());
        $this->assertEquals($this->round1_data['pointstopass'], $exround->getPointsToPass());
        $this->assertEquals($this->round1_data['openingtime'], $exround->getOpeningTime());
        $this->assertEquals($this->round1_data['closingtime'], $exround->getClosingTime());
        $this->assertEquals((bool) $this->round1_data['latesbmsallowed'], $exround->isLateSubmissionAllowed());
        $this->assertEquals($this->round1_data['latesbmsdl'], $exround->getLateSubmissionDeadline());
        $this->assertEquals($this->round1_data['latesbmspenalty'], $exround->getLateSubmissionPenalty(), '', 0.01);
        // float comparison with delta
        $this->assertEquals(60, $exround->getLateSubmissionPointWorth());
        
        $this->assertTrue($exround->hasExpired($this->round1_data['closingtime'] + 3600 * 24));
        $this->assertFalse($exround->hasExpired($this->round1_data['openingtime'] + 3600 * 24));
        $this->assertFalse($exround->isOpen($this->round1_data['openingtime'] - 3600 * 24));
        $this->assertFalse($exround->isOpen($this->round1_data['closingtime'] + 3600 * 24));
        $this->assertTrue($exround->isOpen($this->round1_data['openingtime'] + 3600 * 24));
        $this->assertTrue($exround->isLateSubmissionOpen($this->round1_data['closingtime'] + 3600 * 24));
        $this->assertFalse($exround->isLateSubmissionOpen($this->round1_data['closingtime'] - 3600 * 24));
        $this->assertFalse($exround->isLateSubmissionOpen($this->round1_data['latesbmsdl'] + 3600 * 24));
        $this->assertTrue($exround->hasStarted($this->round1_data['closingtime']));
        $this->assertFalse($exround->hasStarted($this->round1_data['openingtime'] - 3600 * 24));
        
        $this->assertFalse($exround->isHidden());
        $this->assertFalse($exround->isUnderMaintenance());
    }
    
    public function test_updateNameWithOrder() {
        $this->assertEquals('1. Hello world',
                mod_stratumtwo_exercise_round::updateNameWithOrder('2. Hello world', 1, mod_stratumtwo_course_config::MODULE_NUMBERING_ARABIC));
        $this->assertEquals('1. Hello world',
                mod_stratumtwo_exercise_round::updateNameWithOrder('Hello world', 1, mod_stratumtwo_course_config::MODULE_NUMBERING_ARABIC));
        $this->assertEquals('10. Hello world',
                mod_stratumtwo_exercise_round::updateNameWithOrder('III Hello world', 10, mod_stratumtwo_course_config::MODULE_NUMBERING_ARABIC));
        $this->assertEquals('II Hello world',
                mod_stratumtwo_exercise_round::updateNameWithOrder('2. Hello world', 2, mod_stratumtwo_course_config::MODULE_NUMBERING_ROMAN));
        $this->assertEquals('Hello world',
                mod_stratumtwo_exercise_round::updateNameWithOrder('2. Hello world', 3, mod_stratumtwo_course_config::MODULE_NUMBERING_HIDDEN_ARABIC));
        $this->assertEquals('12. VXYii XXX', // name contains characters that are used in roman numbers
                mod_stratumtwo_exercise_round::updateNameWithOrder('X VXYii XXX', 12, mod_stratumtwo_course_config::MODULE_NUMBERING_ARABIC));
    }
    
    public function test_create_round() {
        global $DB, $CFG;
        require_once($CFG->libdir .'/gradelib.php');
        
        $this->resetAfterTest(true);
        
        $this->add_course();
        $this->round1_data = array(
                'course' => $this->course->id,
                'name' => '1. Test round 1',
                'remotekey' => 'testround1',
                'openingtime' => time(),
                'closingtime' => time() + 3600 * 24 * 7,
                'ordernum' => 1,
                'status' => mod_stratumtwo_exercise_round::STATUS_READY,
                'pointstopass' => 0,
                'latesbmsallowed' => 1,
                'latesbmsdl' => time() + 3600 * 24 * 14,
                'latesbmspenalty' => 0.4,
        );
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_stratumtwo');
        $record = $generator->create_instance($this->round1_data);
        $roundId = $record->id;
        
        $this->assertNotEquals(0, $roundId);
        $roundRecord = $DB->get_record(mod_stratumtwo_exercise_round::TABLE, array('id' => $roundId));
        $this->assertTrue($roundRecord !== false);
        $exround = new mod_stratumtwo_exercise_round($roundRecord);
        $this->assertEquals($this->round1_data['name'], $exround->getName());
        
        // test gradebook
        $grade_items = grade_get_grades($this->course->id, 'mod', mod_stratumtwo_exercise_round::TABLE, $roundId, null)->items;
        // gradebook has no grade item when the max points are zero
        $this->assertFalse(isset($grade_items[0])); // round should use itemnumber 0
        
        // test calendar event
        $event = $DB->get_record('event', array(
                'modulename' => mod_stratumtwo_exercise_round::TABLE,
                'instance' => $roundId,
                'eventtype' => mod_stratumtwo_exercise_round::EVENT_DL_TYPE,
        ));
        $this->assertTrue($event !== false);
        $this->assertEquals(1, $event->visible);
        $this->assertEquals($this->course->id, $event->courseid);
        $this->assertEquals("Deadline: {$this->round1_data['name']}", $event->name);
    }
    
    public function test_create_learning_objects() {
        global $DB, $CFG;
        require_once($CFG->libdir .'/gradelib.php');
        
        $this->resetAfterTest(true);
        
        // create a course and a round
        $this->add_course();
        $this->round1_data = array(
                'course' => $this->course->id,
                'name' => '1. Test round 1',
                'remotekey' => 'testround1',
                'openingtime' => time(),
                'closingtime' => time() + 3600 * 24 * 7,
                'ordernum' => 1,
                'status' => mod_stratumtwo_exercise_round::STATUS_READY,
                'pointstopass' => 0,
                'latesbmsallowed' => 1,
                'latesbmsdl' => time() + 3600 * 24 * 14,
                'latesbmspenalty' => 0.4,
        );
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_stratumtwo');
        $record = $generator->create_instance($this->round1_data);
        $roundId = $record->id;
        $roundRecord = $DB->get_record(mod_stratumtwo_exercise_round::TABLE, array('id' => $roundId));
        $this->assertTrue($roundRecord !== false);
        $exround = new mod_stratumtwo_exercise_round($roundRecord);
        
        // create category and exercise
        $categoryRecord = (object) array(
                'course' => $this->course->id,
                'status' => mod_stratumtwo_category::STATUS_READY,
                'name' => 'Test category',
                'pointstopass' => 0,
        );
        $category = mod_stratumtwo_category::createFromId(mod_stratumtwo_category::createNew($categoryRecord));
        $exerciseRecord = (object) array(
                'status' => mod_stratumtwo_learning_object::STATUS_READY,
                'parentid' => null,
                'ordernum' => 1,
                'remotekey' => 'testexercise',
                'name' => 'Exercise A',
                'serviceurl' => 'localhost',
                'maxsubmissions' => 10,
                'pointstopass' => 5,
                'maxpoints' => 10,
        );
        $exercise = $exround->createNewExercise($exerciseRecord, $category);
        
        // test that exercise was created
        $this->assertTrue($exercise !== null);
        $fetchedLobjRecord = $DB->get_record(mod_stratumtwo_learning_object::TABLE, array('id' => $exercise->getId()));
        $this->assertTrue($fetchedLobjRecord !== false);
        $fetchedExRecord = $DB->get_record(mod_stratumtwo_exercise::TABLE, array('id' => $exercise->getSubtypeId()));
        $this->assertTrue($fetchedExRecord !== false);
        
        // test gradebook
        $this->assertTrue($exercise->getGradebookItemNumber() > 0); // zero reserved for round
        $grade_items = grade_get_grades($this->course->id, 'mod', mod_stratumtwo_exercise_round::TABLE, $roundId, null)->items;
        $this->assertTrue(isset($grade_items[$exercise->getGradebookItemNumber()]));
        $item = $grade_items[$exercise->getGradebookItemNumber()];
        $this->assertEquals($exercise->getGradebookItemNumber(), $item->itemnumber);
        $this->assertEquals($roundId, $item->iteminstance);
        $this->assertEquals('1.1 Exercise A', $item->name);
        $this->assertEquals($exerciseRecord->maxpoints, $item->grademax);
        $this->assertEquals(0, $item->grademin);
        $this->assertFalse($item->hidden);
        
        // test round gradebook
        $this->assertTrue(isset($grade_items[0])); // item number 0 reserved for round
        $this->assertEquals(0, $grade_items[0]->itemnumber);
        $this->assertEquals($roundId, $grade_items[0]->iteminstance);
        $this->assertEquals($this->round1_data['name'], $grade_items[0]->name);
        $this->assertEquals($exerciseRecord->maxpoints, $grade_items[0]->grademax);
        $this->assertEquals(0, $grade_items[0]->grademin);
        $this->assertFalse($grade_items[0]->hidden);
        
        // round max points should have increased
        $this->assertEquals($exerciseRecord->maxpoints, $DB->get_field(mod_stratumtwo_exercise_round::TABLE, 'grade',
                array('id' => $roundId), MUST_EXIST));
        
        // create a chapter
        $chapterRecord = (object) array(
                'status' => mod_stratumtwo_learning_object::STATUS_READY,
                'parentid' => null,
                'ordernum' => 2,
                'remotekey' => 'testchapter',
                'name' => 'Chapter A',
                'serviceurl' => 'localhost',
                'generatetoc' => 0,
        );
        $chapter = $exround->createNewChapter($chapterRecord, $category);
        
        // test that exercise was created
        $this->assertTrue($chapter !== null);
        $fetchedLobjRecord = $DB->get_record(mod_stratumtwo_learning_object::TABLE, array('id' => $chapter->getId()));
        $this->assertTrue($fetchedLobjRecord !== false);
        $fetchedChRecord = $DB->get_record(mod_stratumtwo_chapter::TABLE, array('id' => $chapter->getSubtypeId()));
        $this->assertTrue($fetchedChRecord !== false);
        
        // round max points should not have changed
        $this->assertEquals($exerciseRecord->maxpoints, $DB->get_field(mod_stratumtwo_exercise_round::TABLE, 'grade',
                array('id' => $roundId), MUST_EXIST));
        
        // create a hidden exercise
        $exerciseRecord2 = (object) array(
                'status' => mod_stratumtwo_learning_object::STATUS_HIDDEN,
                'parentid' => null,
                'ordernum' => 3,
                'remotekey' => 'testexercise2',
                'name' => 'Exercise B',
                'serviceurl' => 'localhost',
                'maxsubmissions' => 10,
                'pointstopass' => 5,
                'maxpoints' => 10,
        );
        $exercise2 = $exround->createNewExercise($exerciseRecord2, $category);
        // round max points should not have changed
        $this->assertEquals($exerciseRecord->maxpoints, $DB->get_field(mod_stratumtwo_exercise_round::TABLE, 'grade',
                array('id' => $roundId), MUST_EXIST));
    }
    
    public function test_save() {
        global $DB, $CFG;
        require_once($CFG->libdir .'/gradelib.php');
        
        $this->resetAfterTest(true);
        
        // create a course and a round
        $this->add_course();
        $record = $this->add_round1();
        $exround = mod_stratumtwo_exercise_round::createFromId($record->id);
        
        // change some values and save
        $exround->setName('PHP round');
        $exround->setOrder(5);
        
        $exround->save();
        
        // test that database row was updated
        $this->assertEquals('PHP round', $DB->get_field(mod_stratumtwo_exercise_round::TABLE, 'name', array('id' => $record->id)));
        $this->assertEquals(5, $DB->get_field(mod_stratumtwo_exercise_round::TABLE, 'ordernum', array('id' => $record->id)));
        $this->assertEquals(mod_stratumtwo_exercise_round::STATUS_READY, // not changed
                $DB->get_field(mod_stratumtwo_exercise_round::TABLE, 'status', array('id' => $record->id)));
        
        // test gradebook
        $grade_items = grade_get_grades($this->course->id, 'mod', mod_stratumtwo_exercise_round::TABLE, $record->id, null)->items;
        // gradebook has no grade item when the max points are zero
        $this->assertFalse(isset($grade_items[0])); // round should use itemnumber 0
        //$this->assertTrue(isset($grade_items[0])); // round should use itemnumber 0
        //$this->assertEquals('PHP round', $grade_items[0]->name);
        
        // test event
        $event = $DB->get_record('event', array(
                'modulename' => mod_stratumtwo_exercise_round::TABLE,
                'instance' => $record->id,
                'eventtype' => mod_stratumtwo_exercise_round::EVENT_DL_TYPE,
        ));
        $this->assertTrue($event !== false);
        $this->assertEquals(1, $event->visible);
        $this->assertEquals('Deadline: PHP round', $event->name);
    }
    
    public function test_getLearningObjects() {
        $this->resetAfterTest(true);
        
        // create a course and a round
        $this->add_course();
        $record = $this->add_round1();
        $exround = mod_stratumtwo_exercise_round::createFromId($record->id);
        
        // create learning objects
        $category = mod_stratumtwo_category::createFromId(mod_stratumtwo_category::createNew((object) array(
                'course' => $this->course->id,
                'status' => mod_stratumtwo_category::STATUS_READY,
                'name' => 'Test category',
                'pointstopass' => 0,
        )));
        $exercise1 = $exround->createNewExercise((object) array(
                'status' => mod_stratumtwo_learning_object::STATUS_READY,
                'parentid' => null,
                'ordernum' => 1,
                'remotekey' => 'testexercise',
                'name' => 'Exercise A',
                'serviceurl' => 'localhost',
                'maxsubmissions' => 10,
                'pointstopass' => 5,
                'maxpoints' => 10,
        ), $category);
        
        $chapter2 = $exround->createNewChapter((object) array(
                'status' => mod_stratumtwo_learning_object::STATUS_READY,
                'parentid' => null,
                'ordernum' => 2,
                'remotekey' => 'testchapter',
                'name' => 'Chapter A',
                'serviceurl' => 'localhost',
                'generatetoc' => 0,
        ), $category);
        
        $exercise21 = $exround->createNewExercise((object) array(
                'status' => mod_stratumtwo_learning_object::STATUS_UNLISTED,
                'parentid' => $chapter2->getId(),
                'ordernum' => 1,
                'remotekey' => 'testexercise21',
                'name' => 'Embedded Exercise 1',
                'serviceurl' => 'localhost',
                'maxsubmissions' => 10,
                'pointstopass' => 5,
                'maxpoints' => 10,
        ), $category);
        
        $exercise211 = $exround->createNewExercise((object) array(
                'status' => mod_stratumtwo_learning_object::STATUS_READY,
                'parentid' => $exercise21->getId(),
                'ordernum' => 1,
                'remotekey' => 'testexercise211',
                'name' => 'Another exercise below an embedded exercise',
                'serviceurl' => 'localhost',
                'maxsubmissions' => 10,
                'pointstopass' => 5,
                'maxpoints' => 10,
        ), $category);
        
        $exercise22 = $exround->createNewExercise((object) array(
                'status' => mod_stratumtwo_learning_object::STATUS_UNLISTED,
                'parentid' => $chapter2->getId(),
                'ordernum' => 2,
                'remotekey' => 'testexercise22',
                'name' => 'Embedded Exercise 2',
                'serviceurl' => 'localhost',
                'maxsubmissions' => 10,
                'pointstopass' => 5,
                'maxpoints' => 10,
        ), $category);
        
        // test fetching round learning objects and their sorting
        $objects = $exround->getLearningObjects();
        $this->assertEquals($exercise1->getId(), $objects[0]->getId());
        $this->assertEquals($chapter2->getId(), $objects[1]->getId());
        $this->assertEquals($exercise21->getId(), $objects[2]->getId());
        $this->assertEquals($exercise211->getId(), $objects[3]->getId());
        $this->assertEquals($exercise22->getId(), $objects[4]->getId());
    }
    
    public function test_delete() {
        global $DB, $CFG;
        require_once($CFG->libdir .'/gradelib.php');
        
        $this->resetAfterTest(true);
        
        // create a course and a round
        $this->add_course();
        $record = $this->add_round1();
        $exround = mod_stratumtwo_exercise_round::createFromId($record->id);
        
        // create learning objects
        $category = mod_stratumtwo_category::createFromId(mod_stratumtwo_category::createNew((object) array(
                'course' => $this->course->id,
                'status' => mod_stratumtwo_category::STATUS_READY,
                'name' => 'Test category',
                'pointstopass' => 0,
        )));
        $exercise1 = $exround->createNewExercise((object) array(
                'status' => mod_stratumtwo_learning_object::STATUS_READY,
                'parentid' => null,
                'ordernum' => 1,
                'remotekey' => 'testexercise',
                'name' => 'Exercise A',
                'serviceurl' => 'localhost',
                'maxsubmissions' => 10,
                'pointstopass' => 5,
                'maxpoints' => 10,
        ), $category);
        
        $chapter2 = $exround->createNewChapter((object) array(
                'status' => mod_stratumtwo_learning_object::STATUS_READY,
                'parentid' => null,
                'ordernum' => 2,
                'remotekey' => 'testchapter',
                'name' => 'Chapter A',
                'serviceurl' => 'localhost',
                'generatetoc' => 0,
        ), $category);
        
        $exercise21 = $exround->createNewExercise((object) array(
                'status' => mod_stratumtwo_learning_object::STATUS_UNLISTED,
                'parentid' => $chapter2->getId(),
                'ordernum' => 1,
                'remotekey' => 'testexercise21',
                'name' => 'Embedded Exercise 1',
                'serviceurl' => 'localhost',
                'maxsubmissions' => 10,
                'pointstopass' => 5,
                'maxpoints' => 10,
        ), $category);
        
        $exercise211 = $exround->createNewExercise((object) array(
                'status' => mod_stratumtwo_learning_object::STATUS_READY,
                'parentid' => $exercise21->getId(),
                'ordernum' => 1,
                'remotekey' => 'testexercise211',
                'name' => 'Another exercise below an embedded exercise',
                'serviceurl' => 'localhost',
                'maxsubmissions' => 10,
                'pointstopass' => 5,
                'maxpoints' => 10,
        ), $category);
        
        $exercise22 = $exround->createNewExercise((object) array(
                'status' => mod_stratumtwo_learning_object::STATUS_UNLISTED,
                'parentid' => $chapter2->getId(),
                'ordernum' => 2,
                'remotekey' => 'testexercise22',
                'name' => 'Embedded Exercise 2',
                'serviceurl' => 'localhost',
                'maxsubmissions' => 10,
                'pointstopass' => 5,
                'maxpoints' => 10,
        ), $category);
        
        // check that all child objects exist
        $this->assertEquals(5, count($exround->getLearningObjects()));
        
        // delete round
        $exround->deleteInstance();
        
        // test that round and exercises have been deleted
        $this->assertFalse($DB->get_record(mod_stratumtwo_exercise_round::TABLE, array('id' => $exround->getId())));
        $this->assertEquals(0, $DB->count_records(mod_stratumtwo_exercise::TABLE));
        $this->assertEquals(0, $DB->count_records(mod_stratumtwo_learning_object::TABLE, array('roundid' => $record->id)));
        $this->assertEquals(0, $DB->count_records(mod_stratumtwo_chapter::TABLE));
        
        // gradebook and events
        $grade_items = grade_get_grades($this->course->id, 'mod', mod_stratumtwo_exercise_round::TABLE, $record->id, null)->items;
        $this->assertFalse(isset($grade_items[0]));
        
        $this->assertEquals(0, $DB->count_records('event', array(
                'modulename' => mod_stratumtwo_exercise_round::TABLE,
                'instance' => $record->id,
                'eventtype' => mod_stratumtwo_exercise_round::EVENT_DL_TYPE,
        )));
    }
    
    public function test_getExerciseRoundsInCourse() {
        global $DB, $CFG;
        
        $this->resetAfterTest(true);
        
        $this->add_course();
        
        // create exercise rounds
        $numRounds = 5;
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_stratumtwo');
        $rounds = array();
        for ($i = 1; $i <= $numRounds; ++$i) {
            if ($i == 3) {
                $status = mod_stratumtwo_exercise_round::STATUS_HIDDEN;
            } else if ($i == 4) {
                $status = mod_stratumtwo_exercise_round::STATUS_MAINTENANCE;
            } else {
                $status = mod_stratumtwo_exercise_round::STATUS_READY;
            }
            $round = array(
                'course' => $this->course->id,
                'name' => "$i. Test round $i",
                'remotekey' => "testround$i",
                'openingtime' => time(),
                'closingtime' => time() + 3600 * 24 * 7,
                'ordernum' => $i,
                'status' => $status,
                'pointstopass' => 0,
                'latesbmsallowed' => 1,
                'latesbmsdl' => time() + 3600 * 24 * 14,
                'latesbmspenalty' => 0.4,
            );
            $rounds[] = $generator->create_instance($round); // stdClass record
        }
        
        $anotherCourse = $this->getDataGenerator()->create_course();
        $round = array(
                'course' => $anotherCourse->id,
                'name' => "1. Other test round 1",
                'remotekey' => "testround1",
                'openingtime' => time(),
                'closingtime' => time() + 3600 * 24 * 7,
                'ordernum' => 1,
                'status' => mod_stratumtwo_exercise_round::STATUS_MAINTENANCE,
                'pointstopass' => 0,
                'latesbmsallowed' => 1,
                'latesbmsdl' => time() + 3600 * 24 * 14,
                'latesbmspenalty' => 0.4,
        );
        $rounds[] = $generator->create_instance($round); // stdClass record
        
        // test
        $course1_rounds = mod_stratumtwo_exercise_round::getExerciseRoundsInCourse($this->course->id, false);
        $this->assertEquals($numRounds - 1, count($course1_rounds)); // one round is hidden
        $this->assertEquals($rounds[0]->id, $course1_rounds[0]->getId());
        $this->assertEquals($rounds[1]->id, $course1_rounds[1]->getId());
        $this->assertEquals($rounds[3]->id, $course1_rounds[2]->getId());
        $this->assertEquals($rounds[4]->id, $course1_rounds[3]->getId());
        
        $course1_rounds_with_hidden = mod_stratumtwo_exercise_round::getExerciseRoundsInCourse($this->course->id, true);
        $this->assertEquals($numRounds, count($course1_rounds_with_hidden));
        $this->assertEquals($rounds[2]->id, $course1_rounds_with_hidden[2]->getId());
        
        $course2_rounds_with_hidden = mod_stratumtwo_exercise_round::getExerciseRoundsInCourse($anotherCourse->id, true);
        $this->assertEquals(1, count($course2_rounds_with_hidden));
        $this->assertEquals($rounds[count($rounds) - 1]->id, $course2_rounds_with_hidden[0]->getId());
    }
}