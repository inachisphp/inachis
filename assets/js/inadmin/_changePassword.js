window.Inachis.PasswordManager = {
    _init() {
        let saveTimeout = false;
        const $passwordStrength = $('.password-strength');
        $($passwordStrength.data('source')).on('input', () => {
            if (saveTimeout) clearTimeout(saveTimeout);
            saveTimeout = setTimeout(() => {
                const currentPassword = $('#change_password_current_password').val();
                const newPassword = $('#change_password_new_password').val();
                if (currentPassword !== newPassword && window.Inachis.PasswordManager.basicValidatePassword(newPassword)) {
                    $.ajax({
                        url: `${window.Inachis.prefix}/ax/calculate-password-strength`,
                        data: { password: $($passwordStrength.data('source')).val() },
                        method: 'POST',
                    }).done((data) => {
                        const strengthValue = Number(data);

                        if (!Number.isFinite(strengthValue)) {
                            console.error('Invalid password strength value:', data);
                            return;
                        }

                        $passwordStrength.val(strengthValue + 1);
                        const strength = {
                            0: 'Very weak',
                            1: 'Weak',
                            2: 'Medium',
                            3: 'Strong',
                            4: 'Very strong',
                        }

                        const label = strength[strengthValue] ?? 'Unknown';

                        $passwordStrength.html(`Password strength: ${label}`);
                        $('#password-strength-help').html(`Password strength: ${label}`);
                    });
                }
            }, 500);
        });
    },

    basicValidatePassword(password) {
        return password.trim() !== '' && password.length >= 8;
    }
};