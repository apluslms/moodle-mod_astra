<?php

namespace mod_stratumtwo\output;

defined('MOODLE_INTERNAL') || die();

/**
 * Page for deleting one object (learning object/exercise round/category).
 */
class delete_page implements \renderable, \templatable {
    
    protected $courseid;
    protected $objectType;
    protected $message;
    protected $actionUrl;
    
    public function __construct($courseid, $objectType, $message, $actionUrl) {
        $this->courseid = $courseid;
        $this->objectType = $objectType;
        $this->message = $message;
        $this->actionUrl = $actionUrl;
    }
    
    public function export_for_template(\renderer_base $output) {
        $ctx = new \stdClass();
        $ctx->objecttype = $this->objectType;
        $ctx->cancelurl = \mod_stratumtwo\urls\urls::editCourse($this->courseid);
        $ctx->message = $this->message;
        $ctx->actionurl = $this->actionUrl;
        return $ctx;
    }
}