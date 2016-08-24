// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Grunt configuration, modified from the Gruntfile in the Moodle core.
 *
 * @copyright  2014 Andrew Nicols (original)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

module.exports = function(grunt) {
    var path = require('path'),
        fs = require('fs'),
        tasks = {},
        cwd = process.env.PWD || process.cwd(),
        inAMD = path.basename(cwd) == 'amd';

    var saveLicense = require('uglify-save-license');

    // Project configuration.
    grunt.initConfig({
        jshint: {
            options: {jshintrc: '.jshintrc'},
            all: [
                'astra/amd/src/*.js',
                '!astra/amd/src/twbootstrap.js',
                '!astra/amd/src/highlight.js',
            ],
            //files: [inAMD ? cwd + '/src/*.js' : '**/amd/src/*.js'],
            // Bootstrap leaves out semicolons at the end of statements
        },
        uglify: {
            dynamic_mappings: {
                options: {
                    preserveComments: saveLicense,
                },
                files: grunt.file.expandMapping(
                    ['**/src/*.js', '!**/node_modules/**'],
                    '',
                    {
                        cwd: cwd,
                        rename: function(destBase, destPath) {
                            destPath = destPath.replace('src', 'build');
                            destPath = destPath.replace('.js', '.min.js');
                            destPath = path.resolve(cwd, destPath);
                            return destPath;
                        }
                    }
                )
            },
            static_mappings: {
                // if there is a need to avoid processing all files at once, list some files here
                options: {
                    preserveComments: saveLicense,
                },
                files: {
                    'astra/amd/build/aplus_filemodal.min.js': ['astra/amd/src/aplus_filemodal.js'],
                    'astra/amd/build/aplus_tablefilter.min.js': ['astra/amd/src/aplus_tablefilter.js'],
                    'astra/amd/build/aplus_searchselect.min.js': ['astra/amd/src/aplus_searchselect.js'],
                    'astra/amd/build/aplus_chapter.min.js': ['astra/amd/src/aplus_chapter.js'],
                }
            },
        },
        /*less: {
            bootstrapbase: {
                files: {
                    "theme/bootstrapbase/style/moodle.css": "theme/bootstrapbase/less/moodle.less",
                    "theme/bootstrapbase/style/editor.css": "theme/bootstrapbase/less/editor.less",
                },
                options: {
                    compress: true
                }
           }
        }*/
    });

    // Register NPM tasks.
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-jshint');
    //grunt.loadNpmTasks('grunt-contrib-less');

    // Register JS tasks.
    grunt.registerTask('amd', ['uglify:dynamic_mappings']);

    // Register CSS taks.
    //grunt.registerTask('css', ['less:bootstrapbase']);

    // Register the default task.
    grunt.registerTask('default', ['amd']);
};
