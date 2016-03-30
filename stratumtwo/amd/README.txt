==== AMD - Asynchronous Module Definition ====
Frontend Javascript in Moodle uses AMD.
See Moodle docs:
* https://docs.moodle.org/dev/Javascript_Modules
* https://docs.moodle.org/dev/jQuery

Official AMD docs:
* https://github.com/amdjs/amdjs-api/wiki/AMD

Build directory stores minified Javascript source code. 
Grunt from Node.js / NPM is used to minify/uglify JS code in the src directory.
See Gruntfile.js and package.json files in the project root, and the Moodle docs.
