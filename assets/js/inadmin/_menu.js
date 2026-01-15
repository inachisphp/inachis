window.Inachis.NavMenu = {
	navNewVisible: false,
	navUserVisible: false,

	init() {
		// Add menu
		this.bindToggle(
			'.admin__add-content',
			'.admin__nav-new',
			'navNewVisible',
			'New'
		);

		// Settings menu
		document
			.querySelectorAll('a[href*="admin__nav-settings"]')
			.forEach(el =>
				el.addEventListener('click', () => {
					document
						.querySelectorAll('li.menu__collapsed')
						.forEach(li => li.classList.toggle('visually-hidden'));
				})
			);

		// Collapse / expand links
		document
			.querySelectorAll('.admin__nav-expand a, .admin__nav-collapse a')
			.forEach(el =>
				el.addEventListener('click', () => {
					document
						.querySelector('.admin__container')
						?.classList.toggle('admin__container--collapsed');
					document
						.querySelector('.admin__container')
						?.classList.toggle('admin__container--expanded');
				})
			);

		// Mobile menu link
		this.bindToggle(
			'.admin__nav-main__link a',
			'.admin__nav-new',
			'navNewVisible',
			'New',
			() => {
				document
					.querySelector('.admin__container')
					?.classList.toggle('admin__container--collapsed');
				document
					.querySelector('.admin__container')
					?.classList.toggle('admin__container--expanded');

				document
					.querySelector('.admin__nav-main__list')
					?.classList.toggle('is-visible');
			}
		);

		// User menu
		this.bindToggle(
			'.admin__user > a',
			'#admin__user__options',
			'navUserVisible',
			'User'
		);
	},

	bindToggle(triggerSelector, menuSelector, stateProp, label, beforeToggle) {
		const trigger = document.querySelector(triggerSelector);
		const menu = document.querySelector(menuSelector);

		if (!trigger || !menu) {
			return;
		}

		const onDocumentClick = event => {
			if (!menu.contains(event.target) && event.target !== trigger) {
				this.hideMenu(menu, stateProp, label);
				document.removeEventListener('mousedown', onDocumentClick);
			}
		};

		trigger.addEventListener('click', event => {
			event.preventDefault();

			if (beforeToggle) {
				beforeToggle();
			}

			const isVisible = !this[stateProp];
			this[stateProp] = isVisible;

			menu.style.display = isVisible ? 'block' : 'none';

			window.Inachis._log(`${label} menu visible: ${isVisible}`);

			if (isVisible) {
				document.addEventListener('mousedown', onDocumentClick);
			}
		});
	},

	hideMenu(menu, stateProp, label) {
		menu.style.display = 'none';
		this[stateProp] = false;
		window.Inachis._log(`${label} menu visible: false`);
	},
};

$(document).ready(function () {
	window.Inachis.NavMenu.init();
});
