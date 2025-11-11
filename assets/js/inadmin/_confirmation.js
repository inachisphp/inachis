let InachisConfirmationPrompt = {
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
                const $form = InachisConfirmationPrompt.$target.closest('form');

                $('<input type="hidden">')
                    .attr('name', InachisConfirmationPrompt.$target.attr('name'))
                    .val(InachisConfirmationPrompt.$target.val())
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
            InachisConfirmationPrompt.originalEvent = event;
            InachisConfirmationPrompt.$target = $(event.currentTarget);
            InachisConfirmationPrompt.createDialog();
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
        $confirmationPrompt.load(Inachis.prefix + '/ax/confirmation/get', {
            'title': InachisConfirmationPrompt.$target.data('title') ?? '',
            'entity': InachisConfirmationPrompt.$target.data('entity') ?? '',
            'warning': InachisConfirmationPrompt.$target.data('warning') ?? '',
        }, function(responseText, status)
        {
            if (status === 'success') {
                $('.ui-dialog').position({ my: 'center', at: 'center', of: window });
            }
        }, $confirmationPrompt);
    },
};
$(document).ready(function () {
    InachisConfirmationPrompt._init();
});