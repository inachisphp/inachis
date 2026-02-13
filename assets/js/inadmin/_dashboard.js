// window.Inachis.Dashboard = {

// 	init: function()
// 	{
// 		var tabs = document.querySelectorAll('.widget-posts__tabs a');
// 		tabs.forEach(function (tab) {
// 			tab.addEventListener('click', function (e) {
// 			Inachis._log('Posts tab: ' + e.target.dataset.target);
// 			this.activeTab.parentNode.classList.toggle('widget-posts__tabs__active');
// 			e.target.parentNode.classList.toggle('widget-posts__tabs__active');
// 			Inachis._log('Hiding tab: ' + this.activeTab.dataset.target);
// 			Inachis._log('Showing tab: ' + e.target.dataset.target);
// 			document.querySelector('.' + this.activeTab.dataset.target + ',' + '.' + e.target.dataset.target).classList.toggle('widget-posts__active');
// 			this.activeTab = e.target;
// 		});
// 		this.activeTab = tabs[0];
// 	}
// };

// document.addEventListener('DOMContentLoaded', () => {
// 	window.Inachis.Dashboard.init();
// });

