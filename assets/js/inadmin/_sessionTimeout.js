window.Inachis.SessionTimeout = {
    countdown: null,
    countdownDate: null,
    timeoutHandle: null,
    countdownEl: null,

    options: {
        sessionTimeout: 1440,      // seconds
        warnBeforeTimeout: 120,    // seconds
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
        window.Inachis.Dialog.buttons = [
            {
                text: 'Keep me signed-in',
                class: 'button button--positive',
                click: () => {
                    this.continue();
                    window.Inachis.Dialog.close?.();
                },
            },
            {
                text: 'Log off now',
                class: 'button button--negative',
                click: () => {
                    this.logOff();
                },
            },
        ];

        window.Inachis.Dialog.className = 'dialog__sessionTimeout';
        window.Inachis.Dialog.preloadContent = atob(
            this.options.templateEncoded
        );
        window.Inachis.Dialog.title = 'Session time-out';
        window.Inachis.Dialog.createDialog(null);

        const placeholderText = document.querySelector(
            '#dialog__sessionTimeout form > p'
        );

        if (placeholderText) {
            placeholderText.innerHTML = placeholderText.innerHTML.replace(
                '%TIMEOUT%',
                this.options.sessionTimeout / 60
            );
        }

        this.countdownEl = document.querySelector('p.countdown');
        this.startCountdown();
    },

    async continue() {
        try {
            const response = await fetch(
                `${Inachis.prefix}/keep-alive`,
                {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                    },
                }
            );

            if (!response.ok) {
                throw new Error('Keep-alive request failed');
            }

            const data = await response.json();

            clearInterval(this.countdown);

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
                clearInterval(this.countdown);

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
        if (!this.countdownEl) {
            return;
        }

        const pad = n => String(n).padStart(2, '0');

        let output = `${pad(minutes)}:${pad(seconds)}`;
        if (hours > 0) {
            output = `${hours}:${output}`;
        }

        this.countdownEl.textContent = output;
    },
};
