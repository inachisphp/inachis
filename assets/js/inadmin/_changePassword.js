var InachisPasswordManager = {
    _init: function ()
    {
        let saveTimeout = false;
        $('#change_password_new_password').on('input', function()
        {
            if(saveTimeout) clearTimeout(saveTimeout);
            saveTimeout = setTimeout(function() {
                let currentPassword = $('#change_password_current_password').val();
                let newPassword = $('#change_password_new_password').val();
                if (currentPassword !== newPassword && InachisPasswordManager.basicValidatePassword(newPassword)) {
                    $.ajax({
                        url: Inachis.prefix + '/ax/calculate-password-strength',
                        data: { password: $('#change_password_new_password').val() },
                        method: 'POST',
                    }).done(function(data) {
                        $('.strength span')[0].className = 'percent' + ((data+1)*20);
                    });
                }
            }, 500);
        });
    },

    basicValidatePassword: function(password)
    {
        return password.trim() !== '' && password.length >= 8;
    }
};