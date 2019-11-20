Astra: Moodle plugin for accessing A+ style exercise services
=============================================================

This Moodle plugin (mod_astra) implements most of the functionality
available in the A+ system (https://github.com/Aalto-LeTech/a-plus), thus
courses that have been running on A+ with automatically assessed exercises can
be run in Moodle with this plugin. This plugin does not connect to an A+ server
but it implements a similar platform that accepts submissions to exercises,
sends them to an exercise service for grading and stores the grading results
(feedback and points). The plugin uses the same grader protocol as A+ to connect
to exercise services, hence A+ style exercise services should work with both A+
and this Moodle plugin without modifications.

Features in A+ that have NOT been implemented in this Moodle plugin
-------------------------------------------------------------------

- A+ plugins: widgets visible in the sidebar of A+ pages that have been used
  for example to display additional statistics about the course and student
  performance
- group submissions: students form groups and submit a solution to an exercise
  together as a group. The plugin supports only submissions from a single student.
- REST API to access the course data (students, submissions, results,...).
  The plugin allows the teacher to manually export course data (JSON dump of
  exercise results and submitted input, and zip archive of the submitted files),
  but there is no REST API for programmatic access.
- LTI exercise: exercise that connects to the exercise service using the LTI protocol.
  (However, Moodle has its own native activity for LTI exercises.)
- The following exercise settings: confirm the level, difficulty,
  unofficial submissions (after the deadline or after reaching the submission attempt limit)
- Enrollment questionnaires: Moodle has its own course enrollment features and
  Astra does not interfere with those in any way.
- Showing the exercise model solution or template files from the navigation bar
  in the exercise page.
- A+ course settings such as lifesupport time, archive time, head URLs, and
  custom content in the course front page.
- A+ teacher's management features: visualizations of the course data
  (Astra stores points in the Moodle Gradebook, though).
- Student tags (in A+, teachers can add tags to students and filter course
  participants based on the tags).
- A+ batch assessment with a JSON dump (which creates new graded submissions to students)


Moodle block plugin: block_astra_setup
--------------------------------------

The Moodle plugin is bundled with a small Moodle block plugin. The block plugin
is used to provide links to course administrative tasks for teachers. The block
is not visible to students and the block plugin itself does not implement any
functionality (everything is implemented in mod_astra).


Installation
============

Assume `moodledir` is the path to the Moodle installation in the server.
The mod plugin directory `astra` is copied to `moodledir/mod/` directory and
the block plugin `block_astra_setup` is copied to `moodledir/blocks/` directory.
Thus, the plugin files (version.php etc.) should be under the following directories:

* `moodledir/mod/astra/`
* `moodledir/blocks/astra_setup/`

After copying the files, an admin needs to visit the Moodle admin pages
in the web browser and upgrade the database, as usual when installing plugins.

Sitewide configuration of the plugin
------------------------------------

Astra has some settings that affect all instances (activities) of the module.
They are accessed from the Moodle administration menu
(`Site administration -> Plugins -> Activity modules -> Astra exercises`).

Astra requires a secret key for computing hash values that are part of the
grader protocol (used to communicate with exercise services).
The secret key should be 50-100 characters long and consist of ASCII characters.
Astra generates a random key at installation time but it may be replaced with
a new key. If the secret key is changed while a course is running, it may disrupt
exercise submissions that were started before the change but did not completely
finish before it.

If you use HTTPS connection between Moodle and exercise service (i.e., if the exercise
service URL begins with `https://`), you may need to set the path to CA certificates
installed in the Moodle server. The CA certificates are used to verify peer
certificates in HTTPS connections (to the exercise service): if the verification fails,
the connection fails. Astra uses the PHP libcurl library for issuing HTTP(S) requests.
Some servers may have functional default values for libcurl, in which case it is
unnecessary to set the CA certificate configurations in the Astra plugin.


Code organization
=================

- amd: frontend Javascript code as AMD modules, the format that Moodle expects
  from JS code
- assets: CSS styles
- async: PHP scripts that are used for asynchronous grading from the exercise service
- backup: implementation of the Moodle backup API
- classes: PHP class definitions in a format that supports Moodle class auto-loading
  (no require/include needed to use the classes)
  * autosetup: implements automatic setup of course exercises from the exercise
    service configuration
  * event: defines event classes that are used to log events in the Moodle logs
  * export: defines classes that are used to export course data
  * form: defines forms using the Moodle forms API
  * output: part of the Moodle templates and output API, these classes define
    variables for Mustache HTML templates
  * protocol: remote page parses content received from the exercise service and
    initiates the HTTP(S) connection to the service with PHP libcurl
  * summary: classes that collect a summary of the user's status in exercises using
    a minimal amount of database queries
  * task: defines task classes that use the Moodle task API (scheduled or ad-hoc tasks)
  * urls: class that defines the URLs of the plugin in one place (basically file paths).
    If a file path must be changed, it should require only a change in this class
    so that other parts of the code do not break.
  * classes directly under classes directory represent rows in the database tables
    defined by the plugin
- db: defines various things that Moodle requires
  * access.php: defines capabilities that are used in access control (Access API)
  * install.php: code run at installation (in addition to the standard installation
    of the database tables)
  * install.xml: defines the database schema of the plugin
  * messages.php: message providers that use the Moodle message API
  * uninstall.php: code run at uninstallation
  * upgrade.php: code that runs when upgrading the plugin. This is needed
    if the database schema changes after the plugin has been deployed and
    installed in production servers.
- lang: defines translatable strings used in the UI. English and Finnish are
  currently provided.
- pix: icons that Moodle shows, for example, in activities in the course page
- teachers: PHP scripts for numerous tasks for teachers
- templates: Mustache templates, part of the Moodle output API
- tests: PHPUnit tests that use the Moodle test API
- PHP scripts under astra directory correspond to pages that the user sees
- lib.php defines functions that Moodle requires from all module/activity plugins
- mod_form.php defines a form that is used to create/edit activity instances.
  In this plugin, the teacher should use the separate teacher pages instead of
  mod_form.php (edit course link in the block plugin)
- version.php is required by Moodle: it defines the version number of the plugin and
  its dependencies on other plugins and the Moodle version
