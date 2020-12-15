<?php

/**
 * This file keeps track of upgrades to the astra module.
 *
 * Sometimes, changes between versions involve alterations to database
 * structures and other major things that may break installations. The upgrade
 * function in this file will attempt to perform all the necessary actions to
 * upgrade your older installation to the current version. If there's something
 * it cannot do itself, it will tell you what you need to do.  The commands in
 * here will all be database-neutral, using the functions defined in DLL libraries.
 *
 * @package    mod_astra
 * @copyright  2016 Aalto SCI CS dept.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute astra upgrade from the given old version.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_astra_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager(); // Loads ddl manager and xmldb classes.

    /*
     * And upgrade begins here. For each one, you'll need one
     * block of code similar to the next one. Please, delete
     * this comment lines once this file start handling proper
     * upgrade code.
     *
     * if ($oldversion < YYYYMMDD00) { //New version in version.php
     * }
     *
     * For each upgrade block, the file astra/version.php
     * needs to be updated . Such change allows Moodle to know
     * that this file has to be processed.
     *
     * To know more about how to write correct DB upgrade scripts it's
     * highly recommended to read information available at:
     *   http://docs.moodle.org/en/Development:XMLDB_Documentation
     * and to play with the XMLDB Editor (in the admin menu) and its
     * PHP generation possibilities.
     *
     */

    if ($oldversion < 2017091900) {

        // Define field usewidecolumn to be added to astra_lobjects.
        $table = new xmldb_table('astra_lobjects');
        $field = new xmldb_field('usewidecolumn', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'serviceurl');
        
        // Conditionally launch add field usewidecolumn.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
        // Astra savepoint reached.
        upgrade_mod_savepoint(true, 2017091900, 'astra');
    }
    
    if ($oldversion < 2018080900) {
        
        // Define field lang to be added to astra_course_settings.
        $table = new xmldb_table('astra_course_settings');
        $field = new xmldb_field('lang', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, 'en', 'contentnumbering');
        
        // Conditionally launch add field usewidecolumn.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
        // Astra savepoint reached.
        upgrade_mod_savepoint(true, 2018080900, 'astra');
    }

    if ($oldversion < 2020121100) {
        // This version removes the grade items of Astra exercises from
        // the Moodle gradebook.
        // Moodle does not support changes in the gradebook during
        // the server upgrade.
        // Therefore, the Astra exercise grade items should be removed manually
        // before starting the upgrade. There is a CLI script for that:
        // astra/cli/remove_exercise_gradeitems.php.

        // Define field gradeitemnumber to be dropped from astra_exercises.
        $table = new xmldb_table('astra_exercises');
        $field = new xmldb_field('gradeitemnumber');

        // Conditionally launch drop field gradeitemnumber.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Astra savepoint reached.
        upgrade_mod_savepoint(true, 2020121100, 'astra');
    }

    /*
     * Finally, return of upgrade result (true, all went good) to Moodle.
     */
    return true;
}
