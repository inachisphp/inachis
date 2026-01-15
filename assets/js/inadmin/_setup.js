window.Setup = {
	_init: function()
	{
		$('.form__setup input').on('keyup blur change', function(e) {
			var input = $(this);
			if (input.val() != '' && input[0].checkValidity()) {
				input.addClass('input__complete');
			}
		});
	}
};

$(document).ready(function () {
	if ($('.form__setup')) {
		window.Setup._init();
	}
});
