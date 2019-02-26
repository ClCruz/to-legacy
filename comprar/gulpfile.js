var gulp = require('gulp');
var livereload = require('gulp-livereload')
var sass = require('gulp-sass');
var autoprefixer = require('gulp-autoprefixer');
var sourcemaps = require('gulp-sourcemaps');

gulp.task('sass', function () {
  gulp.src('../stylesheets/scss/**/*.scss')
    .pipe(sourcemaps.init())
    .pipe(sass({
      outputStyle: 'compressed'
    }).on('error', sass.logError))
    .pipe(autoprefixer('last 2 version', 'safari 5', 'ie 7', 'ie 8', 'ie 9', 'opera 12.1', 'ios 6', 'android 4'))
    .pipe(sourcemaps.write('./'))
    .pipe(gulp.dest('../stylesheets/'))
    .pipe(livereload());;
});

gulp.task('sassDev', function () {
  gulp.src([
    '../stylesheets/scss/**/*.scss',
    '!../stylesheets/scss/themes/*.scss', // <== !
    '../stylesheets/scss/themes/bringressos/.scss',
  ])
    .pipe(sass({
      outputStyle: 'compressed'
    }).on('error', sass.logError))
    .pipe(gulp.dest('../stylesheets/'))
    .pipe(livereload());;
});


gulp.task('sassLocalhost', function () {
  gulp.src('../stylesheets/scss/themes/tixsme/main.scss')
    .pipe(sourcemaps.init())
    .pipe(sass({
      outputStyle: 'compressed'
    }).on('error', sass.logError))
    .pipe(autoprefixer('last 2 version', 'safari 5', 'ie 7', 'ie 8', 'ie 9', 'opera 12.1', 'ios 6', 'android 4'))
    .pipe(sourcemaps.write('./'))
    .pipe(gulp.dest('../stylesheets/scss/themes/localhost/'));
  gulp.src('../stylesheets/scss/themes/www.cafeteatrorubi.com.br/main.scss')
    .pipe(sourcemaps.init())
    .pipe(sass({
      outputStyle: 'compressed'
    }).on('error', sass.logError))
    .pipe(autoprefixer('last 2 version', 'safari 5', 'ie 7', 'ie 8', 'ie 9', 'opera 12.1', 'ios 6', 'android 4'))
    .pipe(sourcemaps.write('./'))
    .pipe(gulp.dest('../stylesheets/scss/themes/www.cafeteatrorubi.tk/'));
  gulp.src('../stylesheets/scss/themes/www.teatroumc.com.br/main.scss')
    .pipe(sourcemaps.init())
    .pipe(sass({
      outputStyle: 'compressed'
    }).on('error', sass.logError))
    .pipe(autoprefixer('last 2 version', 'safari 5', 'ie 7', 'ie 8', 'ie 9', 'opera 12.1', 'ios 6', 'android 4'))
    .pipe(sourcemaps.write('./'))
    .pipe(gulp.dest('../stylesheets/scss/themes/www.teatroumc.tk/'));
});

gulp.task('watch', function () {
  livereload.listen();
  gulp.watch(([
    '../stylesheets/scss/**/*.scss',
    '!../stylesheets/scss/themes/*.scss', // <== !
    '../stylesheets/scss/themes/bringressos/.scss',
  ]), ['sassDev']);
  gulp.watch(['../stylesheets/themes/bringressos/*.css'], function (files) {
    livereload.changed(files)
  });
});