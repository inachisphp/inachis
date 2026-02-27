const { default: TomSelect } = require("tom-select");
const { default: DatePicker } = require("./components/datePicker.js");

window.Inachis.Components = {
	init() {
		this.initBackToTop();
		this.initClearSearch('');
		this.initCopyPaste('');
		this.initDatePicker();
		this.initExportButton();
		this.initFilterBar();
		this.initOptionSelectors();
		this.initPasswordToggle();
		this.initReadingProgress();
		this.initTomSelect('');
		this.initSelectAllNone('');
		this.initSeriesControls();
		this.initSwitches('');
		this.initUIToggle();

		document.querySelectorAll('.image_preview .button--confirm').forEach(button => {
			button.addEventListener('click', (event) => {
				event.preventDefault();
				const imagePreview = button.closest('.image_preview');
				if (!imagePreview) return;
				const hiddenInput = imagePreview.querySelector('input[type="hidden"]');
				if (hiddenInput) hiddenInput.value = '';
				imagePreview.querySelectorAll('img, button.button--confirm').forEach(el => {
					el.remove();
				});
			});
		});

		document.querySelectorAll('.ui-sortby select#sort').forEach(select => {
			select.addEventListener('change', () => {
				select.closest('form').requestSubmit();
			});
		});

		tabs('.ui-tabbed');
		document.querySelectorAll('.error-select').forEach(el => {
			el.style.display = 'none';
		});
	},

	initBackToTop() {
		const backToTop = document.getElementById('back-to-top');

		window.addEventListener('scroll', () => {
			backToTop.hidden = window.scrollY < 300;
		});
		backToTop.addEventListener('click', () => {
			window.scrollTo({ top: 0, behavior: 'smooth' });
		});
	},

	initClearSearch(selector) {
		document.querySelectorAll(`${selector}.clear-search`).forEach(button => {
			button.addEventListener('click', () => {
				const searchBox = document.querySelector(button.dataset.target);
				searchBox.value = '';
				searchBox.closest('form').requestSubmit();
			});
		});
	},
	initCopyPaste(selector) {
		document.querySelectorAll(`${selector}.button--copy`).forEach(button => {
			button.addEventListener('click', async () => {
				const textSource = document.querySelector(`#${button.dataset.target}`);
				const copyText = (button.dataset.prefix ?? '') + textSource.value;
				try {
					await navigator.clipboard.writeText(copyText);
				} catch (err) {
					console.error('Failed to copy: ', err)
				}
			});
		});
	},
	initDatePicker() {
		const postDateSelector = document.querySelector('#post_postDate');
		if (!postDateSelector) return;
		const datePicker = new DatePicker('#post_postDate', {
			onChange: (formattedDate) => {
				if (window.Inachis?.PostEdit) {
					document.querySelector('#post_url').value = window.Inachis.PostEdit.getUrlFromTitle();
				}
			},
			format: 'dd/mm/yyyy HH:ii',
			materialIcons: true,
		});
	},
	initExportButton() {
		const exportButton = document.querySelector('.button--export');
		if (!exportButton) return;

		exportButton.addEventListener('click', () => {
			const hiddenData = {
				scope: exportButton.dataset.scope,
				content_type: exportButton.dataset.contentType,
				selectedIds: Array.from(form.querySelectorAll('input[name="items[]"]:checked')).map(cb => cb.value),
			}
			const formAction = exportButton.dataset.formAction;
			const listForm = document.querySelector('form.form');
			if (!listForm) return;

			listForm.action = formAction;
			Object.entries(hiddenData).forEach(([name, value]) => {
				const input = document.createElement('input');
				input.type = 'hidden';
				input.name = name;
				input.value = value;
				listForm.appendChild(input);
			});
			listForm.requestSubmit();
		});
	},
	initFilterBar() {
		const toggle = document.querySelector('.filter__toggle');
		const panel  = document.getElementById('filter__options');

		if (!toggle || !panel) {
			return;
		}
		const filterSelects = panel.querySelectorAll('select');

		filterSelects.forEach(select => {
			select.addEventListener('change', () => {
				select.closest('form').requestSubmit();
			});
		});

		toggle.addEventListener('click', () => {
			const isOpen = toggle.getAttribute('aria-expanded') === 'true';
			toggle.setAttribute('aria-expanded', String(!isOpen));

			if (isOpen) {
				panel.classList.remove('is-open');
				panel.addEventListener('transitionend', () =>
					panel.setAttribute('hidden', ''),
					{ once: true }
				);
			} else {
				panel.removeAttribute('hidden');
				panel.offsetHeight;
				panel.classList.add('is-open');
				panel.querySelector('input, select, button')?.focus();
			}
		});
	},
	initOptionSelectors() {
		document.querySelectorAll('.option-selector').forEach(element => {
			optionSelector(element);
		});
	},
	initPasswordToggle() {
		const passwordToggles = document.querySelectorAll('button.button--password-toggle');
		passwordToggles.forEach(toggle => {
			toggle.addEventListener('click', () => {
				const input = document.querySelector(`input[data-controller=${toggle.dataset.action}]`);
				if (input.type === "password") {
					input.type = 'text';
					toggle.innerHTML = 'visibility';
				} else {
					input.type = 'password';
					toggle.innerHTML = 'visibility_off';
				}
			});
		});
	},
	initReadingProgress() {
		const bar = document.querySelector('.reading-progress__bar');
		if (!bar) return;

		if (document.documentElement.scrollHeight <= window.innerHeight) {
			bar.style.display = 'none';
			return;
		}

		function updateProgress() {
			const doc = document.documentElement;
			const scrollTop = doc.scrollTop || document.body.scrollTop;
			const scrollHeight = doc.scrollHeight - doc.clientHeight;

			const progress = scrollHeight > 0
			? (scrollTop / scrollHeight) * 100
			: 0;

			bar.style.width = `${progress}%`;
		}

		window.addEventListener('scroll', updateProgress, { passive: true });
		window.addEventListener('resize', updateProgress);
		window.addEventListener('load', updateProgress);
		document.addEventListener('DOMContentLoaded', updateProgress);
	},
	initTomSelect(selector) {
		document.querySelectorAll(selector + ' .js-select').forEach(el => {
			const descriptionField = el.dataset.renderDescriptionField;
			const isTags = el.dataset.tags === 'true';
			const minQueryLength = 2;

			const options = {
				maxItems: isTags ? null : 1,
				valueField: 'id',
				labelField: 'text',
				searchField: 'text',
				sortField: 'text',
				create: isTags,
				persist: isTags ? false : undefined,
				loadThrottle: 300,

				plugins: {
					clear_button: { title: 'Remove all selected options' },
					...(isTags
						? {
							checkbox_options: {
								checkedClassNames: ['ts-checked'],
								uncheckedClassNames: ['ts-unchecked']
							},
							remove_button: { title: 'Remove this item' }
						}
						: {}
					)
				},

				load: el.dataset.url
				? function(query, callback) {
					query = query.trim();
					if (query.length < minQueryLength) return callback([]);
					fetch(el.dataset.url, {
						method: 'POST',
						headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
						body: new URLSearchParams({ q: query })
					})
						.then(res => res.json())
						.then(json => callback(json.items || []))
						.catch(err => {
							console.error('TomSelect load error:', err);
							callback([]);
						});
					}
				: undefined,

				shouldLoad: query => query.trim().length >= minQueryLength,

				render: {
					option: function(item, escape) {
						let desc = descriptionField ? item[descriptionField] || '' : '';
						if (desc.length > 50) desc = desc.slice(0, 47) + '…';
						return `<div role="option" aria-label="${escape(item.text)}${desc ? ' ' + escape(desc) : ''}">
								${escape(item.text)}
								${desc ? `<small>${escape(desc)}</small>` : ''}
								</div>`;
					},
					item: function(item, escape) {
						return `<div>${escape(item.text)}</div>`;
					},
					no_results: function() {
						return '<div class="no-results" role="alert" aria-live="polite">No matching options</div>';
					}
				},

				maxOptions: 100
			};

			if (!el.placeholder) el.setAttribute('aria-label', 'Select an option');
			el.classList.add('wcag-select');

			new TomSelect(el, options);
		});
	},
	initSelectAllNone(selector) {
		document.querySelectorAll(`${selector}.selectAllNone`).forEach(el => {
			el.addEventListener('click', () => {
				const form = el.closest('form');
				const checkboxes = form.querySelectorAll('input[type=checkbox]');
				checkboxes.forEach(cb => cb.checked = el.checked);
				window.Inachis.Components.toggleActionBar();
			});
		});
		document.querySelectorAll('input[name^="items"]').forEach(el => {
			el.addEventListener('change', () => {
				window.Inachis.Components.toggleActionBar();
			});
		});
	},
	initSeriesControls() {
		document.querySelectorAll('input[name="series[itemList][]"]').forEach(input => {
			input.addEventListener('change', () => {
				const allItems = document.querySelectorAll('input[name="series[itemList][]"]');
				const checkedItems = document.querySelectorAll('input[name="series[itemList][]"]:checked');
				const anyChecked = checkedItems.length > 0;

				document.querySelectorAll('.series__controls').forEach(el => {
					el.classList.toggle('visually-hidden', !anyChecked);
				});
				allItems.forEach(item => {
					const row = item.closest('tr');
					if (!row) return;

					if (item.checked) {
						row.classList.add('selected');
					} else {
						row.classList.remove('selected');
					}
				});
			});
		});
	},
	initSwitches(selector) {
		document
			.querySelectorAll(`${selector} .ui-switch`)
			.forEach((checkbox) => {
				window.Inachis.SwitchButton.create(checkbox, {
					onLabel: checkbox.dataset.labelOn || 'On',
					offLabel: checkbox.dataset.labelOff || 'Off',
				});
			});
	},
	initUIToggle() {
		const uiToggle = document.querySelectorAll('.ui-toggle');
		uiToggle.forEach(el => {
			const targetElement = el.getAttribute('data-target');
			const targetDefaultState = el.getAttribute('data-target-state');
			if (targetDefaultState === 'hidden') {
				document.querySelector(targetElement).classList.add('visually-hidden');
			}
			el.addEventListener('click', () => {
				const targetElement = document.querySelector(el.getAttribute('data-target'));
				targetElement.classList.toggle('visually-hidden');
				targetElement.setAttribute('aria-hidden', targetElement.classList.contains('visually-hidden'));
			});
		});
	},

	toggleActionBar() {
		const items = document.querySelectorAll('input[name^="items"]');

		let anyChecked = false;
		let anyUnchecked = false;

		items.forEach(item => {
			const isChecked = item.checked;
			anyChecked ||= isChecked;
			anyUnchecked ||= !isChecked;

			const row = item.closest('tr');
			const article = item.closest('article');

			if (row) row.classList.toggle('selected', isChecked);
			if (article) article.classList.toggle('selected', isChecked);
		});
		document.querySelectorAll('.fixed-bottom-bar').forEach(el => {
			el.classList.toggle('visually-hidden', !anyChecked);
			el.setAttribute('aria-hidden', !anyChecked);
		});
		document.querySelectorAll('.selectAllNone').forEach(el => {
			el.checked = !anyUnchecked;
			el.setAttribute('aria-checked', !anyUnchecked);
		});
	}
};