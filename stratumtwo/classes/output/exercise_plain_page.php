<?php
namespace mod_stratumtwo\output;

defined('MOODLE_INTERNAL') || die;

/**
 * Page for displaying a plain learning object (exercise/chapter) for embedding into chapters.
 */
class exercise_plain_page implements \renderable, \templatable {
    
    protected $exround;
    protected $learningObject;
    protected $exerciseSummary; // if the learning object is an exercise
    protected $user;
    protected $errorMsg;
    protected $submission;
    
    public function __construct(\mod_stratumtwo_exercise_round $exround,
            \mod_stratumtwo_learning_object $learningObject,
            \stdClass $user,
            $errorMsg = null,
            $submission = null) {
        $this->exround = $exround;
        $this->learningObject = $learningObject;
        $this->user = $user;
        if ($learningObject->isSubmittable()) {
            $this->exerciseSummary = new \mod_stratumtwo\summary\user_exercise_summary($learningObject, $user);
        } else {
            $this->exerciseSummary = null;
        }
        $this->errorMsg = $errorMsg;
        $this->submission = $submission; // if set, the page includes the feedback instead of the exercise description
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
        
        $status_maintenance = ($this->exround->isUnderMaintenance() || $this->learningObject->isUnderMaintenance());
        $not_started = !$this->exround->hasStarted();

        if ($this->submission !== null) {
            // show feedback
            $data->page = new \stdClass();
            $data->page->content = $this->submission->getFeedback();
            
        } else if (!($status_maintenance || $not_started) || $data->is_course_staff) {
            // download exercise description from the exercise service
            try {
                $remotePage = $this->learningObject->loadPage($this->user->id);
                unset($remotePage->remote_page);
                $data->page = $remotePage; // has content field
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
        
        $data->module = $this->exround->getTemplateContext();
        
        if ($this->learningObject->isSubmittable()) {
            $data->exercise = $this->learningObject->getExerciseTemplateContext($this->user, false, false);
            $data->submissions = $this->learningObject->getSubmissionsTemplateContext($this->user->id);

            $data->summary = $this->exerciseSummary->getTemplateContext();
            // add a field to the summary object
            if ($this->exerciseSummary->getBestSubmission() !== null) {
                $data->summary->best_submission_url = \mod_stratumtwo\urls\urls::submission($this->exerciseSummary->getBestSubmission());
            } else {
                $data->summary->best_submission_url = null;
            }
        } else {
            $data->exercise = $this->learningObject->getTemplateContext(false);
        }
        
        $data->toDateStr = new \mod_stratumtwo\output\date_to_string();
        
        return $data;
    }
}