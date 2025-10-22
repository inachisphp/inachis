"use strict";

import gulp from 'gulp';
import concat from 'gulp-concat';
import imagemin from 'gulp-imagemin'
import rename from 'gulp-rename';
import config from '../config.js';

function imgOptimise(src, dest) {
    return gulp.src(src)
        .pipe(imagemin([], { verbose: true }))
        .pipe(gulp.dest(dest));
}

export const imgOptimiseAdmin = () =>
    imgOptimise(config.paths.src.images.admin, config.paths.dist.images.admin);


export const imgOptimiseWeb = () =>
    imgOptimise(config.paths.src.images.web, config.paths.dist.images.web);

export const imgOptimiseAll = gulp.parallel(imgOptimiseAdmin, imgOptimiseWeb);
