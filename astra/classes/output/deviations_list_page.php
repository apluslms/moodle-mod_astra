<?php

namespace mod_astra\output;

defined('MOODLE_INTERNAL') || die();

class deviations_list_page implements \renderable, \templatable {
    
    protected $courseid;
    
    public function __construct($courseid) {
        $this->courseid = $courseid;
    }
    
    public function export_for_template(\renderer_base $output) {
        $ctx = new \stdClass();
        $ctx->add_new_dl_deviation_url = \mod_astra\urls\urls::addDeadlineDeviation($this->courseid);
        $ctx->add_new_sbms_limit_url = \mod_astra\urls\urls::addSubmissionLimitDeviation($this->courseid);
        
        $dl_objects = array();
        $dl_deviation_records = \mod_astra_deadline_deviation::getDeviationsInCourse($this->courseid);
        foreach ($dl_deviation_records as $dl_record) {
            $dl_dev = new \mod_astra_deadline_deviation($dl_record);
            $dl_objects[] = $dl_dev->getTemplateContext();
        }
        $dl_deviation_records->close();
        $ctx->dl_deviations = $dl_objects;
        
        $submit_limit_objects = array();
        $sbms_deviation_records = \mod_astra_submission_limit_deviation::getDeviationsInCourse($this->courseid);
        foreach ($sbms_deviation_records as $submit_limit_record) {
            $sbms_dev = new \mod_astra_submission_limit_deviation($submit_limit_record);
            $submit_limit_objects[] = $sbms_dev->getTemplateContext();
        }
        $sbms_deviation_records->close();
        $ctx->sbms_limit_deviations = $submit_limit_objects;
        
        return $ctx;
    }
}