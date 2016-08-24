<?php
namespace mod_astra\output;

defined('MOODLE_INTERNAL') || die;

class renderer extends \plugin_renderer_base {
    /**
     * Render index.php
     *
     * @param index_page $page
     *
     * @return string html for the page
     */
    protected function render_index_page(\mod_astra\output\index_page $page) {
        $data = $page->export_for_template($this);
        return parent::render_from_template(\mod_astra_exercise_round::MODNAME .'/index_page', $data);
    }
    
    protected function render_exercise_round_page(\mod_astra\output\exercise_round_page $page) {
        $data = $page->export_for_template($this);
        return parent::render_from_template(\mod_astra_exercise_round::MODNAME .'/exercise_round_page', $data);
    }
    
    protected function render_exercise_page(\mod_astra\output\exercise_page $page) {
        $data = $page->export_for_template($this);
        if (isset($data->page->content)) {
            if (isset($data->exercise)) {
                $template = 'exercise_page';
            } else {
                $template = 'chapter_page';
            }
        } else {
            // show a message that the learning object is not available (not open etc.)
            if (isset($data->exercise)) {
                $template = 'exercise_closed_page';
            } else {
                $template = 'chapter_closed_page';
            }
        }
        return parent::render_from_template(\mod_astra_exercise_round::MODNAME ."/$template", $data);
    }
    
    protected function render_submission_page(\mod_astra\output\submission_page $page) {
        $data = $page->export_for_template($this);
        return parent::render_from_template(\mod_astra_exercise_round::MODNAME .'/submission_page', $data);
    }
    
    protected function render_edit_course_page(\mod_astra\output\edit_course_page $page) {
        $data = $page->export_for_template($this);
        return $this->render_from_template(\mod_astra_exercise_round::MODNAME .'/edit_course_page', $data);
    }
    
    protected function render_delete_page(\mod_astra\output\delete_page $page) {
        $data = $page->export_for_template($this);
        return $this->render_from_template(\mod_astra_exercise_round::MODNAME .'/delete_page', $data);
    }
    
    protected function render_inspect_page(\mod_astra\output\inspect_page $page) {
        $data = $page->export_for_template($this);
        return $this->render_from_template(\mod_astra_exercise_round::MODNAME .'/inspect_page', $data);
    }
    
    protected function render_assess_page(\mod_astra\output\assess_page $page) {
        $data = $page->export_for_template($this);
        return $this->render_from_template(\mod_astra_exercise_round::MODNAME .'/assess_page', $data);
    }
    
    protected function render_all_submissions_page(\mod_astra\output\all_submissions_page $page) {
        $data = $page->export_for_template($this);
        return $this->render_from_template(\mod_astra_exercise_round::MODNAME .'/all_submissions_page', $data);
    }
    
    protected function render_deviations_list_page(\mod_astra\output\deviations_list_page $page) {
        $data = $page->export_for_template($this);
        return $this->render_from_template(\mod_astra_exercise_round::MODNAME .'/list_deviations_page', $data);
    }
    
    protected function render_exercise_plain_page(\mod_astra\output\exercise_plain_page $page) {
        $data = $page->export_for_template($this);
        return $this->render_from_template(\mod_astra_exercise_round::MODNAME .'/exercise_plain', $data);
    }
    
    protected function render_export_index_page(\mod_astra\output\export_index_page $page) {
        $data = $page->export_for_template($this);
        return $this->render_from_template(\mod_astra_exercise_round::MODNAME .'/export_index_page', $data);
    }
    
    protected function render_submission_plain_page(\mod_astra\output\submission_plain_page $page) {
        $data = $page->export_for_template($this);
        return $this->render_from_template(\mod_astra_exercise_round::MODNAME .'/submission_plain', $data);
    }
}