var InachisPostEdit = {
	_pageTitle: '',
	_postOrPage: 'post',
	categoryList: '',

	_init: function()
	{
		var currentContentType = window.location.href.match(/(post|page)/gi);
		if (null !== currentContentType) {
			this._postOrPage = currentContentType[0].toLowerCase();
		}
        // if ($('.sf-toolbarreset').length && $('.fixed-bottom-bar').length) {
        // 	var _BarHeight = $('.sf-toolbarreset').css('height');
        // 	$('.fixed-bottom-bar').css('bottom', _BarHeight);
        // 	$('.ui-tabs-panel').css('margin-bottom', _BarHeight);
        // }
		this.initTooltips();
		this.initTitleChange();
		if (this.hasAutosavedValue()) {
			$('#subTitle_label').parent().after(
				$('<p>', {'class': 'autosave-notification'})
					.text('The below content has been recovered from an auto-save and must be saved before publishing.')
					.append(
						$('<button>', {'class': 'button button--negative', 'type': 'button'})
							.text('Clear auto-save')
							.on('click',function(event)
							{
								event.preventDefault();
								easymde.clearAutosavedValue();
								location.reload();
							})
					)
			);
		}
	},

	initTooltips: function()
	{
		$('[data-qtip-content]').qtip({
			content: {
				text: function() {
					return $(this).attr('data-tip-content');
				},
				title: function() {
					return $(this).attr('data-tip-title');
				}
			},
			position: {
				target: 'mouse',
				adjust: { mouse: true }
			}
		});
	},

	initTitleChange: function()
	{
		$(document).on('keyup', '#post__edit #post_title, #post__edit #post_subTitle', $.proxy(function(event)
		{
			if ([
				13, // enter
				16, // shift
				17, // ctrl
				18, // alt
				19, // pause
				20, // capslock
				27, // esc
				33, // page up
				34, // page down
				35, // end
				36, // home
				37, // left
				38, // up
				39, // right
				40, // down
				45, // insert
				91, // left-window / apple
				92, // right-window / apple
				93, // select key
				112, // f1
				113, // f2
				114, // f3
				115, // f4
				116, // f5
				117, // f6
				118, // f7
				119, // f8
				120, // f9
				121, // f10
				122, // f11
				123, // f12
				124, // f13
				125, // f14
				126, // f15
				127, // f16
				128, // f17
				129, // f18
				130, // f19
				144, // num lock
				145 // scroll lock
			].indexOf(event.which) >= 0) {
				return;
			}
			var urlInput = $('input#post_url');
			urlInput.val(this.getUrlFromTitle());
		}, this));
	},

	getUrlFromTitle: function()
	{
		var $originalTitle = $('#post_url').val();
		if ($originalTitle === '' || $originalTitle.match(/\d{4}\/\d{2}\/\d{2}\/[a-z0-9\-]+/)) {
			var $postContainer = $('#post__edit'),
				title = this.urlify($postContainer.find('#post_title').val()),
				subTitle = this.urlify($postContainer.find('#post_subTitle').val());
			if (title.length > 0 && subTitle.length > 0) {
				title += '-';
			}
			title += subTitle;
			if (this._postOrPage === 'post') {
				title = this.convertDate($('#post_postDate').val().substring(0,10)) + '/' + title.substring(0, 255);
			}
			title = this.ensureUniqueUrl();
			return title;
		}
		return $originalTitle;
	},

	convertDate: function(value)
	{
		value = value.split('/');
		if (value[0] == null || value[1] == null || value[2] == null) {
			return '';
		}
		return value[2] + '/' + value[1] + '/' + value[0];
	},

	urlify: function(value)
	{
		if (typeof value === 'undefined') {
			return;
		}
		return value.toLowerCase().replace(/&/g, 'and').replace(/[_\s]/g, '-').normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/[^a-z0-9\-]/gi, '')
	},

	hasAutosavedValue: function()
	{
		return easymde && easymde.options.element.defaultValue !== easymde.value();
	},

	ensureUniqueUrl: function()
	{
		$.ajax({
			data: {
				id: easymde.options.autosave.uniqueId,
				url: $('#post_url')[0].value
			},
			dataType: 'json',
			method: 'post',
			url: '/incc/ax/check-url-usage',
		}).done(function (response) {
			return response.responseText;
		});
	}
};

$(document).ready(function () {
	InachisPostEdit._init();
});
