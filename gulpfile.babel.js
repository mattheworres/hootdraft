const watch = require('./gulp/tasks/watch');
const gulp = require('gulp');
const {build, buildJs, buildCss} = require('./gulp/tasks/build');

gulp.task('default', gulp.series(build, watch, done => done()));
gulp.task('build', build);
gulp.task('buildJs', buildJs);
gulp.task('buildCss', buildCss);
gulp.task('watch', watch);
