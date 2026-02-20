window.Inachis = {
	_debug: false,
	prefix: '/incc',

	_log(msg) {
		if (this._debug) {
			console.log(msg);
		}
	},

	bootstrap() {
		window.Inachis.BulkCreateDialog.init();
		window.Inachis.CategoryManager.init();
		window.Inachis.Components.init();
		window.Inachis.ConfirmationPrompt.init();
		window.Inachis.DragDropTable.init();
		window.Inachis.ImageManager.init();
		window.Inachis.PostEdit.init();
	},

	initOnClick(selector, handler) {
		const el = document.querySelector(selector);
		if (!el) return;

		el.addEventListener('click', e => {
			e.preventDefault();
			handler();
		});
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

document.addEventListener('DOMContentLoaded', () => {
	window.Inachis.bootstrap();
});
