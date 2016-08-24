<?php
namespace mod_astra\output;

defined('MOODLE_INTERNAL') || die;

class all_submissions_page implements \renderable, \templatable {
    
    protected $exercise;
    
    public function __construct(\mod_astra_exercise $ex) {
        $this->exercise = $ex;
    }
    
    public function export_for_template(\renderer_base $output) {
        $ctx = new \stdClass();
        
        $sbmsList = array();
        $submissions = $this->exercise->getAllSubmissions();
        foreach ($submissions as $sbmsRecord) {
            $sbms = new \mod_astra_submission($sbmsRecord);
            $sbmsList[] = $sbms->getTemplateContext(false, false);
        }
        $submissions->close();
        
        $ctx->submissions = $sbmsList;
        $ctx->count = \count($sbmsList);
        $ctx->toDateStr = new \mod_astra\output\date_to_string();
        
        return $ctx;
    }
}