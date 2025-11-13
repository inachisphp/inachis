let InachisNavMenu = {
	_navNewVisible: false,
	_navUserVisible: false,
	_contextMenuVisible: false,

	_init: function()
	{
		// Add menu
		$('.admin__add-content').click($.proxy(function() {
			$('.admin__nav-new').toggle();
			this._navNewVisible = !this._navNewVisible;
			Inachis._log('New menu visible: ' + this._navNewVisible);
			if (this._navNewVisible) {
				$(document).mouseup($.proxy(this.newNavMouseOut, this));
			}
			return false;
		}, this));
		// settings menu
		$('a[href*=admin__nav-settings]').click(function() {
			$('li.menu__collapsed').toggle();
		});
		// collapse/expand links
		$('.admin__nav-expand a, .admin__nav-collapse a').click(function () {
			$('.admin__container').toggleClass('admin__container--collapsed admin__container--expanded');
		});
		// menu link (mobile only)
		$('.admin__nav-main__link a').click($.proxy(function() {
			$('.admin__container').toggleClass('admin__container--collapsed admin__container--expanded');
			$('.admin__nav-main__list').toggle('slide');
			this._navNewVisible = !this._navNewVisible;
			Inachis._log('New menu visible: ' + this._navNewVisible);
			if (this._navNewVisible) {
				$(document).mouseup($.proxy(this.newNavMouseOut, this));
			}
			return false;
		}, this));
		// user menu
		$('.admin__user > a').click($.proxy(function() {
			$('#admin__user__options').toggle();
			this._navUserVisible = !this._navUserVisible;
			Inachis._log('User menu visible: ' + this._navUserVisible);
			if (this._navUserVisible) {
				$(document).mouseup($.proxy(this.userNavMouseOut, this));
			}
			return false;
		}, this));
	},

	newNavMouseOut: function(e)
	{
        this.genericNavMouseOut(
        	e,
            $('.admin__nav-new'),
            '_navNewVisible',
            'New',
            InachisNavMenu.newNavMouseOut
        );
	},

	userNavMouseOut: function(e)
	{
		this.genericNavMouseOut(
			e,
			$('#admin__user__options'),
			'_navUserVisible',
			'User',
            InachisNavMenu.userNavMouseOut
		);
	},

	// use this for handling menus that disappear when clicking away
	genericNavMouseOut: function(e, container, navProperty, menuLabel, callback)
	{
        if (!container.is(e.target) && container.has(e.target).length === 0)  {
            container.hide();
            this[navProperty] = !this[navProperty];
            Inachis._log(menuLabel + ' menu visible: ' + this[navProperty]);
            $(document).unbind('mouseup', callback);
        }
	}
};

$(document).ready(function () {
	InachisNavMenu._init();
});
