<?php

namespace mod_stratumtwo\output;

defined('MOODLE_INTERNAL') || die();

use \mod_stratumtwo\urls\urls;

class export_index_page implements \renderable, \templatable {
    
    protected $courseid; // Moodle course ID
    
    public function __construct($courseid) {
        $this->courseid = $courseid;
    }
    
    public function export_for_template(\renderer_base $output) {
        $data = new \stdClass();
        
        $data->export_results_url = urls::exportResults($this->courseid);
        $data->export_submitted_files_url = urls::exportSubmittedFiles($this->courseid);
        $data->export_submitted_forms_url = urls::exportSubmittedFormInput($this->courseid);
        
        return $data;
    }
}