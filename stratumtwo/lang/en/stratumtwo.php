<?php

/**
 * English strings for stratumtwo.
 *
 * @package    mod_stratumtwo
 * @copyright  2016 Aalto SCI CS dept.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['modulename'] = 'Stratum2 exercise round';
$string['modulenameplural'] = 'Stratum2 exercise rounds';
$string['nostratums'] = 'No Stratum2 exercises in this course';
$string['modulename_help'] = 'External Stratum2 exercises (Aalto SCI Dept. of Computer Science)'; // when type is selected for a new activity

$string['stratum'] = 'Stratum';
$string['pluginadministration'] = 'Stratum2 exercises administration';
$string['pluginname'] = 'Stratum2 exercises'; // Used by Moodle core

// mod_form.php
$string['deadline'] = 'Deadline';
$string['roundname'] = 'Exercise round name';
$string['roundname_help'] = 'This is the name of the exercise round that is shown in Moodle.';
$string['note'] = 'Note';
$string['donotusemodform'] = 'You should not use this form with Stratum2 exercises. Instead, edit the exercises from the Stratum2 setup block.';
$string['status'] = 'Status';
$string['statusready'] = 'Ready';
$string['statushidden'] = 'Hidden';
$string['statusmaintenance'] = 'Maintenance';
$string['remotekey'] = 'Stratum2 remote key';
$string['remotekey_help'] = 'Unique exercise round key in the Stratum2 server.';
$string['pointstopass'] = 'Points to pass';
$string['pointstopass_help'] = 'The points the student must earn in order to pass.';
$string['openingtime'] = 'Opening time';
$string['openingtime_help'] = 'Submissions are not allowed before the opening time.';
$string['closingtime'] = 'Closing time';
$string['closingtime_help'] = 'Submissions after the closing time are late and either are prohibited completely or receive penalties to points.';
$string['latesbmsallowed'] = 'Late submissions allowed';
$string['latesbmsallowed_help'] = 'Late submissions can be allowed untill the late submission deadline and they receive point penalties.';
$string['latesbmsdl'] = 'Late submission deadline';
$string['latesbmsdl_help'] = 'Submissions after the closing time and before the late submission deadline receive penalties in points.';
$string['latesbmspenalty'] = 'Late submission penalty';
$string['latesbmspenalty_help'] = 'Multiplier of points to reduce, as decimal. 0.1 = 10%';

// templates
$string['passed'] = 'Passed';
$string['nosubmissions'] = 'No submissions';
$string['requiredpoints'] = '{$a} points required to pass';
$string['requirednotpassed'] = 'Required exercises are not passed';
$string['opens'] = 'Opens';
$string['latealloweduntil'] = 'Late submission are allowed until {$a}';
$string['latepointsworth'] = 'but points are only worth {$a}%.';
$string['pointsrequiredtopass'] = '{$a} points required to pass the module.';
$string['undermaintenance'] = 'Unfortunately this module is currently under maintenance.';
$string['points'] = 'Points';
$string['required'] = 'Required';
$string['coursestaff'] = 'Course staff';
$string['earlyaccess'] = 'Early access';
$string['viewsubmissions'] = 'View submissions';
$string['notopenedyet'] = 'This module has not been opened yet.';
$string['exercisedescription'] = 'Exercise description';
$string['mysubmissions'] = 'My submissions';
$string['nosubmissionsyet'] = 'No submissions yet';
$string['inspectsubmission'] = 'Inspect submission';
$string['viewallsubmissions'] = 'View all submissions';
$string['editexercise'] = 'Edit exercise';
$string['earnedpoints'] = 'Earned points';
$string['late'] = 'Late';
$string['exerciseinfo'] = 'Exercise info';
$string['yoursubmissions'] = 'Your submissions';
$string['pointsrequiredtopass'] = 'Points required to pass';
$string['totalnumberofsubmitters'] = 'Total number of submitters';
$string['statuserror'] = 'Error';
$string['statuswaiting'] = 'Waiting';
$string['statusinitialized'] = 'Initialized';
$string['submissionnumber'] = 'Submission {$a}';
$string['filesinthissubmission'] = 'Files in this submission';
$string['download'] = 'Download';
$string['assistantfeedback'] = 'Assistant feedback';
$string['noassistantfeedback'] = 'No assistant feedback available for this submission.';
$string['nofeedback'] = 'No grader feedback available for this submission.';
$string['submissioninfo'] = 'Submission info';
$string['submittedon'] = 'Submitted on';
$string['grade'] = 'Grade';
$string['forstafforiginal'] = 'For staff: original';
$string['includeslatepenalty'] = 'Includes late penalty';
$string['submitters'] = 'Submitters';

// Errors
$string['negativeerror'] = 'This value can not be negative.';
$string['closingbeforeopeningerror'] = 'The closing time must be later than the opening time.';
$string['latedlbeforeclosingerror'] = 'Late submission deadline must be later than the closing time.';
$string['zerooneerror'] = 'This value must be between zero and one.';
$string['mustbesetwithlate'] = 'This field must be set when late submissions are enabled.';

/*
$string['stratumfieldset'] = 'Stratum assignment settings';
$string['stratumsbmsmaxbytes'] = 'Submission file max size in bytes';
$string['stratumsbmsmaxbytes_help'] = 'Maximum allowed file size for the submission. For example, 1048576 means 1 MB. Use zero or leave empty for no limit.';

$string['stratummaxpoints'] = 'Max points';
$string['stratummaxpoints_help'] = 'Maximum points in the Stratum (sub)assignment.';
$string['stratumsubmitlimit'] = 'Submit limit';
$string['stratumsubmitlimit_help'] = 'Number of allowed submissions in the Stratum subassignment. Use zero or leave empty for no limit.';
$string['stratumsbmstypes'] = 'Allowed submission file types';
$string['stratumsbmstypes_help'] = 'Give the filetypes with their filename extensions. Separate several types with colons, for example .pdf:.txt (empty value describes only extensionless type, colon at the end of the list includes extensionless type)';
$string['deadline_help'] = 'Deadline for submissions. Leave empty for no deadline.';
$string['stratumasgn'] = 'Stratum assignment';
$string['stratumsubasgn'] = 'Stratum subassignment';

$string['stratumshowintro'] = 'Show the manually typed assignment introduction';
$string['stratumshowintro_help'] = 'Tick this checkbox to show the manually typed assignment description/introduction in the assignment page instead of the description that is fetched from the remote Stratum server.';
$string['stratumapikey'] = 'Stratum API key';
$string['stratumapikey_help'] = 'Secret key to the Stratum course API in the external server. The key must be kept secret, as it authorizes full access to the course (student) data.';

$string['results'] = 'Results';
$string['subasgn'] = 'Subassignment';
$string['submission'] = 'Submission';
$string['submissionfile'] = 'Choose file to submit';
$string['submit'] = 'Submit';
$string['min'] = 'Min';
$string['max'] = 'Max';
$string['bonuslimit'] = 'Bonus limit';
$string['points'] = 'Points';
$string['output'] = 'Output';
$string['submittedfile'] = 'Submitted file';
$string['sbmstime'] = 'Submission time';
$string['bonusdl'] = 'Bonus deadline';
$string['deadline'] = 'Deadline';
$string['submissionsleft'] = 'You have {$a->left} submission(s) left (out of {$a->total}).';
$string['requiredpoints'] = 'You need {$a->minpass} points out of {$a->max} to pass.';
$string['requiredbonuslimit'] = '{$a->bonuslimit} points needed to gain bonus.';
$string['submissionmaxsize'] = 'Submission file size limit';
$string['allowedfiletypes'] = 'Allowed file types';
$string['unchecked'] = 'unchecked';
$string['best'] = 'Best';
$string['bonus'] = 'bonus';
$string['late'] = 'late';
$string['submitlimitexceeded'] = 'submit limit exceeded';
$string['assignment'] = 'Assignment';
$string['newsubmission'] = 'New submission';
$string['backtoassignment'] = 'Back to the assignment';
$string['submitsuccess'] = 'Submission succeeded.';
$string['submitfailure'] = 'Uploading the submission to the Stratum server failed.';
$string['error'] = 'Error';
$string['total'] = 'Total';
$string['status'] = 'Status';
$string['status_correct'] = 'correct';
$string['status_failed'] = 'failed';
$string['status_passed'] = 'passed';
$string['status_late'] = 'late';
$string['status_submit_limit'] = 'submit limit exceeded';
$string['status_unchecked'] = 'unchecked';
$string['bonusgained'] = 'Bonus gained';
$string['yes'] = 'yes';
$string['no'] = 'no';
$string['none'] = 'None';
$string['gradedasgns'] = 'Assignments graded';
$string['gradedstudents'] = '{$a} students have been graded.';

$string['fetchpoints'] = 'Fetch points from the Stratum server';
$string['eventstratumupdategradesfailed'] = 'Stratum grades update to gradebook failed';
$string['eventstratumgradeupdatestarted'] = 'Stratum mass grade update to gradebook started';
*/
// errors
/*
$string['badhttpmethod'] = 'Disallowed HTTP request method.';
$string['missingviewparam'] = 'You must specify a course_module ID or an instance ID';
$string['tempfilefailed'] = 'Submission upload failed. (No Moodle temp file.)';
$string['mustchoosefile'] = 'You must choose a file to submit.';
$string['contactstaff'] = 'Please contact course staff.';
$string['fetchresultsfailed'] = 'Fetching your results for this assignment failed.';
$string['fetchasgnintrofailed'] = 'The assignment description could not be retrieved.';
$string['asgninstancenotfound'] = 'The assignment linked to the subassignment could not be found in the database.';
$string['negativeasgnerror'] = 'Assignment number can not be zero or negative.';
$string['missingqueryparam'] = 'Missing a query parameter.';
$string['mdlcoursenotfound'] = 'Moodle course not found (id="{$a}").';
$string['permdenied'] = 'Permission denied.';
$string['fetchsettingsfailed'] = 'Fetching assignment settings from the external server failed.';

$string['submiterror-2'] = 'Identical submission as the previous one.';
$string['submiterror-3'] = 'Wrong file type.';
$string['submiterror-4'] = 'Student account does not exist.';
$string['submiterror-5'] = 'Incorrect password.';
$string['submiterror-6'] = '(Sub)assignment argument missing.';
$string['submiterror-7'] = 'Empty submission.';
$string['submiterror-8'] = 'Season of the student is inactive.';
$string['submiterror-9'] = 'Assignments config parse error.';
$string['submiterror-10'] = '(Sub)assignment is not in use or does not exist.';
$string['submiterror-11'] = 'Submission is too large.';
$string['submiterror-12'] = 'Student data write error.';
$string['submiterror-13'] = 'Remote course does not exist.';
$string['submiterror-unknown'] = 'Unknown error.';
$string['submiterror-ok'] = 'OK';
$string['submiterror-conn'] = 'Connecting to the Stratum server failed.';
*/

// Events / logging
$string['eventsubmitted'] = 'Student submitted a new solution';
$string['eventstratumconnectionfailed'] = 'Connection to Stratum server failed';
$string['eventstratumserverfailed'] = 'Stratum server failed';
$string['eventexerciseviewed'] = 'Student viewed a Stratum2 exercise';

// capabilities
$string['stratumtwo:addinstance'] = 'Add a new Stratum2 exercise (round), and edit/delete them';
$string['stratumtwo:view'] = 'View a Stratum2 exercise (round)';
$string['stratumtwo:submit'] = 'Submit a new solution to a Stratum2 exercise';
$string['stratumtwo:viewallsubmissions'] = 'View and inspect all submissions to Stratum2 exercises';
$string['stratumtwo:grademanually'] = 'Edit the feedback and grade of a Stratum2 submission manually';
