window.Inachis.SessionTimeout = {
    countdown: null,
    countdownDate: null,
    options: {
        sessionTimeout: 1440,
        warnBeforeTimeout: 120,
        sessionEndTime: '',
        templateEncoded: '',
    },
    _init: function (options = [])
    {
        this.options = Object.assign(this.options, options);
        setTimeout(function () {
            window.Inachis.SessionTimeout.showAlert();
        }, 1000 * (this.options.sessionTimeout - this.options.warnBeforeTimeout));
    },
    showAlert: function ()
    {
        InachisDialog.buttons = [
            {
                text: 'Keep me signed-in',
                class: 'button button--positive',
                click: function () {
                    window.Inachis.SessionTimeout.continue();
                    $(this).dialog('close');
                }
            },
            {
                text: 'Log off now',
                class: 'button button--negative',
                click: () => {
                    window.Inachis.SessionTimeout.logOff();
                }
            }
        ];
        InachisDialog.className = 'dialog__sessionTimeout';
        InachisDialog.preloadContent = atob(window.Inachis.SessionTimeout.options.templateEncoded);
        InachisDialog.title = 'Session time-out';
        InachisDialog.createDialog(null);
        let $placeholderText = $('#dialog__sessionTimeout form > p').first();
        $placeholderText.html($placeholderText.html().replace('%TIMEOUT%', this.options.sessionTimeout / 60));
        this.startCountdown();
    },
    continue: function ()
    {
        $.ajax({
            type: 'post',
            url: Inachis.prefix + '/keep-alive',
            success: function (data, textStatus, jqXHR) {
                clearInterval(window.Inachis.SessionTimeout.countdown);
                window.Inachis.SessionTimeout._init({
                    sessionEndTime: data.time,
                });
            },
            dataType: 'json',
        });
    },
    logOff: function ()
    {
        window.location = '/incc/logout';
    },
    startCountdown: function ()
    {
        this.countdownDate = new Date(this.options.sessionEndTime).getTime();

        this.countdown = setInterval(function () {
            let now = Date.now(),
                distance = window.Inachis.SessionTimeout.countdownDate - now,
                hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)),
                minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60)),
                seconds = Math.floor((distance % (1000 * 60)) / 1000);
            window.Inachis.SessionTimeout.formatCountdown(hours, minutes, seconds);
            if (distance <= 0) {
                clearInterval(window.Inachis.SessionTimeout.countdown);
                $('p.countdown').html('Session has now expired.');
                window.location.reload();
            }
        }, 1000);
    },
    formatCountdown: function(hours, minutes, seconds)
    {
        let output = seconds + 'secs';
        if (minutes > 0) output = minutes + 'mins ' + output;
        if (hours > 0) output = hours + 'hrs ' + output;
        $('p.countdown').html(output);
    }
};
