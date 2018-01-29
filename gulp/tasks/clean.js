const gulp = require('gulp');
const del = require('del');

gulp.task('clean', callback =>
  del([
    'css/**/*',
    'fonts/**/*',
    'img/**/*',
    'js/**/*',
    'index.html',
  ],
  {force: true},
  callback)
);
