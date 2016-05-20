<?php
namespace mod_stratumtwo\output;

defined('MOODLE_INTERNAL') || die;

class submission_plain_page implements \renderable, \templatable {
    
    protected $exround;
    protected $exercise;
    protected $submission;
    protected $user;
    
    public function __construct(\mod_stratumtwo_exercise_round $exround,
            \mod_stratumtwo_exercise $exercise,
            \mod_stratumtwo_submission $submission) {
        $this->exround = $exround;
        $this->exercise = $exercise;
        $this->submission = $submission;
        $this->user = $submission->getSubmitter();
    }
    
    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @return stdClass
     */
    public function export_for_template(\renderer_base $output) {
        $data = new \stdClass();
        $ctx = \context_module::instance($this->exround->getCourseModule()->id);
        $data->is_course_staff = \has_capability('mod/stratumtwo:viewallsubmissions', $ctx);
        
        $data->exercise = $this->exercise->getExerciseTemplateContext($this->user, false, false);
        $data->submission = $this->submission->getTemplateContext(true, false);
        
        $data->toDateStr = new \mod_stratumtwo\output\date_to_string();
        $data->fileSizeFormatter = new \mod_stratumtwo\output\file_size_formatter();
        
        return $data;
    }
}