<?php
namespace mod_stratumtwo\output;

defined('MOODLE_INTERNAL') || die;

/**
 * Page for displaying a learning object (exercise/page).
 */
class exercise_page implements \renderable, \templatable {
    
    protected $exround;
    protected $learningObject;
    protected $exerciseSummary; // if the learning object is an exercise
    protected $user;
    protected $errorMsg;
    
    public function __construct(\mod_stratumtwo_exercise_round $exround,
            \mod_stratumtwo_learning_object $learningObject,
            \stdClass $user, $errorMsg = null) {
        $this->exround = $exround;
        $this->learningObject = $learningObject;
        $this->user = $user;
        if ($learningObject->isSubmittable()) {
            $this->exerciseSummary = new \mod_stratumtwo\summary\user_exercise_summary($learningObject, $user);
        } else {
            $this->exerciseSummary = null;
        }
        $this->errorMsg = $errorMsg;
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
        
        $data->status_maintenance = ($this->exround->isUnderMaintenance() || $this->learningObject->isUnderMaintenance());
        $data->not_started = !$this->exround->hasStarted();

        if (!($data->status_maintenance || $data->not_started) || $data->is_course_staff) {
            try {
                $data->page = $this->learningObject->loadPage($this->user->id);
            } catch (\mod_stratumtwo\protocol\remote_page_exception $e) {
                $data->error = \get_string('serviceconnectionfailed', \mod_stratumtwo_exercise_round::MODNAME);
                $page = new \stdClass();
                $page->content = '';
                $data->page = $page;
            }
        }
        
        if (!is_null($this->errorMsg)) {
            if (isset($data->error)) {
                $data->error .= '<br>'. $this->errorMsg;
            } else {
                $data->error = $this->errorMsg;
            }
        }
        
        if ($this->learningObject->isSubmittable()) {
            $data->exercise = $this->learningObject->getExerciseTemplateContext($this->user, true, true);
            $data->submissions = $this->learningObject->getSubmissionsTemplateContext($this->user->id);
            $data->submission = false;

            $data->summary = $this->exerciseSummary->getTemplateContext();
        } else {
            $data->chapter = $this->learningObject->getTemplateContext(false);
        }
        
        $data->toDateStr = new \mod_stratumtwo\output\date_to_string();
        
        return $data;
        // It should return an stdClass with properties that are only made of simple types:
        // int, string, bool, float, stdClass or arrays of these types
    }
}