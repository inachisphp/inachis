'use strict';

const { parallel } = require('gulp');
const { sassWatch } = require('./sass');
const { jsWatch } = require('./jscombinemin')

exports.default = parallel(sassWatch, jsWatch);