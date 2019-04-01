var gulp = require('gulp'),
    concat = require('gulp-concat'),
    connect = require('gulp-connect-php'),
    cssmin = require('gulp-cssmin'),
    fileInsert = require("gulp-file-insert"),
    htmlReplace = require('gulp-html-replace'),
    inlineSource = require('gulp-inline-source'),
    livereload = require('gulp-livereload');
    minifyHTML = require('gulp-minify-html'),
    templateCache = require('gulp-angular-templatecache'),
    uglify = require("gulp-uglify");

// Server for development
gulp.task('watch', function () {
    livereload.listen();
    gulp.watch(['app/*.*', 'app/js/*.*', 'app/css/*.*'],
               gulp.series('html'));
});
gulp.task('serve', gulp.parallel('watch', function() {
    connect.server({
        base: 'app',
        port: 8010,
        livereload: true
    });
}));
gulp.task('html', function () {
    return gulp.src('app/*.html')
        .pipe(livereload());
});

// Build for production
gulp.task('php', function() {
    return gulp.src('app/*.php')
        .pipe(gulp.dest('build/'));
});

/* Creates build/.tmp/template.js */
gulp.task('templates', function () {
    return gulp.src(['app/*.html', '!app/index.html'])
        .pipe(minifyHTML())
        .pipe(templateCache('templates.js',
                            {
                                base: function (file) { return file.relative; },
                                module: 'rsvp',
                                standAlone: false
                            }))
        .pipe(gulp.dest('build/.tmp'));
});

/* Creates build/.tmp/all.js */
gulp.task('js', gulp.series('templates', function() {
    return gulp.src(['app/js/route.js', 'app/js/*.js', 'app/lib/*.js', 'build/.tmp/templates.js'])
        .pipe(concat('all.js'))
        .pipe(uglify())
        .pipe(gulp.dest('build/.tmp'));
}));

/* Creates build/.tmp/all.css */
gulp.task('css', function () {
	return gulp.src('app/css/*.css')
        .pipe(concat('all.css'))
		.pipe(cssmin())
		.pipe(gulp.dest('build/.tmp'));
});

/* Create build/index.html */
gulp.task('index', function() {
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

/* Default task that kicks off other tasks */
gulp.task('default', gulp.series(gulp.parallel('js', 'css', 'php'), 'index'));

// Server for production
gulp.task('serve-prod', gulp.series('default', function() {
    connect.server({
        base: 'build',
        port: 8010
    });
}));
