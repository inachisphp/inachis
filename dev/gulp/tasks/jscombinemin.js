import gulp from 'gulp';
import concat from 'gulp-concat';
import minify from 'gulp-babel-minify';
import rename from 'gulp-rename';
import config from '../config.js';

function jsCompile(src, dest) {
    return gulp.src([
            `${config.paths.src.js.shared}*.js`,
            `${src}*.js`
        ])
        .pipe(concat('scripts.js'))
        .pipe(minify({ mangle: { keepClassName: true } }))
        .pipe(rename({ suffix: '.min'}))
        .pipe(gulp.dest(dest));
}

export const jsCompileAdmin = () =>
    jsCompile(config.paths.src.js.admin, config.paths.dist.js.admin);

export const jsCompileWeb = () =>
    jsCompile(config.paths.src.js.web, config.paths.dist.js.web);

export const jsCompileAll = gulp.parallel(jsCompileAdmin, jsCompileWeb);

export function jsWatch() {
    gulp.watch(`${config.paths.src.js.all}**/*.js`, jsCompileAll);
}
