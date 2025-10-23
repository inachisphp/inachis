import gulp from 'gulp';
import { sassCompileAdmin, sassWatch } from './sass.js';
import { jsCompileAdmin, jsWatch } from './jscombinemin.js';
import { imgOptimiseAdmin } from "./imagemin.js";

export default gulp.series(
    imgOptimiseAdmin,
    gulp.parallel(sassCompileAdmin, jsCompileAdmin),
    gulp.parallel(sassWatch, jsWatch)
);

export const buildWeb = () => gulp.parallel(
    imgOptimiseWeb,
    sassCompileWeb,
    jsCompileWeb
);

export const buildAdmin = () => gulp.parallel(
    imgOptimiseAdmin,
    sassCompileAdmin,
    jsCompileAdmin
);