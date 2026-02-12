import { Dialog } from '../components/dialog.js';

window.Inachis.SessionTimeout = {
    countdown: null,
    countdownDate: null,
    timeoutHandle: null,
    countdownEl: null,
    dialogInstance: null,

    options: {
        sessionTimeout: 1440, // seconds
        warnBeforeTimeout: 120, // seconds
        sessionEndTime: '',
        templateEncoded: '',
    },

    init(options = {}) {
        this.options = { ...this.options, ...options };

        if (this.timeoutHandle) {
            clearTimeout(this.timeoutHandle);
        }

        const delay =
            (this.options.sessionTimeout - this.options.warnBeforeTimeout) * 1000;

        this.timeoutHandle = setTimeout(() => {
            this.showAlert();
        }, delay);
    },

    showAlert() {
        const content = atob(this.options.templateEncoded);

        this.dialogInstance = new Dialog({
            id: 'dialog__sessionTimeout',
            title: 'Session time-out',
            className: 'dialog__sessionTimeout',
            content,
            buttons: [
                {
                    text: 'Keep me signed-in',
                    class: 'button button--positive',
                    click: async () => {
                        await this.continue();
                        this.dialogInstance.close();
                    },
                },
                {
                    text: 'Log off now',
                    class: 'button button--negative',
                    click: () => {
                        this.logOff();
                    },
                },
            ],
            onOpen: (dialog) => {
                this.handleOpen(dialog);
            },
            onClose: () => {
                this.cleanup();
            },
        });

        this.dialogInstance.open();
    },

    handleOpen(dialog) {
        // Replace placeholder
        const placeholderText = dialog.dialog.querySelector('.dialog-body > p:first-child');
        if (placeholderText) {
            placeholderText.innerHTML =
                placeholderText.innerHTML.replace(
                    '%TIMEOUT%',
                    this.options.sessionTimeout / 60
                );
        }

        this.countdownEl = dialog.dialog.querySelector('p.countdown');
        this.startCountdown();
    },

    async continue() {
        try {
            const response = await fetch(
                `${Inachis.prefix}/keep-alive`,
                {
                    method: 'POST',
                    headers: { Accept: 'application/json' },
                }
            );

            if (!response.ok) {
                throw new Error('Keep-alive request failed');
            }

            const data = await response.json();

            this.cleanup();

            this.init({
                sessionEndTime: data.time,
            });
        } catch (err) {
            console.error('SessionTimeout continue failed:', err);
        }
    },

    logOff() {
        window.location.href = `${Inachis.prefix}/logout`;
    },

    startCountdown() {
        if (!this.options.sessionEndTime) {
            console.warn('SessionTimeout: missing sessionEndTime');
            return;
        }

        this.countdownDate = new Date(
            this.options.sessionEndTime
        ).getTime();

        if (Number.isNaN(this.countdownDate)) {
            console.warn('SessionTimeout: invalid sessionEndTime');
            return;
        }

        this.countdown = setInterval(() => {
            const now = Date.now();
            const distance = this.countdownDate - now;

            if (distance <= 0) {
                this.cleanup();

                if (this.countdownEl) {
                    this.countdownEl.textContent =
                        'Session has now expired.';
                }

                window.location.reload();
                return;
            }

            const hours = Math.floor(distance / (1000 * 60 * 60));
            const minutes = Math.floor(
                (distance % (1000 * 60 * 60)) / (1000 * 60)
            );
            const seconds = Math.floor(
                (distance % (1000 * 60)) / 1000
            );

            this.formatCountdown(hours, minutes, seconds);
        }, 1000);
    },

    formatCountdown(hours, minutes, seconds) {
        if (!this.countdownEl) return;

        const pad = n => String(n).padStart(2, '0');

        let output = `${pad(minutes)}:${pad(seconds)}`;
        if (hours > 0) {
            output = `${hours}:${output}`;
        }

        this.countdownEl.textContent = output;
    },

    cleanup() {
        if (this.countdown) {
            clearInterval(this.countdown);
            this.countdown = null;
        }

        if (this.timeoutHandle) {
            clearTimeout(this.timeoutHandle);
            this.timeoutHandle = null;
        }

        this.countdownEl = null;
    },
};
