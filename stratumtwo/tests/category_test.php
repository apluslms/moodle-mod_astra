<?php

/**
 * Unit tests for category.
 * @group mod_stratumtwo
 */
class mod_stratumtwo_category_testcase extends advanced_testcase {
    
    private $course;
    private $round1;
    
    public function setUp() {
        // create a course instance for testing
        $this->setAdminUser();
        $this->course = $this->getDataGenerator()->create_course();
        
        // create an exercise round
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_stratumtwo');
        $round1_data = array(
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
        $record = $generator->create_instance($round1_data); // stdClass record
        $this->round1 = new mod_stratumtwo_exercise_round($record);
    }
    
    public function test_createNew() {
        global $DB;
        
        $this->resetAfterTest(true);
        
        $catData = array(
                'course' => $this->course->id,
                'status' => mod_stratumtwo_category::STATUS_READY,
                'name' => 'Test category',
                'pointstopass' => 0,
        );
        $catId = mod_stratumtwo_category::createNew((object) $catData);
        
        $this->assertNotEquals(0, $catId);
        $category = mod_stratumtwo_category::createFromId($catId);
        
        $this->assertEquals($catData['pointstopass'], $category->getPointsToPass());
        $this->assertEquals($catData['name'], $category->getName());
        $this->assertEquals($catData['status'], $category->getStatus());
        
        // there should be only one category at this stage
        $this->assertEquals(1, $DB->count_records(mod_stratumtwo_category::TABLE, array('course' => $this->course->id)));
    }
    
    public function test_getCategoriesInCourse() {
        $this->resetAfterTest(true);
        
        $catIds = array();
        $numCats = 4;
        for ($i = 1; $i <= $numCats; ++$i) {
            $catData = array(
                    'course' => $this->course->id,
                    'status' => ($i == 3 ? mod_stratumtwo_category::STATUS_HIDDEN : mod_stratumtwo_category::STATUS_READY),
                    'name' => "Test category $i",
                    'pointstopass' => 0,
            );
            $catIds[] = mod_stratumtwo_category::createNew((object) $catData);
        }
        
        $anotherCourse = $this->getDataGenerator()->create_course();
        $catIds[] = mod_stratumtwo_category::createNew((object) array(
                    'course' => $anotherCourse->id,
                    'status' => mod_stratumtwo_category::STATUS_READY,
                    'name' => "Another test category 1",
                    'pointstopass' => 0,
        ));
        
        $fetchedCats = mod_stratumtwo_category::getCategoriesInCourse($this->course->id, false);
        $this->assertEquals($numCats - 1, count($fetchedCats)); // one cat is hidden
        for ($i = 1; $i <= $numCats; ++$i) {
            if ($i != 3) {
                $this->assertArrayHasKey($catIds[$i - 1], $fetchedCats);
                $this->assertEquals($catIds[$i - 1], $fetchedCats[$catIds[$i - 1]]->getId());
            }
        }
        
        $fetchedCatsHidden = mod_stratumtwo_category::getCategoriesInCourse($this->course->id, true);
        $this->assertEquals($numCats, count($fetchedCatsHidden));
        for ($i = 1; $i <= $numCats; ++$i) {
            $this->assertArrayHasKey($catIds[$i - 1], $fetchedCatsHidden);
            $this->assertEquals($catIds[$i - 1], $fetchedCatsHidden[$catIds[$i - 1]]->getId());
        }
        
        $fetchedCatsHidden = mod_stratumtwo_category::getCategoriesInCourse($anotherCourse->id, true);
        $this->assertEquals(1, count($fetchedCatsHidden));
        $this->assertArrayHasKey($catIds[count($catIds) - 1], $fetchedCatsHidden);
    }
    
    public function test_getLearningObjects() {
        $this->resetAfterTest(true);
        
        $category = mod_stratumtwo_category::createFromId(mod_stratumtwo_category::createNew((object) array(
                'course' => $this->course->id,
                'status' => mod_stratumtwo_category::STATUS_READY,
                'name' => 'Test category',
                'pointstopass' => 0,
        )));
        
        $exercise1 = $this->round1->createNewExercise((object) array(
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
        
        $chapter2 = $this->round1->createNewChapter((object) array(
                'status' => mod_stratumtwo_learning_object::STATUS_READY,
                'parentid' => null,
                'ordernum' => 2,
                'remotekey' => 'testchapter',
                'name' => 'Chapter A',
                'serviceurl' => 'localhost',
                'generatetoc' => 0,
        ), $category);
        
        $exercise21 = $this->round1->createNewExercise((object) array(
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
        
        $exercise211 = $this->round1->createNewExercise((object) array(
                'status' => mod_stratumtwo_learning_object::STATUS_HIDDEN,
                'parentid' => $exercise21->getId(),
                'ordernum' => 1,
                'remotekey' => 'testexercise211',
                'name' => 'Another exercise below an embedded exercise',
                'serviceurl' => 'localhost',
                'maxsubmissions' => 10,
                'pointstopass' => 5,
                'maxpoints' => 10,
        ), $category);
        
        $exercise22 = $this->round1->createNewExercise((object) array(
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
        
        $objectsIds = array($exercise1->getId(), $chapter2->getId(), $exercise21->getId(),
                $exercise211->getId(), $exercise22->getId());
        
        $objects = $category->getLearningObjects(false);
        $this->assertEquals(4, count($objects)); // one object is hidden
        for ($i = 1; $i <= 5; ++$i) {
            if ($i != 4) {
                $this->assertArrayHasKey($objectsIds[$i - 1], $objects);
                $this->assertEquals($objectsIds[$i - 1], $objects[$objectsIds[$i - 1]]->getId());
            }
        }
        
        $objects = $category->getLearningObjects(true);
        $this->assertEquals(5, count($objects));
        for ($i = 1; $i <= 5; ++$i) {
            $this->assertArrayHasKey($objectsIds[$i - 1], $objects);
            $this->assertEquals($objectsIds[$i - 1], $objects[$objectsIds[$i - 1]]->getId());
        }
    }
    
    public function test_updateOrCreate() {
        global $DB;
        
        $this->resetAfterTest(true);
        
        $catData = array(
                'course' => $this->course->id,
                'name' => 'Some category',
                'status' => mod_stratumtwo_category::STATUS_READY,
                'pointstopass' => 5,
        );
        $newCatId = mod_stratumtwo_category::updateOrCreate((object) $catData); // should create new category
        
        $this->assertEquals(1, $DB->count_records(mod_stratumtwo_category::TABLE, array('course' => $this->course->id)));
        $cat = mod_stratumtwo_category::createFromId($newCatId);
        $this->assertEquals('Some category', $cat->getName());
        $this->assertEquals(mod_stratumtwo_category::STATUS_READY, $cat->getStatus());
        
        // update the category
        $catData['status'] = mod_stratumtwo_category::STATUS_HIDDEN;
        $catData['pointstopass'] = 17;
        $catId = mod_stratumtwo_category::updateOrCreate((object) $catData);
        
        $this->assertEquals($newCatId, $catId);
        $cat = mod_stratumtwo_category::createFromId($catId);
        $this->assertEquals('Some category', $cat->getName());
        $this->assertEquals(mod_stratumtwo_category::STATUS_HIDDEN, $cat->getStatus());
        $this->assertEquals(17, $cat->getPointsToPass());
        $this->assertEquals($catId, $cat->getId());
    }
    
    public function test_delete() {
        global $DB;
        
        $this->resetAfterTest(true);
        
        // create categorories, rounds, and exercises
        $category = mod_stratumtwo_category::createFromId(mod_stratumtwo_category::createNew((object) array(
                'course' => $this->course->id,
                'status' => mod_stratumtwo_category::STATUS_READY,
                'name' => 'Test category',
                'pointstopass' => 0,
        )));
        $category2 = mod_stratumtwo_category::createFromId(mod_stratumtwo_category::createNew((object) array(
                'course' => $this->course->id,
                'status' => mod_stratumtwo_category::STATUS_READY,
                'name' => 'Test category 2',
                'pointstopass' => 0,
        )));
        
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_stratumtwo');
        $round2_data = array(
                'course' => $this->course->id,
                'name' => '2. Test round 2',
                'remotekey' => 'testround2',
                'openingtime' => time(),
                'closingtime' => time() + 3600 * 24 * 7,
                'ordernum' => 2,
                'status' => mod_stratumtwo_exercise_round::STATUS_READY,
                'pointstopass' => 0,
                'latesbmsallowed' => 1,
                'latesbmsdl' => time() + 3600 * 24 * 14,
                'latesbmspenalty' => 0.4,
        );
        $record = $generator->create_instance($round2_data); // stdClass record
        $round2 = new mod_stratumtwo_exercise_round($record);
        
        for ($i = 0; $i < 5; ++$i) {
            $round = ($i % 2 == 0 ? $this->round1 : $round2);
            $cat = ($i % 2 == 0 ? $category : $category2);
            
            $round->createNewExercise((object) array(
                    'status' => mod_stratumtwo_learning_object::STATUS_READY,
                    'parentid' => null,
                    'ordernum' => $i + 1,
                    'remotekey' => "testexercise$i",
                    'name' => "Exercise $i",
                    'serviceurl' => 'localhost',
                    'maxsubmissions' => 10,
                    'pointstopass' => 5,
                    'maxpoints' => 10,
            ), $cat);
        }
        
        // test delete
        $category->delete();
        $this->assertEquals(1, $DB->count_records(mod_stratumtwo_category::TABLE, array('course' => $this->course->id)));
        $cats = mod_stratumtwo_category::getCategoriesInCourse($this->course->id);
        $this->assertEquals($category2->getId(), current($cats)->getId());
        $this->assertEquals(0, $DB->count_records(mod_stratumtwo_learning_object::TABLE, array('categoryid' => $category->getId())));
        // the other category should remain unchanged
        $this->assertEquals(2, $DB->count_records(mod_stratumtwo_learning_object::TABLE, array('categoryid' => $category2->getId())));
    }
}