const gulp = require('gulp');
const $ = require('gulp-load-plugins')();
const cfg = require('../config');
const streamqueue = require('streamqueue');
const order = require('gulp-order');
const debug = require('gulp-debug');
const gutil = require('gulp-util');
const pump = require('pump');

const write = function(stream, manifestSuffix, revAssets) {
    if (revAssets == null) { revAssets = true; }
    return stream
        .pipe($.if(cfg.options.revAssets && revAssets, $.rev()))
        .pipe(gulp.dest('js'));
};

gulp.task('js-vendor', function() {
    const isUnminified = file => !/\.min\.js$/.test(file.path);

    const stream = gulp.src(cfg.paths.vendor.js)
        .pipe($.if(cfg.options.sourceMaps, $.sourcemaps.init({loadMaps: true})))
		//.pipe(debug(object => object))
		.pipe($.if(cfg.options.minify && isUnminified, $.uglify()))
		.on('error', function (err) {gutil.log(gutil.colors.red('[Error]'), err.toString());})
        .pipe($.concat('vendor.js', {newLine: '\n'}))
        .pipe($.if(cfg.options.sourceMaps, $.sourcemaps.write()));

    return write(stream, 'vendor');
});


gulp.task('uglify-error-debugging', function (cb) {
	pump([
		gulp.src('app/**/*.js'),
    	$.uglify(),
		gulp.dest('./dist/')
  ], cb);
});

gulp.task('js-app', function() {
    const js = gulp.src(cfg.paths.app.js)
        .pipe(order(cfg.paths.app.jsLoadOrder, { base: './' }))
        .pipe($.if(cfg.options.sourceMaps, $.sourcemaps.init()))
		.pipe($.coffee({bare: true}))
		//.pipe(debug(object => object))
		.pipe($.if(cfg.options.minify, $.ngAnnotate()))
		.on('error', function (err) {gutil.log(gutil.colors.red('[Error]'), err.toString());});

    const stream = streamqueue({objectMode: true}, js)
        .pipe($.if(cfg.options.concat, $.concat('app.js', {newLine: '\n'})))
		.pipe($.if(cfg.options.minify, $.uglify()))
		.on('error', function (err) {gutil.log(gutil.colors.red('[Error]'), err.toString());})
        .pipe($.if(cfg.options.sourceMaps, $.sourcemaps.write()));

    return write(stream, 'app');
});

gulp.task('js-config', function() {
    const config = gulp.src(cfg.paths.app.config)
		//.pipe(debug(object => object))
		.pipe($.ngConstant({
            name: 'config',
            wrap: true
        })
    );

	const stream = streamqueue({objectMode: true}, config)
		//.pipe(debug(object => object))
        .pipe($.if(cfg.options.concat, $.concat('config.js', {newLine: '\n'})));

    return write(stream, 'config', false);
});

gulp.task('js-templates', function (cb) {
	var stream;
	if (!cfg.options.templates) {
		return cb();
	}
	stream = gulp.src(cfg.paths.app.templates).pipe($.naturalSort()).pipe($.if(cfg.options.minify, $.htmlMinifier({
		collapseBooleanAttributes: true,
		collapseWhitespace: true,
		removeAttributeQuotes: true,
		removeComments: true,
		removeEmptyAttributes: true,
		removeRedundantAttributes: true,
		removeScriptTypeAttributes: true,
		removeStyleLinkTypeAttributes: true,
		useShortDoctype: true
	}))).pipe($.angularTemplatecache({
		root: 'app/templates',
		module: 'app'
	}));
	return write(stream, 'templates');
});

gulp.task('js', ['js-vendor', 'js-app', 'js-config', 'js-templates']);
