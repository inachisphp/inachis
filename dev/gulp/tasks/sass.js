import gulp from 'gulp';
import * as dartSass from 'sass';
import gulpSass from 'gulp-sass';
import autoprefixer from 'gulp-autoprefixer';
import cssnano from 'gulp-cssnano';
import rename from 'gulp-rename';
import plumber from 'gulp-plumber';
import config from '../config.js';

const sass = gulpSass(dartSass);

async function compileSass(src, dest) {
    return gulp.src(`${src}**/*.scss`)
        .pipe(plumber())
        .pipe(sass.sync().on('error', sass.logError))
        .pipe(autoprefixer({
            cascade: false,
            overrideBrowserslist: ['last 2 versions']
        }))
        .pipe(cssnano())
        .pipe(rename({ suffix: '.min' }))
        .pipe(gulp.dest(dest));
}

async function copyCSS(src, dest) {
    return gulp.src(`${config.paths.src.sass.admin}*.css`)
        .pipe(rename({ suffix: '.min' }))
        .pipe(gulp.dest(config.paths.dist.sass.admin));
}

export const sassCompileWeb = async () =>
    compileSass(config.paths.src.sass.web, config.paths.dist.sass.web);

export const sassCompileAdmin = async () =>
    compileSass(config.paths.src.sass.admin, config.paths.dist.sass.admin);

export const copyCSSAdmin = async () =>
    copyCSS(config.paths.src.sass.admin, config.paths.dist.sass.admin);

export const sassCompile = gulp.parallel(
    sassCompileWeb,
    sassCompileAdmin,
    copyCSSAdmin
);

export async function sassWatch() {
    gulp.watch(`${config.paths.src.sass.all}**/*.scss`, gulp.series(sassCompile));
}
