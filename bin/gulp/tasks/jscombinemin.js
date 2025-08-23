"use strict";

const gulp = require('gulp');
const config = require('../config');

const concat = require('gulp-concat');
const minify = require('gulp-babel-minify');
const rename = require('gulp-rename');

const jsCompileAdmin = () => {
    return jsCompile(config.paths.src.js.admin , config.paths.dist.js.admin);
};
const jsCompileWeb = () => {
    return jsCompile(config.paths.src.js.web , config.paths.dist.js.web);
};

exports.jsCompileAdmin = jsCompileAdmin;
exports.jsCompileWeb = jsCompileWeb;

exports.jsCompile = gulp.parallel(
    jsCompileAdmin,
    jsCompileWeb
);

exports.jsWatch = function()
{
    gulp.watch(config.paths.src.js.all + '**/*.js', exports.jsCompile);
}

function jsCompile (src, dest, callback)
{
    return gulp.src([
            config.paths.src.js.shared + '*.js',
            src + '*.js'
        ])
        .pipe(concat('scripts.js'))
        .pipe(minify({
            mangle: {
                keepClassName: true
            }
        }))
        .pipe(rename({ suffix: '.min' }))
        .pipe(gulp.dest(dest))
}
