<?php
namespace mod_astra\output;

defined('MOODLE_INTERNAL') || die;

use mod_astra\output\inspect_page;

class assess_page implements \renderable, \templatable {
    
    protected $submission;
    protected $form;
    
    public function __construct(\mod_astra_submission $sbms, $form) {
        $this->submission = $sbms;
        $this->form = $form; // HTML string
    }
    
    public function export_for_template(\renderer_base $output) {
        $ctx = $this->submission->getTemplateContext(true, true, true);
        $ctx->state = $ctx->state;
        unset($ctx->status);
        // Unset the status variable in the top-level context so that
        // it can not affect the points badge in the submission list.

        $ctx->exercise = $this->submission->getExercise()->getExerciseTemplateContext(
                $this->submission->getSubmitter(), false, false);
        $ctx->submissions = $this->submission->getExercise()->getSubmissionsTemplateContext(
                $this->submission->getSubmitter()->id, $this->submission);
        $ctx->submission_count = $this->submission->getExercise()->getSubmissionCountForStudent(
                $this->submission->getSubmitter()->id);

        $context = \context_module::instance($this->submission->getExercise()->getExerciseRound()->getCourseModule()->id);
        $ctx->can_add_deviations = has_capability('mod/astra:addinstance', $context);
        $ctx->add_extra_submissions_url = \mod_astra\urls\urls::upsertSubmissionLimitDeviation(
                $this->submission->getExercise(), $this->submission->getSubmitter()->id,
                $this->submission);

        $ctx->toDateStr = new \mod_astra\output\date_to_string();
        $ctx->fileSizeFormatter = new \mod_astra\output\file_size_formatter();
        
        $ctx->form = $this->form;

        $ctx->deviations = inspect_page::make_deviations_template_context($this->submission);

        return $ctx;
    }
}