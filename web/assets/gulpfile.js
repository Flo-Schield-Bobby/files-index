// Gulp and utils
var gulp       = require('gulp'),
  gutil        = require('gulp-util'),
  size         = require('gulp-size'),
  rename       = require('gulp-rename'),
  watch        = require('gulp-watch'),
  notify       = require('gulp-notify'),
  // Styles [sass, css]
  sass         = require('gulp-ruby-sass'),
  minifycss    = require('gulp-minify-css'),
  autoprefixer = require('gulp-autoprefixer'),
  // Variables
  __folders = {
    source: {
      styles: 'sass'
    },
    dest: {
      styles: 'css'
    }
  };

// Styles
gulp.task('styles', function () {
  return sass(__folders.source.styles + '/', {
      sourcemap: false,
      style: 'expanded',
      trace: true
    })
    .on('error', gutil.log)
    .pipe(autoprefixer({
      browsers: ['> 1%', 'last 2 versions', 'Firefox >= 2', 'ie 10', 'ie 9'],
      cascade: true,
      remove: false
    }))
    .on('error', gutil.log)
    .pipe(gulp.dest(__folders.dest.styles))
    .pipe(size())
    .pipe(minifycss())
    .pipe(size())
    .pipe(rename({
      suffix: '.min'
    }))
    .pipe(gulp.dest(__folders.dest.styles))
    .pipe(notify({
      message: 'Styles task completed @ <%= options.date %>',
      templateOptions: {
        date: new Date()
      }
    }));
});

// Watch
gulp.task('watch', function () {
  // Watch .scss files
  gulp.watch(__folders.source.styles + '/*.{scss,sass}', ['styles']);
});

gulp.task('assets', ['styles']);

// Serve
gulp.task('serve', ['assets'], function () {
  gulp.start('watch');
});

// Default task
gulp.task('default', ['serve']);
