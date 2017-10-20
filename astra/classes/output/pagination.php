<?php
namespace mod_astra\output;

defined('MOODLE_INTERNAL') || die;

/**
 * Class that can be used as a callable (lambda) in Mustache templates:
 * print the pagination component.
 * Pass an instance of this class to the Mustache context.
 * 
 * Example:
 * Preparing context variables before rendering:
 * $context->paginator = new \mod_astra\output\pagination(0, 10, $pageUrlGenerator);
 * 
 * In the Mustache template:
 * {{# paginator }}{{/ paginator }}
 */
class pagination {
    
    // at most how many page links can be shown in the pagination component simultaneously
    // (actually, the real max is sometimes (this + 1))
    const MAX_PAGES_DISPLAY = 10;
    
    protected $output;
    
    /**
     * Create a new instance.
     * @param int $activePage zero-based index of the current active page
     * @param int $numPages total number of pages
     * @param callable $pageUrlGenerator callable that takes one argument,
     *        the zero-based page index, and returns the URL (string) for opening that page
     */
    public function __construct($activePage, $numPages, callable $pageUrlGenerator) {
        $this->output = $this->generate($activePage, $numPages, $pageUrlGenerator);
    }
    
    protected function generate($activePage, $numPages, callable $pageUrlGenerator) {
        if ($numPages <= 1) {
            return '';
        }
        // Bootstrap 4 pagination component
        $out = '<nav aria-label="'. get_string('searchresults', \mod_astra_exercise_round::MODNAME)
             . '"><ul class="pagination justify-content-center">';
        // Note: class justify-content-center is not available in the old Bootstrap 4 alpha version used in Moodle 3.2
        
        // the previous link is disabled if the current page is the first one
        $prevEnabled = $activePage > 0;
        $out .= '<li class="page-item'. (!$prevEnabled ? ' disabled' : '') .'">';
        if ($prevEnabled) {
            $out .= '<a class="page-link" href="'. $pageUrlGenerator($activePage - 1)
                 . '" aria-label="'. get_string('previous', \mod_astra_exercise_round::MODNAME) .'">';
        } else {
            $out .= '<span class="page-link">';
        }
        $out .= '<span aria-hidden="true">&laquo;</span>'
             . '<span class="sr-only">'. get_string('previous', \mod_astra_exercise_round::MODNAME)
             . '</span>';
        if ($prevEnabled) {
            $out .= '</a>';
        } else {
            $out .= '</span>';
        }
        $out .= '</li>';
        
        // if there are many pages, only a few of them are visible in the pagination simultaneously
        // the active page must be visible of course
        $startFrom = $activePage - (int) floor(self::MAX_PAGES_DISPLAY / 2);
        $endTo = $activePage + (int) floor(self::MAX_PAGES_DISPLAY / 2);
        if ($startFrom < 0) {
            $startFrom = 0;
            $endTo = self::MAX_PAGES_DISPLAY - 1;
        }
        if ($endTo >= $numPages) {
            $endTo = $numPages - 1;
            $startFrom = $numPages - self::MAX_PAGES_DISPLAY;
            if ($startFrom < 0) {
                $startFrom = 0;
            }
        }
        
        for ($i = $startFrom; $i <= $endTo; ++$i) {
            $out .= '<li class="page-item'. ($activePage === $i ? ' active' : '') .'">';
            $out .= '<a class="page-link" href="'. $pageUrlGenerator($i) .'">'. ($i + 1);
            if ($activePage === $i) {
                $out .= ' <span class="sr-only">'. get_string('currentparen', \mod_astra_exercise_round::MODNAME)
                     . '</span>';
            }
            $out .= '</a>';
            $out .= '</li>';
        }
        
        // the next link is disabled if the current page is the last one
        $nextEnabled = $activePage < ($numPages - 1);
        $out .= '<li class="page-item'. (!$nextEnabled ? ' disabled' : '') .'">';
        if ($nextEnabled) {
            $out .= '<a class="page-link" href="'. $pageUrlGenerator($activePage + 1)
                 . '" aria-label="'. get_string('next', \mod_astra_exercise_round::MODNAME) .'">';
        } else {
            $out .= '<span class="page-link">';
        }
        $out .= '<span aria-hidden="true">&raquo;</span>'
             . '<span class="sr-only">'. get_string('next', \mod_astra_exercise_round::MODNAME)
             . '</span>';
        if ($nextEnabled) {
            $out .= '</a>';
        } else {
            $out .= '</span>';
        }
        $out .= '</li>';
        
        $out .= '</ul></nav>';
        return $out;
    }
    
    public function __invoke() {
        return $this->output;
    }
}