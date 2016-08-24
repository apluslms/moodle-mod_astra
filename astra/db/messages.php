<?php

defined('MOODLE_INTERNAL') || die();

/**
 * Defines message providers (types of messages being sent)
 *
 * @package mod_stratumtwo
 * @copyright 2016 Aalto SCI CS dept.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$messageproviders = array(
    // notification that an assistant has given new feedback to a submission
    'assistant_feedback_notification' => array(
            // no capability required - all users may receive a message of this type
    ),
);
