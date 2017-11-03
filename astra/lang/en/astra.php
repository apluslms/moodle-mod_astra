<?php

/**
 * English strings for astra.
 *
 * @package    mod_astra
 * @copyright  2016 Aalto SCI CS dept.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['modulename'] = 'Astra exercise round';
$string['modulenameplural'] = 'Astra exercise rounds';
$string['noastras'] = 'No Astra exercises in this course';
$string['modulename_help'] = 'External Astra exercises (Aalto SCI Dept. of Computer Science)'; // when type is selected for a new activity

$string['astra'] = 'Astra';
$string['pluginadministration'] = 'Astra exercises administration';
$string['pluginname'] = 'Astra exercises'; // Used by Moodle core
// Moodle Message API
$string['messageprovider:assistant_feedback_notification'] = 'Notification about new assistant feedback';

// mod_form.php
$string['deadline'] = 'Deadline';
$string['roundname'] = 'Exercise round name';
$string['roundname_help'] = 'This is the name of the exercise round that is shown in Moodle. Note: the number at the start of the name is updated automatically based on the round order and course module numbering setting.';
$string['note'] = 'Note';
$string['donotusemodform'] = 'You should use the Astra exercises setup block to configure exercise rounds and exercises. However, you may use this page to configure common module settings and access restrictions.';
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
$string['latesbmsallowed_help'] = 'Late submissions can be allowed until the late submission deadline and they receive point penalties.';
$string['latesbmsdl'] = 'Late submission deadline';
$string['latesbmsdl_help'] = 'Submissions after the closing time and before the late submission deadline receive penalties in points.';
$string['latesbmspenalty'] = 'Late submission penalty';
$string['latesbmspenalty_help'] = 'Multiplier of points to reduce, as decimal. 0.1 = 10%';
$string['ordernum'] = 'Order';
$string['ordernum_help'] = 'Set the order in which objects are listed in the course page. Smaller ordinal number comes first.';

// templates
$string['exercise'] = 'Exercise';
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
$string['statusrejected'] = 'Rejected';
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
$string['youhavenewfeedback'] = 'You have new personal feedback to exercise <a href="{$a->exurl}">{$a->exname}</a>, <a href="{$a->sbmsurl}">submission {$a->sbmscounter}</a>.';
$string['numbersubmissions'] = '{$a} submissions';
$string['inspect'] = 'Inspect';
$string['gradingsubmission'] = 'Grading submission...';
$string['postingsubmission'] = 'Posting submission...';
$string['loadingexercise'] = 'Loading exercise...';
$string['exerciseresults'] = 'Exercise results';
$string['toc'] = 'Table of contents';
$string['youhaveextrasubmissions'] = 'You have {$a} extra submissions';
$string['withyourextension'] = 'with your personal extension';
$string['close'] = 'Close';
$string['date'] = 'Date';
$string['files'] = 'Files';
$string['loading'] = 'Loading...';
$string['submissionreceived'] = 'Submission received.';
$string['gotofeedback'] = 'Go to feedback';
$string['acceptedforgrading'] = 'Your submission has been accepted for grading.';
$string['exercisecategory'] = 'Exercise category';
$string['statusnototal'] = 'No total points';
$string['participants'] = 'Participants';
$string['resultsof'] = 'Results of {$a}';
$string['numberofparticipants'] = 'Number of participants (any role)';
$string['numberofparticipantswithrole'] = 'Number of participants (with the role {$a})';
$string['numberofparticipantsfilter'] = 'Number of matched participants after filtering';
$string['idnumber'] = 'ID number';
$string['idnumber_help'] = 'ID number (student ID) of the user';
$string['firstname'] = 'First name';
$string['lastname'] = 'Last name';
$string['email'] = 'Email';
$string['sortasc'] = 'Ascending sort';
$string['sortdesc'] = 'Descending sort';
$string['searchresults'] = 'Search results';
$string['previous'] = 'Previous';
$string['next'] = 'Next';
$string['currentparen'] = '(current)';
$string['sortby'] = 'Sort by {$a}';
$string['selectuserrole'] = 'Select user role';
$string['resultsperpage'] = 'Results per page';
$string['searchforparticipants'] = 'Search for participants';
$string['showhidesearch'] = 'Show/hide search';

// teachers edit pages
$string['editcourse'] = 'Edit course';
$string['categoryname'] = 'Category name';
$string['categoryname_help'] = 'Enter a descriptive, short name for the category. It is visible to users.';
$string['createcategory'] = 'Create new category';
$string['cateditsuccess'] = 'The category was updated successfully.';
$string['catcreatesuccess'] = 'The category was created successfully.';
$string['catcreatefailure'] = 'The new category could not be stored in the database.';
$string['automaticsetup'] = 'Automatic setup';
$string['autosetup'] = 'Update and create Astra exercises automatically';
$string['autosetup_help'] = 'Import configuration from the exercise service URL and override course contents (Astra exercise rounds, exercises and categories).';
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
$string['withoutlatepenalty_help'] = 'If checked, late penalty is not applied during the extra time (between the original deadline and the extended deadline).';
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
$string['usewidecolumn'] = 'Use wide column';
$string['usewidecolumn_help'] = 'Remove the info box column on the learning object page in order to provide more space to the content. This is sensible for content chapters, whereas it would hide information on normal exercise pages.';
$string['sbmsfilemaxsize'] = 'Submission max file size (B)';
$string['sbmsfilemaxsize_help'] = 'Maximum allowed file size of a submission file in bytes. For example, 1048576 means 1 MB. Use zero for no limit.';
$string['allowastgrading'] = 'Allow assistant grading';
$string['allowastgrading_help'] = 'If checked, assistants (non-editing teachers) may write feedback and change points for any submission in the exercise.';
$string['allowastviewing'] = 'Allow assistant viewing';
$string['allowastviewing_help'] = 'If checked, assistants (non-editing teachers) may inspect any submission in the exercise.';
$string['exportdata'] = 'Export course data';
$string['exportresults'] = 'Export results';
$string['exportinclallexercises'] = 'Include all exercises';
$string['exportinclallexercises_help'] = 'Tick to include all exercises, or select only some exercises below.';
$string['exportselectexercises'] = 'OR include these exercises';
$string['exportselectcats'] = 'OR include all exercises from these categories';
$string['exportselectrounds'] = 'OR include all exercises from these exercise rounds';
$string['exportselectstudents'] = 'Include only these students';
$string['exportselectstudents_help'] = 'Include only these students or leave empty to include all students in the course.';
$string['exportsubmittedbefore'] = 'Take into account only submissions that were submitted before';
$string['exportsubmittedbefore_help'] = 'Only take into account submissions that were submitted at or before the given date. Disable the date to include all submissions.';
$string['exportmustdefineexercises'] = 'You must define which exercises are included. Select all by ticking the checkbox, untick if you select only some.';
$string['exportuseonemethodtoselectexercises'] = 'You can only use one of the four methods to select exercises that are included.';
$string['exportdescription'] = 'Export exercise points of the course students to a JSON file.';
$string['exportpassedlist'] = '<a href="{$a}">Download</a> a list of students who have passed the course exercise requirements (gained at least minimum required points in all exercises, rounds and categories).';
$string['exportsubmittedfiles'] = 'Export submitted files';
$string['exportsubmittedfilesdesc'] = 'Export submitted files to a zip archive.';
$string['exportsubmittedform'] = 'Export submitted form input';
$string['exportsubmittedformdesc'] = 'Export submitted form input to a JSON file. Uploaded files are not included.';
$string['massregrading'] = 'Mass regrading';
$string['massregradingdesc'] = 'Mass upload existing submissions to the exercise service for regrading.';
$string['regradesubmissions'] = 'Regrade submissions';
$string['massregrinclsbms'] = 'Include submissions';
$string['massregrinclsbms_help'] = 'Select which submissions are included. This obeys the submitted before date.';
$string['massregrsbmserror'] = 'Only submissions with status error';
$string['massregrsbmsall'] = 'All submissions';
$string['massregrsbmslatest'] = 'Only the latest submission of each student';
$string['massregrsbmsbest'] = 'Only the currently best submission (highest points) of each student';
$string['massregrtasksuccess'] = 'The submissions are uploaded to the exercise service for regrading as soon as possible.';
$string['massregrtaskerror'] = 'Error: the regrading task could not be stored in the database. No submissions are uploaded to the exercise service.';
$string['exportindexresultsdesc'] = 'Export exercise results (points) of the students or download a list of students that have passed required exercises.';
$string['exportindexsubmittedfilesdesc'] = 'Export files that students have submitted to exercises as their solutions.';
$string['exportindexsubmittedformsdesc'] = 'Export values students have submitted in exercise forms. This includes, for example, text written in text fields or the selected checkboxes. Uploaded files are not included.';
$string['exercisessubmitted'] = 'Exercises submitted';
$string['submissionsreceived'] = '{$a} submissions received';

// edit course page
$string['exercisecategories'] = 'Exercise categories';
$string['editcategory'] = 'Edit category';
$string['remove'] = 'Remove';
$string['addnewcategory'] = 'Add new category';
$string['exerciserounds'] = 'Exercise rounds';
$string['editmodule'] = 'Edit exercise round';
$string['openround'] = 'Open exercise round';
$string['openexercise'] = 'Open exercise';
$string['addnewexercise'] = 'Add new exercise';
$string['addnewchapter'] = 'Add new chapter';
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
$string['clearcontentcache'] = 'Clear content cache';
$string['cachescleared'] = 'Exercise caches have been cleared.';

// auto setup form
$string['configurl'] = 'Configuration URL';
$string['configurl_help'] = 'Configuration data for course exercises is downloaded from this URL.';
$string['apikey'] = 'API key';
$string['apikey_help'] = 'API key to authorize access to the exercise service.';
$string['sectionnum'] = 'Moodle course section number';
$string['sectionnum_help'] = 'Number (0-N) of the Moodle course section, to which new exercise round activities should be added. Section zero is the course home page, the next section is number 1 and so on (see the navigation in the course page).';
$string['apply'] = 'Apply';
$string['createreminder'] = 'Reminder: in MyCourses, you must have the &quot;Advanced teacher&quot; role to create Astra exercises.';
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
$string['configassistantnotfound'] = '(Assistants) Users with the following student ids were not found: {$a}';
$string['configuserrolesdisallowed'] = 'You are not allowed to modify user roles in the course: assistants were not automatically promoted to non-editing teachers.';
$string['configassistantsnotarray'] = 'Assistants must be given as student ID array.';
$string['confignomanualenrol'] = 'Manual enrolment is not supported in the course and thus assistants can not be enrolled to the course (they gain no access to the course, even if they are given non-editing teacher role in the course modules (exercise rounds)).';

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
$string['duplicateexerciseremotekey'] = 'Learning object with this remote key already exists in the same exercise round.';
$string['invalidobjecttype'] = 'Invalid object type: {$a}.';
$string['idsnotfound'] = 'The following identifiers could not be found in the database: {$a}';
$string['exercisecommerror'] = 'Communication error with the exercise.';
$string['gradingtakeslonger'] = 'Unfortunately grading takes longer than expected. Return later to see the result.';
$string['exerciselobjectexpected'] = 'Exercise expected, but the id matches a learning object of other type.';
$string['toolargesbmsfile'] = 'One of the uploaded files is too large and the submission was not saved! The file size limit is {$a} bytes.';
$string['assistgradingnotallowed'] = 'Assistants may not grade submissions in this exercise.';
$string['assistviewingnotallowed'] = 'Assistants may not view submissions in this exercise.';
$string['exportfilesziperror'] = 'Error in creating the zip archive';
$string['notenrollednosubmit'] = 'You are not enrolled in the course, hence you may not submit new solutions to exercises.';
$string['nosecretkeyset'] = 'The Moodle site administrator has not set the mandatory secret key for the Astra plugin.';
$string['loadingfailed'] = 'Loading failed!';
$string['usernotenrolled'] = 'The user is not enrolled in the course.';

// Events / logging
$string['eventsubmitted'] = 'Student submitted a new solution';
$string['eventserviceconnectionfailed'] = 'Connection to exercise service failed';
$string['eventexerciseservicefailed'] = 'Exercise service failed';
$string['eventexerciseviewed'] = 'Student viewed an Astra exercise';
$string['eventasyncgradingfailed'] = 'Asynchronous grading failed';

// capabilities
$string['astra:addinstance'] = 'Add a new Astra exercise (round), and edit/delete them';
$string['astra:view'] = 'View Astra exercise (round)';
$string['astra:submit'] = 'Submit a new solution to an Astra exercise';
$string['astra:viewallsubmissions'] = 'View and inspect all submissions to Astra exercises';
$string['astra:grademanually'] = 'Edit the feedback and grade of an Astra submission manually';

// cache API
$string['cachedef_exercisedesc'] = 'Cache for exercise descriptions retrieved from an exercise service';

// admin settings (settings.php)
$string['cacertheading'] = 'CA (certificate authority) certificates for secure HTTPS connections to exercise services';
$string['explaincacert'] = 'If HTTPS connections are used (i.e., the service URL starts with https:// in any exercise or learning object), the PHP libcurl networking library must know where the CA certificates are located in the Moodle server. They are used to verify the peer certificate (the certificate of the exercise service). Depending on the server, libcurl default values may work and these settings may be left empty.';
$string['cainfopath'] = 'File path of the CA certificate bundle';
$string['cainfopath_help'] = 'Absolute path to a file that holds one or more CA certificates. If this is set, the next setting (curl_capath) is ignored. In Ubuntu Linux, the file "/etc/ssl/certs/ca-certificates.crt" usually contains the CA bundle. Different operating systems use different default locations!';
$string['cadirpath'] = 'Directory that holds CA certificates';
$string['cadirpath_help'] = 'Absolute path to a directory that holds multiple CA certificates. The certificate file names must be hashed (c_rehash script in OpenSSL). In Ubuntu Linux, the directory "/etc/ssl/certs" is the system default location that already has hashed the file names. Some operating systems may prefer using a CA bundle file as a default over a CA directory (see the previous setting, curl_cainfo).';
$string['asyncsecretkey'] = 'Secret key';
$string['asyncsecretkey_help'] = 'Secret key is used to ensure that only the real exercise service may post grading results back to Astra, that is, the key is used to compute hash values that others can not replicate. The key should be 50-100 characters long and consist of ASCII characters (a-z, A-Z, 0-9, special characters !"#@.- etc.). The key must not leak to users or outsiders and it shall not be stored in the exercise service either.';
