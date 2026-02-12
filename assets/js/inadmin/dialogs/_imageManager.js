import { Dialog } from '../components/dialog.js';

window.Inachis.ImageManager = {

    dialog: null,

    buttons: [
        {
            class: 'button button--positive',
            disabled: true,
            text: 'Choose Image',
            click() {
                window.Inachis.ImageManager.chooseImageAction();
            }
        },
        {
            text: 'Close',
            class: 'button button--info',
            click() {
                this.close();
            }
        }
    ],

    offset: 0,
    limit: 25,
    saveTimeout: null,

    init() {
        const link = document.querySelector('a[data-dialog="imageManager"],button[data-dialog="imageManager"]');
        if (!link) return;

        link.addEventListener('click', e => {
            e.preventDefault();
            this.open(link);
        });
    },

    open(triggerEl) {
        this.dialog?.close();

        this.dialog = new Dialog({
            id: 'dialog__imageManager',
            title: triggerEl?.dataset.title ?? 'Image Manager',
            content: `
                <p>&nbsp;</p>
                <div class="loader"></div>
                <p>&nbsp;</p>
            `,
            buttons: this.buttons.concat(window.Inachis.Dialog?.buttons ?? []),
            view: triggerEl?.dataset.view ?? '',
            onOpen: dialog => {
                this.loadContent(dialog);
            }
        });

        this.dialog.open();
    },

    loadContent(dialog) {
        fetch(`${window.Inachis.prefix}/ax/imageManager/get`, {
            method: 'POST'
        })
        .then(res => res.text())
        .then(html => {
            dialog.setContent(html);
            this._init();
        })
        .catch((e) => {
            console.log(e);
            dialog.setContent('<p>Error loading images</p>');
        });
    },

    _init() {
        if (this.dialog.options.view === 'upload') {
            this.buttons.shift();
            document.querySelector('.ui-dialog-secondary-bar')
                ?.classList.add('visually-hidden');
            this.toggleUploadImage();
        } else {
            this.buttons[0].disabled = true;

            const searchInput = document.querySelector('#ui-dialog-search-input');
            searchInput?.addEventListener('input', () => this.searchImages());

            this.searchImages();
        }
        window.Inachis.FileUpload.init('form.filepond', {
            name: 'image[imageFile]',
            allowMultiple: false,
            instantUpload: false,
            required: true,
        });
        this.updateDialogButtons();

        document.querySelectorAll('.ui-dialog-secondary-bar .button--add')
            .forEach(a => {
                a.addEventListener('click', () => this.toggleUploadImage());
            });

        const form = document.querySelector('.ui-dialog-image-uploader form');

        form?.addEventListener('submit', event => {
            event.preventDefault();
            event.stopPropagation();

            pond.on('processfile', (error, file) => {
            if (error) return;

                const imageTitle = document.querySelector('#image_title')?.value;

                if (this.dialog.options.view === 'upload') {
                    document.querySelector('#filter__keyword').value = imageTitle;
                    this.dialog.close();
                    document.querySelector('form.form__images')?.submit();
                } else {
                    document.querySelector('#ui-dialog-search-input').value = imageTitle;
                    this.toggleUploadImage();
                    this.searchImages();
                }
            });

            pond.on('processfileerror', (file, error) => {
                console.log('@todo show error', error);
            });

            pond.processFiles();
        });
    },

    addPaginationLinks() {
        document.querySelectorAll('nav .pagination li a')
            .forEach(link => {
                link.addEventListener('click', event => {
                    event.preventDefault();

                    const page = parseInt(event.currentTarget.textContent, 10);
                    this.offset = (page - 1) * this.limit;
                    this.searchImages();
                });
            });
    },

    enableChooseButton() {
        this.buttons[0].disabled = false;
        this.updateDialogButtons();
    },

    chooseImageAction() {
        const selected = document.querySelector('.gallery input[type=radio]:checked');
        if (!selected) return;

        const imageTargetId = document
            .querySelector('.image_preview .dialog__link')
            ?.dataset.target;

        if (!imageTargetId) return;

        const previewWrapper = document.querySelector('.image_preview');
        const existingImg = previewWrapper?.querySelector('img');

        document.getElementById(imageTargetId).value = selected.value;

        const src = selected.closest('li')?.querySelector('img')?.src;
        if (!src) return;

        if (existingImg) {
            existingImg.src = src;
        } else {
            const img = document.createElement('img');
            img.alt = 'Preview of chosen image';
            img.src = src;
            document.getElementById(imageTargetId)
                .insertAdjacentElement('afterend', img);
        }

        this.dialog.close();
    },

    searchImages() {
        if (this.saveTimeout) clearTimeout(this.saveTimeout);

        this.saveTimeout = setTimeout(() => {

            const gallery = document.querySelector('.gallery');
            if (!gallery) return;

            gallery.innerHTML = `
                <p>&nbsp;</p>
                <div class="loader"></div>
                <p>&nbsp;</p>
            `;

            const keyword = document.querySelector('#ui-dialog-search-input')?.value ?? '';

            const formData = new FormData();
            formData.append('filter[keyword]', keyword);

            fetch(
                `${window.Inachis.prefix}/ax/imageManager/getImages/${this.offset}/${this.limit}`,
                {
                    method: 'POST',
                    body: formData
                }
            )
            .then(res => res.text())
            .then(html => {
                gallery.innerHTML = html;

                this.offset = 0;
                gallery.scrollTop = 0;

                this.addPaginationLinks();

                gallery.querySelectorAll('input[type=radio]')
                    .forEach(radio => {
                        radio.addEventListener('change', () => {
                            this.enableChooseButton();
                        });
                    });

                const ol = gallery.querySelector('ol');

                if (ol) {
                    const values = [
                        ol.dataset.start,
                        ol.dataset.end,
                        ol.dataset.total
                    ];

                    document.querySelectorAll('#images_count strong')
                        .forEach((el, i) => {
                            el.textContent = values[i];
                        });
                }
            });

        }, 500);
    },

    toggleUploadImage() {
        document.querySelector('.ui-dialog-image-uploader form')?.reset();

        document.querySelector('.ui-dialog-image-uploader')
            ?.classList.toggle('visually-hidden');

        document.querySelector('.gallery')
            ?.classList.toggle('visually-hidden');

        // requestAnimationFrame(() => {
        //     pond.layout();
        // });
    },

    updateDialogButtons() {
        if (!this.dialog) return;

        this.dialog.options.buttons =
            this.buttons.concat(window.Inachis.Dialog?.buttons ?? []);

        this.dialog._renderButtons();
    }
};
