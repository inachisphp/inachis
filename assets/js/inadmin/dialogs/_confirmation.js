import { Dialog } from '../components/dialog.js';

window.Inachis.ConfirmationPrompt = {
    dialog: null,
    targetEl: null,
    isSubmitting: false,

    init() {
        document.addEventListener('click', event => {
            const trigger = event.target.closest('[data-confirm]');
            if (!trigger) return;

            event.preventDefault();
            this.targetEl = trigger;
            this.isSubmitting = false;

            this.open();
        });
    },

    open() {
        this.dialog = new Dialog({
            className: 'dialog__confirmationPrompt',
            title: this.targetEl.dataset.title ?? '',
            content: this.getSkeleton(),
            buttons: this.getButtons(),
            onOpen: () => this.loadContent(),
        });

        this.dialog.open();
    },

    getButtons() {
        return [
            {
                text: 'Cancel',
                class: 'button button--info',
                click: () => this.dialog.close(),
            },
            {
                text: this.targetEl.dataset.confirmText || 'Confirm',
                class: 'button button--negative',
                click: () => this.confirm(),
            },
        ];
    },

    getSkeleton() {
        return `
            <div class="confirmationPrompt">
                <div class="loader"></div>
            </div>
        `;
    },

    async loadContent() {
        if (!this.targetEl) return;

        const params = new URLSearchParams({
            title: this.targetEl.dataset.title ?? '',
            entity: this.targetEl.dataset.entity ?? '',
            warning: this.targetEl.dataset.warning ?? '',
            action: this.targetEl.dataset.confirm ?? '',
        });

        try {
            const response = await fetch(
                `${window.Inachis.prefix}/ax/confirmation/get`,
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: params.toString(),
                }
            );

            if (!response.ok) {
                throw new Error('Failed to load confirmation content');
            }

            const html = await response.text();
            this.dialog.setContent(html);
        } catch (err) {
            console.error('ConfirmationPrompt load failed:', err);
            this.dialog.setContent('<p class="error">Failed to load confirmation.</p>');
        }
    },

    confirm() {
        if (this.isSubmitting || !this.targetEl) return;
        this.isSubmitting = true;

        const confirmBtn = this.dialog.getButton(1);
        confirmBtn?.setAttribute('disabled', 'true');

        const form = this.targetEl.closest('form');

        if (form && this.targetEl.name) {
            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = this.targetEl.name;
            hidden.value = this.targetEl.value ?? '1';
            form.appendChild(hidden);

            form.submit();
        } else if (this.targetEl.dataset.href) {
            window.location.href = this.targetEl.dataset.href;
        }

        this.dialog.close();
    },
};;
