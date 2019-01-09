const gulp = require('gulp');
const $ = require('gulp-load-plugins')();
const cfg = require('../config');
const streamqueue = require('streamqueue');
const pump = require('pump');

const write = (stream, manifestSuffix, revAssets) => {
  const revTheAssets = revAssets === null ? true : revAssets;

  return stream
    .pipe($.if(cfg.options.revAssets && revTheAssets, $.rev()))
    .pipe(gulp.dest('js'));
};

gulp.task('js-vendor', () => {
  const isUnminified = file => !/\.min\.js$/.test(file.path);

  const stream = gulp.src(cfg.paths.vendor.js)
    //.pipe($.if(cfg.options.sourceMaps, $.sourcemaps.init({loadMaps: true})))
    .pipe($.if(cfg.options.minify && isUnminified, $.uglify()))
    //.pipe($.debug({title: 'Vendor Source:'}))
    .pipe($.concat('vendor.js', {newLine: '\n'}))
    //.pipe($.if(cfg.options.sourceMaps, $.sourcemaps.write()))
    ;

  return write(stream, 'vendor');
});

gulp.task('uglify-error-debugging', cb => {
  pump([
    gulp.src('app/**/*.js'),
    $.uglify(),
    gulp.dest('./dist/'),
  ], cb);
});

gulp.task('js-app', () => {
  const babelJs = gulp.src(cfg.paths.app.js)
    .pipe($.plumber())
    .pipe($.order(cfg.paths.app.jsLoadOrder, {base: './'}))
    .pipe($.babel())
    // .pipe(webpack({
    //   module: {
    //     rules: [
    //       {
    //         test: /\.js$/,
    //         exclude: /(node_modules|bower_components)/,
    //         use: {
    //           loader: 'babel-loader',
    //           options: {
    //             presets: ['env'],
    //             plugins: [
    //               //'transform-runtime',
    //               //'transform-regenerator',
    //               //'transform-async-to-generator',
    //               //'syntax-async-functions',
    //               //'transform-es2015-modules-umd',
    //               'angularjs-annotate',
    //             ],
    //           },
    //         },
    //       },
    //     ],
    //   },
    // }))
    .pipe($.if(cfg.options.minify, $.ngAnnotate()))
    //.pipe($.if(cfg.options.sourceMaps, $.sourcemaps.write('.')))
    //.pipe($.debug({title: 'Debug title'}))
    //.on('error', function (err) {$.gutil.log($.gutil.colors.red('[Error]'), err.toString());})
    ;

  const stream = streamqueue({objectMode: true}, babelJs)
    .pipe($.if(cfg.options.concat, $.concat('app.js', {newLine: '\n'})))
    .pipe($.if(cfg.options.minify, $.uglify()))
    //.pipe($.if(cfg.options.sourceMaps, $.sourcemaps.write()))
    // .pipe(webpack({
    //   module: {
    //     rules: [
    //       {
    //         test: /\.js$/,
    //         exclude: /(node_modules|bower_components)/,
    //         use: {
    //           loader: 'babel-loader',
    //           options: {
    //             presets: ['env'],
    //             plugins: [
    //               'transform-runtime',
    //               'transform-regenerator',
    //               'transform-async-to-generator',
    //               'syntax-async-functions',
    //               'transform-es2015-modules-umd',
    //             ],
    //           },
    //         },
    //       },
    //     ],
    //   },
    // }))
    //.on('error', function (err) {gutil.log(gutil.colors.red('[Error]'), err.toString());})
    ;

  return write(stream, 'app');
});

gulp.task('js-config', () => {
  const config = gulp.src(cfg.paths.app.config)
    .pipe($.ngConstant({
      name: 'phpdraft.env',
      wrap: true,
    })
    );

  const stream = streamqueue({objectMode: true}, config)
    .pipe($.if(cfg.options.concat, $.concat('config.js', {newLine: '\n'})));

  return write(stream, 'config', false);
});

gulp.task('js-templates', cb => {
  if (!cfg.options.templates) {
    return cb();
  }

  const stream = gulp.src(cfg.paths.app.templates).pipe($.naturalSort()).pipe($.if(cfg.options.minify, $.htmlMinifier({
    collapseBooleanAttributes: true,
    collapseWhitespace: true,
    removeAttributeQuotes: true,
    removeComments: true,
    removeEmptyAttributes: true,
    removeRedundantAttributes: true,
    removeScriptTypeAttributes: true,
    removeStyleLinkTypeAttributes: true,
    useShortDoctype: true,
  }))).pipe($.angularTemplatecache({
    root: 'app/features',
    //May want to switch to templates-specific module
    module: 'phpdraft',
  }));
  return write(stream, 'templates');
});

gulp.task('js-lint', () => {
  const pathToJs = cfg.paths.app.js;

  return gulp.src(pathToJs)
    .pipe($.eslint())
    .pipe($.eslint.format())
    .pipe($.eslint.failAfterError());
});

// Do: add back lint step once snake_case to camelCase issues have been resolved
gulp.task('js', ['js-vendor', /*'js-lint', */'js-app', 'js-config', 'js-templates']);
