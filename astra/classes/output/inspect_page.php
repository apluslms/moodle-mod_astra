<?php
namespace mod_astra\output;

defined('MOODLE_INTERNAL') || die;

use stdClass;
use mod_astra_exercise_round;
use mod_astra_submission;
use mod_astra_submission_limit_deviation;
use mod_astra_deadline_deviation;
use mod_astra\output\date_to_string;
use mod_astra\output\file_size_formatter;

class inspect_page implements \renderable, \templatable {
    
    protected $submission;
    
    public function __construct(mod_astra_submission $sbms) {
        $this->submission = $sbms;
    }
    
    public function export_for_template(\renderer_base $output) {
        $ctx = $this->submission->getTemplateContext(true, true, true);
        $ctx->state = $ctx->state;

        $ctx->exercise = $this->submission->getExercise()->getExerciseTemplateContext(
                $this->submission->getSubmitter(), false, false);
        $ctx->submissions = $this->submission->getExercise()->getSubmissionsTemplateContext(
                $this->submission->getSubmitter()->id, $this->submission);
        $ctx->submission_count = $this->submission->getExercise()->getSubmissionCountForStudent(
                $this->submission->getSubmitter()->id);

        $context = \context_module::instance($this->submission->getExercise()->getExerciseRound()->getCourseModule()->id);
        $ctx->manual_grading_url = \mod_astra\urls\urls::assessSubmissionManually($this->submission);
        $ctx->resubmit_grading_url = \mod_astra\urls\urls::resubmitToService($this->submission);
        $ctx->allow_manual_grading = (\has_capability('mod/astra:grademanually', $context) &&
                $this->submission->getExercise()->isAssistantGradingAllowed()) ||
                \has_capability('mod/astra:addinstance', $context);

        $ctx->toDateStr = new date_to_string();
        $ctx->fileSizeFormatter = new file_size_formatter();

        $ctx->deviations = self::make_deviations_template_context($this->submission);

        return $ctx;
    }

    public static function make_deviations_template_context(mod_astra_submission $submission) {
        $ctx = new stdClass();
        $strdata = new stdClass();
        $strdata->submitter_name = $submission->getSubmitterName();

        $sbmsdeviation = mod_astra_submission_limit_deviation::findDeviation(
                $submission->getExercise()->getId(), $submission->getSubmitter()->id);
        if ($sbmsdeviation) {
            $ctx->has_submission_deviation = true;
            $strdata->extra_submissions = $sbmsdeviation->getExtraSubmissions();
            $ctx->submission_deviation_desc = get_string('userhasextrasbms',
                    mod_astra_exercise_round::MODNAME, $strdata);
        }

        $dldeviation = mod_astra_deadline_deviation::findDeviation(
                $submission->getExercise()->getId(), $submission->getSubmitter()->id);
        if ($dldeviation) {
            $ctx->has_deadline_deviation = true;
            $strdata->extended_dl = date('r', $dldeviation->getNewDeadline());
            $strdata->extra_minutes = $dldeviation->getExtraTime();
            $strdata->without_late_penalty =
                    $dldeviation->useLatePenalty()
                    ? ''
                    : get_string('withoutlatepenaltysuffix', mod_astra_exercise_round::MODNAME);
            $ctx->deadline_deviation_desc = get_string('userhasdlextension',
                    mod_astra_exercise_round::MODNAME, $strdata);
        }

        if (empty($ctx->has_submission_deviation) && empty($ctx->has_deadline_deviation)) {
            $ctx = false;
        }
        return $ctx;
    }
}