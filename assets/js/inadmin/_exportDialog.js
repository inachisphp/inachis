window.Inachis.Export = {
    buttons: [
        {
            class: 'button button--positive',
            text: 'Export',
            type: 'submit',
            click(event) {
                $('#dialog__export form').submit();
                $('#dialog__export').dialog('close');
            }
        }
    ],

    init() {
        this.updateDialogButtons();

        const $exportList = $('.export__options ul');
        const $selectedItems = $('.content__list input.checkbox:checked');

        if ($selectedItems.length === 0) {
            $('#dialog__export').dialog('close');
        }
        for (let i = 0; i < $selectedItems.length; i++) {
            $exportList.append(this.listify($selectedItems[i]));
        }
        $('.ui-switch').each(function () {
            let $properties = {
                checked: this.checked,
                clear: true,
                height: 20,
                width: 40
            };
            const $el = $(this);
            const data = $(this).data();

            if (data.labelOn) $properties.on_label = data.labelOn;
            if (data.labelOff) $properties.off_label = data.labelOff;

            $(this).switchButton($properties);
        });
    },

    updateDialogButtons() {
        $('#dialog__export').dialog('option', 'buttons', this.buttons.concat(window.Inachis.Dialog.buttons));
    },

    listify(listItem) {
        try {
            const labelEl = listItem.nextElementSibling?.children?.[0];
            if (!labelEl) {
                throw new Error('Missing label element');
            }
            const title = labelEl.textContent.replace(/ draft$/, '');
            const id = listItem.value;

            return `
                <li>
                    <input type="hidden" name="postId[]" value="${id}" />
                    ${title}
                </li>
            `;
        } catch (err) {
            console.warn('Unable to find title for export item:', listItem);
            return null;
        }
    }
};
