<?php
namespace mod_astra\output;

defined('MOODLE_INTERNAL') || die;

require_once(dirname(dirname(dirname(__FILE__))) . '/locallib.php');

class participants_page implements \renderable, \templatable {
    
    const PAGE_SIZE = 50;
    
    protected $course;
    protected $sort;
    protected $filter;
    protected $roleid;
    protected $page;
    
    /**
     * Page for listing the participants in the course.
     * 
     * @param \stdClass $course the course record
     * @param array $sort array of arrays that defines the user fields to sort.
     *        Outer array is indexed from zero and shows the order of the columns
     *        to sort (primary column first). The keys of the nested arrays are field names
     *        (like 'idnumber' in the allowedFilterFields method) and values are
     *        boolean: true for ascending sort, false for descending.
     * @param array $filter array of queries for filtering the user records;
     *        possible keys: 'idnumber', 'firstname', 'lastname', 'email'.
     *        The given query value is surrounded with wildcards so that the result
     *        includes any record containing the queried word. Multiple filters
     *        are ANDed together so that they must all match.
     * @param int $roleid include only participants with this role.
     *        Use -1 for including all roles and 0 for guessing the student role.
     *        This function has to guess which role is the student role if the id is not provided.
     * @param int $page which result page should be shown? Each page may contain up to PAGE_SIZE
     *        rows. Pages are numbered starting from zero.
     */
    public function __construct(\stdClass $course, array $sort = null, array $filter = null,
            $roleid = 0, $page = 0) {
        $this->course = $course;
        $this->sort = $sort;
        $this->filter = $filter;
        $this->roleid = $roleid === null ? 0 : $roleid;
        $this->page = $page === null ? 0 : $page;
    }
    
    public static function allowedFilterFields() {
        return array('idnumber', 'lastname', 'firstname', 'email');
    }
    
    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @return stdClass
     */
    public function export_for_template(\renderer_base $output) {
        global $DB;
        
        $data = new \stdClass();
        $ctx = \context_course::instance($this->course->id);
        //$data->is_course_staff = \has_capability('mod/astra:viewallsubmissions', $ctx);
        
        list($enrolled_users, $totalcount, $matchcount, $roleid) = astra_get_participants(
                $ctx, $this->sort, $this->filter, $this->roleid,
                $this->page * self::PAGE_SIZE, self::PAGE_SIZE);
        $users = array();
        foreach ($enrolled_users as $u) {
            $user = new \stdClass();
            $user->idnumber = $u->idnumber; // id given by the institution, "student id"
            $user->firstname = $u->firstname;
            $user->lastname = $u->lastname;
            $user->email = $u->email;
            $user->link = \mod_astra\urls\urls::userResults($this->course->id, $u->id);
            $users[] = $user;
        }
        
        $data->users = $users;
        $data->totalcount = $totalcount;
        if ($roleid !== -1) {
            // filtered participants by role
            $role = $DB->get_record('role', array('id' => $roleid));
            if ($role === false) {
                // role with the given id not found
                $data->rolename = "roleid $roleid (NOT FOUND)";
            } else {
                $data->rolename = role_get_name($role, $ctx);
            }
        }
        $data->matchcount = $matchcount;
        $data->filter = new \stdClass();
        $data->filter->used = !empty($this->filter);
        $data->filter->filters = array();
        $allowedFilterFields = self::allowedFilterFields();
        foreach ($allowedFilterFields as $field) {
            if (isset($this->filter[$field])) {
                $filt = new \stdClass();
                $filt->field = get_string($field, \mod_astra_exercise_round::MODNAME);
                $filt->query = $this->filter[$field];
                
                $data->filter->filters[] = $filt;
            }
        }
        
        // template callable for printing the sort toggler to the columns that are currently sorted
        $sort = $this->sort === null ? array() : $this->sort;
        $data->sortToggler = function($field) use ($sort) {
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
        
        $data->sorturl = new \stdClass();
        foreach ($allowedFilterFields as $field) {
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
            $data->sorturl->{$field} = \mod_astra\urls\urls::participantList($this->course->id, false,
                    $sortfield, $this->filter, $roleid, $this->page === 0 ? null : $this->page);
        }
        
        $courseid = $this->course->id;
        $sort2 = $this->sort;
        $filter = $this->filter;
        $data->paginator = new pagination($this->page, (int) ceil($matchcount / self::PAGE_SIZE),
                function($pageIdx) use ($courseid, $sort2, $filter, $roleid) {
            return \mod_astra\urls\urls::participantList($courseid, false,
                    $sort2, $filter, $roleid, $pageIdx);
        });
        
        //$data->toDateStr = new \mod_astra\output\date_to_string();
        
        return $data;
    }
}