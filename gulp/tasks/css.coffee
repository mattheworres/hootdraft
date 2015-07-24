gulp = require 'gulp'
$ = require('gulp-load-plugins')()
cfg = require '../config'
browserSync = require 'browser-sync'
path = require 'path'

gulp.task 'css', ->
    gulp.src cfg.paths.app.css
        .pipe $.if cfg.options.sourceMaps, $.sourcemaps.init()
        .pipe $.less(paths: [path.join(__dirname, "app/css")])
        .pipe $.if cfg.options.minify, $.minifyCss()
        .pipe $.if cfg.options.sourceMaps, $.sourcemaps.write()
        .pipe $.if cfg.options.revAssets, $.rev()
        .pipe gulp.dest 'css'
        .pipe browserSync.stream()
