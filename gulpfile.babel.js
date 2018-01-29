const gulp = require('gulp');
const runSequence = require('run-sequence');
const fs = require('fs');

fs.readdirSync('./gulp/tasks')
  .filter(file => /\.(js|coffee)$/i.test(file))
  .map(file => require(`./gulp/tasks/${file}`));

gulp.task('build', cb => runSequence('clean', ['js', 'css', 'images', 'fonts'], 'html', cb));
gulp.task('default', cb => runSequence('build', 'watch', 'open', cb));
