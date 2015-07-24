gulp = require 'gulp'
cfg = require '../config'
browserSync = require 'browser-sync'

gulp.task 'images', ->
    gulp.src cfg.paths.app.img, buffer: false
        .pipe gulp.dest 'img'
        .pipe browserSync.stream()
