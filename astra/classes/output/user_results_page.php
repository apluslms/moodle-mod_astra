<?php
namespace mod_astra\output;

defined('MOODLE_INTERNAL') || die;

class user_results_page implements \renderable, \templatable {
    
    protected $course;
    protected $user;
    protected $courseSummary;
    
    public function __construct(\stdClass $course, \stdClass $user) {
        $this->course = $course;
        $this->user = $user;
        $this->courseSummary = new \mod_astra\summary\user_course_summary($course, $user);
    }
    
    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @return stdClass
     */
    public function export_for_template(\renderer_base $output) {
        global $OUTPUT;
        
        $data = new \stdClass();
        $ctx = \context_course::instance($this->course->id);
        //$data->is_course_staff = \has_capability('mod/astra:viewallsubmissions', $ctx);
        $isEditingTeacher = has_capability('mod/astra:addinstance', $ctx);
        
        $roundsData = array();
        foreach ($this->courseSummary->getExerciseRounds() as $round) {
            $roundCtx = new \stdClass();
            $roundCtx->course_module = $round->getTemplateContext();
            $moduleSummary = $this->courseSummary->getModuleSummary($round->getId());
            $roundCtx->module_summary = $moduleSummary->getTemplateContext();
            $roundCtx->module_summary->classes = 'float-right'; // CSS classes
            $roundCtx->module_contents = $moduleSummary->getModulePointsPanelTemplateContext(!$isEditingTeacher);
            $roundsData[] = $roundCtx;
        }
        $data->rounds = $roundsData;
        
        $categories = array();
        foreach ($this->courseSummary->getCategorySummaries() as $catSummary) {
            $cat = new \stdClass();
            $cat->name = $catSummary->getCategory()->getName();
            $cat->summary = $catSummary->getTemplateContext();
            $cat->status_ready = ($catSummary->getCategory()->getStatus() === \mod_astra_category::STATUS_READY);
            $categories[] = $cat;
        }
        $data->categories = $categories;
        
        $u = new \stdClass();
        $u->idnumber = $this->user->idnumber;
        $u->firstname = $this->user->firstname;
        $u->lastname = $this->user->lastname;
        $u->email = $this->user->email;
        $u->picture = $OUTPUT->user_picture($this->user, array(
                'courseid' => $this->course->id,
        ));
        // the user picture HTML also links to the user profile by default
        $data->user = $u;
        
        $data->toDateStr = new \mod_astra\output\date_to_string();
        
        return $data;
    }
}