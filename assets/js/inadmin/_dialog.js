var InachisDialog = {
    //alreadyInitialised: false,
    className: '',
    buttons: [
        {
            text: 'Close',
            class: 'button button--negative',
            click: function() {
                $(this).dialog('close');
            }
        }
    ],
    templateName: '',
    title: '',
    view: '',

    _init: function()
    {
        $(document).on('click', '.dialog__link', $.proxy(this.createDialog, this));
    },

    createDialog: function(event)
    {
        let dialogWidth = $(window).width() * 0.75;
        if (dialogWidth < 380) {
            dialogWidth = 376;
        }
        let $dialogLink = $(event.currentTarget);
        this.title = $dialogLink.data('title'),
            this.view = $dialogLink.data('view'),
            this.templateName = $dialogLink.data('templateName'),
            this.className = $dialogLink.data('className');//,
            //this.buttons = JSON.parse(window.atob($dialogLink.data('buttons')));
        this.buttons = (typeof this.buttons != 'undefined' && this.buttons instanceof Array) ? this.buttons : [ this.buttons ];

        $('<div id="' + this.className + '"><form class="form"><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p></form></div>').dialog(
            {
                buttons: [].concat(this.buttons),
                close: function()
                {
                    $(this).dialog('destroy');
                    $(this).parent().remove();
                    $('.fixed-bottom-bar').toggle();
                },
                dialogClass: 'ui-dialog-loading',
                draggable: false,
                modal: true,
                open: $.proxy(function()
                {
                    $('.ui-widget-overlay').css('height', $(document).height());
                    $('.fixed-bottom-bar').toggle();
                    this.addDialogContent(this.templateName);
                }, this),
                resizable: false,
                title: this.title,
                width: dialogWidth
            }
        );
    },

    addDialogContent: function(templateName)
    {
        $('.ui-dialog-titlebar-close').addClass('material-icons').html('close');
        $('.ui-dialog-content').load(
            '/incc/ax/' + this.hyphenToCamel(templateName) + '/get', {
                selectedImage: ''
            }, function() // response, status, xhr
            {
                $(this).parent().removeClass('ui-dialog-loading');
                $('.ui-dialog').position({my: 'center', at: 'center', of: window});
            }
        );
    },

    hyphenToCamel: function(hyphenatedString)
    {
        return hyphenatedString.replace(/-([a-z])/g, function (g) { return g[1].toUpperCase(); });
    }
};

$(document).ready(function () {
    InachisDialog._init();
});
