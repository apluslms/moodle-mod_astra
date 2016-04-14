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
// Moodle Message API
$string['messageprovider:assistant_feedback_notification'] = 'Notification about new assistant feedback';

// mod_form.php
$string['deadline'] = 'Deadline';
$string['roundname'] = 'Exercise round name';
$string['roundname_help'] = 'This is the name of the exercise round that is shown in Moodle.';
$string['note'] = 'Note';
$string['donotusemodform'] = 'You should use the Stratum2 exercises setup block to configure exercise rounds and exercises. However, you may use this page to configure common module settings and access restrictions.';
$string['status'] = 'Status';
$string['statusready'] = 'Ready';
$string['statushidden'] = 'Hidden';
$string['statusmaintenance'] = 'Maintenance';
$string['remotekey'] = 'Exercise service remote key';
$string['remotekey_help'] = 'Unique key in the exercise service.';
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
$string['ordernum'] = 'Order';
$string['ordernum_help'] = 'Set the order in which objects are listed in the course page. Smaller ordinal number comes first.';

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
$string['submissions'] = 'Submissions';
$string['nosubmissionsyet'] = 'No submissions yet';
$string['inspectsubmission'] = 'Inspect submission';
$string['viewallsubmissions'] = 'View all submissions';
$string['editexercise'] = 'Edit exercise';
$string['editchapter'] = 'Edit chapter';
$string['earnedpoints'] = 'Earned points';
$string['late'] = 'Late';
$string['exerciseinfo'] = 'Exercise info';
$string['yoursubmissions'] = 'Your submissions';
$string['pointsrequired'] = 'Points required to pass';
$string['totalnumberofsubmitters'] = 'Total number of submitters';
$string['statuserror'] = 'Error';
$string['statuswaiting'] = 'Waiting';
$string['statusinitialized'] = 'Initialized';
$string['statusunlisted'] = 'Unlisted';
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
$string['allsubmissions'] = 'All submissions';
$string['submitteddata'] = 'Submitted data';
$string['submissiontime'] = 'Submission time';
$string['submittedfiles'] = 'Submitted files';
$string['nofiles'] = 'No files';
$string['submittedvalues'] = 'Submitted values';
$string['gradingdata'] = 'Grading data';
$string['assessmanually'] = 'Assess this submission manually';
$string['graderfeedback'] = 'Grader feedback';
$string['resubmittoservice'] = 'Re-submit to exercise service';
$string['resubmitwarning'] = 'Click this button to re-submit this submission to the assessment service. This is meant to be used only in situations where the assessment service has behaved incorrectly so that the grading data is incorrect or the status of the submission never became ready. Caution! Re-submitting overwrites the current grading data.';
$string['assesssubmission'] = 'Assess submission';
$string['assessment'] = 'Assessment';
$string['assesspoints'] = 'Points';
$string['assesspoints_help'] = 'Possible penalties are not applied - the points are set as given. This will override grader points!';
$string['assessastfeedback'] = 'Assistant feedback';
$string['assessastfeedback_help'] = 'HTML formatting is allowed. This will not override machine feedback.';
$string['assessfeedback'] = 'Grader feedback';
$string['assessfeedback_help'] = 'HTML formatting is allowed. This WILL override machine feedback.';
$string['feedbackto'] = 'Feedback to {$a}';
$string['youhavenewfeedback'] = 'You have new personal feedback to exercise <a href="{$a->exurl}">{$a->exname}</a>, <a href="{$a->sbmsurl}">submission {$a->sbmsid}</a>.';
$string['numbersubmissions'] = '{$a} submissions';
$string['inspect'] = 'Inspect';
$string['gradingsubmission'] = 'Grading submission...';
$string['postingsubmission'] = 'Posting submission...';
$string['loadingexercise'] = 'Loading exercise...';
$string['exerciseresults'] = 'Exercise results';
$string['toc'] = 'Table of contents';

