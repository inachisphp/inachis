window.Inachis.NavMenu = {
	init: function () {
		const body = document.body;
		const desktopBtn = document.querySelector('.desktop-menu-toggle');
		const layout = document.querySelector('.layout');
		const mobileBtn = document.querySelector('.mobile-menu-toggle');
		const overlay = document.querySelector('.sidebar-overlay');
		const savedState = localStorage.getItem('sidebarExpanded');
		const sidebar = document.querySelector('.sidebar');
		const submenuToggles = document.querySelectorAll('.submenu-toggle');
		if (savedState === 'true') {
			// layout.style.transition = 'none';
			layout.classList.add('expanded');
			// void layout.offsetWidth;
			// layout.style.transition = '';
		} else {
			layout.classList.remove('expanded');
		}

		// Desktop toggle
		desktopBtn.addEventListener('click', () => {
			layout.classList.toggle('expanded');
			const isExpanded = layout.classList.contains('expanded');
			localStorage.setItem('sidebarExpanded', isExpanded ? 'true' : 'false');
		});

		// Mobile toggle
		mobileBtn.addEventListener('click', () => {
			layout.classList.add('expanded');
			overlay.classList.add('active');
			body.style.overflow = 'hidden';
		});
		overlay.addEventListener('click', closeMenu);
		document.querySelectorAll('.sidebar a').forEach(link => {
			link.addEventListener('click', () => {
				if (link.classList.contains('submenu-toggle')) return;
				if (window.innerWidth <= 768) {
					layout.classList.remove('expanded');
				}
			});
		});
		function closeMenu() {
			layout.classList.remove('expanded');
			overlay.classList.remove('active');
			body.style.overflow = '';
		}

		// User menu toggle
		document.querySelector('.admin__user a').addEventListener('click', e => {
			e.preventDefault();
			const userMenu = document.querySelector('#admin__user__options');
			const open = userMenu.classList.toggle('open');
			userMenu.setAttribute('aria-expanded', open);
		});

		// Sub menu toggles
		submenuToggles.forEach(toggle => {
			toggle.addEventListener('click', e => {
				e.preventDefault();
				const current = toggle.closest('.has-submenu');
				const open = current.classList.toggle('open');
				toggle.setAttribute('aria-expanded', open);
			});
		});
		document.addEventListener('click', e => {
			closeSubMenus(e.target);
		});
		// @todo review the below - need to get it working with LIs instead
		document.querySelectorAll('.submenu a[aria-current="page"]').forEach(link => {
			const parent = link.closest('.has-submenu');
			parent.classList.add('open');
			parent.querySelector('.submenu-toggle')
					?.setAttribute('aria-expanded', 'true');
		});
		function closeSubMenus(current) {
			document.querySelectorAll('.has-submenu.open').forEach(item => {
				if (!item.contains(current)) {
					item.classList.remove('open');
					item.querySelector('.submenu-toggle')
						?.setAttribute('aria-expanded', 'false');
				}
			});
		}

		// Mobile actions
		// let touchStartX = 0;
		// let touchEndX = 0;
		// sidebar.addEventListener('touchstart', e => {
		// 	if (window.innerWidth <= 768 && layout.classList.contains('expanded')) {
		// 		touchStartX = e.changedTouches[0].screenX;
		// 	}
		// 	});

		// 	sidebar.addEventListener('touchmove', e => {
		// 	if (window.innerWidth <= 768 && layout.classList.contains('expanded')) {
		// 		touchEndX = e.changedTouches[0].screenX;
		// 	}
		// 	});
		// 	sidebar.addEventListener('touchend', e => {
		// 	if (window.innerWidth <= 768 && layout.classList.contains('expanded')) {
		// 		if (touchEndX - touchStartX < -50) {
		// 			closeMenu();
		// 		}
		// 		touchStartX = touchEndX = 0;
		// 	}
		// });
	}
}

document.addEventListener('DOMContentLoaded', () => {
	window.Inachis.NavMenu.init();
});
