const gulp = require('gulp');
const cfg = require('../config');
const open = require('open');

gulp.task('open', function(cb) {
    if (!cfg.options.serve) { open(cfg.options.open, cfg.options.browser); }
    return cb();
});
