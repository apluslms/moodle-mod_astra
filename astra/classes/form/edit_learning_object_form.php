<?php

namespace mod_astra\form;

defined('MOODLE_INTERNAL') || die();
require_once("$CFG->libdir/formslib.php");

/**
 * Abstract base class form for editing or creating a new learning object.
 */
abstract class edit_learning_object_form extends \moodleform {

    protected $exround;
    protected $editObjectId;
    protected $courseid;
    
    /**
     * Constructor.
     * @param \mod_astra_exercise_round $exround current exercise round of an existing learning object or
     * the round which a new learning object is going to be added to (can be changed in the form).
     * @param int $editObjectId learning object ID if editing, zero if creating new
     * @param string $action form action URL
     */
    public function __construct(\mod_astra_exercise_round $exround, $editObjectId = 0, $action = null) {
        $this->exround = $exround;
        $this->editObjectId = $editObjectId;
        $this->courseid = $exround->getCourse()->courseid;
        parent::__construct($action); // calls definition()
    }
    
    public function definition() {
        $mform = $this->_form;
        
        // name
        $mform->addElement('text', 'name', \get_string('exercisename', \mod_astra_exercise_round::MODNAME));
        $mform->setType('name', PARAM_NOTAGS);
        $mform->addHelpButton('name', 'exercisename', \mod_astra_exercise_round::MODNAME);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', null, 'maxlength', 255, 'client');
        
        // status
        $mform->addElement('select', 'status', \get_string('status', \mod_astra_exercise_round::MODNAME),
                array(
                        \mod_astra_learning_object::STATUS_READY => \get_string('statusready', \mod_astra_exercise_round::MODNAME),
                        \mod_astra_learning_object::STATUS_HIDDEN => \get_string('statushidden', \mod_astra_exercise_round::MODNAME),
                        \mod_astra_learning_object::STATUS_MAINTENANCE => \get_string('statusmaintenance', \mod_astra_exercise_round::MODNAME),
                        \mod_astra_learning_object::STATUS_UNLISTED => \get_string('statusunlisted', \mod_astra_exercise_round::MODNAME),
                ));
        $mform->addRule('status', null, 'required', null, 'client');
        
        // category
        $cats = \mod_astra_category::getCategoriesInCourse($this->courseid, true);
        $catChoices = array();
        foreach ($cats as $cat) {
            $catChoices[$cat->getId()] = $cat->getName();
        }
        $mform->addElement('select', 'categoryid', \get_string('category', \mod_astra_exercise_round::MODNAME),
                $catChoices);
        $mform->addRule('categoryid', null, 'required', null, 'client');
        
        // exercise round
        $rounds = \mod_astra_exercise_round::getExerciseRoundsInCourse($this->courseid, true);
        $roundChoices = array();
        foreach ($rounds as $round) {
            $roundChoices[$round->getId()] = $round->getName();
        }
        $mform->addElement('select', 'roundid', \get_string('exerciseround', \mod_astra_exercise_round::MODNAME),
                $roundChoices);
        $mform->addRule('roundid', null, 'required', null, 'client');
        $mform->setDefault('roundid', $this->exround->getId());
        
        // parent id, optional (can only choose a learning object from the current round)
        $exercises = $this->exround->getLearningObjects(true);
        $parentChoices = array(0 => '---');
        foreach ($exercises as $ex) {
            if ($ex->getId() != $this->editObjectId) { // parent must not be the exercise itself
                $parentChoices[$ex->getId()] = $ex->getName();
            }
        }
        $mform->addElement('select', 'parentid', \get_string('parentexercise', \mod_astra_exercise_round::MODNAME),
                $parentChoices);
        
        // order amongst exercises in the round
        $mform->addElement('text', 'ordernum', \get_string('ordernum', \mod_astra_exercise_round::MODNAME));
        $mform->setType('ordernum', \PARAM_INT);
        $mform->addHelpButton('ordernum', 'ordernum', \mod_astra_exercise_round::MODNAME);
        $mform->addRule('ordernum', null, 'required', null, 'client');
        $mform->addRule('ordernum', null, 'maxlength', 4, 'client');
        $mform->addRule('ordernum', null, 'numeric', null, 'client');
        
        // remote key (URL component in A+)
        $mform->addElement('text', 'remotekey', \get_string('remotekey', \mod_astra_exercise_round::MODNAME));
        $mform->setType('remotekey', \PARAM_NOTAGS);
        $mform->addHelpButton('remotekey', 'remotekey', \mod_astra_exercise_round::MODNAME);
        $mform->addRule('remotekey', null, 'required', null, 'client');
        $mform->addRule('remotekey', null, 'maxlength', 128, 'client');
        
        // service URL
        $mform->addElement('text', 'serviceurl', \get_string('serviceurl', \mod_astra_exercise_round::MODNAME));
        $mform->setType('serviceurl', \PARAM_NOTAGS);
        $mform->addHelpButton('serviceurl', 'serviceurl', \mod_astra_exercise_round::MODNAME);
        $mform->addRule('serviceurl', null, 'required', null, 'client');
        $mform->addRule('serviceurl', null, 'maxlength', 255, 'client');
        
        // child class should add other fields and action buttons after calling parent::definition()
    }
    
    function validation($data, $files) {
        global $DB;
    
        $errors = parent::validation($data, $files);
    
        // cannot change the round and parent at the same time: 
        // parent must be in the same round as this learning object
        $newExround = \mod_astra_exercise_round::createFromId($data['roundid']);
        $exercises = $newExround->getLearningObjects(true);
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
            $errors['parentid'] = \get_string('parentexinwronground', \mod_astra_exercise_round::MODNAME);
        }
    
        // remotekey should be unique in all learning objects in the course
        // first fetch all exercises/chapters with the same remotekey, check course in the loop
        $exerciseRecords = $DB->get_records_sql(
                \mod_astra_learning_object::getSubtypeJoinSQL(\mod_astra_exercise::TABLE) .
                ' WHERE lob.remotekey = ?',
                array($data['remotekey']));
        $chapterRecords = $DB->get_records_sql(
                \mod_astra_learning_object::getSubtypeJoinSQL(\mod_astra_chapter::TABLE) .
                ' WHERE lob.remotekey = ?',
                array($data['remotekey']));
        $lobjRecords = \array_merge($exerciseRecords, $chapterRecords);
        foreach ($lobjRecords as $exrecord) {
            if (isset($exrecord->maxsubmissions)) {
                $otherObject = new \mod_astra_exercise($exrecord);
            } else {
                $otherObject = new \mod_astra_chapter($exrecord);
            }
            if ($otherObject->getId() != $this->editObjectId &&
                    $otherObject->getExerciseRound()->getCourse()->courseid == $this->courseid) {
                $errors['remotekey'] = \get_string('duplicateexerciseremotekey', \mod_astra_exercise_round::MODNAME);
                break;
            }
        }
    
        return $errors;
    }
}