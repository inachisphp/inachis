var InachisCategoryManager = {
    buttons: [ ],
    saveUrl: '',

    _init: function()
    {
        let $categoryManager = $('#dialog__categoryManager'),
            $categoryMangerTree = $categoryManager.find('ol');
        $(document).on('keyup', '#dialog__categoryManager__new', function(event)
        {
            let $targetElement = $(event.currentTarget),
                $createButton = $('.ui-dialog-buttonset').find('.button--positive').first();
            if ($targetElement.val() === '' || /[^a-z0-9\s\-_'"]/i.test($targetElement.val().normalize('NFD').replace(/[\u0300-\u036f]/g, ''))) {
                $createButton.prop('disabled', true);
                return;
            }
            $createButton.removeAttr('disabled');
        });
        $categoryMangerTree.bonsai({
            expandAll: true
        });
        $categoryMangerTree.bonsai('collapseAll');

        initSwitches('#dialog__categoryManager');
        $('#dialog__imageManager__addnew').on('click', this.showHideAddCategory);
        $('#dialog__categoryManager__cancel').on('click', this.showHideAddCategory);
        $('#dialog__categoryManager__save').on('click', this.saveNewCategory);
        $('#dialog__categoryManager li>span>a').on('click', this.showEditCategory);
        $('#dialog__categoryManager__delete').on('click', this.removeCategory);
        $('[data-action=export_categories]').on('click', this.export);
        $('[data-action=import_categories]').on('click', this.import);
    },

    showHideAddCategory: function()
    {
        InachisCategoryManager.toggleAreasForEditing();
        $('#dialog__categoryManager__new').val('');
        $('#dialog__categoryManager__description').val('');
        $('#dialog__categoryManager__id').val('-1');
        $('#dialog__categoryManager__existing_-1').prop('checked', true);
        $('#dialog__categoryManager .switch-button-label:contains(public)').trigger('click');
        $('#dialog__categoryManager .info').hide();
    },

    showEditCategory: function(event)
    {
        let target = $(event.currentTarget);
        InachisCategoryManager.toggleAreasForEditing();
        $('#dialog__categoryManager__new').val(target.data('title'));
        $('#dialog__categoryManager__description').val(target.data('description'));
        $('#dialog__categoryManager__id').val(target.data('id'));
        if (target.data('visible') === '0') {
            $('#dialog__categoryManager .switch-button-label:contains(private)').trigger('click')
        } else {
            $('#dialog__categoryManager .switch-button-label:contains(public)').trigger('click')
        }
        $('[data-value="' + target.data('parent-id') + '"]>span input').prop("checked", true);
        $('#dialog__categoryManager .info').hide();
        InachisCategoryManager.checkCategoryUsed(target.data('id'));
    },

    checkCategoryUsed: function(categoryId)
    {
        $.ajax(
            Inachis.prefix + '/ax/categoryManager/usage',
            {
                data: { 'id': categoryId, },
                error: function(xhr, textStatus, errorThrown)
                {
                    $('#dialog__categoryManager .flash')
                        .html(errorThrown)
                        .removeClass('flash-success')
                        .addClass('flash-warning')
                    ;
                },
                method: 'POST',
                success: function(data)
                {
                    if (data.count === 0) {
                        $('#dialog__categoryManager__delete')
                            .prop('disabled', false).prop('aria-disabled', false);
                    } else {
                        $('#dialog__categoryManager .info').show();
                    }
                }
            }
        );
    },

    toggleAreasForEditing: function()
    {
        $('#dialog__categoryManager ol input[type=radio]').toggle();
        $('#dialog__categoryManager__top-level-category').toggle();
        $('#dialog__categoryManager__as-subcat').toggle();
        $('#dialog__categoryManager__exportimport').toggle();
        $('#dialog__categoryManager__add-edit-category').toggle();
        $('#dialog__categoryManager li>span>a').toggle();
        $('#dialog__imageManager__addnew').toggle();
        $('#dialog__categoryManager').animate({ scrollTop:0}, 100);
        $('#dialog__categoryManager__delete')
            .prop('disabled', true)
            .prop('aria-disabled', true);
    },

    saveNewCategory: function(event)
    {
        event.preventDefault();
        $('#dialog__categoryManager form')[0].reportValidity();
        let $newCategory = {
            id: $('#dialog__categoryManager__id').first(),
            title: $('#dialog__categoryManager__new').first(),
            description: $('#dialog__categoryManager__description').first(),
            visible: $('#dialog__categoryManager__visible')[0].checked,
            },
        $parentCategory = $('input[name="catParent\[\]"]:checked'),
        $createCategory = $('#dialog__categoryManager__save').first();
        $createCategory.prop('disabled', true).html('Saving…');
        $.ajax(
            Inachis.prefix + '/ax/categoryManager/save',
            {
                complete: function()
                {
                    $('#dialog__categoryManager__save').first().prop('disabled', false).html('Save');
                },
                data: {
                    'id': $newCategory.id.val(),
                    'title': $newCategory.title.val(),
                    'description': $newCategory.description.val(),
                    'visible': $newCategory.visible,
                    'parentID': $parentCategory.val(),
                },
                error: function(xhr, textStatus, errorThrown)
                {
                    $('#dialog__categoryManager .flash')
                        .html(errorThrown)
                        .removeClass('flash-success')
                        .addClass('flash-warning');
                },
                method: 'POST',
                success: function(data)
                {
                    InachisCategoryManager.showHideAddCategory();
                    InachisCategoryManager.fetchCategoryList();
                    $('#dialog__categoryManager .flash')
                        .html(data.success)
                        .removeClass('flash-warning')
                        .addClass('flash-success')
                    ;
                },
            }
        );
    },

    removeCategory: function()
    {
        $.ajax(
            Inachis.prefix + '/ax/categoryManager/delete',
            {
                data: {
                    'id': $('#dialog__categoryManager__id').val(),
                },
                error: function(xhr, textStatus, errorThrown)
                {
                    $('#dialog__categoryManager .flash')
                        .html(errorThrown)
                        .removeClass('flash-success')
                        .addClass('flash-warning');
                },
                method: 'POST',
                success: function()
                {
                    $('#dialog__categoryManager .flash')
                        .html('Category removed')
                        .removeClass('flash-success')
                        .addClass('flash-warning');
                    InachisCategoryManager.toggleAreasForEditing();
                    InachisCategoryManager.fetchCategoryList();
                },
            }
        );
    },

    fetchCategoryList: function()
    {
        $.ajax(
            Inachis.prefix + '/ax/categoryManager/list',
            {
                data: { },
                error: function(xhr, textStatus, errorThrown)
                {
                    $('#dialog__categoryManager .flash')
                        .html(errorThrown)
                        .removeClass('flash-success')
                        .addClass('flash-warning')
                    ;
                },
                method: 'POST',
                success: function(data)
                {
                    let $categoryMangerTree = $('#dialog__categoryManager ol');
                    $categoryMangerTree.html(data);
                    $categoryMangerTree.data('bonsai').update();
                    $categoryMangerTree.data('bonsai').collapseAll();
                    $('#dialog__categoryManager li>span>a').on('click', InachisCategoryManager.showEditCategory);
                },
            }
        );
    },

    export: function()
    {
        // download a json file
    },

    import: function()
    {
        // @todo show dropzone dialog for uploading a json file
    },
};
