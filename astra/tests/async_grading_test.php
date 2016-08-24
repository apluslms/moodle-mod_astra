<?php

require_once(dirname(__FILE__) .'/exercise_test_data.php');
require_once(dirname(dirname(__FILE__)) .'/async/async_lib.php');

/**
 * Unit tests for async grading.
 * @group mod_stratumtwo
 */
class mod_stratumtwo_async_grading_testcase extends advanced_testcase {
    
    use exercise_test_data;
    
    public function setUp() {
        $this->add_test_data();
    }
    
    public function test_async_submission_handler_grade() {
        $this->resetAfterTest(true);
        
        $this->assertEmpty($this->submissions[0]->getFeedback());
        $this->assertEquals(0, $this->submissions[0]->getGrade());
        
        $grading_post_data = array(
                'points' => 10,
                'max_points' => 10,
                'feedback' => 'New test feedback',
        );
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $json = stratumtwo_async_submission_handler($this->exercises[0], $this->student,
                $grading_post_data, $this->submissions[0]);
        
        $this->assertTrue($json['success']);
        $this->assertEmpty($json['errors']);
        
        $sbms = mod_stratumtwo_submission::createFromId($this->submissions[0]->getId());
        $this->assertEquals(mod_stratumtwo_submission::STATUS_READY, $sbms->getStatus());
        $this->assertEquals($grading_post_data['max_points'], $sbms->getGrade());
        $this->assertEquals($grading_post_data['feedback'], $sbms->getFeedback());
    }
    
    public function test_async_submission_handler_create_new() {
        $this->resetAfterTest(true);
        
        $grading_post_data = array(
                'points' => 8,
                'max_points' => 10,
                'feedback' => 'New test feedback 2',
        );
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $json = stratumtwo_async_submission_handler($this->exercises[1], $this->student,
                $grading_post_data);
        
        $this->assertTrue($json['success']);
        $this->assertEmpty($json['errors']);
        
        $toSbms = function($record) {
            return new mod_stratumtwo_submission($record);
        };
        
        $submissions = $this->exercises[1]->getSubmissionsForStudent($this->student->id);
        $submissions_array = array_map($toSbms, iterator_to_array($submissions, false));
        $submissions->close();
        
        $this->assertEquals(1, count($submissions_array));
        $sbms = $submissions_array[0];
        $this->assertEquals(mod_stratumtwo_submission::STATUS_READY, $sbms->getStatus());
        $this->assertEquals($grading_post_data['points'], $sbms->getGrade());
        $this->assertEquals($grading_post_data['feedback'], $sbms->getFeedback());
    }
    
    public function test_get_async_submission_info() {
        $this->resetAfterTest(true);
        
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $json = stratumtwo_async_submission_handler($this->exercises[0], $this->student, null);
        
        $this->assertEquals(10, $json['max_points']);
        $this->assertEquals(3, $json['max_submissions']);
        $this->assertEquals(2, $json['current_submissions']);
        $this->assertEquals(0, $json['current_points']);
        $this->assertTrue($json['is_open']);
    }
}