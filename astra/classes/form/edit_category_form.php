<?php

namespace mod_astra\form;

defined('MOODLE_INTERNAL') || die();
require_once("$CFG->libdir/formslib.php");

/**
 * Form for editing or creating a new category.
 */
class edit_category_form extends \moodleform {

    protected $courseid;
    protected $categoryId;
    
    /**
     * Constructor.
     * @param int $courseid Moodle course ID of the category
     * @param int $editCategoryId ID of the category if editing, zero if creating new
     * @param string $action form action URL
     */
    public function __construct($courseid, $editCategoryId = 0, $action = null) {
        $this->courseid = $courseid;
        $this->categoryId = $editCategoryId;
        parent::__construct($action); // calls definition()
    }
    
    public function definition() {
        $mform = $this->_form;
        
        $mform->addElement('select', 'status', \get_string('status', \mod_astra_exercise_round::MODNAME),
            array(
                \mod_astra_category::STATUS_READY => \get_string('statusready', \mod_astra_exercise_round::MODNAME),
                \mod_astra_category::STATUS_HIDDEN => \get_string('statushidden', \mod_astra_exercise_round::MODNAME),
                \mod_astra_category::STATUS_NOTOTAL => \get_string('statusnototal', \mod_astra_exercise_round::MODNAME),
            ));
        $mform->addRule('status', null, 'required', null, 'client');
        
        $mform->addElement('text', 'name', get_string('categoryname', \mod_astra_exercise_round::MODNAME));
        $mform->setType('name', PARAM_NOTAGS);
        $mform->addHelpButton('name', 'categoryname', \mod_astra_exercise_round::MODNAME);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', null, 'maxlength', 35, 'client');
        
        $mform->addElement('text', 'pointstopass', get_string('pointstopass', \mod_astra_exercise_round::MODNAME));
        $mform->setType('pointstopass', PARAM_INT);
        $mform->addHelpButton('pointstopass', 'pointstopass', \mod_astra_exercise_round::MODNAME);
        $mform->addRule('pointstopass', null, 'numeric', null, 'client');
        $mform->addRule('pointstopass', null, 'required', null, 'client');
        $mform->addRule('pointstopass', null, 'maxlength', 7, 'client');
        $mform->setDefault('pointstopass', 0);
        
        $this->add_action_buttons(true);
    }
    
    function validation($data, $files) {
        global $DB;
        
        $errors = parent::validation($data, $files);
        
        if ($data['pointstopass'] !== '' && $data['pointstopass'] < 0) {
            $errors['pointstopass'] = \get_string('negativeerror', \mod_astra_exercise_round::MODNAME);
        }
        
        foreach (\mod_astra_category::getCategoriesInCourse($this->courseid, true) as $cat) {
            if ($cat->getId() != $this->categoryId && $data['name'] == $cat->getName()) {
                $errors['name'] = \get_string('duplicatecatname', \mod_astra_exercise_round::MODNAME);
                break;
            }
        }
        
        return $errors;
    }
}