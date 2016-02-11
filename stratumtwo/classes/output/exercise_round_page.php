<?php
namespace mod_stratumtwo\output;

defined('MOODLE_INTERNAL') || die;

use renderable;
use renderer_base;
use templatable;
use stdClass;

class exercise_round_page implements \renderable, \templatable {
    
    protected $exround;
    
    public function __construct(\mod_stratumtwo_exercise_round $exround) {
        $this->exround = $exround;
    }
    
    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        $data = new stdClass();
        //$data->sometext = $this->sometext;
        return $data;
        // It should return an stdClass with properties that are only made of simple types:
        // int, string, bool, float, stdClass or arrays of these types
    }
}