Moodle plugin for accessing A+ style exercise services
======================================================

This Moodle plugin (mod_stratumtwo) implements most of the functionality
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


Moodle block plugin: block_stratumtwo_setup
-------------------------------------------

The Moodle plugin is bundled with a small Moodle block plugin. The block plugin
is used to provide links to course administrative tasks for teachers. The block
is not visible to students and the block plugin itself does not implement any
functionality (everything is implemented in mod_stratumtwo).


Installation
============

Assume moodledir is the path to the Moodle installation in the server.
The mod plugin directory stratumtwo is copied to moodledir/mod/ directory and
the block plugin block_stratumtwo_setup is copied to moodledir/blocks/ directory.
Thus, the plugin files (version.php etc.) should be under the following directories:
moodledir/mod/stratumtwo/
moodledir/blocks/stratumtwo_setup/

After copying the files, an admin needs to visit the Moodle admin pages
in the web browser and upgrade the database, as usual when installing plugins.


Code organization
=================

- amd: frontend Javascript code as AMD modules, the format that Moodle expects
  from JS code
- assets: CSS, including Twitter Bootstrap 3 and own CSS code
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
  * install.php: code run at installation
  * install.xml: defines the database schema of the plugin
  * messages.php: message providers that use the Moodle message API
  * uninstall.php: code run at uninstallation
  * upgrade.php: code that runs when upgrading the plugin. This is needed
    if the database schema changes after the plugin has been deployed and
    installed in production servers.
- lang: defines language strings used in the UI. Only English is currently provided.
- pix: icons that Moodle shows, for example, in activities in the course page
- teachers: PHP scripts for numerous tasks for teachers
- templates: Mustache templates, part of the Moodle output API
- tests: PHPUnit tests that use the Moodle test API
- PHP scripts under stratumtwo directory correspond to pages that the user sees
- lib.php defines functions that Moodle requires from all module/activity plugins
- mod_form.php defines a form that is used to create/edit activity instances.
  In this plugin, the teacher should use the separate teachers pages instead of
  mod_form.php
- version.php is required by Moodle: it defines the version number of the plugin and
  its dependencies on other plugins and the Moodle version
