let InachisBulkCreateDialog = {
    $submitButton: null,

    _init: function ()
    {
        $(document).on('click', '.bulk-create__link', $.proxy(function()
        {
            this.createDialog();
        }, this));
    },

    createDialog: function ()
    {
        let dialogWidth = $(window).width() * 0.75;
        if (dialogWidth < 380) {
            dialogWidth = 376;
        }
        $('<div id="dialog__bulkCreate"><p/><p/><p/><p/><p/></div>').dialog({
            buttons: [
                {
                    text: 'Create',
                    class: 'button button--positive',
                    disabled: true,
                    click: $.proxy(this.createPosts, this)
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
                this.getForm()
            }, this),
            resizable: false,
            title: 'Bulk Create Posts',
            width: dialogWidth
        });
    },

    getForm: function ()
    {
        let $bulkCreateForm = $('#dialog__bulkCreate');
        $bulkCreateForm.load(Inachis.prefix + '/ax/bulkCreate/get', {},function(responseText, status)
        {
            if (status === 'success') {
                let seriesTitle = $('#series_title').val();
                if (seriesTitle !== '') {
                    $('#bulk_title').val(seriesTitle);
                    $('#bulk_addDay').prop('checked', true);
                }
                InachisBulkCreateDialog.initInputs();
                $('.ui-dialog').position({ my: 'center', at: 'center', of: window });
            }
        }, $bulkCreateForm);
    },

    initInputs: function ()
    {
        initSelect2('.ui-dialog ');
        $('#bulk_title').on('keyup', this.validateInputs);
        $('#dialog__bulkCreate input[type=date]').each(function ()
        {
            $(this).prop('type', 'text');
            $(this).datetimepicker({
                format: 'd/m/Y',
                timepicker:false,
                validateOnBlue: false,
                onShow: function( ct ){
                    this.setOptions({
                        maxDate: $('#bulk_endDate')?.val() || false,
                        minDate: $('#bulk_startDate')?.val() || false,
                    })
                },
                onChangeDateTime: function (dp,$input)
                {
                    InachisBulkCreateDialog.validateInputs();
                },
            });
        });
    },

    validateInputs()
    {
        let $submitButton = $('.ui-dialog-buttonpane').find('button').first();
        $submitButton.prop('disabled', true);
        if ($('#bulk_startDate').val() && $('#bulk_endDate').val() && $('#bulk_title').val()) {
            $submitButton.prop('disabled', false);
        }
    },

    createPosts()
    {
        this.$submitButton = $('.ui-dialog-buttonpane').find('button').first();
        this.$submitButton.prop('disabled', true).html('Creating…');
        const formData = Object.fromEntries(new FormData(document.querySelector('#dialog__bulkCreate form')));
        formData.tags = $('#bulk_tags').find(':selected').map(function() {
          return this.value;
        }).get();
        formData.categories = $('#bulk_categories').find(':selected').map(function() {
            return this.value;
        }).get();
        $.ajax(
            Inachis.prefix + '/ax/bulkCreate/save',
            {
                complete: $.proxy(function() {

                }, this),
                data: {
                    form: formData,
                    seriesId: easymde.options.autosave.uniqueId,
                },
                method: 'POST',
                error: $.proxy(function() {
                    this.$submitButton.html('Failed to save').addClass('button--negative');
                    setTimeout($.proxy(function()
                    {
                        this.$submitButton.prop('disabled', false).removeClass('button--negative').html('Create');
                    }, this), 1200);
                }, this),
                success: $.proxy(function(data) {
                    if(data === 'Saved') {
                        this.$submitButton.html('<span class="material-icons">done</span> Created');
                        setTimeout(function() {
                            location.reload();
                        }, 5000);
                    } else {
                        this.$submitButton.prop('disabled', false).html('Create');
                    }
                }, this),
            }
        );
    }
};

$(document).ready(function () {
    InachisBulkCreateDialog._init();
});