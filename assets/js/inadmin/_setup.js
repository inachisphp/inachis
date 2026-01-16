window.Setup = {
	_init() {
		$('.form__setup input').on('keyup blur change', (e) => {
			const input = $(e.currentTarget);
			if (input.val() != '' && input[0].checkValidity()) {
				input.addClass('input__complete');
			}
		});
	}
};

$(document).ready(() => {
	if ($('.form__setup')) {
		window.Setup._init();
	}
});
