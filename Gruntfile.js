'use strict';

module.exports = function(grunt) {

    require('load-grunt-tasks')(grunt);

    // Project configuration.
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),

        htmlbuild: {
            dist: {
                src: 'index.html',
                dest: 'build/',
                options: {
                    scripts: {
                        main: 'tmp/rsvp.js'
                    }
                }
            }
        },

        watch: {
            html: {
                files: ['*.html'],
                tasks: ['default'],
                options: {
                    livereload: 35729
                }
            },
            scripts: {
                files: ['*.js'],
                tasks: ['default'],
                options: {
                    livereload: 35729
                }
            },
            php: {
                files: ['*.php'],
                tasks: ['copy'],
                options: {
                    livereload: 35729
                }
            }
        },

        // Serve files
        php: {
            server: {
                options: {
                    port: 9000,
                    hostname: 'localhost',
                    keepalive: true,
                    open: true,
                    base: 'build',
                    livereload: 35729
                }
            }
        },

        // Minify JS
        uglify: {
            build: {
                src: ['main.js', 'rsvp.js', 'login.js', 'print.js', 'tmp/tmpl.js'],
                dest: 'tmp/rsvp.js'
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
                    dest: 'tmp/'
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

        ngtemplates: {
            rsvp: {
                cwd: 'tmp/',
                src: '[a-hj-z]*.html',
                dest: 'tmp/tmpl.js'
            }
        },

        // Clean
        clean: {
            build: {
                files: [{
                    dot: true,
                    src: [
                        'build/', 'tmp'
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
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-angular-templates');
    grunt.loadNpmTasks('grunt-html-build');

    // Default build task and serve task
    grunt.registerTask('default',
                       ['htmlmin', 'ngtemplates',
                        'uglify', 'copy', 'htmlbuild']
                      );
    grunt.registerTask('serve', 'Compile, then start a web server', function (target) {
        grunt.task.run([
            'default',
            'php'
        ]);
    });
};
