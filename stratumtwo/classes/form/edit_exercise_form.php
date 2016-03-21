<?php

namespace mod_stratumtwo\form;

defined('MOODLE_INTERNAL') || die();
require_once("$CFG->libdir/formslib.php");

/**
 * Form for editing or creating a new exercise.
 */
class edit_exercise_form extends \moodleform {

    protected $exround;
    protected $editExerciseId;
    protected $courseid;
    
    /**
     * Constructor.
     * @param \mod_stratumtwo_exercise_round $exround current exercise round of an existing exercise or
     * the round which a new exercise is going to be added to (can be changed in the form).
     * @param int $editExerciseId ID of the exercise if editing, zero if creating new
     * @param string $action form action URL
     */
    public function __construct(\mod_stratumtwo_exercise_round $exround, $editExerciseId = 0, $action = null) {
        $this->exround = $exround;
        $this->editExerciseId = $editExerciseId;
        $this->courseid = $exround->getCourse()->courseid;
        parent::__construct($action); // calls definition()
    }
    
    public function definition() {
        $mform = $this->_form;
        
        // name
        $mform->addElement('text', 'name', \get_string('exercisename', \mod_stratumtwo_exercise_round::MODNAME));
        $mform->setType('name', PARAM_NOTAGS);
        $mform->addHelpButton('name', 'exercisename', \mod_stratumtwo_exercise_round::MODNAME);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', null, 'maxlength', 255, 'client');
        
        // status
        $mform->addElement('select', 'status', \get_string('status', \mod_stratumtwo_exercise_round::MODNAME),
            array(
                \mod_stratumtwo_exercise::STATUS_READY => \get_string('statusready', \mod_stratumtwo_exercise_round::MODNAME),
                \mod_stratumtwo_exercise::STATUS_HIDDEN => \get_string('statushidden', \mod_stratumtwo_exercise_round::MODNAME),
                \mod_stratumtwo_exercise::STATUS_MAINTENANCE => \get_string('statusmaintenance', \mod_stratumtwo_exercise_round::MODNAME),
            ));
        $mform->addRule('status', null, 'required', null, 'client');
        
        // category
        $cats = \mod_stratumtwo_category::getCategoriesInCourse($this->courseid);
        $catChoices = array();
        foreach ($cats as $cat) {
            $catChoices[$cat->getId()] = $cat->getName();
        }
        $mform->addElement('select', 'categoryid', \get_string('category', \mod_stratumtwo_exercise_round::MODNAME),
                $catChoices);
        $mform->addRule('categoryid', null, 'required', null, 'client');
        
        // exercise round
        $rounds = \mod_stratumtwo_exercise_round::getExerciseRoundsInCourse($this->courseid);
        $roundChoices = array();
        foreach ($rounds as $round) {
            $roundChoices[$round->getId()] = $round->getName();
        }
        $mform->addElement('select', 'roundid', \get_string('exerciseround', \mod_stratumtwo_exercise_round::MODNAME),
                $roundChoices);
        $mform->addRule('roundid', null, 'required', null, 'client');
        $mform->setDefault('roundid', $this->exround->getId());
        
        // parent id, optional (can only choose an exercise from the current round)
        $exercises = $this->exround->getExercises(true);
        $parentChoices = array(0 => '---');
        foreach ($exercises as $ex) {
            if ($ex->getId() != $this->editExerciseId) { // parent must not be the exercise itself
                $parentChoices[$ex->getId()] = $ex->getName();
            }
        }
        $mform->addElement('select', 'parentid', \get_string('parentexercise', \mod_stratumtwo_exercise_round::MODNAME),
                $parentChoices);

        // order amongst exercises in the round
        $mform->addElement('text', 'ordernum', \get_string('ordernum', \mod_stratumtwo_exercise_round::MODNAME));
        $mform->setType('ordernum', \PARAM_INT);
        $mform->addHelpButton('ordernum', 'ordernum', \mod_stratumtwo_exercise_round::MODNAME);
        $mform->addRule('ordernum', null, 'required', null, 'client');
        $mform->addRule('ordernum', null, 'maxlength', 4, 'client');
        $mform->addRule('ordernum', null, 'numeric', null, 'client');
        
        // remote key (URL component in A+)
        $mform->addElement('text', 'remotekey', \get_string('remotekey', \mod_stratumtwo_exercise_round::MODNAME));
        $mform->setType('remotekey', \PARAM_NOTAGS);
        $mform->addHelpButton('remotekey', 'remotekey', \mod_stratumtwo_exercise_round::MODNAME);
        $mform->addRule('remotekey', null, 'required', null, 'client');
        $mform->addRule('remotekey', null, 'maxlength', 128, 'client');
        
        // service URL
        $mform->addElement('text', 'serviceurl', \get_string('serviceurl', \mod_stratumtwo_exercise_round::MODNAME));
        $mform->setType('serviceurl', \PARAM_NOTAGS);
        $mform->addHelpButton('serviceurl', 'serviceurl', \mod_stratumtwo_exercise_round::MODNAME);
        $mform->addRule('serviceurl', null, 'required', null, 'client');
        $mform->addRule('serviceurl', null, 'maxlength', 255, 'client');
        
        // max points
        $mform->addElement('text', 'maxpoints', \get_string('maxpoints', \mod_stratumtwo_exercise_round::MODNAME));
        $mform->setType('maxpoints', PARAM_INT);
        $mform->addHelpButton('maxpoints', 'maxpoints', \mod_stratumtwo_exercise_round::MODNAME);
        $mform->addRule('maxpoints', null, 'numeric', null, 'client');
        $mform->addRule('maxpoints', null, 'required', null, 'client');
        $mform->addRule('maxpoints', null, 'maxlength', 7, 'client');
        
        // points to pass
        $mform->addElement('text', 'pointstopass', \get_string('pointstopass', \mod_stratumtwo_exercise_round::MODNAME));
        $mform->setType('pointstopass', PARAM_INT);
        $mform->addHelpButton('pointstopass', 'pointstopass', \mod_stratumtwo_exercise_round::MODNAME);
        $mform->addRule('pointstopass', null, 'numeric', null, 'client');
        $mform->addRule('pointstopass', null, 'required', null, 'client');
        $mform->addRule('pointstopass', null, 'maxlength', 7, 'client');
        $mform->setDefault('pointstopass', 0);
        
        // max submissions limit
        $mform->addElement('text', 'maxsubmissions', \get_string('maxsubmissions', \mod_stratumtwo_exercise_round::MODNAME));
        $mform->setType('maxsubmissions', PARAM_INT);
        $mform->addHelpButton('maxsubmissions', 'maxsubmissions', \mod_stratumtwo_exercise_round::MODNAME);
        $mform->addRule('maxsubmissions', null, 'numeric', null, 'client');
        $mform->addRule('maxsubmissions', null, 'required', null, 'client');
        $mform->addRule('maxsubmissions', null, 'maxlength', 4, 'client');
        $mform->setDefault('maxsubmissions', 10);
        
        // Allow assistant grading?
        // 4th argument is the label displayed after checkbox, 5th arg: HTML attributes,
        // 6th: unchecked/checked values
        //TODO remove if only Moodle access control is used, teacher must promote other users, this setting does not fit into Moodle access control
        $mform->addElement('advcheckbox', 'allowastgrading',
                \get_string('allowastgrading', \mod_stratumtwo_exercise_round::MODNAME),
                '', null, array(0, 1));
        $mform->addHelpButton('allowastgrading', 'allowastgrading', \mod_stratumtwo_exercise_round::MODNAME);
        
        $this->add_action_buttons(true);
    }
    
