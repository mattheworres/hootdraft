gulp = require 'gulp'
cfg = require '../config'
browserSync = require 'browser-sync'
fs = require 'fs'
path = require 'path'
url = require 'url'

pushState = (req, res, next) ->
    fileName = url.parse req.url
    fileName = fileName.href.split(fileName.search).join ''

    fileExists = fs.existsSync(path.join('./', fileName))
    isBSCRequest = fileName.indexOf('browser-sync-client') >= 0

    req.url = '/index.html' unless fileExists or isBSCRequest
    next()

gulp.task 'serve', (cb) ->
    return cb() unless cfg.options.serve

    browserSync.init
        browser: cfg.options.browser
        port: 8000
        server:
            baseDir: './'
            middleware: pushState
        ui:
            port: 8001
    cb()
