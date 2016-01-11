gulp = require 'gulp'
del = require 'del'

gulp.task 'clean', (cb) ->
    del [
        'css/**/*'
        'fonts/**/*'
        'img/**/*'
        'js/**/*'
        'index.html'
    ],
    force: true,
    cb
