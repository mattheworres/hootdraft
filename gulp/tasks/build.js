const gulp = require('gulp');
const clean = require('./clean');
const js = require('./js');
const css = require('./css');
const images = require('./images');
const fonts = require('./fonts');
const html = require('./html');

const build = gulp.series(clean, js, css, images, fonts, html, done => done());
const buildJs = gulp.series(js, done => done());
const buildCss = gulp.series(css, done => done());

module.exports = {build, buildJs, buildCss};