    function validation($data, $files) {
        global $DB;
        
        $errors = parent::validation($data, $files);
        
        if ($data['pointstopass'] !== '' && $data['pointstopass'] < 0) {
            $errors['pointstopass'] = \get_string('negativeerror', \mod_stratumtwo_exercise_round::MODNAME);
        }
        if ($data['maxpoints'] !== '' && $data['maxpoints'] < 0) {
            $errors['maxpoints'] = \get_string('negativeerror', \mod_stratumtwo_exercise_round::MODNAME);
        }
        if ($data['maxsubmissions'] !== '' && $data['maxsubmissions'] < 0) {
            $errors['maxsubmissions'] = \get_string('negativeerror', \mod_stratumtwo_exercise_round::MODNAME);
        }
        
        // cannot change the round and parent at the same time: parent must be in the same round as this exercise
        $newExround = \mod_stratumtwo_exercise_round::createFromId($data['roundid']);
        $exercises = $newExround->getExercises(true);
        $parentInSameRound = false;
        if ($data['parentid']) {
            foreach ($exercises as $ex) {
                if ($ex->getId() == $data['parentid']) {
                    $parentInSameRound = true;
                    break;
                }
            }
        } else {
            $parentInSameRound = true;
        }
        if (!$parentInSameRound) {
            $errors['parentid'] = \get_string('parentexinwronground', \mod_stratumtwo_exercise_round::MODNAME);
        }
        
        // remotekey should be unique in all exercises in the course
        $exerciseRecords = $DB->get_records(\mod_stratumtwo_exercise::TABLE, array('remotekey' => $data['remotekey']));
        foreach ($exerciseRecords as $exrecord) {
            $otherExercise = new \mod_stratumtwo_exercise($exrecord);
            if ($exrecord->id != $this->editExerciseId && 
                    $otherExercise->getExerciseRound()->getCourse()->courseid == $this->courseid) {
                $errors['remotekey'] = \get_string('duplicateexerciseremotekey', \mod_stratumtwo_exercise_round::MODNAME);
                break;
            }
        }
        
        return $errors;
    }
}