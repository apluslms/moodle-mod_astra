<?php
namespace mod_stratumtwo\output;

defined('MOODLE_INTERNAL') || die;

class submission_page implements \renderable, \templatable {
    
    protected $exround;
    protected $exercise;
    protected $submission;
    protected $exerciseSummary;
    protected $user;
    
    public function __construct(\mod_stratumtwo_exercise_round $exround,
            \mod_stratumtwo_exercise $exercise,
            \mod_stratumtwo_submission $submission,
            \stdClass $user) {
        $this->exround = $exround;
        $this->exercise = $exercise;
        $this->submission = $submission;
        $this->user = $user;
        $this->exerciseSummary = new \mod_stratumtwo\summary\user_exercise_summary($exercise, $user);
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
        $data->is_editing_teacher = \has_capability('mod/stratumtwo:addinstance', $ctx);
        $data->is_manual_grader = \has_capability('mod/stratumtwo:grademanually', $ctx);
        
        $data->exercise = $this->exercise->getTemplateContext($this->user);
        $data->submissions = $this->exercise->getSubmissionsTemplateContext($this->user->id);
        $data->submission = $this->submission->getTemplateContext(true);
        
        $data->summary = $this->exerciseSummary->getTemplateContext();
        
        $data->toDateStr = new \mod_stratumtwo\output\date_to_string();
        $data->fileSizeFormatter = new \mod_stratumtwo\output\file_size_formatter();
        
        return $data;
        // It should return an stdClass with properties that are only made of simple types:
        // int, string, bool, float, stdClass or arrays of these types
    }
}