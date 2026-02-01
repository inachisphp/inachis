window.Inachis.PostEdit = {
	pageTitle: '',
	postOrPage: 'post',
	ajaxTimeout: null,

	init() {
		const match = window.location.href.match(/(post|page)/i);
		if (match) {
			this.postOrPage = match[0].toLowerCase();
		}

		this.initTitleChange();

		if (this.hasAutosavedValue()) {
			this.showAutosaveNotification();
		}
	},

	initTitleChange() {
		const inputs = document.querySelectorAll('#post_title, #post_subTitle');

		inputs.forEach(input =>
			input.addEventListener('input', event => {
				if (this.ajaxTimeout) clearTimeout(this.ajaxTimeout);

				const excludedKeys = new Set([
					13, 16, 17, 18, 19, 20, 27, 33, 34, 35, 36,
					37, 38, 39, 40, 45, 91, 92, 93,
					112, 113, 114, 115, 116, 117, 118, 119,
					120, 121, 122, 123, 124, 125, 126, 127,
					128, 129, 130, 145
				]);

				this.ajaxTimeout = setTimeout(() => {
					if (excludedKeys.has(event.which)) return;

					const urlInput = document.querySelector('input#post_url');
					if (urlInput) urlInput.value = this.getUrlFromTitle();
				}, 500);
			})
		);
	},

	getUrlFromTitle() {
		const urlInput = document.querySelector('input#post_url');
		const originalTitle = urlInput?.value || '';

		if (
			originalTitle === '' ||
			/\d{4}\/\d{2}\/\d{2}\/[a-z0-9\-]+/.test(originalTitle)
		) {
			const postContainer = document.querySelector('#post__edit');
			if (!postContainer) return originalTitle;

			let title = this.urlify(
				postContainer.querySelector('#post_title')?.value
			);
			const subTitle = this.urlify(
				postContainer.querySelector('#post_subTitle')?.value
			);

			if (title && subTitle) {
				title += '-';
			}
			title += subTitle;

			if (this.postOrPage === 'post') {
				const postDate = document.querySelector('#post_postDate')?.value;
				if (postDate) {
					title = this.convertDate(postDate.substring(0, 10)) + '/' + title.substring(0, 255);
				}
			}

			this.ensureUniqueUrl(title);
			return title;
		}

		return originalTitle;
	},

	convertDate(value) {
		const parts = value.split('/');
		if (parts.length < 3) return '';
		return `${parts[2]}/${parts[1]}/${parts[0]}`;
	},

	urlify(value) {
		if (!value) return '';
		return value
			.toLowerCase()
			.replace(/&/g, 'and')
			.replace(/[_\s]/g, '-')
			.normalize('NFD')
			.replace(/[\u0300-\u036f]/g, '')
			.replace(/[^a-z0-9\-]/gi, '');
	},

	hasAutosavedValue() {
		return 'easymde' in window && easymde.options.element.defaultValue !== easymde.value();
	},

	showAutosaveNotification() {
		const postTitleInput = document.querySelector('#subTitle_label');
		if (!postTitleInput?.parentNode) return;

		const container = postTitleInput.parentNode;

		const p = document.createElement('p');
		p.className = 'autosave-notification';
		p.textContent = 'The below content has been recovered from an auto-save and must be saved before publishing.';

		const button = document.createElement('button');
		button.type = 'button';
		button.className = 'button button--negative';
		button.textContent = 'Clear auto-save';

		button.addEventListener('click', event => {
			event.preventDefault();
			easymde.clearAutosavedValue();
			location.reload();
		});

		p.appendChild(button);
		container.after(p);
	},

	async ensureUniqueUrl(title) {
		try {
			const params = new URLSearchParams();
			params.append('id', easymde.options.autosave.uniqueId);
			params.append('url', title);

			const response = await fetch(`${window.Inachis.prefix}/ax/check-url-usage`, {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: params.toString(),
			});

			if (!response.ok) throw new Error('Failed to check URL');

			const uniqueUrl = await response.text();

			const urlInput = document.querySelector('input#post_url');
			if (urlInput) urlInput.value = uniqueUrl;
		} catch (err) {
			console.error('PostEdit ensureUniqueUrl failed:', err);
		}
	},
};

$(document).ready(function () {
	window.Inachis.PostEdit.init();
});
