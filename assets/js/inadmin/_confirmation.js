window.Inachis.ConfirmationPrompt = {
    buttons: [
        {
            class: 'button button--info',
            text: 'No, Cancel',
            click: function ()
            {
                $('#dialog__confirmationPrompt').dialog('close');
            }
        },
        {
            class: 'button button--negative',
            text: 'Yes, Delete',
            click: function ()
            {
                $('#dialog__confirmationPrompt').dialog('close');
                const $form = window.Inachis.ConfirmationPrompt.$target.closest('form');

                $('<input type="hidden">')
                    .attr('name', window.Inachis.ConfirmationPrompt.$target.attr('name'))
                    .val(window.Inachis.ConfirmationPrompt.$target.val())
                    .appendTo($form);
                $form[0].submit();
            }
        }
    ],
    originalEvent: null,
    $target: null,

    _init: function()
    {
        $('button.button--confirm').on('click', function (event) {
            event.preventDefault();
            window.Inachis.ConfirmationPrompt.originalEvent = event;
            window.Inachis.ConfirmationPrompt.$target = $(event.currentTarget);
            window.Inachis.ConfirmationPrompt.createDialog();
        });
    },

    createDialog: function ()
    {
        let dialogWidth =  420;
        if (dialogWidth > $(window).width()) {
            dialogWidth = $(window).width() * 0.795;
        }
        $('<div id="dialog__confirmationPrompt"><p/><div class="loader"></div><p/><p/><p/></div>').dialog({
            buttons: this.buttons,
            close: function()
            {
                $(this).dialog('destroy');
                $(this).parent().remove();
                $('.fixed-bottom-bar').toggle();
            },
            draggable: false,
            modal: true,
            open: $.proxy(function()
            {
                $('.fixed-bottom-bar').toggle();
                $('.ui-dialog-titlebar-close').addClass('material-icons').html('close');
                this.getConfirm()
            }, this),
            resizable: false,
            title: '',
            width: dialogWidth
        });
    },

    getConfirm: function()
    {
        let $confirmationPrompt = $('#dialog__confirmationPrompt');
        $confirmationPrompt.load(window.Inachis.prefix + '/ax/confirmation/get', {
            'title': window.Inachis.ConfirmationPrompt.$target.data('title') ?? '',
            'entity': window.Inachis.ConfirmationPrompt.$target.data('entity') ?? '',
            'warning': window.Inachis.ConfirmationPrompt.$target.data('warning') ?? '',
        }, function(responseText, status)
        {
            if (status === 'success') {
                $('.ui-dialog').position({ my: 'center', at: 'center', of: window });
            }
        }, $confirmationPrompt);
    },
};
$(document).ready(function () {
    window.Inachis.ConfirmationPrompt._init();
});