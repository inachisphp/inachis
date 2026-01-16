window.Inachis.Components = {
	initialize() {
		this.initClearSearch('');
		this.initCopyPaste('');
		this.initDatePicker();
		this.initFilterBar();
		this.initPasswordToggle();
		this.initSelect2('');
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

		// jQuery Tabs
		$('.ui-tabbed').tabs();
		$('.error-select').hide();

		$(() => {
			$('#progressbar').progressbar({
				value: $('#progressbar').data('percentage')
			});
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
		const $filterOptions = $('.filter .filter__toggle');
		$filterOptions.on('click', function () {
			$('#filter__options').toggle();
			$(this).toggleClass('selected');
		});
		if ($('#filter__keyword').val() !== '') {
			$('#filter__options').toggle();
			$filterOptions.toggleClass('selected');
		}
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
	// See https://select2.github.io/examples.html
	initSelect2(selector) {
		$(`${selector}.js-select`).each(function () {
			const $properties = {
				allowClear: true,
				maximumInputLength: 20,
				width: 'element',
			};
			if ($(this).attr('data-tags')) {
				$properties.tags = 'true';
				$properties.tokenSeparators = [','];
			}
			if ($(this).attr('data-url')) {
				$properties.ajax = {
					url: $(this).attr('data-url'),
					dataType: 'json',
					data: function (params) {
						return {
							q: params.term,
							page: params.page
						};
					},
					delay: 250,
					method: 'POST',
					processResults: function (data, params) {
						params.page = params.page || 1;
						return {
							results: data.items,
							pagination: {
								more: (params.page * 25) < data.totalCount
							}
						};
					},
					cache: false
				};
			}
			$(this).select2($properties);
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
		$(`${selector} .ui-switch`).each(function () {
			const $properties = {
				checked: this.checked,
				clear: true,
				height: 20,
				width: 40
			};
			if ($(this).attr('data-label-on')) {
				$properties.on_label = $(this).attr('data-label-on');
			}
			if ($(this).attr('data-label-off')) {
				$properties.off_label = $(this).attr('data-label-off');
			}
			$(this).switchButton($properties);
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
