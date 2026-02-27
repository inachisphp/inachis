//http://webdesign.tutsplus.com/tutorials/how-to-display-update-notifications-in-the-browser-tab--cms-23458
window.Inachis.Notifications = {
	_pageTitle: '',

	_init() {
		this._pageTitle = document.title;

		// add event handler for checking for updates
	},

	updateTitle(notificationCount) {
		if (notificationCount > 0) {
			document.title = `(${notificationCount}) ${document.title}`;
		} else {
			//document.title = document.title;
		}

	}
};

document.addEventListener('DOMContentLoaded', () => {
	window.Inachis.Notifications._init();
});
