'use strict';

module.exports = function(grunt) {

    require('load-grunt-tasks')(grunt);

    // Project configuration.
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),

        // Serve files
        php: {
            server: {
                options: {
                    port: 9000,
                    hostname: 'localhost',
                    keepalive: true,
                    base: 'build'
                }
            }
        },

        // Minify JS
        uglify: {
            options: {
                banner: '/*! <%= pkg.name %> <%= grunt.template.today("yyyy-mm-dd") %> */\n'
            },
            build: {
                src: 'rsvp.js',
                dest: 'build/rsvp.js'
            }
        },

        // Minify HTML
        htmlmin: {
            build: {
                options: {
                    removeComments: true,
                    collapseWhitespace: true
                },
                files: [{
                    expand: true,
                    cwd: './',                           // Project root
                    src: '*.html',
                    dest: 'build/'
                }]
            }
        },

        // Copy PHP files
        copy: {
            build: {
                files: [{
                    // includes files within path
                    expand: true,
                    src: ['*.php'],
                    dest: 'build/',
                    filter: 'isFile'
                }]
            }
        },

        // Clean
        clean: {
            build: {
                files: [{
                    dot: true,
                    src: [
                        'build/'
                    ]
                }]
            }
        }
    });

    // Load the plugins we use
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-htmlmin');
    grunt.loadNpmTasks('grunt-contrib-copy');
    grunt.loadNpmTasks('grunt-contrib-clean');

    // Default task plus other tasks
    grunt.registerTask('default', ['uglify', 'htmlmin', 'copy']);
    grunt.registerTask('serve', 'Compile, then start a web server', function (target) {
        grunt.task.run([
            'default',
            'php'
        ]);
    });
};
