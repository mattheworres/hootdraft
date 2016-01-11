gulp = require 'gulp'
cfg = require '../config'
browserSync = require 'browser-sync'

gulp.task 'watch-html', ['html'], browserSync.reload
gulp.task 'watch-img', ['img'], browserSync.reload
gulp.task 'watch-js-templates', ['js-templates'], browserSync.reload
gulp.task 'watch-js-app', ['js-app'], browserSync.reload

gulp.task 'watch', ->
    gulp.watch cfg.paths.app.css, ['css']
    gulp.watch cfg.paths.app.html, ['watch-html']
    gulp.watch cfg.paths.app.img, ['watch-img']
    gulp.watch cfg.paths.app.templates, ['watch-js-templates']
    gulp.watch cfg.paths.app.js, ['watch-js-app']
