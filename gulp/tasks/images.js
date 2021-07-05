const gulp = require('gulp');
const cfg = require('../config');
const browserSync = require('browser-sync');

const images = () =>
  gulp.src(cfg.paths.app.img, {buffer: false})
    .pipe(gulp.dest('img'))
    .pipe(browserSync.stream());

gulp.task('images', images);
module.exports = images;
