gulp = require 'gulp'
$ = require('gulp-load-plugins')()
cfg = require '../config'
browserSync = require 'browser-sync'
path = require 'path'

write = (stream, manifestSuffix) ->
    stream
        .pipe $.if cfg.options.revAssets, $.rev()
        .pipe gulp.dest 'css'

gulp.task 'css-vendor', ->
    stream = gulp.src cfg.paths.vendor.css
        .pipe $.if cfg.options.sourcemaps, $.sourcemaps.init()
        .pipe $.if cfg.options.minify, $.minifyCss()
        .pipe $.if cfg.options.revAssets, $.rev()
        .pipe $.concat 'style-vendor.css', newLine: '\n'
        .pipe gulp.dest 'css'

    #write stream, 'vendor'

gulp.task 'css-app', ->
    gulp.src cfg.paths.app.css
        .pipe $.if cfg.options.sourceMaps, $.sourcemaps.init()
        .pipe $.less(paths: [path.join(__dirname, "app/css")])
        .pipe $.if cfg.options.minify, $.minifyCss()
        .pipe $.if cfg.options.sourceMaps, $.sourcemaps.write()
        .pipe $.if cfg.options.revAssets, $.rev()
        .pipe gulp.dest 'css'
        .pipe browserSync.stream()

gulp.task 'css', ['css-vendor', 'css-app']