const { default: TomSelect } = require("tom-select");

window.Inachis.Components = {
	initialize() {
		this.initBackToTop();
		this.initClearSearch('');
		this.initCopyPaste('');
		this.initDatePicker();
		this.initFilterBar();
		this.initOptionSelectors();
		this.initPasswordToggle();
		this.initReadingProgress();
		this.initTomSelect('');
		this.initSelectAllNone('');
		this.initSeriesControls();
		this.initSwitches('');
		this.initUIToggle();

		$('.image_preview .button--confirm').on('click', (event) => {
			event.preventDefault();
			const $imagePreview = $('.image_preview');
			$imagePreview.find('input[type=hidden]').val('');
			$imagePreview.find('img').remove();
			$imagePreview.find('button.button--confirm').remove();
		});

		$('.ui-sortby select#sort').on('change', function () {
			$(this).closest('form').trigger('submit');
		});

		tabs('.ui-tabbed');
		$('.error-select').hide();
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
		$(`${selector}.clear-search`).on('click', function () {
			const $searchBox = $($(this).attr('data-target'));
			$searchBox.val('');
			$searchBox.closest('form').trigger('submit');
		});
	},
	initCopyPaste(selector) {
		$(`${selector}.button--copy`).on('click', async function () {
			const $textSource = $(`#${$(this).attr('data-target')}`);
			const copyText = ($(this).attr('data-prefix') ?? '') + $textSource.val();
			try {
				await navigator.clipboard.writeText(copyText);
			} catch (err) {
				console.error('Failed to copy: ', err)
			}
		});
	},
	initDatePicker() {
		// http://xdsoft.net/jqplugins/datetimepicker/
		// if ($('html').attr('lang')) {
		// 	$.datetimepicker.setLocale($('html').attr('lang'));
		// }
		$('#post_postDate').each(function () {
			$(this).datetimepicker({
				format: 'd/m/Y H:i',
				validateOnBlue: false,
				onChangeDateTime: function (dp, $input) {
					if (window.Inachis.PostEdit) {
						// @todo Need to update JS so that it only updates URL if previously set URL matches the auto-generated pattern
						$('input#post_url').val(window.Inachis.PostEdit.getUrlFromTitle());
					}
				}
			});
		});
	},
	initFilterBar() {
		const toggle = document.querySelector('.filter__toggle');
		const panel  = document.getElementById('filter__options');
		const filterSelects = panel.querySelectorAll('select');

		if (!toggle || !panel) {
			return;
		}

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
		$('button.button--password-toggle').on('click', function () {
			const $button = $(this);
			const $input = $(`input[data-controller=${$button.data('action')}]`);
			if ($input.attr('type') === "password") {
				$input.attr('type', 'text');
				$button.html('visibility');
			} else {
				$input.attr('type', 'password');
				$button.html('visibility_off');
			}
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
		$(`${selector}.selectAllNone`).on('click', function () {
			$(this).closest('form').first().find('input[type=checkbox]').prop('checked', $(this).prop('checked'));
			window.Inachis.Components.toggleActionBar();
		});
		$('input[name^="items"]').on('change', this.toggleActionBar);
	},
	initSeriesControls() {
		$('input[name=series\\[itemList\\]\\[\\]]').on('change', function () {
			const uncheckedItems = $('input[name=series\\[itemList\\]\\[\\]]:not(:checked)');
			const checkedItems = $('input[name=series\\[itemList\\]\\[\\]]:checked');
			const anyChecked = checkedItems.length > 0;
			$('.series__controls').toggleClass('visually-hidden', !anyChecked);

			checkedItems.closest('tr').addClass('selected');
			uncheckedItems.closest('tr').removeClass('selected');
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
		const $uiToggle = $('.ui-toggle');
		$uiToggle.each(function () {
			const targetElement = $(this).attr('data-target');
			const targetDefaultState = $(this).attr('data-target-state');
			if (targetDefaultState === 'hidden') {
				$(targetElement).hide();
			}
		});
		$uiToggle.on('click', function () {
			$($(this).attr('data-target')).toggle();
		});
	},

	toggleActionBar() {
		const uncheckedItems = $('input[name^="items"]:not(:checked)');
		const checkedItems = $('input[name^="items"]:checked');
		const anyUnchecked = uncheckedItems.length > 0;
		const anyChecked = checkedItems.length > 0;
		checkedItems.closest('tr').addClass('selected');
		checkedItems.closest('article').addClass('selected');
		uncheckedItems.closest('tr').removeClass('selected');
		uncheckedItems.closest('article').removeClass('selected');
		$('.fixed-bottom-bar').toggleClass('visually-hidden', !anyChecked);
		$('.selectAllNone').prop('checked', !anyUnchecked);
	}
};

$(document).ready(() => {
	window.Inachis.Components.initialize();
});
