<?php

namespace mod_astra\cache;

defined('MOODLE_INTERNAL') || die;

/**
 * Cache for exercise/learning object page content retrieved from an exercise service.
 * This class uses the Moodle Cache API (MUC, Moodle Universal Cache).
 * 
 * Derived from A+ (a-plus/exercise/cache/exercise.py).
 *
 */
class exercise_cache {
    
    const KEY_PREFIX = 'exercise'; // used for cache key
    const CACHE_API_AREA = 'exercisedesc'; // cache API area name, defined in astra/db/caches.php
    
    protected $learning_object;
    protected $language;
    protected $userid;
    protected $data; // data stored in the cache
    protected $cache; // Moodle cache object
    
    public function __construct(\mod_astra_learning_object $learning_object, $language, $userid) {
        $this->learning_object = $learning_object;
        $this->language = $language;
        $this->userid = $userid;
        
        // initialize Moodle cache API
        $this->cache = \cache::make(\mod_astra_exercise_round::MODNAME, self::CACHE_API_AREA);
        
        // check if key is found in cache
        // is the data stale?
        $key = $this->get_key();
        $this->data = $this->cache->get($key);
        if ($this->needs_generation()) {
            $this->generate_data();
            // set data to cache if it is cacheable
            if ($this->data['expires'] > time()) {
                $this->cache->set($key, $this->data);
            }
        }
    }
    
    protected function get_key() {
        return self::key($this->learning_object->getId(), $this->language);
    }
    
    protected static function key($learning_object_id, $language) {
        // concatenate KEY_PREFIX, exercise lobjectid, language
        return self::KEY_PREFIX . '_' . $learning_object_id . '_' . $language;
    }
    
    protected function needs_generation() {
        return $this->data === false || $this->data['expires'] < time();
    }
    
    protected function generate_data() {
        try {
            $last_modified = !empty($this->data) ? $this->data['last_modified'] : null;
            $page = $this->learning_object->loadPage($this->userid, $last_modified); //TODO language
            $this->data = array(
                    'content' => $page->content,
                    'expires' => $page->expires,
                    'last_modified' => $page->last_modified,
                    'injected_css_urls' => $page->injected_css_urls,
                    'injected_js_urls_and_inline' => $page->injected_js_urls_and_inline,
                    'inline_jquery_scripts' => $page->inline_jquery_scripts,
            );
        } catch (\mod_astra\protocol\remote_page_not_modified $e) {
            // set new expires value
            $expires = $e->expires();
            if ($expires) {
                $this->data['expires'] = $expires;
            }
        }
    }
    
    public function get_content() {
        return $this->data['content'];
    }
    
    public function get_expires() {
        return $this->data['expires'];
    }
    
    public function get_last_modified() {
        return $this->data['last_modified'];
    }
    
    public function get_injected_css_urls() {
        return $this->data['injected_css_urls'];
    }
    
    public function get_injected_js_urls_and_inline() {
        return $this->data['injected_js_urls_and_inline'];
    }
    
    public function get_inline_jquery_scripts() {
        return $this->data['inline_jquery_scripts'];
    }
    
    public function invalidate_instance() {
        return $this->cache->delete($this->get_key());
    }
    
    /**
     * Purge one learning object with all language variants from the cache.
     * @param int $learning_object_id
     * @return int the number of items successfully deleted from the cache
     */
    public static function invalidate_exercise_all_lang($learning_object_id) {
        $cache = \cache::make(\mod_astra_exercise_round::MODNAME, self::CACHE_API_AREA);
        $keys = array();
        
        $languages = array_keys(get_string_manager()->get_list_of_translations());
        
        foreach ($languages as $lang) {
            $keys[] = self::key($learning_object_id, $lang);
        }
        
        return $cache->delete_many($keys);
    }
    
    /**
     * Purge all learning objects in the given course from the cache.
     * @param int $courseid
     * @return int the number of items successfully removed from the cache
     */
    public static function invalidate_course($courseid) {
        global $DB;
        
        $category_ids = array_keys(\mod_astra_category::getCategoriesInCourse($courseid, true));
        if (empty($category_ids)) {
            return 0; // no categories, no learning objects in the course, no cache to clear
        }
        
        $cache = \cache::make(\mod_astra_exercise_round::MODNAME, self::CACHE_API_AREA);
        $keys = array();
        
        // all learning objects in the course
        $learning_object_ids = $DB->get_records_sql(
                'SELECT id FROM {'. \mod_astra_learning_object::TABLE.'} lob 
                 WHERE lob.categoryid IN ('. implode(',', $category_ids) .')');
        // returns an array of stdClass, objects have field id, array_map takes the ids out
        $learning_object_ids = array_map(function($record) {
            return $record->id;
        }, $learning_object_ids);
        // all languages that the course may use (languages enabled in the Moodle site)
        $languages = array_keys(get_string_manager()->get_list_of_translations());
        // language codes, e.g., en for English
        
        foreach ($learning_object_ids as $lobjid) {
            foreach ($languages as $lang) {
                $keys[] = self::key($lobjid, $lang);
            }
        }
        
        return $cache->delete_many($keys);
    }
    
    /**
     * Purge the whole cache (all exercise caches for all courses).
     * Not recommended: the underlying cache store may purge other caches too
     * (other than these exercise caches).
     * @return boolean success
     */
    public static function invalidate_all() {
        $cache = \cache::make(\mod_astra_exercise_round::MODNAME, self::CACHE_API_AREA);
        return $cache->purge();
    }
}
