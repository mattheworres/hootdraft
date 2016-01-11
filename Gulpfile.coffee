gulp = require 'gulp'
runSequence = require 'run-sequence'
wrench = require 'wrench'

###
  Load all gulp tasks auto-magically 
###
wrench.readdirSyncRecursive('./gulp/tasks').filter((file) ->
  /\.(js|coffee)$/i.test file
).map (file) ->
  require "./gulp/tasks/#{file}"

gulp.task 'build', (cb) -> runSequence('clean', ['js', 'css', 'images', 'fonts'], 'html', cb)
gulp.task 'default', (cb) -> runSequence('build', 'watch', 'serve', 'open', cb)