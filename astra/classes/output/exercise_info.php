<?php
namespace mod_astra\output;

defined('MOODLE_INTERNAL') || die;

/**
 * Page for the exercise info box (HTML fragment, not a complete document)
 * for AJAX requests.
 */
class exercise_info implements \renderable, \templatable {
    
    protected $learningObject;
    protected $exerciseSummary; // if the learning object is an exercise
    protected $user;
    
    public function __construct(\mod_astra_learning_object $learningObject,
            \stdClass $user) {
        $this->learningObject = $learningObject;
        $this->user = $user;
        if ($learningObject->isSubmittable()) {
            $this->exerciseSummary = new \mod_astra\summary\user_exercise_summary($learningObject, $user);
        } else {
            $this->exerciseSummary = null;
        }
    }
    
    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @return stdClass
     */
    public function export_for_template(\renderer_base $output) {
        $data = new \stdClass();
        
        if ($this->learningObject->isSubmittable()) {
            $data->exercise = $this->learningObject->getExerciseTemplateContext($this->user, true, true);
            $data->summary = $this->exerciseSummary->getTemplateContext();
        } else {
            $data->exercise = $this->learningObject->getTemplateContext(true);
            $data->summary = null;
        }
        
        $data->toDateStr = new \mod_astra\output\date_to_string();
        
        return $data;
    }
}
