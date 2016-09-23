var InachisNotifications = {
	_pageTitle: '',

	_init: function()
	{
		this._pageTitle = document.title;

		// add event handler for checking for updates
	},

	updateTitle: function (notificationCount)
	{
		if (notificationCount > 0) {
			document.title = '(' + notificationCount + ') ' + document.title;
		} else {
			document.title = document.title;
		}
		
	}
};

$(document).ready(function () {
	InachisNotifications._init();
});
