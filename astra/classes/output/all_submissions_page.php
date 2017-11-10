<?php
namespace mod_astra\output;

defined('MOODLE_INTERNAL') || die;

class all_submissions_page implements \renderable, \templatable {
    
    const PAGE_SIZE = 100;
    
    protected $exercise;
    protected $sort;
    protected $filter;
    protected $page;
    protected $pagesize;
    protected $filterform;
    
    public function __construct(\mod_astra_exercise $ex, array $sort = null,
            array $filter = null, $page = 0, $pagesize = self::PAGE_SIZE,
            \mod_astra\form\filter_submissions_form $filterform = null) {
        $this->exercise = $ex;
        $this->sort = $sort;
        $this->filter = $filter;
        $this->page = $page === null ? 0 : $page;
        $this->pagesize = isset($pagesize) ? min(max($pagesize, 10), 1000) : self::PAGE_SIZE;
        $this->filterform = $filterform;
    }
    
    public function export_for_template(\renderer_base $output) {
        $ctx = new \stdClass();
        
        $sbmsList = array();
        $excludeErrors = (isset($this->filter['status']) && $this->filter['status'] == -2) ? true : false;
        list($submissions, $totalcount) = $this->exercise->getAllSubmissions(
                $excludeErrors, $this->filter, $this->sort,
                $this->page * $this->pagesize, $this->pagesize);
        foreach ($submissions as $sbmsRecord) {
            $sbms = new \mod_astra_submission($sbmsRecord);
            $c = new \stdClass();
            $c->fullname = fullname($sbmsRecord);
            $c->submitter_results_url = \mod_astra\urls\urls::userResults(
                    $this->exercise->getExerciseRound()->getCourseModule()->course,
                    $sbmsRecord->submitter);
            $c->idnumber = $sbmsRecord->idnumber;
            $c->submission_time = $sbms->getSubmissionTime();
            $c->late_penalty_applied = $sbms->getLatePenaltyApplied();
            if ($c->late_penalty_applied !== null) {
                $c->late_penalty_applied_percent = (int) round($c->late_penalty_applied * 100);
            }
            $c->state = $sbms->getStatus(true, true);
            $c->points = $sbms->getGrade();
            $assistantFeedback = $sbms->getAssistantFeedback();
            $c->has_assistant_feedback = !empty($assistantFeedback);
            $c->inspecturl = \mod_astra\urls\urls::inspectSubmission($sbms);
            
            $sbmsList[] = $c;
        }
        $submissions->close();
        
        $ctx->submissions = $sbmsList;
        $ctx->count = $totalcount;
        
        // display the currently used search queries to the user
        $ctx->filter = new \stdClass();
        $ctx->filter->used = !empty($this->filter);
        $ctx->filter->filters = array();
        foreach (array('firstname', 'lastname', 'idnumber') as $field) {
            if (isset($this->filter[$field])) {
                $filt = new \stdClass();
                $filt->field = get_string($field, \mod_astra_exercise_round::MODNAME);
                $filt->query = $this->filter[$field];
                
                $ctx->filter->filters[] = $filt;
            }
        }
        if (isset($this->filter['status']) && $this->filter['status'] != -1) {
            $filt = new \stdClass();
            $filt->field = get_string('status', \mod_astra_exercise_round::MODNAME);
            $filt->query = \mod_astra\form\filter_submissions_form::statusValues()[$this->filter['status']];
            
            $ctx->filter->filters[] = $filt;
        }
        if (isset($this->filter['submissiontimebef']) || isset($this->filter['submissiontimeaft'])) {
            $filt = new \stdClass();
            $filt->field = get_string('submissiontime', \mod_astra_exercise_round::MODNAME);
            $filt->query =
                (isset($this->filter['submissiontimeaft']) ? date('r', $this->filter['submissiontimeaft']) : '')
                . ' - '
                . (isset($this->filter['submissiontimebef']) ? date('r', $this->filter['submissiontimebef']) : '');
            
            $ctx->filter->filters[] = $filt;
        }
        if (isset($this->filter['gradeless']) || isset($this->filter['gradegreater'])) {
            $filt = new \stdClass();
            $filt->field = get_string('grade', \mod_astra_exercise_round::MODNAME);
            $filt->query =
                (isset($this->filter['gradegreater']) ? $this->filter['gradegreater'] : '')
                . ' - '
                . (isset($this->filter['gradeless']) ? $this->filter['gradeless'] : '');
            
            $ctx->filter->filters[] = $filt;
        }
        if (isset($this->filter['hasassistfeedback']) && $this->filter['hasassistfeedback'] != 'all') {
            $filt = new \stdClass();
            $filt->field = get_string('assistantfeedback', \mod_astra_exercise_round::MODNAME);
            $filt->query = ($this->filter['hasassistfeedback'] == 'yes')
                    ? get_string('yesassistfeedback', \mod_astra_exercise_round::MODNAME)
                    : get_string('noassistfeedback', \mod_astra_exercise_round::MODNAME);
            $ctx->filter->filters[] = $filt;
        }
        
        // URLs for sorting by each column
        $ctx->sorturl = new \stdClass();
        $sort = $this->sort === null ? array() : $this->sort;
        foreach (self::allowedFilterFields(true) as $field) {
            // links for each column for sorting by that column
            $sortfield = array(array($field, true));
            // by default ascending order
            
            foreach ($sort as $fieldASC) {
                if ($fieldASC[0] == $field) {
                    // this column is currently being sorted, the link should switch
                    // between ascending and descending
                    $sortfield[0][1] = !$fieldASC[1];
                    break;
                }
            }
            $ctx->sorturl->{$field} = \mod_astra\urls\urls::submissionList($this->exercise, false,
                    $sortfield, $this->filter, $this->page === 0 ? null : $this->page,
                    $this->pagesize);
        }
        
        // template callable for displaying sort icons on the columns being sorted
        $ctx->sortToggler = function($field) use ($sort) {
            global $OUTPUT;
            $field = trim($field);
            foreach ($sort as $fieldASC) {
                if ($fieldASC[0] == $field) {
                    $asc = ($fieldASC[1] ? 'asc' : 'desc');
                    return $OUTPUT->pix_icon('t/sort_'. $asc,
                            get_string('sort'. $asc, \mod_astra_exercise_round::MODNAME));
                }
            }
            return ''; // this field is not sorted, no sort toggler icon
        };
        
        $exercise = $this->exercise;
        $sort = $this->sort;
        $filter = $this->filter;
        $pagesize = $this->pagesize;
        $ctx->paginator = new pagination($this->page, (int) ceil($ctx->count / $this->pagesize),
                function($pageIdx) use ($exercise, $sort, $filter, $pagesize) {
                    return \mod_astra\urls\urls::submissionList($exercise, false,
                            $sort, $filter, $pageIdx, $pagesize);
                });
        
        $ctx->filterform = isset($this->filterform) ? $this->filterform->render() : null;
        
        $ctx->toDateStr = new \mod_astra\output\date_to_string();
        
        return $ctx;
    }
    
    public static function allowedFilterFields($onlySortable = false) {
        $fields = array('firstname', 'lastname', 'idnumber', 'status');
        if ($onlySortable) {
            $fields[] = 'submissiontime';
            $fields[] = 'grade';
            return $fields;
        }
        $fields[] = 'submissiontimebef'; // before
        $fields[] = 'submissiontimeaft'; // after
        $fields[] = 'gradeless';
        $fields[] = 'gradegreater';
        $fields[] = 'hasassistfeedback';
        return $fields;
    }
}