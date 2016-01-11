gulp = require 'gulp'
cfg = require '../config'
open = require 'open'

gulp.task 'open', (cb) ->
    open cfg.options.open, cfg.options.browser unless cfg.options.serve
    cb()
