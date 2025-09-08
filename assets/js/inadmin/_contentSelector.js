var InachisContentSelectorDialog = {
    alreadyInitialised: false,

    _init: function()
    {
        $(document).on('click', '.content-selector__link', $.proxy(function()
        {
            this.createDialog();
        }, this));
    },

    createDialog: function()
    {
        var dialogWidth = $(window).width() * 0.75;
        if (dialogWidth < 380) {
            dialogWidth = 376;
        }
        $('<div id="dialog__contentSelector"><form class="form"><ol data-name="pages[]"></ol></form></div>').dialog(
            {
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
                    this.addDialogContent();
                    this.getContentList();
                }, this),
                resizable: false,
                title: 'Choose content…',
                width: dialogWidth
            });
    },

    addDialogContent: function()
    {
        $('.ui-dialog-titlebar-close').addClass('material-icons').html('close');
        $(document).on('change', '.ui-dialog .bonsai input[type=checkbox]', function ()
        {
            $('.ui-dialog .button--positive').prop(
                'disabled',
                !$('.ui-dialog .bonsai input[type=checkbox]:checked').length > 0
            );
        });
    },

    addContentToSeries: function()
    {
        // dependency on page having a SimpleMDE interface available
        if (!easymde.options.autosave.uniqueId) {
            return;
        }
        var $selectedContent = [],
            $choseContent = $('.ui-dialog-buttonpane').find('button').first();
        $('.ui-dialog .bonsai input[type=checkbox]:checked').each(function() {
            $selectedContent.push($(this).val());
        });
        $choseContent.prop('disabled', true).html('Saving…');
        $.ajax(
            '/incc/ax/contentSelector/save',
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
                    if(data == 'Saved') {
                        $choseContent.html('<span class="material-icons">done</span> Content added');
                        setTimeout(function() {
                            location.reload();
                        }, 5000);
                        // $.ajax(
                        //     '/incc/series/contents',
                        //      data: { 'seriesId': easymde.options.autosave.uniqueId },
                        //      method: 'POST',
                        //      success: function(data)
                        //      {
                        //          $('.edit__list') = data;
                        //      }
                        // );
                    }
                }, $choseContent)
            }
        );
    },

    getContentList: function()
    {
        // @todo filter out content already in series
        var $contentSelector = $('#dialog__contentSelector');
        $contentSelector.find('ol').load('/incc/ax/contentSelector/get',
            {
                seriesId: easymde.options.autosave.uniqueId,
                default: null
            }, function(responseText, status) {
                var $uiDialog = $('.ui-dialog'),
                    $categoryMangerTree = $contentSelector.find('ol');
                if (responseText.trim() === '') {
                    var $dialogParas = $('#dialog__contentSelector').find('p');
                    $dialogParas.last().toggle();
                }
                if (status === 'success') {
                    if (this.alreadyInitialised) {
                        $categoryMangerTree.bonsai('update');
                        $categoryMangerTree.bonsai('expandAll');
                        $uiDialog.position({ my: 'center', at: 'center', of: window });
                        return;
                    }
                    $categoryMangerTree.bonsai({
                        createInputs: 'checkbox',
                        expandAll: true
                    });
                    this.alreadyInitialised = true;
                }
                $uiDialog.position({ my: 'center', at: 'center', of: window });
            }, $contentSelector);
    }
};

$(document).ready(function () {
    InachisContentSelectorDialog._init();
});
