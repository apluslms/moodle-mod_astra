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
        
        $roundsData = array();
        foreach ($this->rounds as $round) {
            $roundCtx = new \stdClass();
            $roundCtx->course_module = $round->getTemplateContext();
            $moduleSummary = $this->courseSummary->getModuleSummary($round->getId());
            $roundCtx->module_summary = $moduleSummary->getTemplateContext();
            $roundCtx->module_summary->classes = 'float-right'; // CSS classes
            $roundCtx->categories = $moduleSummary->getExercisesByCategoriesTemplateContext();
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
        
        $roundIds = array();
        foreach ($rounds as $exround) {
            $roundIds[] = $exround->getId();
        }
        // all visible categories in the course
        $catIds = array();
        foreach ($this->courseSummary->getCategorySummaries() as $catSummary) {
            $catIds[] = $catSummary->getCategory()->getId();
        }
        
        // all learning objects in the course, minimize the number of DB queries
        if (empty($roundIds) || empty($catIds)) {
            $exerciseRecords = array();
            $chapterRecords = array();
        } else {
            $params = array(\mod_astra_learning_object::STATUS_HIDDEN);
            $exerciseRecords = $DB->get_records_sql(
                    \mod_astra_learning_object::getSubtypeJoinSQL(\mod_astra_exercise::TABLE) .
                    ' WHERE lob.roundid IN ('. \implode(',', $roundIds) .') AND lob.status != ? AND lob.categoryid IN ('. \implode(',', $catIds) .')',
                    $params);
            $chapterRecords = $DB->get_records_sql(
                    \mod_astra_learning_object::getSubtypeJoinSQL(\mod_astra_chapter::TABLE) .
                    ' WHERE lob.roundid IN ('. \implode(',', $roundIds) .') AND lob.status != ? AND lob.categoryid IN ('. \implode(',', $catIds) .')',
                    $params);
        }
        
        $lobjectsByRoundId = array(); // organize by round ID
        foreach ($roundIds as $rid) {
            $lobjectsByRoundId[$rid] = array();
        }
        foreach ($exerciseRecords as $rec) {
            $lobjectsByRoundId[$rec->roundid][] = new \mod_astra_exercise($rec);
        }
        foreach ($chapterRecords as $rec) {
            $lobjectsByRoundId[$rec->roundid][] = new \mod_astra_chapter($rec);
        }
        
        $toc = new \stdClass(); // table of contents
        $toc->exercise_rounds = array();
        foreach ($rounds as $exround) {
            $roundCtx = $exround->getTemplateContext();
            $roundCtx->has_started = $exround->hasStarted();
            $roundCtx->lobjects = self::buildRoundLobjectsContextForToc($lobjectsByRoundId[$exround->getId()]);
            $toc->exercise_rounds[] = $roundCtx;
        }
        return $toc;
    }
    
    /**
     * Return a template context object of the learning objects in an exercise round for
     * the use in a table of contents.
     * @param \mod_astra_learning_object[] $learningObjects learning objects in a round
     * @return \stdClass[]
     */
    public static function buildRoundLobjectsContextForToc($learningObjects) {
        $orderSortCallback = function($obj1, $obj2) {
            $ord1 = $obj1->getOrder();
            $ord2 = $obj2->getOrder();
            if ($ord1 < $ord2) {
                return -1;
            } else if ($ord1 == $ord2) {
                return 0;
            } else {
                return 1;
            }
        };
        
        // $parentid may be null to get top-level learning objects
        $children = function($parentid) use ($learningObjects, &$orderSortCallback) {
            $child_objs = array();
            foreach ($learningObjects as $obj) {
                if ($obj->getParentId() == $parentid && !$obj->isUnlisted())
                    $child_objs[] = $obj;
            }
            // sort children by ordernum, they all have the same parent
            usort($child_objs, $orderSortCallback);
            return $child_objs;
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