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
        this.updateDialogButtons();
        $('.ui-dialog-secondary-bar a').click(this.toggleUploadImage);
        $('.ui-dialog-image-uploader form').submit(function (event)
        {
            var $submitButton = $('.ui-dialog-image-uploader button[type=submit]');
            $submitButton.prop('disabled', true);
            $.ajax({
                type: 'POST',
                url: $submitButton.data('submit-url'),
                data: $(this).serializeArray(),
                dataType: 'json',
                encode: true
            })
                .done(function(data) {
                    if (data['result'] === 'success') {
                        var $imageCount = $('.ui-dialog-secondary-bar strong'),
                            newElementSelector = '#chosenImage_' + data['image']['id'],
                            $gallery = $('.gallery ol'),
                            newImage = '<li>' +
                                '<label for="chosenImage_' + data['image']['id'] + '">' +
                                    '<img alt="' + data['image']['altText'] + '" src="' + data['image']['filename'] + '" />' +
                                    '<span>' + data['image']['title'] + '</span>' +
                                '</label>' +
                                '<input id="chosenImage_' + data['image']['id'] + '" name="chosenImage[]" type="radio" value="' + data['image']['id'] + '" />';
                        if (data['image']['filename'].substring(0, 4) === 'http') {
                            newImage += '<em class="material-icons">link</em>';
                        }
                        newImage += '</li>';
                        $gallery.append(newImage);
                        $imageCount.text(parseInt($imageCount.text(), 10) + 1);
                        $(newElementSelector).change(InachisImageManager.enableChooseButton);
                        $('.gallery').animate(
                            {
                                scrollTop: $(newElementSelector).offset().top
                            },
                            2000
                        );
                    }
                    InachisImageManager.toggleUploadImage();
                    $('.ui-dialog-image-uploader button[type=submit]').prop('disabled', false);
                });
        });
        $('.gallery input[type=radio]').change(InachisImageManager.enableChooseButton);
        $('#ui-dialog-search-input').on('input', function (event) { InachisImageManager.searchImages(); });
        this.addPaginationLinks();
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
