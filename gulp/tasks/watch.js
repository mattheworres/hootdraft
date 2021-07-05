const gulp = require('gulp');
const cfg = require('../config');
const {build, buildJs, buildCss} = require('../tasks/build');

const watch = done => {
  gulp.watch(cfg.paths.app.less, buildCss);
  gulp.watch(cfg.paths.app.html, build);
  gulp.watch(cfg.paths.app.img, build);
  gulp.watch(cfg.paths.app.templates, buildJs);
  gulp.watch(cfg.paths.app.js, buildJs);
  done();
};

gulp.task('watch', watch);

module.exports = watch;
