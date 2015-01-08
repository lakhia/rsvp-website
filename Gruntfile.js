'use strict';

module.exports = function(grunt) {

    require('load-grunt-tasks')(grunt);

    // Project configuration.
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),

        htmlbuild: {
            dist: {
                src: 'app/index.html',
                dest: 'build/',
                options: {
                    scripts: {
                        main: 'tmp/rsvp.js'
                    },
                    styles: {
                        main: 'app/css/app.css'
                    }
                }
            }
        },

        watch: {
            html: {
                files: ['app/*.html'],
                tasks: ['default'],
                options: {
                    livereload: 35729
                }
            },
            scripts: {
                files: ['app/js/*.js'],
                tasks: ['default'],
                options: {
                    livereload: 35729
                }
            },
            php: {
                files: ['app/*.php'],
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
                src: ['app/js/main.js', 'app/js/*.js', 'tmp/tmpl.js'],
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
                    cwd: 'app',
                    src: '*.html',
                    dest: 'tmp/'
                }]
            }
        },

        // Copy PHP files
        copy: {
            build: {
                files: [{
                    expand: true,
                    cwd: 'app',
                    src: ['*.php'],
                    dest: 'build/'
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
                        'build', 'tmp'
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
