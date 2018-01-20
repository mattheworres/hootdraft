/*
 * decaffeinate suggestions:
 * DS102: Remove unnecessary code created because of implicit returns
 * Full docs: https://github.com/decaffeinate/decaffeinate/blob/master/docs/suggestions.md
 */
const gulp = require('gulp');
const cfg = require('../config');
const browserSync = require('browser-sync');
const fs = require('fs');
const path = require('path');
const url = require('url');

const pushState = function(req, res, next) {
    let fileName = url.parse(req.url);
    fileName = fileName.href.split(fileName.search).join('');

    const fileExists = fs.existsSync(path.join('./', fileName));
    const isBSCRequest = fileName.indexOf('browser-sync-client') >= 0;

    if (!fileExists && !isBSCRequest) { req.url = '/index.html'; }

	next();
};

gulp.task('serve', function(cb) {
    if (!cfg.options.serve) { return cb(); }

    browserSync.init({
        browser: cfg.options.browser,
        port: 8000,
        server: {
            baseDir: './',
            middleware: pushState
        },
        ui: {
            port: 8001
        }
    });

	cb();
});
