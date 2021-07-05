const gulp = require('gulp');
const $ = require('gulp-load-plugins')();
const cfg = require('../config');

const html = () => {
  const injectionFiles = gulp.src([
    'css/style*.css',
    'css/style-*.css',
    'js/vendor.js',
    'js/vendor-*.js',
    'js/app.js',
    'js/app-*.js',
    'js/angular_base/*.js',
    'js/**/*.module.js',
    'js/base/application/*.js',
    'js/**/*.js',
  ],
  {read: false}
  );

  const injectOptions = {
    addRootSlash: false,
  };
  return gulp.src(cfg.paths.app.html).pipe($.inject(injectionFiles, injectOptions)).pipe($.if(cfg.options.minify, $.htmlMinifier({
    collapseBooleanAttributes: true,
    collapseWhitespace: true,
    removeAttributeQuotes: true,
    removeComments: true,
    removeEmptyAttributes: true,
    removeRedundantAttributes: true,
    removeScriptTypeAttributes: true,
    removeStyleLinkTypeAttributes: true,
    useShortDoctype: true,
  }))).pipe(gulp.dest('.'));
};

gulp.task('html', html);
module.exports = html;