// teachers edit pages
$string['editcourse'] = 'Edit course';
$string['categoryname'] = 'Category name';
$string['categoryname_help'] = 'Enter a descriptive, short name for the category. It is visible to users.';
$string['createcategory'] = 'Create new category';
$string['cateditsuccess'] = 'The category was updated successfully.';
$string['catcreatesuccess'] = 'The category was created successfully.';
$string['catcreatefailure'] = 'The new category could not be stored in the database.';
$string['automaticsetup'] = 'Automatic setup';
$string['autosetup'] = 'Update and create Stratum2 exercises automatically';
$string['autosetup_help'] = 'Import configuration from the exercise service URL and override course contents (Stratum2 exercise rounds, exercises and categories).';
$string['createmodule'] = 'Create exercise round';
$string['modeditsuccess'] = 'The exercise round was updated successfully.';
$string['modcreatesuccess'] = 'The exercise round was created successfully.';
$string['modcreatefailure'] = 'The new exercise round could not be stored in the database.';
$string['modeditfailure'] = 'Updating the exercise round failed.';
$string['createexercise'] = 'Create new exercise';
$string['createchapter'] = 'Create new chapter';
$string['lobjecteditsuccess'] = 'The learning object was updated successfully.';
$string['lobjcreatesuccess'] = 'The learning object was created successfully.';
$string['lobjcreatefailure'] = 'The new learning object could not be stored in the database.';
$string['exercisename'] = 'Exercise name';
$string['exercisename_help'] = 'Name of the exercise that is shown to users.';
$string['category'] = 'Category';
$string['exerciseround'] = 'Exercise round';
$string['parentexercise'] = 'Parent exercise';
$string['parentexercise_help'] = 'If set, this exercise is listed under the parent. The parent must be in the same exercise round.';
$string['serviceurl'] = 'Service URL';
$string['serviceurl_help'] = 'Absolute URL of this exercise in the exercise service.';
$string['maxpoints'] = 'Max points';
$string['maxpoints_help'] = 'Max points that a student may earn in this exercise.';
$string['maxsubmissions'] = 'Max submissions';
$string['maxsubmissions_help'] = 'Maximum number of submissions a student is allowed to submit in the exercise. Set to zero for no limit.';
$string['allowastgrading'] = 'Allow assistant grading';
$string['allowastgrading_help'] = 'If checked, assistants may write feedback and change points for any submission in the exercise.';
$string['deleteexercise'] = 'Delete exercise';
$string['confirmobjectremoval'] = 'Confirm {$a} removal';
$string['cancel'] = 'Cancel';
$string['learningobjectlow'] = 'learning object';
$string['learningobjectremoval'] = 'You are removing {$a->type} {$a->name}. Are you sure?';
$string['exerciseremovalnote'] = '<p>If you remove this exercise, <b>all submissions to the exercise will also be removed</b>.</p>';
$string['categoryremovalnote'] = '<p>If you remove this category, <b>all exercises in the category will also be removed</b>.</p>';
$string['roundremovalnote'] = '<p>If you remove this exercise round, <b>all exercises in the round will also be removed</b>.</p>';
$string['deleteobject'] = 'Delete object';
$string['categorylow'] = 'category';
$string['roundlow'] = 'exercise round';
$string['deviations'] = 'Deviations';
$string['addnewdldeviations'] = 'Add new deadline deviations';
$string['dldeviations'] = 'Deadline deviations';
$string['submitter'] = 'Submitter';
$string['extraminutes'] = 'Extra minutes';
$string['extraminutes_help'] = 'Amount of extra time given in minutes.';
$string['withoutlatepenalty'] = 'Without late penalty';
$string['withoutlatepenalty_help'] = 'If checked, late penalty is not applied during extra time (between the original deadline and the extended deadline). Late penalty is always applied between the extended deadline and the extended late submission deadline.';
$string['actions'] = 'Actions';
$string['nodldeviations'] = 'No deadline deviations.';
$string['addnewsbmslimitdeviations'] = 'Add new submission limit deviations';
$string['submitlimitdeviations'] = 'Submission limit deviations';
$string['extrasubmissions'] = 'Extra submissions';
$string['extrasubmissions_help'] = 'Number of extra submissions allowed. These are added to the original number of submissions allowed.';
$string['nosbmslimitdeviations'] = 'No submission limit deviations.';
$string['search'] = 'Search';
$string['nomatches'] = 'No matches';
$string['searchfor'] = 'Search for...';
$string['deviationsexisted'] = 'Deviations already existed for the following users and exercises. They were not modified.';
$string['deviationscreationerror'] = 'Deviations could not be created for the following users and exercises (database error).';
$string['deviationscreatesuccess'] = 'All deviations created successfully.';
$string['back'] = 'Back';
$string['adddeviationsubmitternote'] = 'You can enter students either in the text input or in the multiselect box (hold ctrl down and left-click). If you use the text input, enter student ids or usernames separated by commas.';
$string['generatetoc'] = 'Generate table of contents';
$string['generatetoc_help'] = 'If checked, a table of contents of the exercise round is automatically added to the start of the chapter page.';

