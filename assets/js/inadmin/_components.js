let InachisComponents = {
	initialize: function() {
		this.initClearSearch('');
		this.initDatePicker();
		this.initFilterBar();
		this.initSelect2('');
		this.initSelectAllNone('');
		this.initSeriesControls();
		this.initSwitches('');
		this.initUIToggle();

		// jQuery Tabs
		$('.ui-tabbed').tabs();
		$('.error-select').hide();

		$(function() {
			$('#progressbar').progressbar({
				value: $('#progressbar').data('percentage')
			});
		});
	},

	initClearSearch: function (selector)
	{
		$(selector + '.clear-search').on('click', function()
		{
			let $searchBox = $($(this).attr('data-target'));
			$searchBox.val('');
			$searchBox.closest('form').trigger('submit');
		});
	},
	initDatePicker: function ()
	{
		// http://xdsoft.net/jqplugins/datetimepicker/
		// if ($('html').attr('lang')) {
		// 	$.datetimepicker.setLocale($('html').attr('lang'));
		// }
		$('#post_postDate').each(function ()
		{
			$(this).datetimepicker({
				format: 'd/m/Y H:i',
				validateOnBlue: false,
				onChangeDateTime: function(dp,$input)
				{
					if (InachisPostEdit) {
						// @todo Need to update JS so that it only updates URL if previously set URL matches the auto-generated pattern
						$('input#post_url').val(InachisPostEdit.getUrlFromTitle());
					}
				}
			});
		});
	},
	initFilterBar: function()
	{
		let $filterOptions = $('.filter .filter__toggle');
		$filterOptions.on('click', function()
		{
			$('#filter__options').toggle();
			$(this).toggleClass('selected');
		});
		if ($('#filter__keyword').val() !== '') {
			$('#filter__options').toggle();
			$filterOptions.toggleClass('selected');
		}
	},
	// See https://select2.github.io/examples.html
	initSelect2: function (selector)
	{
		$(selector + '.js-select').each(function ()
		{
			let $properties = {
				allowClear: true,
				maximumInputLength: 20,
				width: '40%'
			};
			if ($(this).attr('data-tags')) {
				$properties.tags = 'true';
				$properties.tokenSeparators = [ ',' ];
			}
			if ($(this).attr('data-url')) {
				$properties.ajax = {
					url: $(this).attr('data-url'),
					dataType: 'json',
					data: function (params)
					{
						return {
							q: params.term,
							page: params.page
						};
					},
					delay: 250,
					method: 'POST',
					processResults: function (data, params)
					{
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
	initSelectAllNone: function (selector)
	{
		$(selector + '.selectAllNone').on('click', function()
		{
			$(this).closest('form').first().find('input[type=checkbox]').prop('checked', $(this).prop('checked'));
			InachisComponents.toggleActionBar();
		});
		$('input[name^="items"]').on('change', this.toggleActionBar);
	},
	initSeriesControls: function ()
	{
		$('input[name=series\\[itemList\\]\\[\\]]').on('change', function() {
			let anyChecked = $('input[name=series\\[itemList\\]\\[\\]]:checked').length > 0;
			$('.series__controls').toggleClass('visually-hidden', !anyChecked);
		});
	},
	initSwitches: function (selector)
	{
		$(selector + ' .ui-switch').each(function ()
		{
			var $properties = {
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
	initUIToggle: function ()
	{
		let $uiToggle = $('.ui-toggle');
		$uiToggle.each(function()
		{
			var targetElement = $(this).attr('data-target'),
				targetDefaultState = $(this).attr('data-target-state');
			if (targetDefaultState === 'hidden') {
				$(targetElement).hide();
			}
		});
		$uiToggle.on('click', function()
		{
			$($(this).attr('data-target')).toggle();
		});
	},

	toggleActionBar: function ()
	{
		let anyUnchecked = $('input[name^="items"]:not(:checked)').length > 0;
		let anyChecked = $('input[name^="items"]:checked').length > 0;
		$('.fixed-bottom-bar').toggleClass('visually-hidden', !anyChecked);
		$('.selectAllNone').prop('checked', !anyUnchecked);
	}
};

$(document).ready(function() {
	InachisComponents.initialize();
});
