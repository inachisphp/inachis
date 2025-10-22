'use strict';

const gulp = require('gulp');
const sass = require('gulp-sass')(require('sass'));
const autoprefixer = import('gulp-autoprefixer');
const config = require('../config');
const cssnano = require('gulp-cssnano');
const rename = require('gulp-rename');


const sassCompileWeb = () => {
  return sassCompile(config.paths.src.sass.web , config.paths.dist.sass.web);
};
const sassCompileAdmin = () => {
  return sassCompile(config.paths.src.sass.admin, config.paths.dist.sass.admin);
};

const copyCSS = () => {
    return gulp.src(config.paths.src.sass.admin + '*.css')
        .pipe(cssnano())
        .pipe(rename({suffix: '.min'}))
        .pipe(gulp.dest(config.paths.dist.sass.admin));
};

function sassCompile (scssSource, cssDest)
{
    return gulp.src(scssSource + '**/*.scss')
        .pipe(sass()).on('error', sass.logError)
        // .pipe(autoprefixer('last 2 version'))
        .pipe(cssnano())
        .pipe(rename({suffix: '.min'}))
        .pipe(gulp.dest(cssDest));
}

exports.sassCompileAdmin = sassCompileAdmin;
exports.sassCompileWeb = sassCompileWeb;
exports.copyCSS = copyCSS;

exports.sassCompile = gulp.parallel(
    sassCompileAdmin,
    sassCompileWeb,
    copyCSS
);

exports.sassWatch = function() {
    gulp.watch(config.paths.src.sass.all + '**/*.scss', exports.sassCompile);
}
