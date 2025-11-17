let InachisPasswordManager = {
    _init: function ()
    {
        let saveTimeout = false;
        const $passwordStrength = $('.password-strength');
        $($passwordStrength.data('source')).on('input', function()
        {
            if(saveTimeout) clearTimeout(saveTimeout);
            saveTimeout = setTimeout(function() {
                let currentPassword = $('#change_password_current_password').val();
                let newPassword = $('#change_password_new_password').val();
                if (currentPassword !== newPassword && InachisPasswordManager.basicValidatePassword(newPassword)) {
                    $.ajax({
                        url: Inachis.prefix + '/ax/calculate-password-strength',
                        data: { password: $($passwordStrength.data('source')).val() },
                        method: 'POST',
                    }).done(function(data) {
                        $passwordStrength.val(data + 1);
                        const strength = {
                            0: 'Very weak',
                            1: 'Weak',
                            2: 'Medium',
                            3: 'Strong',
                            4: 'Very strong',
                        }
                        $passwordStrength.html('Strength: ' + strength[data]);
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