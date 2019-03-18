<?php

defined('MOODLE_INTERNAL') || die;

/** Local settings defined with constants.
 * These are not expected to change after the plugin installation so
 * they are not saved in the Moodle configuration database.
 */

define('ASTRA_REMOTE_PAGE_HOSTS_MAP', array());

define('ASTRA_OVERRIDE_SUBMISSION_HOST', null);

// Development and testing settings
//define('ASTRA_REMOTE_PAGE_HOSTS_MAP', array(
//    'grader:8080' => 'localhost:8080',
//));

//define('ASTRA_OVERRIDE_SUBMISSION_HOST', 'http://moodle');
