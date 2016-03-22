<?php
namespace mod_stratumtwo\output;

defined('MOODLE_INTERNAL') || die;

class renderer extends \plugin_renderer_base {
    /**
     * Render index.php
     *
     * @param index_page $page
     *
     * @return string html for the page
     */
    protected function render_index_page($page) {
        $data = $page->export_for_template($this);
        return parent::render_from_template(\mod_stratumtwo_exercise_round::MODNAME .'/index_page', $data);
    }
    
    protected function render_exercise_round_page(\mod_stratumtwo\output\exercise_round_page $page) {
        $data = $page->export_for_template($this);
        return parent::render_from_template(\mod_stratumtwo_exercise_round::MODNAME .'/exercise_round_page', $data);
    }
    
    protected function render_exercise_page(\mod_stratumtwo\output\exercise_page $page) {
        $data = $page->export_for_template($this);
        if (isset($data->page->content)) {
            return parent::render_from_template(\mod_stratumtwo_exercise_round::MODNAME .'/exercise_page', $data);
        } else {
            return parent::render_from_template(\mod_stratumtwo_exercise_round::MODNAME .'/exercise_closed_page', $data);
        }
    }
    
    protected function render_submission_page(\mod_stratumtwo\output\submission_page $page) {
        $data = $page->export_for_template($this);
        return parent::render_from_template(\mod_stratumtwo_exercise_round::MODNAME .'/submission_page', $data);
    }
    
    protected function render_edit_course_page($page) {
        $data = $page->export_for_template($this);
        return $this->render_from_template(\mod_stratumtwo_exercise_round::MODNAME .'/edit_course_page', $data);
    }
    
    protected function render_delete_page(\mod_stratumtwo\output\delete_page $page) {
        $data = $page->export_for_template($this);
        return $this->render_from_template(\mod_stratumtwo_exercise_round::MODNAME .'/delete_page', $data);
    }
    
    protected function render_inspect_page(\mod_stratumtwo\output\inspect_page $page) {
        $data = $page->export_for_template($this);
        return $this->render_from_template(\mod_stratumtwo_exercise_round::MODNAME .'/inspect_page', $data);
    }
    
}