<?php
namespace mod_astra\output;

defined('MOODLE_INTERNAL') || die;

class index_page implements \renderable, \templatable {
    
    protected $course;
    protected $rounds;
    protected $courseSummary;
    
    public function __construct(\stdClass $course, \stdClass $user) {
        $this->course = $course;
        $this->courseSummary = new \mod_astra\summary\user_course_summary($course, $user);
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
        $data->is_course_staff = \has_capability('mod/astra:viewallsubmissions', $ctx);
        $isEditingTeacher = has_capability('mod/astra:addinstance', $ctx);
        
        $roundsData = array();
        foreach ($this->rounds as $round) {
            $roundCtx = new \stdClass();
            $roundCtx->course_module = $round->getTemplateContext();
            $moduleSummary = $this->courseSummary->getModuleSummary($round->getId());
            $roundCtx->module_summary = $moduleSummary->getTemplateContext();
            $roundCtx->module_summary->classes = 'float-right'; // CSS classes
            $roundCtx->module_contents = $moduleSummary->getModulePointsPanelTemplateContext(
                    false, !$isEditingTeacher);
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
        
        $data->toDateStr = new \mod_astra\output\date_to_string();
        
        $data->toc = $this->getCourseTableOfContentsContext();
        
        return $data;
    }
    
    protected function getCourseTableOfContentsContext() {
        global $DB;
        
        // remove rounds with status UNLISTED from the table of contents,
        // hidden rounds should already be removed from $this->rounds
        $rounds = \array_filter($this->rounds, function($round) {
            return $round->getStatus() !== \mod_astra_exercise_round::STATUS_UNLISTED;
        });
        
        $toc = new \stdClass(); // table of contents
        $toc->exercise_rounds = array();
        foreach ($rounds as $exround) {
            $roundCtx = $exround->getTemplateContext();
            $moduleSummary = $this->courseSummary->getModuleSummary($exround->getId());
            $roundCtx->lobjects = self::buildRoundLobjectsContextForToc($moduleSummary->getLearningObjects());
            $toc->exercise_rounds[] = $roundCtx;
        }
        return $toc;
    }
    
    /**
     * Return a template context object of the learning objects in an exercise round for
     * the use in a table of contents.
     * @param \mod_astra_learning_object[] $learningObjects learning objects in a round
     *        sorted in the display order
     * @return \stdClass[]
     */
    public static function buildRoundLobjectsContextForToc(array $learningObjects) {
        
        $lobjectsByParent = array();
        foreach ($learningObjects as $obj) {
            $parentid = $obj->getParentId();
            $parentid = empty($parentid) ? 'top' : $parentid;
            if (!$obj->isUnlisted()) {
                if (!isset($lobjectsByParent[$parentid])) {
                    $lobjectsByParent[$parentid] = array();
                }
                
                $lobjectsByParent[$parentid][] = $obj;
            }
        }
        
        // $parentid may be null to get top-level learning objects
        $children = function($parentid) use ($lobjectsByParent) {
            $parentid = $parentid === null ? 'top' : $parentid;
            if (isset($lobjectsByParent[$parentid])) {
                return $lobjectsByParent[$parentid];
            }
            return array();
        };
        
        $traverse = function($parentid) use (&$children, &$traverse) {
            $container = array();
            foreach ($children($parentid) as $child) {
                $childCtx = new \stdClass();
                $childCtx->is_empty = $child->isEmpty();
                $childCtx->name = $child->getName();
                $childCtx->url = \mod_astra\urls\urls::exercise($child);
                $childCtx->children = $traverse($child->getId());
                $childCtx->has_children = \count($childCtx->children) > 0;
                $container[] = $childCtx;
            }
            return $container;
        };
        
        return $traverse(null);
    }
}