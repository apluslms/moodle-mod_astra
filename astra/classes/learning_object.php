<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Base class for learning objects (exercises and chapters).
 * Each learning object belongs to one exercise round
 * and one category. A learning object has a service URL that is used to connect to
 * the exercise service.
 */
abstract class mod_astra_learning_object extends mod_astra_database_object {
    const TABLE = 'astra_lobjects'; // database table name
    const STATUS_READY       = 0;
    const STATUS_HIDDEN      = 1;
    const STATUS_MAINTENANCE = 2;
    const STATUS_UNLISTED    = 3;
    
    // SQL fragment for joining learning object base class table with a subtype table
    // self::TABLE constant cannot be used in this definition since old PHP versions
    // only support literal constants
    // Usage of this constant: sprintf(mod_astra_learning_object::SQL_SUBTYPE_JOIN, fields, SUBTYPE_TABLE)
    const SQL_SUBTYPE_JOIN = 'SELECT %s FROM {astra_lobjects} lob JOIN {%s} ex ON lob.id = ex.lobjectid';
    // SQL fragment for selecting all fields in the subtype join query: this avoids the conflict of
    // id columns in both the base table and the subtype table. Id is taken from the subtype and
    // the subtype table should have a column lobjectid which is the id in the base table.
    const SQL_SELECT_ALL_FIELDS = 'ex.*,lob.status,lob.categoryid,lob.roundid,lob.parentid,lob.ordernum,lob.remotekey,lob.name,lob.serviceurl,lob.usewidecolumn';
    
    // cache of references to other records, used in corresponding getter methods
    protected $category = null;
    protected $exerciseRound = null;
    protected $parentObject = null;
    
    public static function getSubtypeJoinSQL($subtype = mod_astra_exercise::TABLE, $fields = self::SQL_SELECT_ALL_FIELDS) {
        return sprintf(self::SQL_SUBTYPE_JOIN, $fields, $subtype);
    }
    
    /**
     * Create object of the corresponding class from an existing database ID.
     * @param int $id learning object ID (base table)
     * @return mod_astra_exercise|mod_astra_chapter
     */
    public static function createFromId($id) {
        global $DB;
        
        $where = ' WHERE lob.id = ?';
        $sql = self::getSubtypeJoinSQL(mod_astra_exercise::TABLE) . $where;
        $row = $DB->get_record_sql($sql, array($id), IGNORE_MISSING);
        if ($row !== false) {
            // this learning object is an exercise
            return new mod_astra_exercise($row);
        } else {
            // no exercise found, this learning object should be a chapter
            $sql = self::getSubtypeJoinSQL(mod_astra_chapter::TABLE) . $where;
            $row = $DB->get_record_sql($sql, array($id), MUST_EXIST);
            return new mod_astra_chapter($row);
        }
    }
    
    /**
     * Return the ID of this learning object (ID in the base table).
     * @see mod_astra_database_object::getId()
     */
    public function getId() {
        // assume that id field is from the subtype, see constant SQL_SELECT_ALL_FIELDS
        return $this->record->lobjectid; // id in the learning object base table
    }
    
    /**
     * Return ID of this learning object in its subtype table
     * (different to the ID in the base table).
     */
    public function getSubtypeId() {
        // assume that id field is from the subtype, see constant SQL_SELECT_ALL_FIELDS
        return $this->record->id; // id in the subtype (exercises/chapters) table
    }
    
    public function save() {
        global $DB;
        // Must save to both base table and subtype table.
        // subtype: $this->record->id should be the ID in the subtype table
        $DB->update_record(static::TABLE, $this->record);
        
        // must change the id value in the record for base table
        $record = clone $this->record;
        $record->id = $this->getId();
        return $DB->update_record(self::TABLE, $record);
    }
    
    public function getStatus($asString = false) {
        if ($asString) {
            switch ((int) $this->record->status) {
                case self::STATUS_READY:
                    return get_string('statusready', mod_astra_exercise_round::MODNAME);
                    break;
                case self::STATUS_MAINTENANCE:
                    return get_string('statusmaintenance', mod_astra_exercise_round::MODNAME);
                    break;
                case self::STATUS_UNLISTED:
                    return get_string('statusunlisted', mod_astra_exercise_round::MODNAME);
                    break;
                default:
                    return get_string('statushidden', mod_astra_exercise_round::MODNAME);
            }
        }
        return (int) $this->record->status;
    }
    
    public function getCategory() {
        if (is_null($this->category)) {
            $this->category = mod_astra_category::createFromId($this->record->categoryid);
        }
        return $this->category;
    }
    
    public function getCategoryId() {
        return $this->record->categoryid;
    }
    
