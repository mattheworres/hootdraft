gulp = require 'gulp'
$ = require('gulp-load-plugins')()
cfg = require '../config'

gulp.task 'html', ->
    injectionFiles = gulp.src [
        'css/style.css'
        'css/style-*.css'
        'js/vendor.js'
        'js/vendor-*.js'
        'js/app.js'
        'js/app-*.js'
        'js/**/*.js'
    ], read: false

    injectOptions =
        addRootSlash: false

    gulp.src cfg.paths.app.html
        .pipe $.inject injectionFiles, injectOptions
        .pipe $.if cfg.options.minify, $.htmlMinifier
            collapseBooleanAttributes: true
            collapseWhitespace: true
            removeAttributeQuotes: true
            removeComments: true
            removeEmptyAttributes: true
            removeRedundantAttributes: true
            removeScriptTypeAttributes: true
            removeStyleLinkTypeAttributes: true
            useShortDoctype: true
        .pipe gulp.dest ''
