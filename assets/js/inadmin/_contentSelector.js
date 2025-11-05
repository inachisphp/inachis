let InachisContentSelectorDialog = {
    offset: 0,
    limit: 25,
    saveTimeout: false,

    _init: function()
    {
        $(document).on('click', '.content-selector__link', $.proxy(function()
        {
            this.createDialog();
        }, this));
    },

    createDialog: function()
    {
        let dialogWidth = $(window).width() * 0.75;
        if (dialogWidth < 380) {
            dialogWidth = 376;
        }
        $('<div id="dialog__contentSelector"><p/><p/><div class="loader"></div><p/><p/></div>').dialog({
            buttons: [
                {
                    text: 'Attach to series',
                    class: 'button button--positive',
                    disabled: true,
                    click: $.proxy(this.addContentToSeries, this)
                },
                {
                    text: 'Close',
                    class: 'button button--info',
                    click: function() {
                        $(this).dialog('close');
                    }
                }
            ],
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
                this.getContentList();
            }, this),
            resizable: false,
            title: 'Choose content…',
            width: dialogWidth
        });
    },

    initInputs: function()
    {
        $('#dialog__contentSelector .pagination li a').on('click', function(event) {
            event.preventDefault();
            InachisContentSelectorDialog.offset = ($(event.currentTarget).html() - 1) * InachisContentSelectorDialog.limit;
            InachisContentSelectorDialog.getContentList();
            return false;
        });
        $('#ui-dialog-search-input').on('input', function (event) {
            if(InachisContentSelectorDialog.saveTimeout) {
                clearTimeout(InachisContentSelectorDialog.saveTimeout);
            }
            InachisContentSelectorDialog.saveTimeout = setTimeout(function() {
                InachisContentSelectorDialog.offset = 0;
                InachisContentSelectorDialog.getContentList();
            }, 500);
        });
        $(document).on('change', '#dialog__contentSelector input[type=checkbox]', function ()
        {
            $('.ui-dialog .button--positive').prop(
                'disabled',
                !$('#dialog__contentSelector input[type=checkbox]:checked').length > 0
            );
        });
    },

    addContentToSeries: function()
    {
        let $selectedContent = [],
            $choseContent = $('.ui-dialog-buttonpane').find('button').first();
        $('#dialog__contentSelector input[type=checkbox]:checked').each(function() {
            $selectedContent.push($(this).val());
        });
        $choseContent.prop('disabled', true).html('Saving…');
        $.ajax(
            Inachis.prefix + '/ax/contentSelector/save',
            {
                complete: $.proxy(function()
                {
                    setTimeout($.proxy(function()
                    {
                        $choseContent.prop('disabled', false).removeClass('button--negative');
                        $(this).closest('.ui-dialog-content').dialog('close');
                    }, $choseContent), 1200);
                }, $choseContent),
                data: {
                    'ids': $selectedContent,
                    'seriesId': easymde.options.autosave.uniqueId
                },
                error: $.proxy(function()
                {
                    $choseContent.html('Failed to save').addClass('button--negative');
                    setTimeout($.proxy(function()
                    {
                        $choseContent.prop('disabled', false).removeClass('button--negative').html('Attach to series');
                    }, $choseContent), 1200);
                }, $choseContent),
                method: 'POST',
                success: $.proxy(function(data)
                {
                    if(data === 'Saved') {
                        $choseContent.html('<span class="material-icons">done</span> Content added');
                        setTimeout(function() {
                            location.reload();
                        }, 5000);
                    } else {
                        $choseContent.html('No changes saved');
                        $choseContent.prop('disabled', false);
                    }
                }, $choseContent)
            }
        );
    },

    getContentList: function()
    {
        let $contentSelector = $('#dialog__contentSelector');
        $contentSelector.find('.form').html('<p/><div class="loader"></div><p/>');
        $contentSelector.load(Inachis.prefix + '/ax/contentSelector/get',
            {
                offset: InachisContentSelectorDialog.offset,
                limit: InachisContentSelectorDialog.limit,
                filters: { keyword: $('#ui-dialog-search-input').val() },
                seriesId: easymde.options.autosave.uniqueId,
            }, function(responseText, status) {
                let $uiDialog = $('.ui-dialog');
                if (status === 'success') {
                    InachisContentSelectorDialog.initInputs();
                    $uiDialog.position({ my: 'center', at: 'center', of: window });
                    return;
                }
                $uiDialog.position({ my: 'center', at: 'center', of: window });
            }, $contentSelector);
    }
};

$(document).ready(function () {
    InachisContentSelectorDialog._init();
});