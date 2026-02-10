window.Inachis.PasswordManager = {
  _init() {
    let saveTimeout = null;
    const passwordStrength = document.querySelector('.password-strength');
    const sourceSelector = passwordStrength.dataset.source;
    const sourceInput = document.querySelector(sourceSelector);

    sourceInput.addEventListener('input', () => {
      if (saveTimeout) clearTimeout(saveTimeout);

      saveTimeout = setTimeout(async () => {
        const currentPassword = document.querySelector('#change_password_current_password').value;
        const newPassword = document.querySelector('#change_password_new_password').value;

        if (currentPassword !== newPassword && window.Inachis.PasswordManager.basicValidatePassword(newPassword)) {
          try {
            const response = await fetch(`${window.Inachis.prefix}/ax/calculate-password-strength`, {
              method: 'POST',
              headers: {
                'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
              },
              body: new URLSearchParams({
                password: sourceInput.value,
              }),
            });

            if (!response.ok) throw new Error(response.statusText);

            const data = await response.text();
            const strengthValue = Number(data);

            if (!Number.isFinite(strengthValue)) {
              console.error('Invalid password strength value:', data);
              return;
            }

            passwordStrength.value = strengthValue + 1;

            const strength = {
              0: 'Very weak',
              1: 'Weak',
              2: 'Medium',
              3: 'Strong',
              4: 'Very strong',
            };

            const label = strength[strengthValue] ?? 'Unknown';

            passwordStrength.textContent = `Password strength: ${label}`;
            const helpEl = document.getElementById('password-strength-help');
            if (helpEl) helpEl.textContent = `Password strength: ${label}`;

          } catch (error) {
            console.error('Password strength request failed:', error);
          }
        }
      }, 500);
    });
  },

  basicValidatePassword(password) {
    return password.trim() !== '' && password.length >= 8;
  }
};