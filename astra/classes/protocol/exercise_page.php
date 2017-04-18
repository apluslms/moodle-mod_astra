<?php
namespace mod_astra\protocol;

defined('MOODLE_INTERNAL') || die;

use \mod_astra_learning_object;
use \stdClass;

/**
 * Simple class that represents an exercise (/learning object) page retrieved
 * from an exercise service. This class gathers scalar data about the page
 * (HTML string content, boolean and integer values from meta data, etc.).
 *
 * Derived from A+ (a-plus/exercise/protocol/exercise_page.py).
 */
class exercise_page {
    
    public $exercise;
    public $content;
    public $meta = array();
    public $is_loaded = false;
    public $is_graded = false;
    public $is_accepted = false;
    public $is_wait = false;
    public $points = 0;
    public $expires = 0;
    public $last_modified = '';
    public $injected_css_urls;
    public $injected_js_urls_and_inline;
    public $inline_jquery_scripts;
    
    
    public function __construct(mod_astra_learning_object $learning_object) {
        $this->exercise = $learning_object;
    }
    
    public function get_template_context() {
        $data = new stdClass();
        $data->content = $this->content;
        return $data;
    }
}