    public function getExerciseRound() {
        if (is_null($this->exerciseRound)) {
            $this->exerciseRound = mod_astra_exercise_round::createFromId($this->record->roundid);
        }
        return $this->exerciseRound;
    }
    
    public function getParentObject() {
        if (empty($this->record->parentid)) {
            return null;
        }
        if (is_null($this->parentObject)) {
            $this->parentObject = self::createFromId($this->record->parentid);
        }
        return $this->parentObject;
    }
    
    public function getParentId() {
        if (empty($this->record->parentid)) {
            return null;
        }
        return (int) $this->record->parentid;
    }
    
    /**
     * Return an array of the learning objects that are direct children of
     * this learning object.
     * @param bool $includeHidden if true, hidden learning objects are included
     * @return mod_astra_learning_object[]
     */
    public function getChildren($includeHidden = false) {
        global $DB;
        
        $where = ' WHERE lob.parentid = ?';
        $orderBy = ' ORDER BY ordernum ASC';
        $params = array($this->getId());
        
        if ($includeHidden) {
            $where .= $orderBy;
        } else {
            $where .= ' AND lob.status != ?' . $orderBy;
            $params[] = self::STATUS_HIDDEN;
        }
        $ex_sql = self::getSubtypeJoinSQL(mod_astra_exercise::TABLE) . $where;
        $ch_sql = self::getSubtypeJoinSQL(mod_astra_chapter::TABLE) . $where;
        $exerciseRecords = $DB->get_records_sql($ex_sql, $params);
        $chapterRecords = $DB->get_records_sql($ch_sql, $params);
        
        // gather learning objects into one array
        $learningObjects = array();
        foreach ($exerciseRecords as $ex) {
            $learningObjects[] = new mod_astra_exercise($ex);
        }
        foreach ($chapterRecords as $ch) {
            $learningObjects[] = new mod_astra_chapter($ch);
        }
        // sort the combined array, compare ordernums since all objects have the same parent
        usort($learningObjects, function($obj1, $obj2) {
            $ord1 = $obj1->getOrder();
            $ord2 = $obj2->getOrder();
            if ($ord1 < $ord2) {
                return -1;
            } else if ($ord1 == $ord2) {
                return 0;
            } else {
                return 1;
            }
        });
        
        return $learningObjects;
    }
    
    public function getOrder() {
        return (int) $this->record->ordernum;
    }
    
    public function getRemoteKey() {
        return $this->record->remotekey;
    }
    
    public function getNumber() {
        $parent = $this->getParentObject();
        if ($parent !== null) {
            return $parent->getNumber() . ".{$this->record->ordernum}";
        }
        return ".{$this->record->ordernum}";
    }
    
    public function getName($includeOrder = true, string $lang = null) {
        require_once(dirname(dirname(__FILE__)) .'/locallib.php');
        // number formatting based on A+ (a-plus/exercise/exercise_models.py)
        
        $name = astra_parse_localization($this->record->name, $lang);
        if ($includeOrder && $this->getOrder() >= 0) {
            $conf = $this->getExerciseRound()->getCourseConfig();
            if ($conf !== null) {
                $contentNumbering = $conf->getContentNumbering();
                $moduleNumbering = $conf->getModuleNumbering();
            } else {
                $contentNumbering = mod_astra_course_config::getDefaultContentNumbering();
                $moduleNumbering = mod_astra_course_config::getDefaultModuleNumbering();
            }
            
            if ($contentNumbering == mod_astra_course_config::CONTENT_NUMBERING_ARABIC) {
                $number = $this->getNumber();
                if ($moduleNumbering == mod_astra_course_config::MODULE_NUMBERING_ARABIC ||
                        $moduleNumbering == mod_astra_course_config::MODULE_NUMBERING_HIDDEN_ARABIC) {
                    return $this->getExerciseRound()->getOrder() . "$number $name";
                }
                // leave out the module number ($number starts with a dot)
                return substr($number, 1) .' '. $name;
            } else if ($contentNumbering == mod_astra_course_config::CONTENT_NUMBERING_ROMAN) {
                return astra_roman_numeral($this->getOrder()) .' '. $name;
            }
        }
        return $name;
    }
    
    public function getServiceUrl() {
        $value = $this->record->serviceurl;
        if (substr($value, 0, 1) === '|') {
            // multiple language versions
            // example: |fi:http://grader.org/static/intro_fi.html|en:http://grader.org/static/intro_en.html|
            $current_lang = current_language();
            $variants = array_filter(explode('|', $value));
            // filter empty strings
            $langs = array();
            foreach ($variants as $variant) {
                $parts = explode(':', $variant, 2);
                // the first colon (:) separates the language code from the value
                // URLs usually have colons in the value too
                if (count($parts) !== 2) {
                    continue;
                }
                list($lang, $val) = $parts;
                $langs[$lang] = $val;
                
                if ($lang === $current_lang) {
                    return $val;
                }
            }
            if (isset($langs['en'])) {
                return $langs['en'];
            } else if (!empty($langs)) {
                return reset($langs);
            }
        }
        return $value;
    }
    
