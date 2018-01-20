const gulp = require('gulp');
const cfg = require('../config');

gulp.task('fonts-vendor', () =>
    gulp.src(cfg.paths.vendor.fonts, {buffer: false})
        .pipe(gulp.dest('fonts'))
);

gulp.task('fonts-app', () =>
    gulp.src(cfg.paths.app.fonts, {buffer: false})
        .pipe(gulp.dest('fonts'))
);

gulp.task('fonts', ['fonts-vendor', 'fonts-app']);