// edit course page
$string['exercisecategories'] = 'Exercise categories';
$string['editcategory'] = 'Edit category';
$string['remove'] = 'Remove';
$string['addnewcategory'] = 'Add new category';
$string['exerciserounds'] = 'Exercise rounds';
$string['editmodule'] = 'Edit exercise round';
$string['openround'] = 'Open exercise round';
$string['openexercise'] = 'Open exercise';
$string['addnewlearningobject'] = 'Add new learning object';
$string['addnewmodule'] = 'Add new exercise round';
$string['save'] = 'Save';
$string['renumerateformodules'] = 'Renumerate learning objects for each module';
$string['renumerateignoremodules'] = 'Renumerate learning objects ignoring modules';
$string['modulenumbering'] = 'Module numbering';
$string['contentnumbering'] = 'Content numbering';
$string['nonumbering'] = 'No numbering';
$string['arabicnumbering'] = 'Arabic';
$string['romannumbering'] = 'Roman';
$string['hiddenarabicnum'] = 'Hidden arabic';
$string['backtocourseedit'] = 'Back to the course edit page.';

// auto setup form
$string['configurl'] = 'Configuration URL';
$string['configurl_help'] = 'Configuration data for course exercises is downloaded from this URL.';
$string['apikey'] = 'API key';
$string['apikey_help'] = 'API key to authorize access to the exercise service.';
$string['sectionnum'] = 'Moodle course section number';
$string['sectionnum_help'] = 'Number (0-N) of the Moodle course section, to which new exercise round activities should be added. Section zero is the course home page, the next section is number 1 and so on (see the navigation in the course page).';
$string['apply'] = 'Apply';
$string['createreminder'] = 'Reminder: in MyCourses, you must have the &quot;Advanced teacher&quot; role to create Stratum2 exercises.';
$string['backtocourse'] = 'Back to the course';
$string['autosetupsuccess'] = 'Configuration was downloaded and applied successfully.';
$string['autosetuperror'] = 'There were errors in the automatic setup.';

// auto setup errors
$string['configjsonparseerror'] = 'The response from the server could not be parsed as JSON.';
$string['configcategoriesmissing'] = 'Categories are required as JSON object.';
$string['configmodulesmissing'] = 'Modules (exercise rounds) are required as JSON array.';
$string['configcatnamemissing'] = 'Category requires a name.';
$string['configbadstatus'] = 'Status has an invalid value: {$a}.';
$string['configbadint'] = 'Expected integer, but received: {$a}.';
$string['configmodkeymissing'] = 'Module (exercise round) requires a key.';
$string['configbadfloat'] = 'Expected floating-point number, but received: {$a}.';
$string['configbaddate'] = 'Unable to parse date: {$a}.';
$string['configbadduration'] = 'Unable to parse duration: {$a}.';
$string['configexercisekeymissing'] = 'Exercise requires a key.';
$string['configexercisecatmissing'] = 'Exercise requires a category.';
$string['configexerciseunknowncat'] = 'Exercise has an unknown category: {$a}.';


// plugin file area descriptions
$string['submittedfilesareadescription'] = 'Submitted files in exercises with file uploads';

// Errors
$string['error'] = 'Error';
$string['negativeerror'] = 'This value can not be negative.';
$string['closingbeforeopeningerror'] = 'The closing time must be later than the opening time.';
$string['latedlbeforeclosingerror'] = 'Late submission deadline must be later than the closing time.';
$string['zerooneerror'] = 'This value must be between zero and one.';
$string['mustbesetwithlate'] = 'This field must be set when late submissions are enabled.';
$string['serviceconnectionfailed'] = 'Connecting to the exercise service failed!';
$string['submissionfailed'] = 'Your new submission could not be stored in the server!';
$string['uploadtoservicefailed'] = 'Your submission was received but it could not be sent to the exercise service for grading!';
$string['youmaynotsubmit'] = 'You may not submit new solutions to this exercise any more!';
$string['servicemalfunction'] = 'The exercise assessment service is malfunctioning. This submission is now marked as erroneous.';
$string['duplicatecatname'] = 'Category with this name already exists.';
$string['duplicateroundremotekey'] = 'Exercise round with this remote key already exists.';
$string['parentexinwronground'] = 'Parent learning object must be in the same exercise round. Unset parent if you are changing the round of this learning object.';
$string['duplicateexerciseremotekey'] = 'Learning object with this remote key already exists in the course.';
$string['invalidobjecttype'] = 'Invalid object type: {$a}.';
$string['idsnotfound'] = 'The following identifiers could not be found in the database: {$a}';
$string['exercisecommerror'] = 'Communication error with the exercise.';
$string['gradingtakeslonger'] = 'Unfortunately grading takes longer than expected. Return later to see the result.';

/*
$string['stratumsbmsmaxbytes'] = 'Submission file max size in bytes';
$string['stratumsbmsmaxbytes_help'] = 'Maximum allowed file size for the submission. For example, 1048576 means 1 MB. Use zero or leave empty for no limit.';

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
