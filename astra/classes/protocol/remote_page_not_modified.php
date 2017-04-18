<?php
namespace mod_astra\protocol;

defined('MOODLE_INTERNAL') || die;

/**
 * Remote page has not been modified since the given timestamp.
 * 
 * Derived from A+ (a-plus/lib/remote_page.py).
 */
class remote_page_not_modified extends \Exception {
    
    protected $expires;
    
    public function __construct($expires = 0, $message = null, $code = null, $previous = null) {
        parent::__construct($message, $code, $previous);
        $this->expires = $expires;
    }
    
    public function expires() {
        return $this->expires;
    }
    
    public function set_expires($expires) {
        $this->expires = $expires;
    }
}