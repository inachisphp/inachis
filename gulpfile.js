const gulp = require('gulp');
const requireDir = require('require-dir');
// load tasks
requireDir('./bin/gulp/tasks', {
    recurse: true,
    mapValue: function(value) {
        if (typeof value === 'object') {
            const keys = Object.keys(value);
            return keys.map(taskName => {
                return gulp.task(taskName, value[taskName])
            });
        }
    }
});
