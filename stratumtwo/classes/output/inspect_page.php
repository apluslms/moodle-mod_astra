<?php
namespace mod_stratumtwo\output;

defined('MOODLE_INTERNAL') || die;

class inspect_page implements \renderable, \templatable {
    
    protected $submission;
    
    public function __construct(\mod_stratumtwo_submission $sbms) {
        $this->submission = $sbms;
    }
    
    public function export_for_template(\renderer_base $output) {
        $ctx = $this->submission->getTemplateContext(true, true);
        $ctx->status = $ctx->state;
        
        $ctx->exercise = $this->submission->getExercise()->getTemplateContext(null, false, false);
        
        $context = \context_module::instance($this->submission->getExercise()->getExerciseRound()->getCourseModule()->id);
        $ctx->manual_grading_url = \mod_stratumtwo\urls\urls::assessSubmissionManually($this->submission);
        $ctx->resubmit_grading_url = \mod_stratumtwo\urls\urls::resubmitToService($this->submission);
        $ctx->allow_manual_grading = \has_capability('mod/stratumtwo:grademanually', $context);
        
        $ctx->toDateStr = new \mod_stratumtwo\output\date_to_string();
        $ctx->fileSizeFormatter = new \mod_stratumtwo\output\file_size_formatter();
        
        return $ctx;
    }
}