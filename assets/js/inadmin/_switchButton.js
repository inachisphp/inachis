window.Inachis.SwitchButton = {
    create(checkbox, options = {}) {
        if (!(checkbox instanceof HTMLInputElement) || checkbox.type !== 'checkbox') {
            throw new Error('createSwitchButton must be called with a checkbox input');
        }

        const defaults = {
            onLabel: 'On',
            offLabel: 'Off',
            labelsPlacement: 'both', // both | left | right
            onCallback: null,
            offCallback: null,
        };

        const state = {
            checkbox,
            options: { ...defaults, ...options },
            wrapper: null,
            button: null,
            onLabelEl: null,
            offLabelEl: null,
        };

        function init() {
            create();
            bind();
            syncFromCheckbox();
        }

        function create() {
            checkbox.classList.add('switch-button-input');

            state.wrapper = document.createElement('span');
            state.wrapper.className = 'switch-button';

            state.button = document.createElement('button');
            state.button.type = 'button';
            state.button.className = 'switch-button-control';
            state.button.setAttribute('role', 'switch');
            state.button.setAttribute('aria-checked', 'false');

            state.onLabelEl = createLabel(state.options.onLabel, 'on');
            state.offLabelEl = createLabel(state.options.offLabel, 'off');

            checkbox.insertAdjacentElement('afterend', state.wrapper);

            if (state.options.labelsPlacement !== 'right') {
                state.wrapper.appendChild(state.offLabelEl);
            }

            state.wrapper.appendChild(state.button);

            if (state.options.labelsPlacement !== 'left') {
                state.wrapper.appendChild(state.onLabelEl);
            }
        }

        function createLabel(text, stateName) {
            const label = document.createElement('span');
            label.className = `switch-button-label ${stateName}`;
            label.textContent = text;
            return label;
        }

        function bind() {
            state.button.addEventListener('click', toggle);

            state.button.addEventListener('keydown', (e) => {
                if (e.key === ' ' || e.key === 'Enter') {
                    e.preventDefault();
                    toggle();
                }
            });

            [state.onLabelEl, state.offLabelEl].forEach((label) => {
                label.addEventListener('click', () => {
                    if (isDisabled()) return;
                    state.button.focus();
                    toggle();
                });
            });

            checkbox.addEventListener('change', syncFromCheckbox);
        }

        function toggle() {
            if (isDisabled()) return;
            checkbox.checked = !checkbox.checked;
            checkbox.dispatchEvent(new Event('change', { bubbles: true }));
        }

        function syncFromCheckbox() {
            const checked = checkbox.checked;

            state.wrapper.classList.toggle('checked', checked);
            state.button.setAttribute('aria-checked', String(checked));

            if (checked && typeof state.options.onCallback === 'function') {
                state.options.onCallback.call(checkbox);
            }

            if (!checked && typeof state.options.offCallback === 'function') {
                state.options.offCallback.call(checkbox);
            }
        }

        function isDisabled() {
            return checkbox.disabled || checkbox.readOnly;
        }

        init();

        return {
            destroy() {
                state.wrapper.remove();
            }
        };
    }
};