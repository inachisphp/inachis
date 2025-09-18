var InachisCategoryManager = {
    alreadyInitialised: false,
    buttons: [
        {
            class: 'button button--positive',
            disabled: true,
            text: 'Create Category',
            click: function ()
            {
                InachisCategoryManager.saveNewCategory();
            }
        }
    ],
    saveUrl: '',

    _init: function()
    {
        this.updateDialogButtons();

        var $categoryManager = $('#dialog__categoryManager'),
            $categoryMangerTree = $categoryManager.find('ol');
        // if (this.alreadyInitialised) {
        //     $categoryMangerTree.bonsai('update');
        //     $categoryMangerTree.bonsai('expandAll');
        //     return;
        // }
        $(document).on('keyup', '#dialog__categoryManager__new', function(event)
        {
            var $targetElement = $(event.currentTarget),
                $createButton = $('.ui-dialog-buttonset').find('.button--positive').first();
            if ($targetElement.val() === '' || /[^a-z0-9\s\-_'"]/i.test($targetElement.val().normalize('NFD').replace(/[\u0300-\u036f]/g, ''))) {
                $createButton.prop('disabled', true);
                return;
            }
            $createButton.removeAttr('disabled');
        });
        $categoryMangerTree.bonsai({
            addExpandAll: true,
            // addSelectAll: true,
            createInputs: 'radio',
            expandAll: false
        });
        this.alreadyInitialised = true;
    },

    saveNewCategory: function()
    {
        var $newCategory = $('#dialog__categoryManager').find('p').find('input').first(),
            $parentCategory = $('input[name="catParent\[\]"]:checked'),
            $createCategory = $('.ui-dialog-buttonpane').find('button').first();
        $createCategory.prop('disabled', true).html('Saving…');
        $.ajax(
            Inachis.prefix + '/ax/categoryManager/save',
            {
                complete: $.proxy(function()
                {
                    setTimeout($.proxy(function()
                    {
                        $createCategory.prop('disabled', false).removeClass('button--negative').html('Create Category')
                    }, $createCategory), 1200);
                }, $createCategory),
                data: {
                    'title': $newCategory.val(),
                    'parentID': $parentCategory.val()
                },
                error: $.proxy(function()
                {
                    $createCategory.html('Failed to save').addClass('button--negative');
                }, $createCategory),
                method: 'POST',
                success: $.proxy(function()
                {
                    // this.getCategoryTree();

                    // @todo need to update tree

                    $createCategory.html('<span class="material-icons">done</span> Saved OK');
                    $newCategory.val('');
                }, this)
            }
        );
    },

    updateDialogButtons: function()
    {
        $('#dialog__categoryManager').dialog('option', 'buttons', this.buttons.concat(InachisDialog.buttons));
    }
};