    public function getUseWideColumn() {
        return (bool) $this->record->usewidecolumn;
    }
    
    public function isEmpty() {
        return empty($this->record->serviceurl);
    }
    
    public function isHidden() {
        return $this->getStatus() === self::STATUS_HIDDEN;
    }
    
    public function isUnlisted() {
        return $this->getStatus() === self::STATUS_UNLISTED;
    }
    
    public function isUnderMaintenance() {
        return $this->getStatus() === self::STATUS_MAINTENANCE;
    }
    
    public function setStatus($status) {
        $this->record->status = $status;
    }
    
    public function setOrder($newOrder) {
        $this->record->ordernum = $newOrder;
    }
   
    public function isSubmittable() {
        return false;
    }
    
    /**
     * Delete this learning object from the database. Possible child learning
     * objects are also deleted.
     */
    public function deleteInstance() {
        global $DB;
        
        foreach ($this->getChildren(true) as $child) {
            $child->deleteInstance();
        }
        
        // delete this object, subtable and base table
        $DB->delete_records(static::TABLE, array('id' => $this->getSubtypeId()));
        return $DB->delete_records(self::TABLE, array('id' => $this->getId()));
    }
    
    protected function getSiblingContext($next = true) {
        // if $next true, get the next sibling; if false, get the previous sibling
        global $DB;
        
        $context = context_module::instance($this->getExerciseRound()->getCourseModule()->id);
        $isTeacher = has_capability('moodle/course:manageactivities', $context);
        $isAssistant = has_capability('mod/astra:viewallsubmissions', $context);
        
        $order = $this->getOrder();
        $parentid = $this->getParentId();
        $params = array(
                'roundid' => $this->record->roundid,
                'ordernum' => $order,
                'parentid' => $parentid,
        );
        $where = 'roundid = :roundid';
        $where .= ' AND ordernum '. ($next ? '>' : '<') .' :ordernum';
        // skip some uncommon details in the hierarchy of the round content and assume that
        // siblings are in the same level (they have the same parent)
        if ($parentid === null) {
            $where .= " AND parentid IS NULL";
        } else {
            $where .= " AND parentid = :parentid";
        }
        if ($isAssistant && !$isTeacher) {
            // assistants do not see hidden objects
            $where .= ' AND status <> :status';
            $params['status'] = self::STATUS_HIDDEN;
        } else if (!$isTeacher) {
            // students see normally enabled objects
            $where .= ' AND status = :status';
            $params['status'] = self::STATUS_READY;
        }
        $sort = 'ordernum '. ($next ? 'ASC' : 'DESC');
        
        $results = $DB->get_records_select(self::TABLE, $where, $params, $sort, '*', 0, 1);
        
        if (!empty($results)) {
            // the next object is in the same round
            $record = reset($results);
            $record->lobjectid = $record->id;
            // hack: the record does not contain the data of the learning object subtype since the DB query did not join the tables
            unset($record->id);
            // use the chapter class here since this abstract learning object class may not be instantiated
            // the subtype of the learning object is not needed here
            $sibling = new mod_astra_chapter($record);
            $ctx = new stdClass();
            $ctx->name = $sibling->getName();
            $ctx->link = \mod_astra\urls\urls::exercise($sibling);
            $ctx->accessible = $this->getExerciseRound()->hasStarted();
            return $ctx;
        } else {
            // the sibling is the next/previous round
            if ($next) {
                return $this->getExerciseRound()->getNextSiblingContext();
            } else {
                return $this->getExerciseRound()->getPreviousSiblingContext();
            }
        }
    }
    
    public function getNextSiblingContext() {
        return $this->getSiblingContext(true);
    }
    
    public function getPreviousSiblingContext() {
        return $this->getSiblingContext(false);
    }
    
