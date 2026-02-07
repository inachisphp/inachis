window.Inachis.Export = {
    admonition: null,
    typeRadios: null,
    scopeRadios: null,
    scopeOptions: null,
    filterOptions: null,
    keyword: null,
    manualOptions: null,
    manualTableContainer: null,
    selectAllNone: null,
    currentController: null,
    selectedIds: [],

    url: null,

    init(url) {
        this.url = url;
        this.admonition = document.querySelector('.admonition__info');
        this.typeRadios = document.querySelectorAll('input[name="content_type"]');
        this.scopeRadios = document.querySelectorAll('input[name="scope"]');
        this.scopeOptions = document.querySelectorAll('fieldset[data-content-type]');
        this.filterOptions = document.getElementById('filter__options');
        this.keyword = document.getElementById('filter__keyword');
        this.manualOptions = document.getElementById('manual__options');
        this.manualTableContainer = document.getElementById('manual__table-container');

        this.initTypeRadios();
        this.initScopeRadios();

        this.debouncedLoadTable = window.Inachis.debounce(this.loadTable.bind(this), 300);

        // Manual search
        this.keyword.addEventListener('input', e => {
            this.debouncedLoadTable(e.target.value, 1);
        });
        // prevent accidental submission of form when searching
        this.keyword.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
            }
        });
    },

    initTypeRadios() {
        this.typeRadios.forEach(radio => {
            radio.addEventListener('change', () => {
                this.showScopeOptions(radio.value);
                this.showFilterOptions('');
            });
        });
    },

    initScopeRadios() {
        this.scopeRadios.forEach(radio => {
            radio.addEventListener('change', () => {
                this.showFilterOptions(radio.value);
            });
        });
    },

    initCheckboxes() {
        this.selectAllNone = document.querySelector('.selectAllNone');
        if (!this.selectAllNone) {
            return;
        }
        const manualCheckboxes = this.manualTableContainer.querySelectorAll('input[type=checkbox]:not(.selectAllNone)');
        // check/uncheck all checkboxes in the table
        this.selectAllNone.addEventListener('change', () => {
            manualCheckboxes.forEach(cb => {
                cb.checked = this.selectAllNone.checked;
                this.updateSelectedIds(cb.value, cb.checked);
            });
        });
        manualCheckboxes.forEach(cb => {
            cb.addEventListener('change', () => {
                if (!cb.checked) {
                    this.selectAllNone.checked = false;
                }
                this.updateSelectedIds(cb.value, cb.checked);
            });
        });
    },

    initPagination() {
        this.manualTableContainer.querySelectorAll('.pagination-link').forEach(link => {
            link.addEventListener('click', e => {
                e.preventDefault();
                this.loadTable(document.getElementById('filter__keyword').value, link.dataset.page);
            });
        });
    },

    updateSelectedIds(value, checked) {
        if (checked) {
            this.selectedIds = [...new Set([...this.selectedIds, value])];
        } else {
            this.selectedIds.splice(this.selectedIds.indexOf(value), 1);
        }
        this.syncIds();
    },

    syncIds() {
        document.getElementById('selectedIds').value = this.selectedIds.join(',');
        this.admonition.innerHTML = `<strong>${this.selectedIds.length}</strong> items will be exported.`;
    },

    showScopeOptions(type) {
        this.scopeOptions.forEach(option => {
            option.checked = false;
            if (option.dataset.contentType === type) {
                option.removeAttribute('hidden');
                option.removeAttribute('aria-hidden');
            } else {
                option.setAttribute('hidden', 'true');
                option.setAttribute('aria-hidden', 'true');
            }
        });
    },

    showFilterOptions(type) {
        // clean up old event listener by removing DOM element cleanly
        if (this.selectAllNone) {
            this.selectAllNone.remove();
        }
        this.selectedIds = [];
        this.syncIds();
        this.manualTableContainer.innerHTML = '';
        this.keyword.value = '';
        this.keyword.focus();
        if (type === 'filtered') {
            this.filterOptions.removeAttribute('hidden');
            this.filterOptions.removeAttribute('aria-hidden');
        } else {
            this.filterOptions.setAttribute('hidden', 'true');
            this.filterOptions.setAttribute('aria-hidden', 'true');
        }
        if (type === 'manual') {
            this.manualOptions.removeAttribute('hidden');
            this.manualOptions.removeAttribute('aria-hidden');
        } else {
            this.manualOptions.setAttribute('hidden', 'true');
            this.manualOptions.setAttribute('aria-hidden', 'true');
        }
    },

    loadTable(query = '', page = 1) {
        if (this.currentController) {
            this.currentController.abort();
        }
        this.currentController = new AbortController();
        const type = document.querySelector('input[name="content_type"]:checked').value;

        fetch(`${this.url}?content_type=${type}&q=${encodeURIComponent(query)}&page=${page}&selectedIds=${this.selectedIds.join(',')}`, {
            signal: this.currentController.signal
        })
            .then(result => {
                if (!result.ok) {
                    throw new Error('Network response was not ok');
                }
                return result.text();
            })
            .then(html => {
                this.manualTableContainer.innerHTML = html;
                this.initCheckboxes();
                this.initPagination();
            })
            .catch(err => {
                if (err.name === 'AbortError') {
                    return;
                }
                console.error(err);
            });
    }
}
