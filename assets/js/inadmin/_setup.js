window.Setup = {
	init() {
		document.querySelectorAll('.form__setup input').forEach((input) => {
			input.addEventListener('keyup', (e) => {
				if (input.value != '' && input.checkValidity()) {
					input.classList.add('input__complete');
				}
			});
		});
	}
};

document.addEventListener('DOMContentLoaded', () => {
	if (document.querySelector('.form__setup')) {
		window.Setup.init();
	}
});
