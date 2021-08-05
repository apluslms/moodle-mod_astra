/**
 * Grunt configuration. Grunt is particularly used to minify JavaScript modules.
 * Adapted from Moodle's Gruntfile.js.
 *
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

module.exports = function(grunt) {
    const path = require('path');

    let files = null;
    if (grunt.option('files')) {
        // Accept a comma separated list of files to process.
        files = grunt.option('files').split(',');
    }
    // Globbing pattern for matching all AMD JS source files.
    let amdSrc = ["astra/amd/src/**/*.js"];

    /**
     * Function to generate the destination for the uglify task
     * (e.g. build/file.min.js). This function will be passed to
     * the rename property of files array when building dynamically:
     * http://gruntjs.com/configuring-tasks#building-the-files-object-dynamically
     *
     * @param {String} destPath the current destination
     * @param {String} srcPath the  matched src path
     * @return {String} The rewritten destination path.
     */
    var babelRename = function(destPath, srcPath) {
        destPath = srcPath.replace('src', 'build');
        destPath = destPath.replace('.js', '.min.js');
        return destPath;
    };

    // Project configuration.
    grunt.initConfig({
        eslint: {
            // Even though warnings dont stop the build we don't display warnings by default because
            // at this moment we've got too many core warnings.
            // To display warnings call: grunt eslint --show-lint-warnings
            // To fail on warnings call: grunt eslint --max-lint-warnings=0
            // Also --max-lint-warnings=-1 can be used to display warnings but not fail.
            options: {
                quiet: (!grunt.option('show-lint-warnings')) && (typeof grunt.option('max-lint-warnings') === 'undefined'),
                maxWarnings: ((typeof grunt.option('max-lint-warnings') !== 'undefined') ? grunt.option('max-lint-warnings') : -1)
            },
            amd: {src: files ? files : amdSrc},
        },
        babel: {
            options: {
                sourceMaps: true,
                comments: false,
                plugins: [
                    'transform-es2015-modules-amd-lazy',
                    'system-import-transformer',
                    // This plugin modifies the Babel transpiling for "export default"
                    // so that if it's used then only the exported value is returned
                    // by the generated AMD module.
                    //
                    // It also adds the Moodle plugin name to the AMD module definition
                    // so that it can be imported as expected in other modules.
                    path.resolve('babel-plugin-add-module-to-define.js'),
                    '@babel/plugin-syntax-dynamic-import',
                    '@babel/plugin-syntax-import-meta',
                    ['@babel/plugin-proposal-class-properties', {'loose': false}],
                    '@babel/plugin-proposal-json-strings'
                ],
                presets: [
                    ['minify', {
                        // This minification plugin needs to be disabled because it breaks the
                        // source map generation and causes invalid source maps to be output.
                        simplify: false,
                        builtIns: false
                    }],
                    ['@babel/preset-env', {
                        targets: {
                            browsers: [
                                ">0.25%",
                                "last 2 versions",
                                "not ie <= 10",
                                "not op_mini all",
                                "not Opera > 0",
                                "not dead"
                            ]
                        },
                        modules: false,
                        useBuiltIns: false
                    }]
                ]
            },
            dist: {
                files: [{
                    expand: true,
                    src: files ? files : amdSrc,
                    rename: babelRename
                }]
            }
        },
    });

    // Register NPM tasks.
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-eslint');
    grunt.loadNpmTasks('grunt-babel');

    // Register JS tasks.
    grunt.registerTask('amd', ['eslint:amd', 'babel']);

    // Register the default task.
    grunt.registerTask('default', ['babel']);
};

