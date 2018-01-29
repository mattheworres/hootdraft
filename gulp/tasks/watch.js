const gulp = require('gulp');
const cfg = require('../config');
const browserSync = require('browser-sync');

gulp.task('watch-html', ['html'], browserSync.reload);
gulp.task('watch-img', ['img'], browserSync.reload);
gulp.task('watch-js-templates', ['js-templates'], browserSync.reload);
gulp.task('watch-js-app', ['js'], browserSync.reload);
gulp.task('watch-less', ['css'], browserSync.reload);

gulp.task('watch', () => {
  gulp.watch(cfg.paths.app.css, ['watch-less']);
  gulp.watch(cfg.paths.app.html, ['watch-html']);
  gulp.watch(cfg.paths.app.img, ['watch-img']);
  gulp.watch(cfg.paths.app.templates, ['watch-js-templates']);
  gulp.watch(cfg.paths.app.js, ['watch-js-app']);
});
