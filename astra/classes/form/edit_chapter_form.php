<?php

namespace mod_stratumtwo\form;

defined('MOODLE_INTERNAL') || die();
require_once("$CFG->libdir/formslib.php");

/**
 * Form for editing or creating a new chapter.
 */
class edit_chapter_form extends \mod_stratumtwo\form\edit_learning_object_form {

    public function definition() {
        $mform = $this->_form;
        
        parent::definition();
        
        // Generate table of contents of the exercise round to the start of the chapter page?
        // 4th argument is the label displayed after checkbox, 5th arg: HTML attributes,
        // 6th: unchecked/checked values
        $mform->addElement('advcheckbox', 'generatetoc',
                \get_string('generatetoc', \mod_stratumtwo_exercise_round::MODNAME),
                '', null, array(0, 1));
        $mform->addHelpButton('generatetoc', 'generatetoc', \mod_stratumtwo_exercise_round::MODNAME);
        
        $this->add_action_buttons(true);
    }
    
}