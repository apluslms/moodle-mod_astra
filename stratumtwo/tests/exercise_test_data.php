<?php

trait exercise_test_data {
    
    protected $course;
    protected $round1;
    protected $round2;
    protected $category;
    protected $exercises;
    protected $submissions;
    protected $student;
    protected $student2;
    
    protected function add_test_data() {
        // create a course instance for testing
        $this->setAdminUser();
        $this->course = $this->getDataGenerator()->create_course();
    
        $this->student = $this->getDataGenerator()->create_user();
        $this->student2 = $this->getDataGenerator()->create_user();
        
        // create 2 exercise rounds
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_stratumtwo');
        $round_data = array(
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
        $record = $generator->create_instance($round_data); // stdClass record
        $this->round1 = new mod_stratumtwo_exercise_round($record);
        $round_data = array(
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
        $record = $generator->create_instance($round_data); // stdClass record
        $this->round2 = new mod_stratumtwo_exercise_round($record);
    
        // create category
        $this->category = mod_stratumtwo_category::createFromId(mod_stratumtwo_category::createNew((object) array(
                'course' => $this->course->id,
                'status' => mod_stratumtwo_category::STATUS_READY,
                'name' => 'Testing exercises',
                'pointstopass' => 0,
        )));
    
        // create exercises
        $this->exercises = array();
        $this->exercises[] = $this->add_exercise(array(), $this->round1, $this->category);
        $this->exercises[] = $this->add_exercise(array('parentid' => $this->exercises[0]->getId()), $this->round1, $this->category);
        $this->exercises[] = $this->add_exercise(array('parentid' => $this->exercises[0]->getId()), $this->round1, $this->category);
        $this->exercises[] = $this->add_exercise(array('parentid' => $this->exercises[2]->getId()), $this->round1, $this->category);
        $this->exercises[] = $this->add_exercise(array(), $this->round1, $this->category);
        $this->exercises[] = $this->add_exercise(array(), $this->round1, $this->category);
        
        // create submissions
        $this->submissions = array();
        $this->submissions[] = mod_stratumtwo_submission::createFromId(
                mod_stratumtwo_submission::createNewSubmission($this->exercises[0], $this->student->id));
        $this->submissions[] = mod_stratumtwo_submission::createFromId(
                mod_stratumtwo_submission::createNewSubmission($this->exercises[0], $this->student->id,
                        null, mod_stratumtwo_submission::STATUS_ERROR));
        $this->submissions[] = mod_stratumtwo_submission::createFromId(
                mod_stratumtwo_submission::createNewSubmission($this->exercises[0], $this->student2->id));
    }
    
    protected function add_exercise(array $data, mod_stratumtwo_exercise_round $round, mod_stratumtwo_category $category) {
        static $counter = 0;
        ++$counter;
        $defaults = array(
                'status' => mod_stratumtwo_learning_object::STATUS_READY,
                'parentid' => null,
                'ordernum' => $counter,
                'remotekey' => "testexercise$counter",
                'name' => "Exercise $counter",
                'serviceurl' => 'localhost',
                'maxsubmissions' => 3,
                'pointstopass' => 5,
                'maxpoints' => 10,
        );
        foreach ($defaults as $key => $val) {
            if (!isset($data[$key])) {
                $data[$key] = $val;
            }
        }
        return $round->createNewExercise((object) $data, $category);
    }
}