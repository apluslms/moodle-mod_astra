<?php
namespace mod_astra\output;

defined('MOODLE_INTERNAL') || die;

require_once(dirname(dirname(dirname(__FILE__))).'/locallib.php');

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
    protected $wait;
    
    public function __construct(\mod_astra_exercise_round $exround,
            \mod_astra_learning_object $learningObject,
            \stdClass $user,
            $errorMsg = null,
            $submission = null,
            $wait_for_async_grading = false) {
        $this->exround = $exround;
        $this->learningObject = $learningObject;
        $this->user = $user;
        if ($learningObject->isSubmittable()) {
            $this->exerciseSummary = new \mod_astra\summary\user_exercise_summary($learningObject, $user);
        } else {
            $this->exerciseSummary = null;
        }
        $this->errorMsg = $errorMsg;
        $this->submission = $submission; // if set, the page includes the feedback instead of the exercise description
        $this->wait = $wait_for_async_grading; // if submission is set and $wait is true,
        // tell the frontend JS to poll for the status of the submission until the grading is complete
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
        
        $status_maintenance = ($this->exround->isUnderMaintenance() || $this->learningObject->isUnderMaintenance());
        $not_started = !$this->exround->hasStarted();

        if ($this->submission !== null) {
            // show feedback
            $data->page = new \stdClass();
            $data->page->content = astra_filter_exercise_content($this->submission->getFeedback(), $ctx);
            $data->page->is_wait = $this->wait;
            $data->submission = $this->submission->getTemplateContext();
            
        } else if (!($status_maintenance || $not_started) || $data->is_course_staff) {
            // download exercise description from the exercise service
            try {
                $page = $this->learningObject->load($this->user->id);
                $page->content = astra_filter_exercise_content($page->content, $ctx);
                $data->page = $page->get_template_context(); // has content field
            } catch (\mod_astra\protocol\remote_page_exception $e) {
                $data->error = \get_string('serviceconnectionfailed', \mod_astra_exercise_round::MODNAME);
                $page = new \stdClass();
                $page->content = '';
                $data->page = $page;
            }
        } else if ($status_maintenance) {
            $data->page = new \stdClass();
            $data->page->content = '<p>'. \get_string('undermaintenance', \mod_astra_exercise_round::MODNAME) .'</p>';
        } else {
            // not started
            $data->page = new \stdClass();
            $data->page->content = '<p>'. \get_string('notopenedyet', \mod_astra_exercise_round::MODNAME) .'</p>';
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
                $data->summary->best_submission_url = \mod_astra\urls\urls::submission($this->exerciseSummary->getBestSubmission());
            } else {
                $data->summary->best_submission_url = null;
            }
        } else {
            $data->exercise = $this->learningObject->getTemplateContext(false);
        }
        
        $data->toDateStr = new \mod_astra\output\date_to_string();
        
        return $data;
    }
}