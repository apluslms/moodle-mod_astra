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
 * This script synchronizes the grade items of Astra exercise rounds in
 * the Moodle gradebook. The old grade items are deleted and they are created
 * again. The grades are read from the existing submissions and the exercise
 * round total grades are written to the gradebook.
 *
 * This script can be run after the Moodle 3.9 upgrade when Astra contains
 * only exercise round grades in the gradebook (no individual exercises).
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

cli_writeln('This script will delete Astra exercise round grade items from');
cli_writeln('the Moodle gradebook and recreate them. The students\' grades will be');
cli_writeln('read from the Astra submissions table and inserted into the gradebook.');
cli_writeln('There are grade items only for the exercise rounds,');
cli_writeln('not for individual exercises.');
$input = cli_input('Do you want to continue? (y/n)', 'n');
if ($input !== 'y' && $input !== 'Y') {
    cli_writeln('Aborting...');
    exit(0);
}

// Fetch all Astra exercise rounds from the database.
$allrounds = $DB->get_recordset(mod_astra_exercise_round::TABLE);
$deleteerrors = array();
$updateerrors = array();
$writeerrors = array();
foreach ($allrounds as $record) {
    $exround = new mod_astra_exercise_round($record);
    $res1 = $exround->deleteGradebookItem();
    $res2 = $exround->updateGradebookItem();
    $res3 = false;
    if ($res2 === GRADE_UPDATE_OK) {
        $res3 = $exround->writeAllGradesToGradebook();
    }

    if ($res1 !== GRADE_UPDATE_OK) {
        $deleteerrors[] = array(
            'course' => $record->course, // Course ID
            'instance' => $record->id,
            'name' => $exround->getName()
        );
    }
    if ($res2 !== GRADE_UPDATE_OK) {
        $updateerrors[] = array(
            'course' => $record->course,
            'instance' => $record->id,
            'name' => $exround->getName()
        );
    }
    if ($res3 !== GRADE_UPDATE_OK) {
        $writeerrors[] = array(
            'course' => $record->course,
            'instance' => $record->id,
            'name' => $exround->getName()
        );
    }
}
$allrounds->close();

$success = empty($deleteerrors) && empty($updateerrors) && empty($writeerrors);

if ($success) {
    cli_writeln('Successfully updated the Astra grades in the gradebook.');
    exit(0);
}

function print_errors($errors, $msg) {
    foreach ($errors as $err) {
        $courseid = $err['course'];
        $instanceid = $err['instance'];
        $name = $err['name'];
        cli_writeln($msg);
        cli_writeln('Course ID: '. $courseid .', Astra instance ID: '. $instanceid .', Astra activity name: '. $name);
    }
}

print_errors($deleteerrors, 'Failed to delete grade item.');
print_errors($updateerrors, 'Failed to update grade item.');
print_errors($writeerrors, 'Failed to write new grades to the grade item.');

exit(0);

