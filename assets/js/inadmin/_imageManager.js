var InachisImageManager = {
    buttons: [
        {
            class: 'button button--positive',
            disabled: true,
            text: 'Choose Image',
            click: function ()
            {
                InachisImageManager.chooseImageAction();
            }
        }
    ],

    saveUrl: '',
    offset: 0,
    limit: 25,
    saveTimeout: false,

    _init: function()
    {
        if (InachisDialog.view === 'upload') {
            this.buttons = [];
            $('.ui-dialog-secondary-bar').toggle();
            this.toggleUploadImage();
        } else {
            $('.gallery input[type=radio]').change(InachisImageManager.enableChooseButton);
            $('#ui-dialog-search-input').on('input', function (event) {
                InachisImageManager.searchImages();
            });
            this.addPaginationLinks();
        }
        this.updateDialogButtons();
        $('.ui-dialog-secondary-bar a').click(this.toggleUploadImage);
        $('.ui-dialog-image-uploader form').submit(function (event) {
            event.preventDefault();
            event.stopPropagation();
            dropzone.on('success', file => {
                if (InachisDialog.view === 'upload') {
                    // close the dialog
                } else {
                    InachisImageManager.toggleUploadImage();
                    InachisImageManager.addPaginationLinks();
                }
            });
            dropzone.on('error', file => {
                console.log('show error, remove file, and keep submit button disabled until file re-added');
            });
            dropzone.processQueue();
        });
    },

    addPaginationLinks: function()
    {
        $('nav .pagination li a').on('click', function(event) {
            event.preventDefault();
            InachisImageManager.offset = $(event.currentTarget).html() * (InachisImageManager.limit - 1);
            InachisImageManager.searchImages();
            return false;
        });
    },

    enableChooseButton: function()
    {
        InachisImageManager.buttons[0].disabled = false;
        InachisImageManager.updateDialogButtons();
    },

    chooseImageAction: function()
    {
        var selectedImage = $('.gallery input[type=radio]:checked'),
            imageTarget = $('.image_preview .dialog__link').data('target');
        $('#' + imageTarget).val(selectedImage.val());
        $('.image_preview img').prop('src', selectedImage.siblings().first().children('img').prop('src'));
        $('#dialog__imageManager').dialog('close');
    },

    searchImages: function ()
    {
        if(InachisImageManager.saveTimeout) clearTimeout(InachisImageManager.saveTimeout);
        InachisImageManager.saveTimeout = setTimeout(function() {
            $('.gallery').load(
                Inachis.prefix + '/ax/imageManager/getImages/' + InachisImageManager.offset +'/' + InachisImageManager.limit,
                {
                    filter: {
                        keyword: $('#ui-dialog-search-input').val(),
                    },
                },
                function ()
                {
                    InachisImageManager.offset = 0;
                    $('.gallery').animate({ scrollTop:0}, 100);
                    InachisImageManager.addPaginationLinks();
                    $('#images_count').html($('.gallery ol').attr('data-total'));
                }
            );
        }, 500);
    },

    toggleUploadImage: function()
    {
        $('.ui-dialog-image-uploader form').trigger('reset');
        $('.ui-dialog-image-uploader').toggle();
        $('.gallery').toggle();
    },

    updateDialogButtons: function()
    {
        $('#dialog__imageManager').dialog('option', 'buttons', this.buttons.concat(InachisDialog.buttons));
    }
};
