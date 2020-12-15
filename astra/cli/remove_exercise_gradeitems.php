<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This script removes the grade items of Astra exercises in the Moodle gradebook.
 * The grade items of the exercise rounds are preserved. Only one grade item
 * per Astra activity instance is left and that is the exercise round total sum.
 *
 * It is necessary to delete the grade items before upgrading to Moodle 3.9
 * because mod_astra does not support exercise grade items in Moodle 3.9 any
 * longer.
 *
 * @package    mod_astra
 * @subpackage cli
 * @copyright  2020 Markku Riekkinen
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->libdir . '/gradelib.php');

cli_writeln('This script will delete all Astra exercise grade items from the Moodle gradebook.');
cli_writeln('It is necessary to delete those grade items before upgrading to Moodle 3.9.');
cli_writeln('The grade items of Astra exercise ROUNDS will be preserved.');
$input = cli_input('Do you want to continue? (y/n)', 'n');
if ($input !== 'y' && $input !== 'Y') {
    cli_writeln('Aborting...');
    exit(0);
}

// This script assumes that it is run before the Moodle 3.9 upgrade.
// It uses the gradeitemnumber field from the Astra exercises table that
// is removed in the Moodle 3.9 upgrade.
$dbman = $DB->get_manager();
$table = new xmldb_table(mod_astra_exercise::TABLE);
$field = new xmldb_field('gradeitemnumber');
if (!$dbman->field_exists($table, $field)) {
    cli_writeln('The gradeitemnumber field does not exist in the '. mod_astra_exercise::TABLE .' table!');
    cli_writeln('This script does not work after the Moodle 3.9 upgrade.');
    cli_writeln('Aborting. No changes have been made in the database.');
    exit(0);
}

$allexercises = $DB->get_recordset_sql(
    "SELECT lob.id,lob.roundid,ex.gradeitemnumber,exround.course
       FROM {". mod_astra_learning_object::TABLE ."} lob
       JOIN {". mod_astra_exercise::TABLE ."} ex ON lob.id = ex.lobjectid
       JOIN {". mod_astra_exercise_round::TABLE ."} exround ON lob.roundid = exround.id"
);
$successcounter = 0;
$failures = array();
foreach($allexercises as $ex) {
    $res = grade_update(
        'mod/' . mod_astra_exercise_round::TABLE,
        $ex->course,
        'mod',
        mod_astra_exercise_round::TABLE,
        $ex->roundid,
        $ex->gradeitemnumber,
        null,
        array('deleted' => 1)
    );
    if ($res === GRADE_UPDATE_OK) {
        $successcounter += 1;
    } else {
        $failures[] = array(
            'course' => $ex->course,
            'instance' => $ex->roundid,
            'itemnumber' => $ex->gradeitemnumber
        );
    }
}
$allexercises->close();

cli_writeln('Successfully deleted '. $successcounter .' Astra exercise grade items.');
if (!empty($failures)) {
    cli_writeln('Removal of the following Astra exercise grade items FAILED:');
    foreach ($failures as $tuple) {
        $courseid = $tuple['course'];
        $instance = $tuple['instance'];
        $itemnumber = $tuple['itemnumber'];
        cli_writeln('Course ID: '. $courseid .', Astra instance ID: '. $instance .', grade item number: '. $itemnumber);
    }
}
exit(0);

