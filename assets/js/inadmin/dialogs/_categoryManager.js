import { Dialog } from '../components/dialog.js';

window.Inachis.CategoryManager = {
    buttons: [],
    categoryManagerLink: null,
    penzaiTree: null,
    saveUrl: '',

    init() {
        this.categoryManagerLink = document.querySelector('a[data-dialog="categoryManager"]');
        if (!this.categoryManagerLink) return;

        this.categoryManagerLink.addEventListener('click', e => {
            e.preventDefault();
            this.open();
        });
    },

    open() {
        this.dialog?.close();

        this.dialog = new Dialog({
          id: 'dialog__categoryManager',
          title: this.categoryManagerLink.dataset.title ?? 'Categories',
          className: 'dialog--bulk-create',
          content: `
            <p>&nbsp;</p>
            <div class="loader"></div>
            <p>&nbsp;</p>
          `,
          buttons: [
            {
              text: 'Close',
              class: 'button button--info',
              click() {
                this.close();
              }
            }
          ],
          onOpen: dialog => {
            document.querySelector('.fixed-bottom-bar')?.classList.toggle('hidden');
            this.loadForm(dialog);
          },
          onClose: () => {
            document.querySelector('.fixed-bottom-bar')?.classList.toggle('hidden');
          }
        });

        this.dialog.open();

    },

    loadForm(dialog) {
        fetch(`${window.Inachis.prefix}/ax/categoryManager/get`, { method: 'POST' })
        .then(res => res.text())
        .then(html => {
            dialog.setContent(html);
            // this.submitButton = dialog.getButton(0);
            this.initInputs(dialog.dialog);
        })
        .catch(() => {
            dialog.setContent('<p>Error loading form</p>');
        });
    },

    initInputs(container) {
        const categoryManager = document.getElementById('dialog__categoryManager');
        const categoryManagerTree = categoryManager.querySelector('ol');
        const createButton = document.getElementById('dialog__categoryManager__save');

        this.penzaiTree = penzai(categoryManagerTree, {
            checkboxes: false,
            expandAll: false,
        });
        document.addEventListener('keyup', (event) => {
            const targetElement = event.target;
            if (!targetElement.matches('#dialog__categoryManager__new')) return;

            const value = targetElement.value;
            const normalizedValue = value.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
            const isInvalid = value === '' || /[^a-z0-9\s\-_'"]/i.test(normalizedValue);
            createButton.disabled = isInvalid;
        });
        window.Inachis.Components.initSwitches('#dialog__categoryManager');
        document.getElementById('dialog__imageManager__addnew').addEventListener('click', this.showHideAddCategory);
        document.getElementById('dialog__categoryManager__cancel').addEventListener('click', this.showHideAddCategory);
        createButton.addEventListener('click', this.saveNewCategory);
        createButton.disabled = true;
        document.querySelectorAll('#dialog__categoryManager li>span>a').forEach(
            el => el.addEventListener('click', this.showEditCategory)
        );
        document.getElementById('dialog__categoryManager__delete').addEventListener('click', this.removeCategory);
    },

    showHideAddCategory() {
        window.Inachis.CategoryManager.toggleAreasForEditing();
        document.getElementById('dialog__categoryManager__new').value = '';
        document.getElementById('dialog__categoryManager__description').value = '';
        document.getElementById('dialog__categoryManager__id').value = '-1';
        document.getElementById('dialog__categoryManager__existing_-1').checked = true;
        const publicLabel = Array.from(document.querySelectorAll('#dialog__categoryManager .switch-button-label'))
            .find(label => label.textContent.includes('public'));
        if (publicLabel) publicLabel.click();
        document.querySelector('#dialog__categoryManager .info').style.display = 'none';
        // document.querySelector('#dialog__categoryManager .info').ariaHidden = 'true';
    },

    showEditCategory(event) {
        const target = event.currentTarget;
        window.Inachis.CategoryManager.toggleAreasForEditing();
        document.getElementById('dialog__categoryManager__new').value = target.dataset.title || '';
        document.getElementById('dialog__categoryManager__description').value = target.dataset.description || '';
        document.getElementById('dialog__categoryManager__id').value = target.dataset.id || '';
        const dialog = document.getElementById('dialog__categoryManager');
        const switchLabels = dialog.querySelectorAll('.switch-button-label');
        if (target.dataset.visible === '0') {
            switchLabels.forEach(label => {
                if (label.textContent.includes('private')) {
                    label.click();
                }
            });
        } else {
            switchLabels.forEach(label => {
                if (label.textContent.includes('public')) {
                    label.click();
                }
            });
            switchLabels.forEach(label => {
                if (label.textContent.includes('public')) {
                    label.click();
                }
            });
        }
        const parentInputWrapper = document.querySelector(
            `[data-value="${target.dataset.parentId}"] > span input`
        );
        if (parentInputWrapper) {
            parentInputWrapper.checked = true;
        }
        const infoElements = dialog.querySelectorAll('.info');
        infoElements.forEach(el => el.style.display = 'none');
        window.Inachis.CategoryManager.checkCategoryUsed(target.dataset.id);
    },

    checkCategoryUsed(categoryId) {
        const url = `${window.Inachis.prefix}/ax/categoryManager/usage`;
        const postData = new URLSearchParams({ id: categoryId });

        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: postData.toString()
        })
        .then(response => response.json())
        .then(data => {
            if (data.count === 0) {
                const deleteButton = document.getElementById('dialog__categoryManager__delete');
                deleteButton.disabled = false;
                deleteButton.setAttribute('aria-disabled', 'false');
            } else {
                document.querySelectorAll('#dialog__categoryManager .info')
                    .forEach(el => el.style.display = 'block');
            }
        })
        .catch(error => {
            const flash = document.querySelector('#dialog__categoryManager .flash');
            if (flash) {
                flash.innerHTML = error;
                flash.classList.remove('flash-success');
                flash.classList.add('flash-warning');
            }
        });
    },

    toggleAreasForEditing() {
        document.querySelectorAll('#dialog__categoryManager ol input[type=radio]').forEach(
            el => { el.hidden = !el.hidden; el.setAttribute('aria-hidden', !el.hidden); }
        );
        const addEditPanel = document.getElementById('dialog__categoryManager__add-edit-category');
        addEditPanel.hidden = !addEditPanel.hidden;

        const topLevelCategoryOption = document.getElementById('dialog__categoryManager__top-level-category');
        const asSubcatOptionLabel = document.getElementById('dialog__categoryManager__as-subcat');
        topLevelCategoryOption.hidden = !topLevelCategoryOption.hidden;
        topLevelCategoryOption.setAttribute('aria-hidden', !topLevelCategoryOption.hidden);
        asSubcatOptionLabel.hidden = !asSubcatOptionLabel.hidden;
        asSubcatOptionLabel.setAttribute('aria-hidden', !asSubcatOptionLabel.hidden);

        document.querySelectorAll('#dialog__categoryManager li>span>a').forEach(
            el => { el.hidden = !el.hidden; el.setAttribute('aria-hidden', !el.hidden); }
        );
        document.getElementById('dialog__imageManager__addnew').style.display = (document.getElementById('dialog__imageManager__addnew').style.display === 'none') ? '' : 'none';
        document.getElementById('dialog__categoryManager').scrollTo({
            top: 0,
            behavior: 'smooth'
        });
        const deleteButton = document.getElementById('dialog__categoryManager__delete');
        deleteButton.disabled = true;
        deleteButton.setAttribute('aria-disabled', true);
    },

    saveNewCategory(event) {
        event.preventDefault();
        document.querySelector('#dialog__categoryManager form').reportValidity();
        const newCategory = {
            id: document.getElementById('dialog__categoryManager__id').value,
            title: document.getElementById('dialog__categoryManager__new').value,
            description: document.getElementById('dialog__categoryManager__description').value,
            visible: document.getElementById('dialog__categoryManager__visible').checked,
        };
        const parentCategory = document.querySelector('input[name="catParent[]"]:checked');
        const createCategory = document.getElementById('dialog__categoryManager__save');
        createCategory.disabled = true;
        createCategory.innerHTML = 'Saving…';
        const url = `${Inachis.prefix}/ax/categoryManager/save`;

        const postData = new URLSearchParams({
            id: newCategory.id,
            title: newCategory.title,
            description: newCategory.description,
            visible: newCategory.visible,
            parentID: parentCategory.value
        });

        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: postData.toString()
        })
        .then(response => response.json())
        .then(data => {
            window.Inachis.CategoryManager.showHideAddCategory();
            window.Inachis.CategoryManager.fetchCategoryList();

            const flash = document.querySelector('#dialog__categoryManager .flash');
            if (flash) {
                flash.innerHTML = data.success;
                flash.classList.remove('flash-warning');
                flash.classList.add('flash-success');
            }
        })
        .catch(error => {
            const flash = document.querySelector('#dialog__categoryManager .flash');
            if (flash) {
                flash.innerHTML = error;
                flash.classList.remove('flash-success');
                flash.classList.add('flash-warning');
            }
        })
        .finally(() => {
            const saveButton = document.querySelector('#dialog__categoryManager__save');
            if (saveButton) {
                saveButton.disabled = false;
                saveButton.innerHTML = 'Save';
            }
        });

    },

    removeCategory() {
        const url = `${window.Inachis.prefix}/ax/categoryManager/delete`;
        const postData = new URLSearchParams({
            id: document.querySelector('#dialog__categoryManager__id').value
        });

        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: postData.toString()
        })
        .then(response => response.json())
        .then(() => {
            const flash = document.querySelector('#dialog__categoryManager .flash');
            if (flash) {
                flash.innerHTML = 'Category removed';
                flash.classList.remove('flash-success');
                flash.classList.add('flash-warning');
            }

            window.Inachis.CategoryManager.toggleAreasForEditing();
            window.Inachis.CategoryManager.fetchCategoryList();
        })
        .catch(error => {
            const flash = document.querySelector('#dialog__categoryManager .flash');
            if (flash) {
                flash.innerHTML = error;
                flash.classList.remove('flash-success');
                flash.classList.add('flash-warning');
            }
        });
    },

    fetchCategoryList() {
        const url = `${window.Inachis.prefix}/ax/categoryManager/list`;
        const postData = new URLSearchParams();

        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: postData.toString()
        })
        .then(response => response.text())
        .then(data => {
            const categoryManagerTree = document.querySelector('#dialog__categoryManager ol');
            if (categoryManagerTree) {
                categoryManagerTree.innerHTML = data;
            }

            this.penzaiTree.update();
            this.penzaiTree.collapseAll();

            document
                .querySelectorAll('#dialog__categoryManager li > span > a')
                .forEach(el => {
                    el.addEventListener(
                        'click',
                        window.Inachis.CategoryManager.showEditCategory
                    );
                });
        })
        .catch(error => {
            const flash = document.querySelector('#dialog__categoryManager .flash');
            if (flash) {
                flash.innerHTML = error;
                flash.classList.remove('flash-success');
                flash.classList.add('flash-warning');
            }
        });

    },
};