<?php

/**
 * Defines the version and other meta-info about the plugin
 *
 * Setting the $plugin->version to 0 prevents the plugin from being installed.
 * See https://docs.moodle.org/dev/version.php for more info.
 *
 * @package    mod_astra
 * @copyright  2017 Aalto SCI CS dept.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'mod_astra';
$plugin->version = 2021010400;
$plugin->release = 'v1.10.1';
$plugin->requires = 2020061500; // Moodle 3.9
$plugin->maturity = MATURITY_STABLE;
$plugin->dependencies = array(
        'theme_boost' => 2018120300, // so that the Bootstrap 4 framework is available
);
