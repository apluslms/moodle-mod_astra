<?php
namespace mod_stratumtwo\output;

defined('MOODLE_INTERNAL') || die;

/**
 * Class that can be used as a callable (lambda) in Mustache templates:
 * convert a file size in bytes (integer) to a human-readable string.
 * Pass an instance of this class to the Mustache context.
 * In the template, you must supply one argument to the callable: the file size.
 * 
 * Example
 * Preparing context variables before rendering:
 * $context->fileSizeFormatter = new \mod_stratumtwo\output\file_size_formatter();
 * $context->filesize = 1024;
 * 
 * In the Mustache template:
 * {{# fileSizeFormatter }}{{ filesize }}{{/ fileSizeFormatter }}
 */
class file_size_formatter {
    
    /**
     * Create a new instance.
     */
    public function __construct() {
    }
    
    public function __invoke($fileSize, $mustacheHelper) {
        // the file size must be rendered to get the integer, otherwise it is a string like '{{ size }}'
        return self::humanreadable_bytes($mustacheHelper->render($fileSize));
    }
    
    /** Return human-readable string of the bytes, using an appropriate unit prefix.
     * (kilo, mega, ...) (binary prefix, kilo = 1024)
     * @param int $bytes bytes to convert
     * @param int $decimals number of decimals in the result
     * @return string human-readable bytes
     */
    public static function humanreadable_bytes($bytes, $decimals = 2) {
        // modified from source: http://php.net/manual/en/function.filesize.php#106569 user notes by rommel at rommelsantor dot com
        $sz = 'BKMGTP';
        $factor = (int) \floor((\strlen("$bytes") - 1) / 3);
        if ($factor > 5) {
            $factor = 5; // $sz index out of bounds
        }
        $suffix = '';
        if ($factor > 0) {
            $suffix = 'B'; // B after the kilo/mega/...
        }
        return \sprintf("%.{$decimals}f ", $bytes / \pow(1024, $factor)) . $sz[$factor] . $suffix;
    }
}