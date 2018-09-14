<?php

namespace mod_astra\output;

defined('MOODLE_INTERNAL') || die();

/**
 * Page for deleting one object (learning object/exercise round/category).
 */
class delete_page implements \renderable, \templatable {
    
    protected $courseid;
    protected $objectType;
    protected $message;
    protected $actionUrl;
    protected $affectedLearningObjects;
    
    public function __construct($courseid, $objectType, $message, $actionUrl,
            array $affectedLearningObjects = null) {
        $this->courseid = $courseid;
        $this->objectType = $objectType;
        $this->message = $message;
        $this->actionUrl = $actionUrl;
        $this->affectedLearningObjects = $affectedLearningObjects;
    }
    
    public function export_for_template(\renderer_base $output) {
        $ctx = new \stdClass();
        $ctx->objecttype = $this->objectType;
        $ctx->cancelurl = \mod_astra\urls\urls::editCourse($this->courseid);
        $ctx->message = $this->message;
        $ctx->actionurl = $this->actionUrl;
        $lobjectsCtx = array();
        if (isset($this->affectedLearningObjects)) {
            foreach ($this->affectedLearningObjects as $lobject) {
                if ($lobject->isSubmittable()) {
                    $lobjectsCtx[] = $lobject->getExerciseTemplateContext(null, true, false, false);
                } else {
                    $lobjectsCtx[] = $lobject->getTemplateContext(false, false);
                }
            }
        }
        $ctx->lobjects = $lobjectsCtx;
        return $ctx;
    }
}