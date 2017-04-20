<?php

/**
 * Astra module admin settings and defaults
 *
 * @package   mod_astra
 * @copyright 2017 Aalto SCI CS dept.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    // secret key is used to generate keyed hash values for the asynchronous exercise service API
    $settings->add(new admin_setting_configtext(
            mod_astra_exercise_round::MODNAME . '/secretkey', // name
            get_string('asyncsecretkey', mod_astra_exercise_round::MODNAME), // visible name
            get_string('asyncsecretkey_help', mod_astra_exercise_round::MODNAME), // description
            \mod_astra_submission::getRandomString(100, true), // default value, generate a new random key
            '/^[[:ascii:]]{50,100}$/', // validation: regular expression, ASCII characters, 50-100 chars long
            80)); // size of the text field
    
    // cURL CA certificate locations, used in HTTPS connections to the exercise service
    $settings->add(new admin_setting_heading(
            mod_astra_exercise_round::MODNAME . '/curl_ca_heading', // name (not really used for a heading)
            get_string('cacertheading', mod_astra_exercise_round::MODNAME), // heading
            get_string('explaincacert', mod_astra_exercise_round::MODNAME))); // information/description
    // CAINFO: a single CA certificate bundle (absolute path to the file)
    $settings->add(new admin_setting_configtext(
            mod_astra_exercise_round::MODNAME . '/curl_cainfo', // name (key for the setting)
            get_string('cainfopath', mod_astra_exercise_round::MODNAME), // visible name
            get_string('cainfopath_help', mod_astra_exercise_round::MODNAME), // description
            null, // default value
            PARAM_RAW, // parameter type for validation, raw: no cleaning besides removing invalid utf-8 characters
            50)); // size of the text field
    
    // CAPATH: directory that contains CA certificates (file names hashed like OpenSSL expects)
    $settings->add(new admin_setting_configtext(
            mod_astra_exercise_round::MODNAME . '/curl_capath', // name (key for the setting)
            get_string('cadirpath', mod_astra_exercise_round::MODNAME), // visible name
            get_string('cadirpath_help', mod_astra_exercise_round::MODNAME), // description
            null, // default value
            PARAM_RAW, // parameter type for validation, raw: no cleaning besides removing invalid utf-8 characters
            50)); // size of the text field
}
