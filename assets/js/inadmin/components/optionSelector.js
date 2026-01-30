(function (global) {
    function optionSelector(root, options = {}) {
        root.setAttribute("role", "radiogroup");

        const settings = {
            ripple: true,
            ...options
        };
        const body = document.body;
        const radios = root?.querySelectorAll('input[type="radio"]');
        const labels = root?.querySelectorAll('label');

        function updateSelection(selection) {
            labels.forEach(label => label.setAttribute('aria-checked', 'false'));
            const checkedLabel = root.querySelector(`label[for="${selection.id}"]`);
            if (checkedLabel) checkedLabel.setAttribute('aria-checked', 'true');
            window.Inachis._log(`Selected ${selection.id}`);
        }

        radios.forEach(radio => {
            radio.addEventListener('change', () => updateSelection(radio));
        });

        labels.forEach(label => {
            label.addEventListener('keydown', e => {
                if (e.key === 'Enter' || e.key === ' ') {
                    const id = label.getAttribute('for');
                    const target = document.getElementById(id);
                    target.checked = true;
                    updateSelection(target);
                    e.preventDefault();
                }
            });
            if (settings.ripple) {
                label.addEventListener('click', e => {
                    const ripple = document.createElement('span');
                    ripple.classList.add('ripple');

                    // Determine ripple color based on current theme if set
                    const theme = body.className;
                    ripple.style.background = theme === 'dark' 
                    ? 'rgba(255,255,255,0.1)'
                    : 'rgba(255,255,255,0.3)';

                    // Position ripple where clicked
                    const rect = label.getBoundingClientRect();
                    const size = Math.max(rect.width, rect.height);
                    ripple.style.width = ripple.style.height = size + 'px';
                    ripple.style.left = e.clientX - rect.left - size / 2 + 'px';
                    ripple.style.top = e.clientY - rect.top - size / 2 + 'px';

                    label.appendChild(ripple);
                    ripple.addEventListener('animationend', () => ripple.remove());
                });
            }
        });
    }

    // Expose globally for legacy usage
    global.optionSelector = optionSelector;

})(window);