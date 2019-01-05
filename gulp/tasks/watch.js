const gulp = require('gulp');
const cfg = require('../config');
//const browserSync = require('browser-sync');

gulp.task('watch-html', ['html'], () => gulp.start('build'));
gulp.task('watch-img', ['img'], () => gulp.start('build'));
gulp.task('watch-js-templates', ['js-templates'], () => gulp.start('build-js'));
gulp.task('watch-js-app', ['js'], () => gulp.start('build-js'));
gulp.task('watch-less', ['css'], () => gulp.start('build-css'));

gulp.task('watch', () => {
  gulp.watch(cfg.paths.app.css, ['watch-less']);
  gulp.watch(cfg.paths.app.html, ['watch-html']);
  gulp.watch(cfg.paths.app.img, ['watch-img']);
  gulp.watch(cfg.paths.app.templates, ['watch-js-templates']);
  gulp.watch(cfg.paths.app.js, ['watch-js-app']);
});
