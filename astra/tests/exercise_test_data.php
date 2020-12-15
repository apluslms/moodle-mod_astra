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
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_astra');
        $round_data = array(
                'course' => $this->course->id,
                'name' => '1. Test round 1',
                'remotekey' => 'testround1',
                'openingtime' => $this->timenow,
                'closingtime' => $this->timenow + 3600 * 24 * 7,
                'ordernum' => 1,
                'status' => mod_astra_exercise_round::STATUS_READY,
                'pointstopass' => 0,
                'latesbmsallowed' => 1,
                'latesbmsdl' => $this->timenow + 3600 * 24 * 14,
                'latesbmspenalty' => 0.4,
        );
        $record = $generator->create_instance($round_data); // stdClass record
        $this->round1 = new mod_astra_exercise_round($record);
        $round_data = array(
                'course' => $this->course->id,
                'name' => '2. Test round 2',
                'remotekey' => 'testround2',
                'openingtime' => $this->timenow,
                'closingtime' => $this->timenow + 3600 * 24 * 7,
                'ordernum' => 2,
                'status' => mod_astra_exercise_round::STATUS_READY,
                'pointstopass' => 0,
                'latesbmsallowed' => 1,
                'latesbmsdl' => $this->timenow + 3600 * 24 * 14,
                'latesbmspenalty' => 0.4,
        );
        $record = $generator->create_instance($round_data); // stdClass record
        $this->round2 = new mod_astra_exercise_round($record);

        // create category
        $this->category = mod_astra_category::createFromId(mod_astra_category::createNew((object) array(
                'course' => $this->course->id,
                'status' => mod_astra_category::STATUS_READY,
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
        $this->submissions[] = mod_astra_submission::createFromId(
                mod_astra_submission::createNewSubmission($this->exercises[0], $this->student->id));
        $this->submissions[] = mod_astra_submission::createFromId(
                mod_astra_submission::createNewSubmission($this->exercises[0], $this->student->id,
                        null, mod_astra_submission::STATUS_ERROR));
        $this->submissions[] = mod_astra_submission::createFromId(
                mod_astra_submission::createNewSubmission($this->exercises[0], $this->student2->id));
    }

    protected function add_exercise(array $data, mod_astra_exercise_round $round, mod_astra_category $category) {
        static $counter = 0;
        ++$counter;
        $defaults = array(
                'status' => mod_astra_learning_object::STATUS_READY,
                'parentid' => null,
                'ordernum' => $counter,
                'remotekey' => "testexercise$counter",
                'name' => "Exercise $counter",
                'serviceurl' => 'http://localhost',
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

    protected function add_submission($exerciseorid, $submitterorid, array $data=null) {
        global $DB;

        static $counter = 0;
        ++$counter;

        if (!is_int($exerciseorid) && !is_numeric($exerciseorid)) {
            $exerciseorid = $exerciseorid->getId();
        }
        $exerciseorid = (int) $exerciseorid;
        if (!is_int($submitterorid) && !is_numeric($submitterorid)) {
            $submitterorid = $submitterorid->id;
        }
        if ($data === null) {
            $data = array();
        }

        $defaults = array(
            'status' => mod_astra_submission::STATUS_READY,
            'submissiontime' => $this->timenow + $counter,
            'hash' => mod_astra_submission::getRandomString(),
            'exerciseid' => $exerciseorid,
            'submitter' => $submitterorid,
            'feedback' => 'test feedback',
            'grade' => 0,
            'gradingtime' => $this->timenow + $counter + 1,
            'servicepoints' => 0,
            'servicemaxpoints' => 10,
        );
        foreach ($defaults as $key => $val) {
            if (!isset($data[$key])) {
                $data[$key] = $val;
            }
        }
        $id = $DB->insert_record(mod_astra_submission::TABLE, (object) $data);
        return mod_astra_submission::createFromId($id);
    }
}
