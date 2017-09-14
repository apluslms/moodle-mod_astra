<?php
namespace mod_astra\output;

defined('MOODLE_INTERNAL') || die;

class submission_plain_page implements \renderable, \templatable {
    
    protected $exround;
    protected $exercise;
    protected $submission;
    protected $user;
    protected $exerciseSummary;
    
    public function __construct(\mod_astra_exercise_round $exround,
            \mod_astra_exercise $exercise,
            \mod_astra_submission $submission) {
        $this->exround = $exround;
        $this->exercise = $exercise;
        $this->submission = $submission;
        $this->user = $submission->getSubmitter();
        $this->exerciseSummary = new \mod_astra\summary\user_exercise_summary($exercise, $this->user);
    }
    
    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @return stdClass
     */
    public function export_for_template(\renderer_base $output) {
        $data = new \stdClass();
        $ctx = \context_module::instance($this->exround->getCourseModule()->id);
        $data->is_course_staff = \has_capability('mod/astra:viewallsubmissions', $ctx);
        
        $data->exercise = $this->exercise->getExerciseTemplateContext($this->user, false, false);
        $data->submission = $this->submission->getTemplateContext(true, false);
        $data->summary = $this->exerciseSummary->getTemplateContext();
        
        $data->toDateStr = new \mod_astra\output\date_to_string();
        $data->fileSizeFormatter = new \mod_astra\output\file_size_formatter();
        
        return $data;
    }
}