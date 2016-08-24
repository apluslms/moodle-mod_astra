<?php
namespace mod_stratumtwo\output;

defined('MOODLE_INTERNAL') || die;

/**
 * Page for displaying a learning object (exercise/chapter).
 */
class exercise_page implements \renderable, \templatable {
    
    protected $exround;
    protected $learningObject;
    protected $exerciseSummary; // if the learning object is an exercise
    protected $user;
    protected $errorMsg;
    protected $page_requires;
    
    public function __construct(\mod_stratumtwo_exercise_round $exround,
            \mod_stratumtwo_learning_object $learningObject,
            \stdClass $user, \page_requirements_manager $page_requires,
            $errorMsg = null) {
        $this->exround = $exround;
        $this->learningObject = $learningObject;
        $this->user = $user;
        $this->page_requires = $page_requires; // from $PAGE->requires
        if ($learningObject->isSubmittable()) {
            $this->exerciseSummary = new \mod_stratumtwo\summary\user_exercise_summary($learningObject, $user);
        } else {
            $this->exerciseSummary = null;
        }
        $this->errorMsg = $errorMsg;
    }
    
    /**
     * Copy CSS and JS requirements from the remote page head (with data-aplus attributes)
     * to the Moodle page.
     * @param \mod_stratumtwo\protocol\remote_page $remotePage
     */
    protected function setMoodlePageRequirements(\mod_stratumtwo\protocol\remote_page $remotePage) {
        foreach ($remotePage->getInjectedCSS_URLs() as $cssUrl) {
            // absolute (external) URL must be passed as moodle_url instance
            $this->page_requires->css(new \moodle_url($cssUrl));
        }
        
        list($jsUrls, $jsInlineCode) = $remotePage->getInjectedJsUrlsAndInline();
        foreach ($jsUrls as $jsUrl) {
            // absolute (external) URL must be passed as moodle_url instance
            $this->page_requires->js(new \moodle_url($jsUrl));
        }
        foreach ($jsInlineCode as $inlineCode) {
            // the code probably is not using any AMD modules but the Moodle page API
            // does not have other methods to inject inline JS code to the page
            $this->page_requires->js_amd_inline($inlineCode);
        }
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
        if ($this->learningObject->isSubmittable()) {
            $data->is_manual_grader = 
                ($this->learningObject->isAssistantGradingAllowed() && \has_capability('mod/stratumtwo:grademanually', $ctx)) ||
                $data->is_editing_teacher;
            $data->can_inspect = ($this->learningObject->isAssistantViewingAllowed() && $data->is_course_staff) ||
                $data->is_editing_teacher;
        } else {
            $data->is_manual_grader = \has_capability('mod/stratumtwo:grademanually', $ctx);
            $data->can_inspect = $data->is_course_staff;
        }
        
        $data->status_maintenance = ($this->exround->isUnderMaintenance() || $this->learningObject->isUnderMaintenance());
        $data->not_started = !$this->exround->hasStarted();

        if (!($data->status_maintenance || $data->not_started) || $data->is_course_staff) {
            try {
                $remotePage = $this->learningObject->loadPage($this->user->id);
                $this->setMoodlePageRequirements($remotePage->remote_page);
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
        
        if ($this->learningObject->isSubmittable()) {
            $data->exercise = $this->learningObject->getExerciseTemplateContext($this->user, true, true);
            $data->submissions = $this->learningObject->getSubmissionsTemplateContext($this->user->id);
            $data->submission = false;

            $data->summary = $this->exerciseSummary->getTemplateContext();
        } else {
            $data->chapter = $this->learningObject->getTemplateContext(false);
            if ($this->learningObject->shouldGenerateTableOfContents()) {
                $data->round_toc = \mod_stratumtwo\output\exercise_round_page::getRoundTableOfContentsContext($this->exround);
            } else {
                $data->round_toc = false;
            }
        }
        
        $data->toDateStr = new \mod_stratumtwo\output\date_to_string();
        
        return $data;
        // It should return an stdClass with properties that are only made of simple types:
        // int, string, bool, float, stdClass or arrays of these types
    }
}