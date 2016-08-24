<?php
namespace mod_astra\output;

defined('MOODLE_INTERNAL') || die;

class exercise_round_page implements \renderable, \templatable {
    
    protected $exround;
    protected $moduleSummary;
    
    public function __construct(\mod_astra_exercise_round $exround, \stdClass $user) {
        $this->exround = $exround;
        $this->moduleSummary = new \mod_astra\summary\user_module_summary($exround, $user);
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
        $data->course_module = $this->exround->getTemplateContext();
        $data->module_summary = $this->moduleSummary->getTemplateContext();
        $data->module_summary->classes = 'pull-right'; // CSS classes
        $data->categories = $this->moduleSummary->getExercisesByCategoriesTemplateContext();
        
        $data->toDateStr = new \mod_astra\output\date_to_string();
        
        $data->toc = self::getRoundTableOfContentsContext($this->exround);
        
        return $data;
        // It should return an stdClass with properties that are only made of simple types:
        // int, string, bool, float, stdClass or arrays of these types
    }
    
    /**
     * Return table of contents context for an exercise round.
     * @param \mod_astra_exercise_round $exround
     * @return stdClass
     */
    public static function getRoundTableOfContentsContext(\mod_astra_exercise_round $exround) {
        $toc = $exround->getTemplateContext();
        $toc->has_started = $exround->hasStarted();
        $toc->lobjects = \mod_astra\output\index_page::buildRoundLobjectsContextForToc(
                $exround->getLearningObjects(false, false));
        return $toc;
    }
}