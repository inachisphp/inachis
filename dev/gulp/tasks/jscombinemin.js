import gulp from 'gulp';
import concat from 'gulp-concat';
import minify from 'gulp-babel-minify';
import rename from 'gulp-rename';
import config from '../config.js';

function jsCompile(src, dest) {
    const files = [
        `${config.paths.src.js.shared}_*.js`,
        `${src}_*.js`
    ];
    if (src.includes('inadmin')) {
        files.unshift(`${config.paths.src.js.admin}Inachis.js`);
    }
    return gulp.src(files)
        .pipe(concat('scripts.js'))
        .pipe(minify({ mangle: { keepClassName: true } }))
        .pipe(rename({ suffix: '.min'}))
        .pipe(gulp.dest(dest));
}

function copyJS(src, dest) {
    return gulp.src([ `${src}[^_][^Inachis]*.js` ])
        // .pipe(rename(function (path) {
        //     if (!path.basename.endsWith('.min')) {
        //         path.basename += '.min';
        //     }
        // }))
        .pipe(gulp.dest(dest));
}

export const jsCompileAdmin = () =>
    jsCompile(config.paths.src.js.admin, config.paths.dist.js.admin);

export const jsCompileWeb = () =>
    jsCompile(config.paths.src.js.web, config.paths.dist.js.web);

export const jsCopyAdmin = () =>
    copyJS(config.paths.src.js.admin, config.paths.dist.js.admin);

export const jsCopyWeb = () =>
    copyJS(config.paths.src.js.web, config.paths.dist.js.web);

export const jsCompileAll = gulp.parallel(
    jsCompileAdmin,
    jsCompileWeb,
    jsCopyWeb,
    jsCopyAdmin,
);

export function jsWatch() {
    gulp.watch(`${config.paths.src.js.all}**/*.js`, jsCompileAll);
}
