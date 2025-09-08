function initSwitches(selector)
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
}

function initClearSearch(selector)
{
	$(selector + '.clear-search').on('click', function()
	{
		$($(this).attr('data-target')).val('').val('');
	});
}

$(document).ready(function() {
	var $uiToggle = $('.ui-toggle');
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

	var $filterOptions = $('.filter .filter__toggle');
	$filterOptions.on('click', function()
	{
		$('#filter__options').toggle();
		$(this).toggleClass('selected');
	});
	if ($('#filter__keyword').val() !== '') {
		$('#filter__options').toggle();
		$('.filter .filter__toggle').toggleClass('selected');
	}

	// https://select2.github.io/examples.html
	$('.js-select').each(function ()
	{
		var $properties = {
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

	// https://github.com/daredevel/jquery-tree
	$('ui-tree').each(function()
	{
		
	});

	// http://xdsoft.net/jqplugins/datetimepicker/
	// if ($('html').attr('lang')) {
	// 	$.datetimepicker.setLocale($('html').attr('lang'));
	// }
	$('#post_postDate').each(function ()
	{
		$(this).datetimepicker({
			format: 'd/m/Y H:i',
			validateOnBlue: false,
            onChangeDateTime:function(dp,$input) {
				if (InachisPostEdit) {
					// @todo Need to update JS so that it only updates URL if previously set URL matches the auto-generated pattern
                    $('input#post_url').val(InachisPostEdit.getUrlFromTitle());
				}
            }
		});
	});

	initSwitches('');
	initClearSearch('');

	// jQuery Tabs
	$('.ui-tabbed').tabs();

	// Select all/none buttons
	$('.button--select-all').on('click', function()
	{
		$(this).closest('form').first().find('input[type=checkbox]').prop('checked', true);
	});
	$('.button--select-none').on('click', function()
	{
		$(this).closest('form').first().find('input[type=checkbox]').prop('checked', false);
	});
	$('.error-select').hide();

    $(function() {
        $('#progressbar').progressbar({
            value: $('#progressbar').data('percentage')
        });
    });
});
