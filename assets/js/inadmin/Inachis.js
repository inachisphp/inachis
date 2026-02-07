window.Inachis = {
	_debug: false,
	prefix: '/incc',

	_log(msg) {
		if (this._debug) {
			console.log(msg);
		}
	},

	debounce(fn, delay = 300) {
		let timer;

		return function (...args) {
			clearTimeout(timer);
			timer = setTimeout(() => {
				fn.apply(this, args);
			}, delay);
		};
	}
};
