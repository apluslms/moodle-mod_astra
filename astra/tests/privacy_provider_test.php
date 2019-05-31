<?php

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\request\transform;

use mod_astra\privacy\provider;

/**
 * Unit tests for the privacy API.
 * @group mod_astra
 */
class mod_astra_privacy_provider_testcase extends \core_privacy\tests\provider_testcase {

    protected $course;
    protected $course2;
    protected $round1;
    protected $round2;
    protected $round3;
    protected $round4;
    protected $category;
    protected $category2;
    protected $exercises;
    protected $exercises2;
    protected $exercises3;
    protected $submissions;
    protected $student;
    protected $student2;
    protected $deadlinedevs;
    protected $sbmslimitdevs;

    protected function add_test_data() {
        // create a course instance for testing
        $this->setAdminUser();
        $this->course = $this->getDataGenerator()->create_course();
        $this->course2 = $this->getDataGenerator()->create_course();

        $this->student = $this->getDataGenerator()->create_user();
        $this->student2 = $this->getDataGenerator()->create_user();

        // create 2 exercise rounds
        $generator = $this->getDataGenerator()->get_plugin_generator(mod_astra_exercise_round::MODNAME);
        $round_data = array(
                'course' => $this->course->id,
                'name' => '1. Test round 1',
                'remotekey' => 'testround1',
                'openingtime' => time(),
                'closingtime' => time() + 3600 * 24 * 7,
                'ordernum' => 1,
                'status' => mod_astra_exercise_round::STATUS_READY,
                'pointstopass' => 0,
                'latesbmsallowed' => 1,
                'latesbmsdl' => time() + 3600 * 24 * 14,
                'latesbmspenalty' => 0.4,
        );
        $record = $generator->create_instance($round_data); // stdClass record
        $this->round1 = new mod_astra_exercise_round($record);
        $round_data = array(
                'course' => $this->course->id,
                'name' => '2. Test round 2',
                'remotekey' => 'testround2',
                'openingtime' => time(),
                'closingtime' => time() + 3600 * 24 * 7,
                'ordernum' => 2,
                'status' => mod_astra_exercise_round::STATUS_READY,
                'pointstopass' => 0,
                'latesbmsallowed' => 1,
                'latesbmsdl' => time() + 3600 * 24 * 14,
                'latesbmspenalty' => 0.4,
        );
        $record = $generator->create_instance($round_data); // stdClass record
        $this->round2 = new mod_astra_exercise_round($record);
        // Two rounds in another course.
        $round_data = array(
                'course' => $this->course2->id,
                'name' => '1. Round in another course',
                'remotekey' => 'testround1c2',
                'openingtime' => time(),
                'closingtime' => time() + 3600 * 24 * 7,
                'ordernum' => 1,
                'status' => mod_astra_exercise_round::STATUS_READY,
                'pointstopass' => 0,
                'latesbmsallowed' => 1,
                'latesbmsdl' => time() + 3600 * 24 * 14,
                'latesbmspenalty' => 0.4,
        );
        $record = $generator->create_instance($round_data); // stdClass record
        $this->round3 = new mod_astra_exercise_round($record);
        $round_data = array(
                'course' => $this->course2->id,
                'name' => '2. Unused round in another course',
                'remotekey' => 'testround2c2',
                'openingtime' => time(),
                'closingtime' => time() + 3600 * 24 * 7,
                'ordernum' => 2,
                'status' => mod_astra_exercise_round::STATUS_READY,
                'pointstopass' => 0,
                'latesbmsallowed' => 1,
                'latesbmsdl' => time() + 3600 * 24 * 14,
                'latesbmspenalty' => 0.6,
        );
        $record = $generator->create_instance($round_data); // stdClass record
        $this->round4 = new mod_astra_exercise_round($record);

        // create category
        $this->category = mod_astra_category::createFromId(mod_astra_category::createNew((object) array(
                'course' => $this->course->id,
                'status' => mod_astra_category::STATUS_READY,
                'name' => 'Testing exercises',
                'pointstopass' => 0,
        )));
        $this->category2 = mod_astra_category::createFromId(mod_astra_category::createNew((object) array(
                'course' => $this->course2->id,
                'status' => mod_astra_category::STATUS_READY,
                'name' => 'Another course category',
                'pointstopass' => 0,
        )));

        // create exercises
        $this->exercises = array();
        $this->exercises[] = $this->add_exercise(array(), $this->round1, $this->category);
        $this->exercises[] = $this->add_exercise(array('parentid' => $this->exercises[0]->getId()), $this->round1, $this->category);
        $this->exercises[] = $this->add_exercise(array('parentid' => $this->exercises[0]->getId()), $this->round1, $this->category);
        $this->exercises[] = $this->add_exercise(array('parentid' => $this->exercises[2]->getId()), $this->round1, $this->category);
        $this->exercises[] = $this->add_exercise(array(), $this->round1, $this->category);
        $this->exercises2[] = $this->add_exercise(array(), $this->round2, $this->category);
        $this->exercises3[] = $this->add_exercise(array(), $this->round3, $this->category2);

        // create submissions
        $this->submissions = array();
        $this->submissions[] = mod_astra_submission::createFromId(
                mod_astra_submission::createNewSubmission($this->exercises[0], $this->student->id,
                array('submitteddata' => 4)));
        $this->add_submitted_file($this->submissions[0], 'myfile.txt', 'filekey');
        $this->submissions[0]->grade(6, 10, 'here is feedback', array('graderdata' => 2));
        $this->submissions[] = mod_astra_submission::createFromId(
                mod_astra_submission::createNewSubmission($this->exercises[0], $this->student->id,
                        null, mod_astra_submission::STATUS_ERROR));
        $this->submissions[] = mod_astra_submission::createFromId(
                mod_astra_submission::createNewSubmission($this->exercises[1], $this->student->id));

        $this->submissions[] = mod_astra_submission::createFromId(
                mod_astra_submission::createNewSubmission($this->exercises[0], $this->student2->id));
        $this->add_submitted_file($this->submissions[3], 'myfile.txt', 'filekey');
        $this->submissions[] = mod_astra_submission::createFromId(
                mod_astra_submission::createNewSubmission($this->exercises[2], $this->student2->id));
        $this->submissions[] = mod_astra_submission::createFromId(
                mod_astra_submission::createNewSubmission($this->exercises3[0], $this->student2->id));
        $this->add_submitted_file($this->submissions[5], 'newfile.txt', 'newfilekey');

        // create deadline and submission limit extensions
        $this->deadlinedevs = array();
        $this->sbmslimitdevs = array();
        $this->deadlinedevs[] = mod_astra_deadline_deviation::createFromId(
            mod_astra_deadline_deviation::createNew($this->exercises2[0]->getId(), $this->student->id, 60, true));
        $this->sbmslimitdevs[] = mod_astra_submission_limit_deviation::createFromId(
            mod_astra_submission_limit_deviation::createNew($this->exercises2[0]->getId(), $this->student2->id, 5));
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

    protected function add_submitted_file($submission, $filename, $filekey) {
        $handle = tmpfile();
        fwrite($handle, "writing to tempfile");
        $path = stream_get_meta_data($handle)['uri'];
        $submission->addSubmittedFile($filename, $filekey, $path);
        fclose($handle);
    }

    public function test_get_metadata() {
        $collection = new \core_privacy\local\metadata\collection(mod_astra_exercise_round::MODNAME);
        $collection = provider::get_metadata($collection);
        $this->assertDebuggingNotCalled();
        $items = $collection->get_collection();
        $this->assertNotEmpty($items);
    }

    public function test_get_contexts_for_userid() {
        $this->resetAfterTest(true);
        $this->add_test_data();

        $contextlist = provider::get_contexts_for_userid($this->student->id);
        $contextids = $contextlist->get_contextids();
        $this->assertCount(2, $contextids);
        $expected = array(
            context_module::instance($this->round1->getCourseModule()->id)->id,
            context_module::instance($this->round2->getCourseModule()->id)->id,
        );
        sort($expected);
        sort($contextids);
        $this->assertEquals($expected, $contextids);

        // Repeat for the other user.
        $contextlist = provider::get_contexts_for_userid($this->student2->id);
        $contextids = $contextlist->get_contextids();
        $this->assertCount(3, $contextids);
        $expected = array(
            context_module::instance($this->round1->getCourseModule()->id)->id,
            context_module::instance($this->round2->getCourseModule()->id)->id,
            context_module::instance($this->round3->getCourseModule()->id)->id,
        );
        sort($expected);
        sort($contextids);
        $this->assertEquals($expected, $contextids);

        // Admin user who should have no data in Astra.
        // User id 2 should be the default admin user.
        $contextlist = provider::get_contexts_for_userid(2);
        $contextids = $contextlist->get_contextids();
        $this->assertCount(0, $contextids);
        $this->assertEmpty($contextids);
    }

    public function test_get_users_in_context() {
        $this->resetAfterTest(true);
        $this->add_test_data();

        // Round 1
        $context = context_module::instance($this->round1->getCourseModule()->id);
        $userlist = new \core_privacy\local\request\userlist($context, mod_astra_exercise_round::MODNAME);
        provider::get_users_in_context($userlist);
        $userids = $userlist->get_userids();
        $this->assertCount(2, $userids);
        $expected = array(
            $this->student->id,
            $this->student2->id,
        );
        sort($expected);
        sort($userids);
        $this->assertEquals($expected, $userids);

        // Round 2
        $context = context_module::instance($this->round2->getCourseModule()->id);
        $userlist = new \core_privacy\local\request\userlist($context, mod_astra_exercise_round::MODNAME);
        provider::get_users_in_context($userlist);
        $userids = $userlist->get_userids();
        $this->assertCount(2, $userids);
        $expected = array(
            $this->student->id,
            $this->student2->id,
        );
        sort($expected);
        sort($userids);
        $this->assertEquals($expected, $userids);

        // Round 3
        $context = context_module::instance($this->round3->getCourseModule()->id);
        $userlist = new \core_privacy\local\request\userlist($context, mod_astra_exercise_round::MODNAME);
        provider::get_users_in_context($userlist);
        $userids = $userlist->get_userids();
        $this->assertCount(1, $userids);
        $expected = array(
            $this->student2->id,
        );
        $this->assertEquals($expected, $userids);

        // Round 4
        $context = context_module::instance($this->round4->getCourseModule()->id);
        $userlist = new \core_privacy\local\request\userlist($context, mod_astra_exercise_round::MODNAME);
        provider::get_users_in_context($userlist);
        $userids = $userlist->get_userids();
        $this->assertCount(0, $userids);
        $this->assertEmpty($userids);
    }

    public function test_export_user_data_empty() {
        $this->resetAfterTest(true);
        $this->add_test_data();

        // The admin user with id 2 should have no data in Astra.
        $user = core_user::get_user(2);
        $contexts = array(
            context_module::instance($this->round1->getCourseModule()->id),
            context_module::instance($this->round2->getCourseModule()->id),
            context_module::instance($this->round3->getCourseModule()->id),
            context_module::instance($this->round4->getCourseModule()->id),
        );
        $contextids = array_map(function($ctx) {
            return $ctx->id;
        }, $contexts);
        $contextlist = new \core_privacy\local\request\approved_contextlist(
            $user, mod_astra_exercise_round::MODNAME, $contextids);
        provider::export_user_data($contextlist);
        foreach ($contexts as $ctx) {
            $this->assertFalse(
                \core_privacy\local\request\writer::with_context($ctx)
                    ->has_any_data_in_any_context());
        }

        // empty contextlist
        $user = $this->student;
        $contextids = array();
        $contextlist = new \core_privacy\local\request\approved_contextlist(
            $user, mod_astra_exercise_round::MODNAME, $contextids);
        provider::export_user_data($contextlist);
        foreach ($contexts as $ctx) {
            $this->assertFalse(
                \core_privacy\local\request\writer::with_context($ctx)
                    ->has_any_data_in_any_context());
        }
    }

    public function test_export_user_data() {
        $this->resetAfterTest(true);
        $this->add_test_data();

        $context = context_module::instance($this->round1->getCourseModule()->id);
        $this->export_context_data_for_user($this->student->id, $context, mod_astra_exercise_round::MODNAME);
        $writer = \core_privacy\local\request\writer::with_context($context);
        $subcontext = ['exerciseid_'. $this->exercises[0]->getId()];

        $submission = $writer->get_data(array_merge($subcontext,
            ['submissions', "sid_{$this->submissions[0]->getId()}"]));
        $this->assertTrue($writer->has_any_data_in_any_context());
        $this->check_exported_submission($submission, $this->submissions[0]);
        $files = $writer->get_files(array_merge($subcontext,
            ['submissions', "sid_{$this->submissions[0]->getId()}"]));
        $this->assertCount(1, $files);

        $submission = $writer->get_data(array_merge($subcontext,
            ['submissions', "sid_{$this->submissions[1]->getId()}"]));
        $this->check_exported_submission($submission, $this->submissions[1]);
        $files = $writer->get_files(array_merge($subcontext,
            ['submissions', "sid_{$this->submissions[1]->getId()}"]));
        $this->assertEmpty($files);

        // submission to another exercise
        $submission = $writer->get_data(['exerciseid_'. $this->exercises[1]->getId(),
            'submissions', "sid_{$this->submissions[2]->getId()}"]);
        $this->check_exported_submission($submission, $this->submissions[2]);
        $files = $writer->get_files(['exerciseid_'. $this->exercises[1]->getId(),
            'submissions', "sid_{$this->submissions[2]->getId()}"]);
        $this->assertEmpty($files);

        // another user's submission should not be included
        $submission = $writer->get_data(['exerciseid_'. $this->exercises[0]->getId(),
            'submissions', "sid_{$this->submissions[3]->getId()}"]);
        $this->assertEmpty($submission);

        $this->assertEmpty($writer->get_data(array_merge($subcontext, ['deadline_extensions'])));
        $this->assertEmpty($writer->get_data(array_merge($subcontext, ['submission_limit_extensions'])));
    }

    public function test_export_user_data2() {
        $this->resetAfterTest(true);
        $this->add_test_data();

        $context = context_module::instance($this->round2->getCourseModule()->id);
        $this->export_context_data_for_user($this->student2->id, $context, mod_astra_exercise_round::MODNAME);
        $writer = \core_privacy\local\request\writer::with_context($context);
        $this->assertTrue($writer->has_any_data_in_any_context());
        $subcontext = ['exerciseid_'. $this->exercises2[0]->getId()];

        // submission to a different context should not be included
        $this->assertEmpty($writer->get_data(array_merge($subcontext,
            ['submissions', "sid_{$this->submissions[3]->getId()}"])));
        $files = $writer->get_files(array_merge($subcontext,
            ['submissions', "sid_{$this->submissions[3]->getId()}"]));
        $this->assertEmpty($files);
        $this->assertEmpty($writer->get_data(['exerciseid_'. $this->exercises[0]->getId(),
            'submissions', "sid_{$this->submissions[3]->getId()}"]));
        $this->assertEmpty($writer->get_files(['exerciseid_'. $this->exercises[0]->getId(),
            'submissions', "sid_{$this->submissions[3]->getId()}"]));

        // this submission limit extension should be included in this context
        $sbmsextension = $writer->get_data(array_merge($subcontext,
            ['submission_limit_extensions', 'id_'. $this->sbmslimitdevs[0]->getId()]));
        $this->assertEquals('Yes', $sbmsextension->submitter_is_you);
        $this->assertObjectNothasAttribute('submitter', $sbmsextension);
        $this->assertEquals($this->sbmslimitdevs[0]->getRecord()->exerciseid, $sbmsextension->exerciseid);
        $this->assertEquals($this->sbmslimitdevs[0]->getExtraSubmissions(), $sbmsextension->extrasubmissions);
    }

    public function test_export_user_data2b() {
        $this->resetAfterTest(true);
        $this->add_test_data();

        $context = context_module::instance($this->round2->getCourseModule()->id);
        $this->export_context_data_for_user($this->student->id, $context, mod_astra_exercise_round::MODNAME);
        $writer = \core_privacy\local\request\writer::with_context($context);
        $this->assertTrue($writer->has_any_data_in_any_context());
        $subcontext = ['exerciseid_'. $this->exercises2[0]->getId()];

        // submission to a different context should not be included
        $this->assertEmpty($writer->get_data(array_merge($subcontext,
            ['submissions', "sid_{$this->submissions[0]->getId()}"])));
        $files = $writer->get_files(array_merge($subcontext,
            ['submissions', "sid_{$this->submissions[0]->getId()}"]));
        $this->assertEmpty($files);
        $this->assertEmpty($writer->get_data(['exerciseid_'. $this->exercises[0]->getId(),
            'submissions', "sid_{$this->submissions[0]->getId()}"]));
        $this->assertEmpty($writer->get_files(['exerciseid_'. $this->exercises[0]->getId(),
            'submissions', "sid_{$this->submissions[0]->getId()}"]));

        // this deadline extension should be included in this context
        $dlextension = $writer->get_data(array_merge($subcontext,
            ['deadline_extensions', 'id_'. $this->deadlinedevs[0]->getId()]));
        $this->assertEquals('Yes', $dlextension->submitter_is_you);
        $this->assertEquals('Yes', $dlextension->withoutlatepenalty);
        $this->assertObjectNothasAttribute('submitter', $dlextension);
        $this->assertEquals($this->deadlinedevs[0]->getRecord()->exerciseid, $dlextension->exerciseid);
        $this->assertEquals($this->deadlinedevs[0]->getExtraTime(), $dlextension->extraminutes);
    }

    public function test_export_user_data3() {
        $this->resetAfterTest(true);
        $this->add_test_data();

        $context = context_module::instance($this->round3->getCourseModule()->id);
        $this->export_context_data_for_user($this->student2->id, $context, mod_astra_exercise_round::MODNAME);
        $writer = \core_privacy\local\request\writer::with_context($context);
        $subcontext = ['exerciseid_'. $this->exercises3[0]->getId()];

        $submission = $writer->get_data(array_merge($subcontext,
            ['submissions', "sid_{$this->submissions[5]->getId()}"]));
        $this->assertTrue($writer->has_any_data_in_any_context());
        $this->check_exported_submission($submission, $this->submissions[5]);
        $files = $writer->get_files(array_merge($subcontext,
            ['submissions', "sid_{$this->submissions[5]->getId()}"]));
        $this->assertCount(1, $files);

        $this->assertEmpty($writer->get_data(array_merge($subcontext, ['deadline_extensions'])));
        $this->assertEmpty($writer->get_data(array_merge($subcontext, ['submission_limit_extensions'])));
        // extensions from a different context should not be included
        $this->assertEmpty($writer->get_data(['exerciseid_'. $this->exercises2[0]->getId(),
            'deadline_extensions', 'id_'. $this->deadlinedevs[0]->getId()]));
        $this->assertEmpty($writer->get_data(['exerciseid_'. $this->exercises2[0]->getId(),
            'submission_limit_extensions', 'id_'. $this->sbmslimitdevs[0]->getId()]));
    }

    public function test_export_user_data4() {
        $this->resetAfterTest(true);
        $this->add_test_data();

        $context = context_module::instance($this->round4->getCourseModule()->id);
        $this->export_context_data_for_user($this->student->id, $context, mod_astra_exercise_round::MODNAME);
        $writer = \core_privacy\local\request\writer::with_context($context);
        $this->assertFalse($writer->has_any_data_in_any_context());
    }

    protected function check_exported_submission(stdClass $exported, mod_astra_submission $sbms) {
        $sbmsrec = $sbms->getRecord();
        $this->assertEquals($sbms->getStatus(true, true), $exported->status);
        $this->assertEquals(transform::datetime($sbmsrec->submissiontime), $exported->submissiontime);
        $this->assertEquals($sbmsrec->exerciseid, $exported->exerciseid);
        $this->assertEquals(($sbmsrec->grader !== null ? transform::user($sbmsrec->grader) : null),
            $exported->grader);
        $this->assertEquals($sbmsrec->feedback, $exported->feedback);
        $this->assertEquals($sbmsrec->assistfeedback, $exported->assistfeedback);
        $this->assertEquals($sbmsrec->grade, $exported->grade);
        $this->assertEquals(transform::datetime($sbmsrec->gradingtime), $exported->gradingtime);
        $this->assertEquals($sbmsrec->latepenaltyapplied, $exported->latepenaltyapplied); // danger of float comparison
        $this->assertEquals($sbmsrec->servicepoints, $exported->servicepoints);
        $this->assertEquals($sbmsrec->servicemaxpoints, $exported->servicemaxpoints);
        $this->assertEquals($sbmsrec->submissiondata, $exported->submissiondata);
        $this->assertEquals($sbmsrec->gradingdata, $exported->gradingdata);

        $this->assertEquals('Yes', $exported->submitter_is_you);
        $this->assertObjectNotHasAttribute('hash', $exported);
        $this->assertObjectNothasAttribute('submitter', $exported);
        $this->assertObjectNotHasAttribute('contextid', $exported);
    }

    public function test_delete_data_for_all_users_in_context() {
        global $DB;
        $this->resetAfterTest(true);
        $this->add_test_data();

        $context = context_module::instance($this->round1->getCourseModule()->id);
        provider::delete_data_for_all_users_in_context($context);
        // all user data in the round1 should be deleted now
        $fs = get_file_storage();
        $exercises = $this->exercises; // the exercises of round1
        foreach ($exercises as $ex) {
            $submissions = $DB->get_records(mod_astra_submission::TABLE, array(
                'exerciseid' => $ex->getId(),
            ));
            $this->assertEmpty($submissions);
            // submitted files
            $files = $fs->get_area_files($context->id,
                mod_astra_exercise_round::MODNAME,
                mod_astra_submission::SUBMITTED_FILES_FILEAREA);
            $this->assertEmpty($files);

            // deadline extensions
            $dldevs = $DB->get_records(mod_astra_deadline_deviation::TABLE, array(
                'exerciseid' => $ex->getId(),
            ));
            $this->assertEmpty($dldevs);

            // submission limit extensions
            $sbmsdevs = $DB->get_records(mod_astra_submission_limit_deviation::TABLE, array(
                'exerciseid' => $ex->getId(),
            ));
            $this->assertEmpty($sbmsdevs);
        }

        // Check that other rounds were not affected by the delete operation.
        $round3submissionsrs = $this->exercises3[0]->getSubmissionsForStudent($this->student2->id, false);
        $round3submissions = array();
        foreach ($round3submissionsrs as $rec) {
            $round3submissions[] = new mod_astra_submission($rec);
        }
        $round3submissionsrs->close();
        $this->assertCount(1, $round3submissions);
        $this->assertEquals($this->submissions[5]->getId(), $round3submissions[0]->getId());
        $this->assertCount(1, $round3submissions[0]->getSubmittedFiles());

        $this->assertEquals(1, $DB->count_records(mod_astra_deadline_deviation::TABLE));
        $this->assertEquals(1, $DB->count_records(mod_astra_submission_limit_deviation::TABLE));
    }

    public function test_delete_data_for_user() {
        global $DB;
        $this->resetAfterTest(true);
        $this->add_test_data();

        $contexts = array(
            context_module::instance($this->round1->getCourseModule()->id),
            context_module::instance($this->round2->getCourseModule()->id),
        );
        $contextids = array_map(function($ctx) {
            return $ctx->id;
        }, $contexts);
        $contextlist = new \core_privacy\local\request\approved_contextlist(
            $this->student, mod_astra_exercise_round::MODNAME, $contextids);
        $deletedsubmissionids = array(
            $this->submissions[0]->getId(),
            $this->submissions[1]->getId(),
            $this->submissions[2]->getId(),
        );
        provider::delete_data_for_user($contextlist);

        $exercises = array_merge($this->exercises, $this->exercises2);
        foreach ($exercises as $ex) {
            $this->assertEquals(0, $DB->count_records(mod_astra_submission::TABLE, array(
                'exerciseid' => $ex->getId(),
                'submitter' => $this->student->id,
            )));

            // deadline extensions
            $dldevs = $DB->get_records(mod_astra_deadline_deviation::TABLE, array(
                'exerciseid' => $ex->getId(),
                'submitter' => $this->student->id,
            ));
            $this->assertEmpty($dldevs);

            // submission limit extensions
            $sbmsdevs = $DB->get_records(mod_astra_submission_limit_deviation::TABLE, array(
                'exerciseid' => $ex->getId(),
                'submitter' => $this->student->id,
            ));
            $this->assertEmpty($sbmsdevs);
        }
        // submitted files
        $fs = get_file_storage();
        $contextround1 = context_module::instance($this->round1->getCourseModule()->id);
        foreach ($deletedsubmissionids as $sid) {
            $files = $fs->get_area_files($contextround1->id,
                mod_astra_exercise_round::MODNAME,
                mod_astra_submission::SUBMITTED_FILES_FILEAREA,
                $sid);
            $this->assertEmpty($files);
        }

        // Test that other contexts or other users were not affected.
        $this->assertEquals(1, $DB->count_records(mod_astra_submission_limit_deviation::TABLE));
        $this->assertEquals(1, $DB->count_records(mod_astra_submission::TABLE, array(
            'exerciseid' => $this->exercises[0]->getId(),
            'submitter' => $this->student2->id,
        )));
        $this->assertEquals(1, $DB->count_records(mod_astra_submission::TABLE, array(
            'exerciseid' => $this->exercises[2]->getId(),
            'submitter' => $this->student2->id,
        )));
        $this->assertEquals(1, $DB->count_records(mod_astra_submission::TABLE, array(
            'exerciseid' => $this->exercises3[0]->getId(),
            'submitter' => $this->student2->id,
        )));
        $files = $fs->get_area_files($contextround1->id,
            mod_astra_exercise_round::MODNAME,
            mod_astra_submission::SUBMITTED_FILES_FILEAREA,
            $this->submissions[3]->getId(),
            'itemid, filepath, filename',
            false);
        $this->assertCount(1, $files);
        $files = $fs->get_area_files($contextround1->id,
            mod_astra_exercise_round::MODNAME,
            mod_astra_submission::SUBMITTED_FILES_FILEAREA,
            $this->submissions[4]->getId(),
            'itemid, filepath, filename',
            false);
        $this->assertEmpty($files);
        $contextround3 = context_module::instance($this->round3->getCourseModule()->id);
        $files = $fs->get_area_files($contextround3->id,
            mod_astra_exercise_round::MODNAME,
            mod_astra_submission::SUBMITTED_FILES_FILEAREA,
            $this->submissions[5]->getId(),
            'itemid, filepath, filename',
            false);
        $this->assertCount(1, $files);
    }

    public function test_delete_data_for_user2() {
        global $DB;
        $this->resetAfterTest(true);
        $this->add_test_data();

        // delete something for student2 so that the user has something left in another context
        $contexts = array(
            context_module::instance($this->round1->getCourseModule()->id),
            context_module::instance($this->round2->getCourseModule()->id),
        );
        $contextids = array_map(function($ctx) {
            return $ctx->id;
        }, $contexts);
        $contextlist = new \core_privacy\local\request\approved_contextlist(
            $this->student2, mod_astra_exercise_round::MODNAME, $contextids);
        $deletedsubmissionids = array(
            $this->submissions[3]->getId(),
            $this->submissions[4]->getId(),
        );
        provider::delete_data_for_user($contextlist);

        $exercises = array_merge($this->exercises, $this->exercises2);
        foreach ($exercises as $ex) {
            $this->assertEquals(0, $DB->count_records(mod_astra_submission::TABLE, array(
                'exerciseid' => $ex->getId(),
                'submitter' => $this->student2->id,
            )));

            // deadline extensions
            $dldevs = $DB->get_records(mod_astra_deadline_deviation::TABLE, array(
                'exerciseid' => $ex->getId(),
                'submitter' => $this->student2->id,
            ));
            $this->assertEmpty($dldevs);

            // submission limit extensions
            $sbmsdevs = $DB->get_records(mod_astra_submission_limit_deviation::TABLE, array(
                'exerciseid' => $ex->getId(),
                'submitter' => $this->student->id,
            ));
            $this->assertEmpty($sbmsdevs);
        }
        // submitted files
        $fs = get_file_storage();
        $contextround1 = context_module::instance($this->round1->getCourseModule()->id);
        foreach ($deletedsubmissionids as $sid) {
            $files = $fs->get_area_files($contextround1->id,
                mod_astra_exercise_round::MODNAME,
                mod_astra_submission::SUBMITTED_FILES_FILEAREA,
                $sid);
            $this->assertEmpty($files);
        }

        // Test that other contexts or other users were not affected.
        $this->assertEquals(1, $DB->count_records(mod_astra_deadline_deviation::TABLE));
        $this->assertEquals(2, $DB->count_records(mod_astra_submission::TABLE, array(
            'exerciseid' => $this->exercises[0]->getId(),
            'submitter' => $this->student->id,
        )));
        $this->assertEquals(1, $DB->count_records(mod_astra_submission::TABLE, array(
            'exerciseid' => $this->exercises[1]->getId(),
            'submitter' => $this->student->id,
        )));
        $this->assertEquals(0, $DB->count_records(mod_astra_submission::TABLE, array(
            'exerciseid' => $this->exercises[2]->getId(),
            'submitter' => $this->student->id,
        )));
        $files = $fs->get_area_files($contextround1->id,
            mod_astra_exercise_round::MODNAME,
            mod_astra_submission::SUBMITTED_FILES_FILEAREA,
            $this->submissions[0]->getId(),
            'itemid, filepath, filename',
            false);
        $this->assertCount(1, $files);
        $files = $fs->get_area_files($contextround1->id,
            mod_astra_exercise_round::MODNAME,
            mod_astra_submission::SUBMITTED_FILES_FILEAREA,
            $this->submissions[1]->getId(),
            'itemid, filepath, filename',
            false);
        $this->assertEmpty($files);
        $files = $fs->get_area_files($contextround1->id,
            mod_astra_exercise_round::MODNAME,
            mod_astra_submission::SUBMITTED_FILES_FILEAREA,
            $this->submissions[2]->getId(),
            'itemid, filepath, filename',
            false);
        $this->assertEmpty($files);
    }

    public function test_delete_data_for_users() {
        global $DB;
        $this->resetAfterTest(true);
        $this->add_test_data();

        $context = context_module::instance($this->round1->getCourseModule()->id);
        $userids = [$this->student->id, $this->student2->id];
        $userlist = new \core_privacy\local\request\approved_userlist(
            $context, mod_astra_exercise_round::MODNAME, $userids);
        // Delete round1 data for both users.
        provider::delete_data_for_users($userlist);

        $fs = get_file_storage();
        $exercises = $this->exercises; // the exercises of round1
        foreach ($exercises as $ex) {
            $submissions = $DB->get_records(mod_astra_submission::TABLE, array(
                'exerciseid' => $ex->getId(),
            ));
            $this->assertEmpty($submissions);

            // deadline extensions
            $dldevs = $DB->get_records(mod_astra_deadline_deviation::TABLE, array(
                'exerciseid' => $ex->getId(),
            ));
            $this->assertEmpty($dldevs);

            // submission limit extensions
            $sbmsdevs = $DB->get_records(mod_astra_submission_limit_deviation::TABLE, array(
                'exerciseid' => $ex->getId(),
            ));
            $this->assertEmpty($sbmsdevs);
        }
        // submitted files
        $files = $fs->get_area_files($context->id,
            mod_astra_exercise_round::MODNAME,
            mod_astra_submission::SUBMITTED_FILES_FILEAREA);
        $this->assertEmpty($files);

        // Check that other rounds were not affected by the delete operation.
        $round3submissionsrs = $this->exercises3[0]->getSubmissionsForStudent($this->student2->id, false);
        $round3submissions = array();
        foreach ($round3submissionsrs as $rec) {
            $round3submissions[] = new mod_astra_submission($rec);
        }
        $round3submissionsrs->close();
        $this->assertCount(1, $round3submissions);
        $this->assertEquals($this->submissions[5]->getId(), $round3submissions[0]->getId());
        $this->assertCount(1, $round3submissions[0]->getSubmittedFiles());

        $this->assertEquals(1, $DB->count_records(mod_astra_deadline_deviation::TABLE));
        $this->assertEquals(1, $DB->count_records(mod_astra_submission_limit_deviation::TABLE));
    }

    public function test_delete_data_for_users_one_user() {
        global $DB;
        $this->resetAfterTest(true);
        $this->add_test_data();

        // Delete round1 data for only one user.
        $deletedsubmissionids = array(
            $this->submissions[0]->getId(),
            $this->submissions[1]->getId(),
            $this->submissions[2]->getId(),
        );
        $context = context_module::instance($this->round1->getCourseModule()->id);
        $userids = [$this->student->id];
        $userlist = new \core_privacy\local\request\approved_userlist(
            $context, mod_astra_exercise_round::MODNAME, $userids);
        provider::delete_data_for_users($userlist);

        $exercises = $this->exercises;
        foreach ($exercises as $ex) {
            $this->assertEquals(0, $DB->count_records(mod_astra_submission::TABLE, array(
                'exerciseid' => $ex->getId(),
                'submitter' => $this->student->id,
            )));

            // deadline extensions
            $dldevs = $DB->get_records(mod_astra_deadline_deviation::TABLE, array(
                'exerciseid' => $ex->getId(),
                'submitter' => $this->student->id,
            ));
            $this->assertEmpty($dldevs);

            // submission limit extensions
            $sbmsdevs = $DB->get_records(mod_astra_submission_limit_deviation::TABLE, array(
                'exerciseid' => $ex->getId(),
                'submitter' => $this->student->id,
            ));
            $this->assertEmpty($sbmsdevs);
        }
        // submitted files
        $fs = get_file_storage();
        foreach ($deletedsubmissionids as $sid) {
            $files = $fs->get_area_files($context->id,
                mod_astra_exercise_round::MODNAME,
                mod_astra_submission::SUBMITTED_FILES_FILEAREA,
                $sid);
            $this->assertEmpty($files);
        }

        // Test that other contexts or other users were not affected.
        $this->assertEquals(1, $DB->count_records(mod_astra_deadline_deviation::TABLE));
        $this->assertEquals(1, $DB->count_records(mod_astra_submission_limit_deviation::TABLE));
        $this->assertEquals(1, $DB->count_records(mod_astra_submission::TABLE, array(
            'exerciseid' => $this->exercises[0]->getId(),
            'submitter' => $this->student2->id,
        )));
        $this->assertEquals(1, $DB->count_records(mod_astra_submission::TABLE, array(
            'exerciseid' => $this->exercises[2]->getId(),
            'submitter' => $this->student2->id,
        )));
        $this->assertEquals(1, $DB->count_records(mod_astra_submission::TABLE, array(
            'exerciseid' => $this->exercises3[0]->getId(),
            'submitter' => $this->student2->id,
        )));
        $files = $fs->get_area_files($context->id,
            mod_astra_exercise_round::MODNAME,
            mod_astra_submission::SUBMITTED_FILES_FILEAREA,
            $this->submissions[3]->getId(),
            'itemid, filepath, filename',
            false);
        $this->assertCount(1, $files);
        $files = $fs->get_area_files($context->id,
            mod_astra_exercise_round::MODNAME,
            mod_astra_submission::SUBMITTED_FILES_FILEAREA,
            $this->submissions[4]->getId(),
            'itemid, filepath, filename',
            false);
        $this->assertEmpty($files);
        $contextround3 = context_module::instance($this->round3->getCourseModule()->id);
        $files = $fs->get_area_files($contextround3->id,
            mod_astra_exercise_round::MODNAME,
            mod_astra_submission::SUBMITTED_FILES_FILEAREA,
            $this->submissions[5]->getId(),
            'itemid, filepath, filename',
            false);
        $this->assertCount(1, $files);
        $file = reset($files);
        $this->assertEquals('writing to tempfile', $file->get_content());
        $this->assertEquals('newfile.txt', $file->get_filename());
    }
}
