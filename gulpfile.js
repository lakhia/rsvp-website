var gulp = require('gulp'),
    templateCache = require('gulp-angular-templatecache'),
    minifyHTML = require('gulp-minify-html'),
    uglify = require("gulp-uglify"),
    concat = require('gulp-concat'),
    htmlReplace = require('gulp-html-replace'),
    cssmin = require('gulp-cssmin'),
    fileInsert = require("gulp-file-insert"),
    inlineSource = require('gulp-inline-source'),
    connect = require('gulp-connect-php'),
    livereload = require('gulp-livereload');

// Server for development
gulp.task('serve', ['watch'], function() {
    connect.server({
        base: 'app',
        port: 8010,
        livereload: true
    });
});
gulp.task('html', function () {
    gulp.src('app/*.html')
        .pipe(livereload());
});
gulp.task('watch', function () {
    livereload.listen();
    gulp.watch(['app/*.*', 'app/js/*.*'],
               ['html']);
});

// Server for production
gulp.task('serve-prod', ['default'], function() {
    connect.server({
        base: 'build',
        port: 8020
    });
});

// Build for production
gulp.task('default', ['js','css', 'php'], function() {
    return gulp.src('app/index.html')
        .pipe(htmlReplace({
            'script': {
                src: '../build/.tmp/all.js',
                tpl: '<script src="%s" inline></script>'
            },
            'style': {
                src: '../build/.tmp/all.css',
                tpl: '<link rel="stylesheet" href="%s" inline>'
            },
            'cdn': '#includeCDN'
        }))
        .pipe(inlineSource())
        .pipe(fileInsert({
            "#includeCDN": "app/lib/cdn.html"
        }))
        .pipe(minifyHTML())
        .pipe(gulp.dest('build/'));
});

gulp.task('php', function() {
    gulp.src('app/*.php')
        .pipe(gulp.dest('build/'));
});

gulp.task('js', ['templates'], function() {
    return gulp.src(['app/js/main.js', 'app/js/*.js', 'app/lib/*.js', 'build/.tmp/templates.js'])
        .pipe(concat('all.js'))
        .pipe(uglify())
        .pipe(gulp.dest('build/.tmp'));
});

gulp.task('templates', function () {
    return gulp.src(['app/*.html', ,'!app/index.html'])
        .pipe(minifyHTML())
        .pipe(templateCache('templates.js',
                            {
                                module: 'rsvp',
                                standAlone: false
                            }))
        .pipe(gulp.dest('build/.tmp'));
});

gulp.task('css', function () {
	return gulp.src('app/css/*.css')
        .pipe(concat('all.css'))
		.pipe(cssmin())
		.pipe(gulp.dest('build/.tmp'));
});
