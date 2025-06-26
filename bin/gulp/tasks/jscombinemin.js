"use strict";

const gulp = require('gulp');
const config = require('../config');

const concat = require('gulp-concat');
const minify = require('gulp-babel-minify');
const pump = require('pump');
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

// exports.jsWatch = function() {
//     gulp.watch(config.paths.src.js.all + '**/*.js', [
//         'js:compile-web',
//         'js:compile-admin'
//     ]);
// }

function jsCompile (src, dest, callback)
{
    pump(
        [
            gulp.src([ config.paths.src.js.shared + '**/*.js', src + '**/*.js' ]),
            concat('scripts.js'),
            minify({
                mangle: {
                    keepClassName: true
                }
            }),
            rename({ suffix: '.min' }),
            gulp.dest(dest)
        ],
        callback
    );
}
