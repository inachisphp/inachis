window.Inachis.ImageManager = {
    buttons: [
        {
            class: 'button button--positive',
            disabled: true,
            text: 'Choose Image',
            click() {
                window.Inachis.ImageManager.chooseImageAction();
            }
        }
    ],

    saveUrl: '',
    offset: 0,
    limit: 25,
    saveTimeout: false,

    _init() {
        if (window.Inachis.Dialog.view === 'upload') {
            this.buttons = [];
            $('.ui-dialog-secondary-bar').toggle();
            this.toggleUploadImage();
        } else {
            window.Inachis.ImageManager.buttons[0].disabled = true;
            $('#ui-dialog-search-input').on('input', function (event) {
                window.Inachis.ImageManager.searchImages();
            });
            window.Inachis.ImageManager.searchImages();
        }
        this.updateDialogButtons();
        $('.ui-dialog-secondary-bar a').click(this.toggleUploadImage);
        $('.ui-dialog-image-uploader form').submit(function (event) {
            event.preventDefault();
            event.stopPropagation();
            dropzone.on('success', file => {
                const image_title = $('#image_title').val();
                if (window.Inachis.Dialog.view === 'upload') {
                    $('#filter__keyword').val(image_title);
                    $('#dialog__imageManager').dialog('destroy');
                    $('form.form__images').submit();
                } else {
                    $('#ui-dialog-search-input').val(image_title);
                    window.Inachis.ImageManager.toggleUploadImage();
                    window.Inachis.ImageManager.searchImages();
                }
            });
            dropzone.on('error', file => {
                console.log('@todo show error, remove file, and keep submit button disabled until file re-added');
            });
            dropzone.processQueue();
        });
    },

    addPaginationLinks() {
        $('nav .pagination li a').on('click', function (event) {
            event.preventDefault();
            window.Inachis.ImageManager.offset = ($(event.currentTarget).html() - 1) * window.Inachis.ImageManager.limit;
            window.Inachis.ImageManager.searchImages();
            return false;
        });
    },

    enableChooseButton() {
        window.Inachis.ImageManager.buttons[0].disabled = false;
        window.Inachis.ImageManager.updateDialogButtons();
    },

    chooseImageAction() {
        const selectedImage = $('.gallery input[type=radio]:checked');
        const imageTargetId = $('.image_preview .dialog__link').data('target');
        const $imagePreview = $('.image_preview');
        const $imagePreviewImage = $imagePreview.find('img');

        $('#' + imageTargetId).val(selectedImage.val());
        if ($imagePreviewImage.length) {
            $imagePreviewImage.prop('src', selectedImage.siblings().first().children('img').prop('src'));
        } else {
            $('<img>', {
                alt: 'Preview of chosen image',
                src: selectedImage.siblings().first().children('img').prop('src'),
            }).insertAfter('#' + imageTargetId);
        }
        $('#dialog__imageManager').dialog('close');
    },

    searchImages() {
        if (window.Inachis.ImageManager.saveTimeout) clearTimeout(window.Inachis.ImageManager.saveTimeout);
        window.Inachis.ImageManager.saveTimeout = setTimeout(() => {
            $('.gallery').html('<p/><div class="loader"></div><p/>');
            $('.gallery').load(
                `${window.Inachis.prefix}/ax/imageManager/getImages/${window.Inachis.ImageManager.offset}/${window.Inachis.ImageManager.limit}`,
                {
                    filter: {
                        keyword: $('#ui-dialog-search-input').val(),
                    },
                },
                function () {
                    window.Inachis.ImageManager.offset = 0;
                    $('.gallery').animate({ scrollTop: 0 }, 100);
                    window.Inachis.ImageManager.addPaginationLinks();
                    $('.gallery input[type=radio]').on('change', window.Inachis.ImageManager.enableChooseButton);
                    const $ol = $('.gallery ol');
                    const values = [
                        $ol.attr('data-start'),
                        $ol.attr('data-end'),
                        $ol.attr('data-total')
                    ];
                    $('#images_count strong').each(function (i) {
                        $(this).html(values[i]);
                    });
                }
            );
        }, 500);
    },

    toggleUploadImage() {
        $('.ui-dialog-image-uploader form').trigger('reset');
        $('.ui-dialog-image-uploader').toggle();
        $('.gallery').toggle();
    },

    updateDialogButtons() {
        $('#dialog__imageManager').dialog('option', 'buttons', this.buttons.concat(window.Inachis.Dialog.buttons));
    }
};
