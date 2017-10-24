<?php
namespace mod_astra\output;

defined('MOODLE_INTERNAL') || die;

require_once(dirname(dirname(dirname(__FILE__))).'/locallib.php');

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
    
    public function __construct(\mod_astra_exercise_round $exround,
            \mod_astra_learning_object $learningObject,
            \stdClass $user, \page_requirements_manager $page_requires,
            $errorMsg = null) {
        $this->exround = $exround;
        $this->learningObject = $learningObject;
        $this->user = $user;
        $this->page_requires = $page_requires; // from $PAGE->requires
        if ($learningObject->isSubmittable()) {
            $this->exerciseSummary = new \mod_astra\summary\user_exercise_summary($learningObject, $user);
        } else {
            $this->exerciseSummary = null;
        }
        $this->errorMsg = $errorMsg;
    }
    
    /**
     * Copy CSS and JS requirements from the remote page head (with data-aplus attributes)
     * to the Moodle page. Likewise, JS inline scripts with data-astra-jquery attribute
     * are copied from anywhere in the remote page, and they are automatically embedded
     * in AMD code that loads the jQuery JS library.
     * @param \mod_astra\protocol\exercise_page $page
     */
    protected function setMoodlePageRequirements(\mod_astra\protocol\exercise_page $page) {
        foreach ($page->injected_css_urls as $cssUrl) {
            // absolute (external) URL must be passed as moodle_url instance
            $this->page_requires->css(new \moodle_url($cssUrl));
        }
        
        list($jsUrls, $jsInlineCode) = $page->injected_js_urls_and_inline;
        foreach ($jsUrls as $jsUrl) {
            // absolute (external) URL must be passed as moodle_url instance
            $this->page_requires->js(new \moodle_url($jsUrl));
        }
        foreach ($jsInlineCode as $inlineCode) {
            // the code probably is not using any AMD modules but the Moodle page API
            // does not have other methods to inject inline JS code to the page
            $this->page_requires->js_amd_inline($inlineCode);
        }
        
        // inline scripts (JS code inside <script>) with jQuery
        foreach ($page->inline_jquery_scripts as $scriptElem) {
            // import jQuery in the Moodle way, jQuery module is visible to the code in the given name $scriptElem[1]
            $js = 'require(["jquery"], function('. $scriptElem[1] .') { '. $scriptElem[0] .' });';
            $this->page_requires->js_amd_inline($js);
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
        $data->is_course_staff = \has_capability('mod/astra:viewallsubmissions', $ctx);
        $data->is_editing_teacher = \has_capability('mod/astra:addinstance', $ctx);
        if ($this->learningObject->isSubmittable()) {
            /*$data->is_manual_grader = 
                ($this->learningObject->isAssistantGradingAllowed() && \has_capability('mod/astra:grademanually', $ctx)) ||
                $data->is_editing_teacher;*/
            $data->can_inspect = ($this->learningObject->isAssistantViewingAllowed() && $data->is_course_staff) ||
                $data->is_editing_teacher;
        } else {
            //$data->is_manual_grader = \has_capability('mod/astra:grademanually', $ctx);
            $data->can_inspect = $data->is_course_staff;
        }
        
        $data->status_maintenance = ($this->exround->isUnderMaintenance() || $this->learningObject->isUnderMaintenance());
        $data->not_started = !$this->exround->hasStarted();

        if (!($data->status_maintenance || $data->not_started) || $data->is_course_staff) {
            try {
                $page = $this->learningObject->load($this->user->id);
                $this->setMoodlePageRequirements($page);
                $page->content = astra_filter_exercise_content($page->content, $ctx);
                $data->page = $page->get_template_context(); // has content field
            } catch (\mod_astra\protocol\remote_page_exception $e) {
                $data->error = \get_string('serviceconnectionfailed', \mod_astra_exercise_round::MODNAME);
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
            $data->exercise = $this->learningObject->getTemplateContext(false);
            if ($this->learningObject->shouldGenerateTableOfContents()) {
                $data->round_toc = \mod_astra\output\exercise_round_page::getRoundTableOfContentsContext($this->exround);
            } else {
                $data->round_toc = false;
            }
        }
        
        $data->toDateStr = new \mod_astra\output\date_to_string();
        
        return $data;
        // It should return an stdClass with properties that are only made of simple types:
        // int, string, bool, float, stdClass or arrays of these types
    }
}