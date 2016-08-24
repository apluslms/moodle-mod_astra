<?php
namespace mod_astra\output;

defined('MOODLE_INTERNAL') || die;

class inspect_page implements \renderable, \templatable {
    
    protected $submission;
    
    public function __construct(\mod_astra_submission $sbms) {
        $this->submission = $sbms;
    }
    
    public function export_for_template(\renderer_base $output) {
        $ctx = $this->submission->getTemplateContext(true, true);
        $ctx->status = $ctx->state;
        
        $ctx->exercise = $this->submission->getExercise()->getExerciseTemplateContext(null, false, false);
        
        $context = \context_module::instance($this->submission->getExercise()->getExerciseRound()->getCourseModule()->id);
        $ctx->manual_grading_url = \mod_astra\urls\urls::assessSubmissionManually($this->submission);
        $ctx->resubmit_grading_url = \mod_astra\urls\urls::resubmitToService($this->submission);
        $ctx->allow_manual_grading = (\has_capability('mod/astra:grademanually', $context) &&
                $this->submission->getExercise()->isAssistantGradingAllowed()) ||
                \has_capability('mod/astra:addinstance', $context);
        
        $ctx->toDateStr = new \mod_astra\output\date_to_string();
        $ctx->fileSizeFormatter = new \mod_astra\output\file_size_formatter();
        
        return $ctx;
    }
}