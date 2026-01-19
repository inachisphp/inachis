window.Inachis.PasswordToggle = {
    values: {
        visibleLabel: { type: String, default: 'Show password' },
        visibleIcon: { type: String, default: 'Default' },
        hiddenLabel: { type: String, default: 'Hide password' },
        hiddenIcon: { type: String, default: 'Default' },
        buttonClasses: Array,
    },

    isDisplayed: false,
    button: null,

    DEFAULT_VISIBLE_ICON: `
        <svg xmlns="http://www.w3.org/2000/svg" class="toggle-password-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" focusable="false">
            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
            <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
        </svg>
    `,

    DEFAULT_HIDDEN_ICON: `
        <svg xmlns="http://www.w3.org/2000/svg" class="toggle-password-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" focusable="false">
            <path fill-rule="evenodd" d="M3.707 2.293a1 1 0 00-1.414 1.414l14 14a1 1 0 001.414-1.414l-1.473-1.473A10.014 10.014 0 0019.542 10C18.268 5.943 14.478 3 10 3a9.958 9.958 0 00-4.512 1.074l-1.78-1.781zm4.261 4.26l1.514 1.515a2.003 2.003 0 012.45 2.45l1.514 1.514a4 4 0 00-5.478-5.478z" clip-rule="evenodd" />
            <path d="M12.454 16.697L9.75 13.992a4 4 0 01-3.742-3.741L2.335 6.578A9.98 9.98 0 00.458 10c1.274 4.057 5.065 7 9.542 7 .847 0 1.669-.105 2.454-.303z" />
        </svg>
    `,

    connect() {
        this.visibleIcon = this.resolveIcon(
            this.visibleIconValue,
            this.DEFAULT_VISIBLE_ICON
        );

        this.hiddenIcon = this.resolveIcon(
            this.hiddenIconValue,
            this.DEFAULT_HIDDEN_ICON
        );

        this.button = this.createButton();
        this.element.insertAdjacentElement('afterend', this.button);

        this.dispatchEvent('connect', {
            element: this.element,
            button: this.button,
        });
    },

    disconnect() {
        if (!this.button) return;

        this.button.removeEventListener('click', this.toggle);
        this.button.remove();
        this.button = null;
    },

    createButton() {
        const button = document.createElement('button');

        button.type = 'button';
        button.tabIndex = 0;
        if (Array.isArray(this.buttonClassesValue)) {
            button.classList.add(...this.buttonClassesValue);
        }
        button.setAttribute('aria-pressed', 'false');
        button.setAttribute('aria-controls', this.element.id || '');
        button.setAttribute('aria-label', this.visibleLabelValue);

        button.innerHTML = this.getVisibleMarkup();
        button.addEventListener('click', this.toggle);

        return button;
    },

    toggle(event) {
        event.preventDefault();

        this.isDisplayed = !this.isDisplayed;

        const button = event.currentTarget;
        const isShown = this.isDisplayed;

        requestAnimationFrame(() => {
            this.element.type = isShown ? 'text' : 'password';

            button.innerHTML = isShown
                ? this.getHiddenMarkup()
                : this.getVisibleMarkup();

            button.setAttribute('aria-pressed', String(isShown));
            button.setAttribute(
                'aria-label',
                isShown ? this.hiddenLabelValue : this.visibleLabelValue
            );
        });

        this.dispatchEvent(isShown ? 'show' : 'hide', {
            element: this.element,
            button,
        });
    },

    getVisibleMarkup() {
        return `${this.visibleIcon} <span class="sr-only">${this.visibleLabelValue}</span>`;
    },

    getHiddenMarkup() {
        return `${this.hiddenIcon} <span class="sr-only">${this.hiddenLabelValue}</span>`;
    },

    resolveIcon(value, fallback) {
        return value && value !== 'Default' ? value : fallback;
    },

    dispatchEvent(name, payload) {
        this.dispatch(name, {
            detail: payload,
            prefix: 'toggle-password',
        });
    },
};
