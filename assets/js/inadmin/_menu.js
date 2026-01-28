window.Inachis.NavMenu = {
	init: function () {
		const layout = document.querySelector('.layout');
		const sidebar = document.querySelector('.sidebar');
		const mobileBtn = document.querySelector('.mobile-menu-toggle');
		const desktopBtn = document.querySelector('.desktop-menu-toggle');
		const overlay = document.querySelector('.sidebar-overlay');
		const savedState = localStorage.getItem('sidebarExpanded');
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

		function closeMenu() {
			layout.classList.remove('expanded');
			overlay.classList.remove('active');
			body.style.overflow = '';
		}

		document.querySelector('.admin__user a').addEventListener('click', e => {
			e.preventDefault();
			const userMenu = document.querySelector('#admin__user__options');
			const open = userMenu.classList.toggle('open');
			userMenu.setAttribute('aria-expanded', open);
		});

		document.querySelectorAll('.submenu-toggle').forEach(toggle => {
			toggle.addEventListener('click', e => {
				e.preventDefault();
				const current = toggle.closest('.has-submenu');

				document.querySelectorAll('.has-submenu.open').forEach(item => {
					if (item !== current) {
						item.classList.remove('open');
						item.querySelector('.submenu-toggle')
							?.setAttribute('aria-expanded', 'false');
					}
				});

				const open = current.classList.toggle('open');
				toggle.setAttribute('aria-expanded', open);
			});
		});

		let touchStartX = 0;
		let touchEndX = 0;

		sidebar.addEventListener('touchstart', e => {
		if (window.innerWidth <= 768 && layout.classList.contains('expanded')) {
			touchStartX = e.changedTouches[0].screenX;
		}
		});

		sidebar.addEventListener('touchmove', e => {
		if (window.innerWidth <= 768 && layout.classList.contains('expanded')) {
			touchEndX = e.changedTouches[0].screenX;
		}
		});

		sidebar.addEventListener('touchend', e => {
		if (window.innerWidth <= 768 && layout.classList.contains('expanded')) {
			if (touchEndX - touchStartX < -50) { // swipe left
			closeMenu();
			}
			touchStartX = touchEndX = 0;
		}
		});
	}
}

document.addEventListener('DOMContentLoaded', () => {
	window.Inachis.NavMenu.init();
});
