'use strict';

module.exports = function(grunt) {

    require('load-grunt-tasks')(grunt);

    // Project configuration.
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),

        cssmin: {
            target: {
                files: {
                    'tmp/app.css': ['app/css/*.css']
                }
            }
        },

        htmlbuild: {
            dist: {
                src: 'app/index.html',
                dest: 'tmp/',
                options: {
                    scripts: {
                        main: 'tmp/app.js'
                    },
                    styles: {
                        main: 'tmp/app.css'
                    }
                }
            }
        },

        watch: {
            client: {
                files: ['app/*.html', 'app/css/*', 'app/js/*.js'],
                tasks: ['default'],
                options: {
                    livereload: 35729
                }
            },
            server: {
                files: ['app/*.php'],
                tasks: ['copy']
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
                src: ['app/js/main.js', 'app/lib/*.js', 'app/js/*.js',
                      'tmp/tmpl.js'],
                dest: 'tmp/app.js'
            }
        },

        // Minify HTML
        htmlmin: {
            // Process all except index.html
            build: {
                options: {
                    removeComments: true,
                    collapseWhitespace: true
                },
                files: [{
                    expand: true,
                    cwd: 'app',
                    src: ['*.html','!index.html'],
                    dest: 'tmp/'
                }]
            },
            // Process only top-level index.html file
            top: {
                options: {
                    removeComments: true,
                    collapseWhitespace: true
                },
                files: [{
                    src: ['tmp/index.html'],
                    dest: 'build/index.html'
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
                src: ['*.html','!index.html'],
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
    grunt.loadNpmTasks('grunt-contrib-cssmin');
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-angular-templates');
    grunt.loadNpmTasks('grunt-html-build');

    // Default build task and serve task
    grunt.registerTask('default',
                       ['htmlmin:build', 'ngtemplates',
                        'uglify', 'copy', 'cssmin', 'htmlbuild',
                        'htmlmin:top']
                      );
    grunt.registerTask('serve', 'Compile, then start a web server',
        function (target) {
            grunt.task.run([
                'default',
                'php'
            ]);
        });
};
