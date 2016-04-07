<?php

/**
 * Redirect the user to the appropriate submission related page
 *
 * @package   mod_stratumtwo
 * @category  grade
 * @copyright 2016 Aalto SCI CS dept.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . "../../../config.php");

$id = required_param('id', PARAM_INT);// Course module ID.
// Item number may be != 0 for activities that allow more than one grade per user.
$itemnumber = optional_param('itemnumber', 0, PARAM_INT);
$userid = optional_param('userid', 0, PARAM_INT); // Graded user ID (optional).

// In the simplest case just redirect to the view page.
redirect('view.php?id='.$id);
//TODO round or exercise page, teachers to inspect page
