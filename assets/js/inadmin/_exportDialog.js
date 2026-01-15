window.Inachis.Export = {
    buttons: [
        {
            class: 'button button--positive',
            text: 'Export',
            type: 'submit',
            click: function (event)
            {
                $('#dialog__export form').submit();
                $('#dialog__export').dialog('close');
            }
        }
    ],

    _init: function()
    {
        this.updateDialogButtons();
        const $exportList = $('.export__options ul'),
            $selectedItems = $('.content__list input.checkbox:checked');
        if ($selectedItems.length == 0) {
            $('#dialog__export').dialog('close');
        }
        for (let i = 0; i < $selectedItems.length; i++) {
            $exportList.append(this.listify($selectedItems[i]));
        }
        $('.ui-switch').each(function ()
        {
            let $properties = {
                checked: this.checked,
                clear: true,
                height: 20,
                width: 40
            };
            if ($(this).attr('data-label-on')) {
                $properties.on_label = $(this).attr('data-label-on');
            }
            if ($(this).attr('data-label-off')) {
                $properties.off_label = $(this).attr('data-label-off');
            }
            $(this).switchButton($properties);
        });
    },

    updateDialogButtons: function()
    {
        $('#dialog__export').dialog('option', 'buttons', this.buttons.concat(window.Inachis.Dialog.buttons));
    },

    listify: function(listItem)
    {
        try {
            let listItem__Title = listItem.nextElementSibling.children[0].innerText.replace(/ draft/, ''),
                listItem__Id =  listItem.value;
            return '<li><input type="hidden" name="postId[]" value="' + listItem__Id + '" />' + listItem__Title  + '</li>';
        } catch (err) {
            if (console) {
                console.log('Unable to find title for export item:');
                console.log(listItem);
            }
        }
    }
};
