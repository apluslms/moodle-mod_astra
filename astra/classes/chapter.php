<?php
defined('MOODLE_INTERNAL') || die();

/**
 * A chapter displays content on a single page and can embed exercises
 * into the content. The content is downloaded from the exercise service.
 * The chapter itself has no grading or submissions associated with it.
 */
class mod_astra_chapter extends mod_astra_learning_object {
    const TABLE = 'astra_chapters';
    
    public function shouldGenerateTableOfContents() {
        return (bool) $this->record->generatetoc;
    }
    
    public function getTemplateContext($includeCourseModule = true) {
        $ctx = parent::getTemplateContext($includeCourseModule);
        $ctx->generate_toc = $this->shouldGenerateTableOfContents();
        return $ctx;
    }
}