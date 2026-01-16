window.Inachis.BulkCreateDialog = {
    $submitButton: null,

    _init() {
        $(document).on('click', '.bulk-create__link', () => {
            this.createDialog();
        });
    },

    createDialog() {
        let dialogWidth = $(window).width() * 0.75;
        if (dialogWidth < 380) {
            dialogWidth = 376;
        }
        $('<div id="dialog__bulkCreate"><p/><div class="loader"></div><p/></div>').dialog({
            buttons: [
                {
                    text: 'Create',
                    class: 'button button--positive',
                    disabled: true,
                    click: () => this.createPosts()
                },
                {
                    text: 'Close',
                    class: 'button button--info',
                    click: function () {
                        $(this).dialog('close');
                    }
                }
            ],
            close: function () {
                $(this).dialog('destroy');
                $(this).parent().remove();
                $('.fixed-bottom-bar').toggle();
            },
            draggable: false,
            modal: true,
            open: () => {
                $('.fixed-bottom-bar').toggle();
                $('.ui-dialog-titlebar-close').addClass('material-icons').html('close');
                this.getForm();
            },
            resizable: false,
            title: 'Bulk Create Posts',
            width: dialogWidth
        });
    },

    getForm() {
        const $bulkCreateForm = $('#dialog__bulkCreate');
        $bulkCreateForm.load(
            `${window.Inachis.prefix}/ax/bulkCreate/get`,
            {},
            (responseText, status) => {
                if (status === 'success') {
                    const seriesTitle = $('#series_title').val();
                    if (seriesTitle !== '') {
                        $('#bulk_title').val(seriesTitle);
                        $('#bulk_addDay').prop('checked', true);
                    }
                    window.Inachis.BulkCreateDialog.initInputs();
                    $('.ui-dialog').position({ my: 'center', at: 'center', of: window });
                }
            }
        );
    },

    initInputs() {
        window.Inachis.Components.initSelect2('.ui-dialog ');
        $('#bulk_title').on('keyup', this.validateInputs);
        $('#dialog__bulkCreate input[type=date]').each(function () {
            $(this).prop('type', 'text');
            $(this).datetimepicker({
                format: 'd/m/Y',
                timepicker: false,
                validateOnBlue: false,
                onShow: function (ct) {
                    this.setOptions({
                        maxDate: $('#bulk_endDate')?.val() || false,
                        minDate: $('#bulk_startDate')?.val() || false,
                    })
                },
                onChangeDateTime: function (dp, $input) {
                    window.Inachis.BulkCreateDialog.validateInputs();
                },
            });
        });
    },

    validateInputs() {
        const $submitButton = $('.ui-dialog-buttonpane').find('button').first();
        $submitButton.prop('disabled', true);
        if ($('#bulk_startDate').val() && $('#bulk_endDate').val() && $('#bulk_title').val()) {
            $submitButton.prop('disabled', false);
        }
    },

    createPosts() {
        this.$submitButton = $('.ui-dialog-buttonpane').find('button').first();
        this.$submitButton.prop('disabled', true).html('Creating…');
        const formData = Object.fromEntries(new FormData(document.querySelector('#dialog__bulkCreate form')));
        formData.tags = $('#bulk_tags').find(':selected').map(function () {
            return this.value;
        }).get();
        formData.categories = $('#bulk_categories').find(':selected').map(function () {
            return this.value;
        }).get();
        $.ajax(
            `${window.Inachis.prefix}/ax/bulkCreate/save`,
            {
                complete: () => {

                },
                data: {
                    form: formData,
                    seriesId: easymde.options.autosave.uniqueId,
                },
                method: 'POST',
                error: () => {
                    this.$submitButton.html('Failed to save').addClass('button--negative');
                    setTimeout(() => {
                        this.$submitButton.prop('disabled', false).removeClass('button--negative').html('Create');
                    }, 1200);
                },
                success: (data) => {
                    if (data === 'Saved') {
                        this.$submitButton.html('<span class="material-icons">done</span> Created');
                        setTimeout(() => {
                            location.reload();
                        }, 5000);
                    } else {
                        this.$submitButton.prop('disabled', false).html('Create');
                    }
                },
            }
        );
    }
};

$(document).ready(() => {
    window.Inachis.BulkCreateDialog._init();
});