// window.Inachis.Dashboard = {
//
// 	_init: function()
// 	{
// 		var tabs = $('.widget-posts__tabs a').click($.proxy(function (e) {
// 			Inachis._log('Posts tab: ' + e.target.dataset.target);
// 			$(this.activeTab.parentNode).toggleClass('widget-posts__tabs__active');
// 			$(e.target.parentNode).toggleClass('widget-posts__tabs__active');
// 			Inachis._log('Hiding tab: ' + this.activeTab.dataset.target);
// 			Inachis._log('Showing tab: ' + e.target.dataset.target);
// 			$('.' + this.activeTab.dataset.target + ',' + '.' + e.target.dataset.target).toggleClass('widget-posts__active');
// 			this.activeTab = e.target;
// 		}, this));
// 		this.activeTab = tabs[0];
// 	}
// };
//
// $(document).ready(function () {
// 		window.Inachis.Dashboard._init();
// });

