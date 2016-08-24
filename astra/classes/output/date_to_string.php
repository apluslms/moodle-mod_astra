<?php
namespace mod_stratumtwo\output;

defined('MOODLE_INTERNAL') || die;

/**
 * Class that can be used as a callable (lambda) in Mustache templates:
 * convert a Unix timestamp (integer) to a date string.
 * Pass an instance of this class to the Mustache context.
 * In the template, you must supply one argument to the callable: the timestamp.
 * 
 * Example
 * Preparing context variables before rendering:
 * $context->toDateStr = new \mod_stratumtwo\output\date_to_string();
 * $context->timestamp = time();
 * 
 * In the Mustache template:
 * {{# toDateStr }}{{ timestamp }}{{/ toDateStr }}
 */
class date_to_string {
    
    protected $format;
    
    /**
     * Create a new instance.
     * @param string $format Format string to the PHP date function
     */
    public function __construct($format = 'r') {
        $this->format = $format;
    }
    
    public function __invoke($timestamp, $mustacheHelper) {
        // the timestamp must be rendered to get the integer, otherwise it is a string like '{{ date }}'
        return \date($this->format, $mustacheHelper->render($timestamp));
    }
}