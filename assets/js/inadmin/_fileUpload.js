window.Inachis.FileUpload = {
    options: {
        name: '',
        allowMultiple: true,
        instantUpload: true,
        acceptedFiles: [],
        acceptedFilesMap: {},
        required: true,
    },
    pond: null,
    selector: '',

    loadScript: function (src) {
        return new Promise((resolve, reject) => {
            const s = document.createElement('script');
            s.src = src;
            s.onload = resolve;
            s.onerror = reject;
            document.head.appendChild(s);
        });
    },

    loadCSS: function (src) {
        return new Promise((resolve, reject) => {
            const l = document.createElement('link');
            l.rel = 'stylesheet';
            l.href = src;
            l.onload = resolve;
            l.onerror = reject;
            document.head.appendChild(l);
        });
    },

    init: async function(selector, options) {
        this.options = {...options};
        await Promise.all([
            this.loadCSS('/assets/css/incc/filepond.min.css'),
            this.loadCSS('/assets/css/incc/filepond-plugin-image-preview.min.css')
        ]);
        await this.loadScript('/assets/js/incc/filepond.min.js');
        await Promise.all([
            this.loadScript('/assets/js/incc/filepond-plugin-image-preview.min.js'),
            this.loadScript('/assets/js/incc/filepond-plugin-file-validate-size.min.js'),
            this.loadScript('/assets/js/incc/filepond-plugin-file-validate-type.min.js')
        ]);
        if (!FilePond) return;

        FilePond.registerPlugin(FilePondPluginImagePreview);
        FilePond.registerPlugin(FilePondPluginFileValidateSize);
        FilePond.registerPlugin(FilePondPluginFileValidateType);

        const form = document.querySelector(selector);
        if (form) {
            const inputElement = form.querySelector('input[type="file"]');
            this.pond = FilePond.create(inputElement);
            if (this.options.required) {
                const submitButton = form.querySelector('button[type=submit]');
                submitButton.disabled = true;
                submitButton.setAttribute('aria-disabled', true);
            }
            const label = inputElement.closest('label') || form.querySelector(`label[for="${inputElement.id}"]`);
            if (label) {
                label.style.display = 'none';
            }

            this.pond.setOptions(this.options);

            if (this.options.instantUpload) {
                pond.on('addfile', (error, file) => {
                    if (!error) {
                        submitButton.disabled = false;
                        submitButton.setAttribute('aria-disabled', false);
                    }
                });
                pond.on('removefile', (file) => {
                    if (pond.getFiles().length === 0) {
                        submitButton.disabled = true;
                        submitButton.setAttribute('aria-disabled', true);
                    }
                });
            }
        }
    }
};


//                pond.processFiles().then(() => {
//                    form.submit();
//                });
//            const formData = new FormData(form);
//            const files = pond.getFiles();
//            files.forEach(file => {
//                formData.append(paramName, file);
//            });
//            const xhr = new XMLHttpRequest();
//            xhr.open('POST', form.action);
//            xhr.onload = function() {
//                if (xhr.status === 200) {
//                    window.location.href = xhr.responseText;
//                }
//            };
//            xhr.send(formData);
//            output.append('filesize', file.size);
//            const additionalFormData = new FormData(document.querySelector('{{ selector|default('') }}'));
//            for (const key of additionalFormData.keys()) {
//                output.append(key, additionalFormData.get(key));
//            }
//            document.querySelctorAll('{{ selector|default('') }} button[type=submit]').each('disabled', true);