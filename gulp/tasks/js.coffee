gulp = require 'gulp'
$ = require('gulp-load-plugins')()
cfg = require '../config'
streamqueue = require 'streamqueue'

write = (stream, manifestSuffix) ->
    stream
        .pipe $.if cfg.options.revAssets, $.rev()
        .pipe gulp.dest 'js'

gulp.task 'js-vendor', ->
    isUnminified = (file) -> not /\.min\.js$/.test file.path

    stream = gulp.src cfg.paths.vendor.js
        .pipe $.if cfg.options.sourceMaps, $.sourcemaps.init loadMaps: true
        .pipe $.if cfg.options.minify and isUnminified, $.uglify()
        .pipe $.concat 'vendor.js', newLine: '\n'
        .pipe $.if cfg.options.sourceMaps, $.sourcemaps.write()

    write stream, 'vendor'

gulp.task 'js-app', ->
    js = gulp.src cfg.paths.app.js
        .pipe $.naturalSort()
        .pipe $.if cfg.options.sourceMaps, $.sourcemaps.init()
        .pipe $.coffee()
        .pipe $.if cfg.options.minify, $.ngAnnotate()

    config = gulp.src cfg.paths.app.config
        .pipe $.ngConstant
            name: 'config'
            wrap: true
 
    stream = streamqueue objectMode: true, js, config
        .pipe $.if cfg.options.concat, $.concat 'app.js', newLine: '\n'
        .pipe $.if cfg.options.minify, $.uglify()
        .pipe $.if cfg.options.sourceMaps, $.sourcemaps.write()

    write stream, 'app'

gulp.task 'js-templates', (cb) ->
    return cb() unless cfg.options.templates

    stream = gulp.src cfg.paths.app.templates
        .pipe $.naturalSort()
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
        .pipe $.angularTemplatecache
            root: 'app/templates'
            module: 'app'

    write stream, 'templates'

gulp.task 'js', ['js-vendor', 'js-app', 'js-templates']
