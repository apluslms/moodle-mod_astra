<?php
namespace mod_stratumtwo\output;

defined('MOODLE_INTERNAL') || die;

class index_page implements \renderable, \templatable {
    
    protected $course;
    protected $rounds;
    protected $courseSummary;
    
    public function __construct(\stdClass $course, \stdClass $user) {
        $this->course = $course;
        $this->courseSummary = new \mod_stratumtwo\summary\user_course_summary($course, $user);
        $this->rounds = $this->courseSummary->getExerciseRounds();
    }
    
    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @return stdClass
     */
    public function export_for_template(\renderer_base $output) {
        $data = new \stdClass();
        $ctx = \context_course::instance($this->course->id);
        $data->is_course_staff = \has_capability('mod/stratumtwo:viewallsubmissions', $ctx);
        
        $roundsData = array();
        foreach ($this->rounds as $round) {
            $roundCtx = new \stdClass();
            $roundCtx->course_module = $round->getTemplateContext();
            $moduleSummary = $this->courseSummary->getModuleSummary($round->getId());
            $roundCtx->module_summary = $moduleSummary->getTemplateContext();
            $roundCtx->module_summary->classes = 'pull-right'; // CSS classes
            $roundCtx->categories = $moduleSummary->getExercisesByCategoriesTemplateContext();
            $roundsData[] = $roundCtx;
        }
        $data->rounds = $roundsData;
        
        $categories = array();
        foreach ($this->courseSummary->getCategorySummaries() as $catSummary) {
            $cat = new \stdClass();
            $cat->name = $catSummary->getCategory()->getName();
            $cat->summary = $catSummary->getTemplateContext();
            $cat->status_ready = ($catSummary->getCategory()->getStatus() === \mod_stratumtwo_category::STATUS_READY);
            $categories[] = $cat;
        }
        $data->categories = $categories;
        
        $data->toDateStr = new \mod_stratumtwo\output\date_to_string();
        
        return $data;
    }
}