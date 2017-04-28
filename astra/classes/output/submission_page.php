<?php
namespace mod_astra\output;

defined('MOODLE_INTERNAL') || die;

class submission_page implements \renderable, \templatable {
    
    protected $exround;
    protected $exercise;
    protected $submission;
    protected $exerciseSummary;
    protected $user;
    protected $wait;
    
    public function __construct(\mod_astra_exercise_round $exround,
            \mod_astra_exercise $exercise,
            \mod_astra_submission $submission,
            $wait = false) {
        $this->exround = $exround;
        $this->exercise = $exercise;
        $this->submission = $submission;
        $this->user = $submission->getSubmitter();
        $this->exerciseSummary = new \mod_astra\summary\user_exercise_summary($exercise, $this->user);
        $this->wait = $wait;
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
        $data->is_editing_teacher = \has_capability('mod/astra:addinstance', $ctx);
        $data->is_manual_grader =
                ($this->exercise->isAssistantGradingAllowed() && \has_capability('mod/astra:grademanually', $ctx)) ||
                $data->is_editing_teacher;
        $data->can_inspect = ($this->exercise->isAssistantViewingAllowed() && $data->is_course_staff) ||
                $data->is_editing_teacher;
        
        $data->exercise = $this->exercise->getExerciseTemplateContext($this->user);
        $data->submissions = $this->exercise->getSubmissionsTemplateContext($this->user->id);
        $data->submission = $this->submission->getTemplateContext(true);
        
        $data->summary = $this->exerciseSummary->getTemplateContext();
        
        $data->toDateStr = new \mod_astra\output\date_to_string();
        $data->fileSizeFormatter = new \mod_astra\output\file_size_formatter();
        
        $data->page = new \stdClass();
        $data->page->is_wait = $this->wait;
        
        return $data;
        // It should return an stdClass with properties that are only made of simple types:
        // int, string, bool, float, stdClass or arrays of these types
    }
}