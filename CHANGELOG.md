# Changelog

## [1.11.0] - 2021-08-06
Release v1.11 for Moodle 3.10. There are no user-visible changes,
only maintenance changes for the new Moodle version.

### Changed
* Upgrade the highlight.js library from v9 to v11.
* Convert JavaScript modules from the AMD format to ES6.
  Moodle is moving to ES6 modules.
* Fix PHPUnit tests. Moodle upgraded the framework.

## [1.10.1] - 2021-01-04
###  Changed
* Increase the length of the category name field in the database.

### Fixed
* Bug fixes in chapter links and CSS styles.

## [1.10.0] - 2020-12-21

Release v1.10.0, January 2021. Compatible with A+/MOOC-Grader v1.8.
Requires Moodle 3.9 LTS.

### Changed

*  Update the plugin for Moodle 3.9 (long-term support). The new version does not work on older Moodle releases.
*  Removed exercises from the Moodle gradebook. The gradebook contains only the total points of the exercise round. This change was necessary due to gradebook changes in Moodle 3.8. Moodle no longer supports a variable number of grade items per one activity instance in the gradebook. Therefore, Astra adds only one grade item per activity instance to the gradebook and that grade item shows the total points of the exercise round (one exercise round corresponds to one activity instance in Astra). In older Moodle versions, it was possible to create a different number of grade items for each activity instance.
*  Astra does not modify the gradebook total aggregation method anymore. You can change it manually.
*  There is no limit on the number of exercises in one exercise round. The summer 2020 release had a limit of 30 exercises as a workaround for the gradebook issues.
*  The Astra edit course page has a new gradebook synchronization feature in the botton of the page. You may use if the grades in the gradebook seem to be outdated and to not correspond to the best submissions. Normally, it is not necessary to run the synchronization manually, but it is useful if the grades become somehow broken.
*  Updated the CSS styles so that they work with MOOC-Grader v1.8. The grader has changed the HTML structure of questionnaires.
*  Updated the relative link fixes in chapters since A+ v1.8 and a-plus-rst-tools v1.3 have changed their behaviour.

### Upgrading (for system administrators)

When an old installation is upgraded, the gradebook grades for the Astra exercise grade items must be manually removed before upgrading the Astra plugin to the Moodle 3.9 version. The grade items are easy to remove with the given script. Follow these instructions:

1.  Copy the new mod_astra to the Moodle mod directory. Do not run the upgrade in the Moodle admin UI yet.
2.  Run the command-line command: `php moodle/mod/astra/cli/remove_exercise_gradeitems.php`
3.  Run the upgrade in the Moodle admin UI.
4.  Run the command-line command: `php moodle/mod/astra/cli/sync_gradebook_grades.php`