    public function getTemplateContext($includeCourseModule = true, $includeSiblings = false) {
        $ctx = new stdClass();
        $ctx->url = \mod_astra\urls\urls::exercise($this);
        $parent = $this->getParentObject();
        if ($parent === null) {
            $ctx->parenturl = null;
        } else {
            $ctx->parenturl = \mod_astra\urls\urls::exercise($parent);
        }
        $ctx->displayurl = \mod_astra\urls\urls::exercise($this, false, false);
        $ctx->name = $this->getName();
        $ctx->use_wide_column = $this->getUseWideColumn();
        $ctx->editurl = \mod_astra\urls\urls::editExercise($this);
        $ctx->removeurl = \mod_astra\urls\urls::deleteExercise($this);
        
        if ($includeCourseModule) {
            $ctx->course_module = $this->getExerciseRound()->getTemplateContext();
        }
        $ctx->status_ready = ($this->getStatus() === self::STATUS_READY);
        $ctx->status_str = $this->getStatus(true);
        $ctx->status_unlisted = ($this->getStatus() === self::STATUS_UNLISTED);
        $ctx->status_maintenance = ($this->getStatus() === self::STATUS_MAINTENANCE ||
                $this->getExerciseRound()->getStatus() === mod_astra_exercise_round::STATUS_MAINTENANCE);
        $ctx->is_submittable = $this->isSubmittable();
        
        $ctx->category = $this->getCategory()->getTemplateContext(false);
        
        if ($includeSiblings) {
            $ctx->next = $this->getNextSiblingContext();
            $ctx->previous = $this->getPreviousSiblingContext();
        }
        
        return $ctx;
    }
    
    public function getLoadUrl($userid, $submissionOrdinalNumber, $language) {
        // this method can be overridden in child classes to change the URL in loadPage method
        $query_data = array(
                'lang' => $language,
        );
        return $this->getServiceUrl() .'?'. http_build_query($query_data, 'i_', '&');
    }
    
    /**
     * Load the learning object/exercise page (from the cache if available,
     * otherwise from the exercise service).
     * 
     * @param int $userid user ID
     * @return \mod_astra\protocol\exercise_page the exercise page
     */
    public function load($userid) {
        $page = new \mod_astra\protocol\exercise_page($this);
        $language = $this->exerciseRound->checkCourseLang(current_language()); // e.g., 'en'
        $cache = new \mod_astra\cache\exercise_cache($this, $language, $userid);
        
        $page->content = $cache->get_content();
        $page->injected_css_urls = $cache->get_injected_css_urls();
        $page->injected_js_urls_and_inline = $cache->get_injected_js_urls_and_inline();
        $page->inline_jquery_scripts = $cache->get_inline_jquery_scripts();
        $page->expires = $cache->get_expires();
        $page->last_modified = $cache->get_last_modified();
        $page->is_loaded = true;
        
        return $page;
    }
    
    /**
     * Load the exercise page from the exercise service.
     * @param int $userid user ID
     * @param string $language language of the content of the page, e.g., 'en' for English
     * (lang query parameter in the grader protocol)
     * @param null|string $last_modified value for If-Modified-Since HTTP request header
     * @throws mod_astra\protocol\remote_page_exception if there are errors
     * in connecting to the server
     * @throws \mod_astra\protocol\remote_page_not_modified if $last_modified is given and
     * the remote page has not been modified
     * @return \mod_astra\protocol\exercise_page the exercise page
     */
    public function loadPage($userid, $language = 'en', $last_modified = null) {
        global $DB;
        
        $courseConfig = $this->getExerciseRound()->getCourseConfig();
        $api_key = ($courseConfig ? $courseConfig->getApiKey() : null);
        if (empty($api_key)) {
            $api_key = null; // $courseConfig gives an empty string if not set
        }
        
        $submissionCount = $DB->count_records(\mod_astra_submission::TABLE, array(
                'submitter' => $userid,
                'exerciseid' => $this->getId(),
        ));
        // must increment $submissionCount since the exercise description must match the next new submission
        $serviceUrl = $this->getLoadUrl($userid, $submissionCount + 1, $language);
        try {
            $remotePage = new \mod_astra\protocol\remote_page($serviceUrl, false, null, null,
                    $api_key, $last_modified);
            $remotePage->setLearningObject($this);
            return $remotePage->loadExercisePage($this);
        } catch (\mod_astra\protocol\service_connection_exception $e) {
            // error logging
            $event = \mod_astra\event\service_connection_failed::create(array(
                    'context' => context_module::instance($this->getExerciseRound()->getCourseModule()->id),
                    'other' => array(
                            'error' => $e->getMessage(),
                            'url' => $serviceUrl,
                            'objtable' => self::TABLE,
                            'objid' => $this->getId(),
                    )
            ));
            $event->trigger();
            throw $e;
        } catch (\mod_astra\protocol\exercise_service_exception $e) {
            $event = \mod_astra\event\exercise_service_failed::create(array(
                    'context' => context_module::instance($this->getExerciseRound()->getCourseModule()->id),
                    'other' => array(
                            'error' => $e->getMessage(),
                            'url' => $serviceUrl,
                            'objtable' => self::TABLE,
                            'objid' => $this->getId(),
                    )
            ));
            $event->trigger();
            throw $e;
        }
    }
    
}