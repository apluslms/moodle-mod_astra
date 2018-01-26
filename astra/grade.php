<?php

/**
 * Redirect the user to the appropriate submission related page
 *
 * @package   mod_astra
 * @category  grade
 * @copyright 2016 Aalto SCI CS dept.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '../../../config.php');

$id = required_param('id', PARAM_INT); // Course module ID.
// item number separates the grade items of the round and its exercises
$itemnumber = optional_param('itemnumber', 0, PARAM_INT);
$userid = optional_param('userid', 0, PARAM_INT); // Graded user ID (optional).

list($course, $cm) = get_course_and_cm_from_cmid($id, mod_astra_exercise_round::TABLE);
$exround = mod_astra_exercise_round::createFromId($cm->instance);

if ($itemnumber == 0) {
    // exercise round
    redirect(\mod_astra\urls\urls::exerciseRound($exround, true));
} else {
    $exerciseRecord = $DB->get_record_sql(
            mod_astra_learning_object::getSubtypeJoinSQL(mod_astra_exercise::TABLE) .
            ' WHERE lob.roundid = ? AND ex.gradeitemnumber = ?',
            array($exround->getId(), $itemnumber), MUST_EXIST);
    $exercise = new mod_astra_exercise($exerciseRecord);
    $context = context_module::instance($id);
    if ((has_capability('mod/astra:viewallsubmissions', $context) && $exercise->isAssistantViewingAllowed()) ||
            has_capability('mod/astra:addinstance', $context)) {
        redirect(\mod_astra\urls\urls::submissionList($exercise, true));
    } else {
        redirect(\mod_astra\urls\urls::exercise($exercise, true, false));
    }
}
