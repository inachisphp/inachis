import { Dialog } from '../components/dialog.js';

window.Inachis.ContentSelectorDialog = {
  dialog: null,
  offset: 0,
  limit: 25,
  saveTimeout: null,

  init() {
    document.addEventListener('click', e => {
      const link = e.target.closest('.content-selector__link');
      if (!link) return;

      e.preventDefault();
      this.open();
    });
  },

  open() {
    this.dialog?.close();
    this.offset = 0;

    this.dialog = new Dialog({
      id: 'dialog__contentSelector',
      title: 'Choose content…',
      content: `
        <p>&nbsp;</p>
        <div class="loader"></div>
        <p>&nbsp;</p>
      `,
      buttons: [
        {
          text: 'Attach to series',
          class: 'button button--positive',
          disabled: true,
          click: () => this.addContentToSeries()
        },
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
        this.loadContentList(dialog);
      },
      onClose: () => {
        document.querySelector('.fixed-bottom-bar')?.classList.toggle('hidden');
      }
    });

    this.dialog.open();
  },

  /* -----------------------------
     Content loading
     ----------------------------- */

  loadContentList(dialog) {
    const body = dialog.body;
    body.innerHTML = '<p/><div class="loader"></div><p/>';

    const keyword =
      dialog.dialog.querySelector('#ui-dialog-search-input')?.value || '';

    fetch(`${Inachis.prefix}/ax/contentSelector/get`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({
        offset: this.offset,
        limit: this.limit,
        seriesId: easymde.options.autosave.uniqueId,
        'filters[keyword]': keyword
      })
    })
      .then(res => res.text())
      .then(html => {
        body.innerHTML = html;
        this.initInputs(dialog);
      })
      .catch(() => {
        body.innerHTML = '<p role="alert">Failed to load content</p>';
      });
  },

  /* -----------------------------
     Input wiring (pagination, search, checkboxes)
     ----------------------------- */

  initInputs(dialog) {
    const container = dialog.dialog;
    const submitBtn = dialog.getButton(0);

    // Pagination
    container
      .querySelectorAll('.pagination li a')
      .forEach(link => {
        link.addEventListener('click', e => {
          e.preventDefault();
          this.offset = (Number(link.textContent) - 1) * this.limit;
          this.loadContentList(dialog);
        });
      });

    // Search input (debounced)
    const search = container.querySelector('#ui-dialog-search-input');
    if (search) {
      search.addEventListener('input', () => {
        clearTimeout(this.saveTimeout);
        this.saveTimeout = setTimeout(() => {
          this.offset = 0;
          this.loadContentList(dialog);
        }, 500);
      });
    }

    // Checkbox → enable button
    container.addEventListener('change', e => {
      if (!e.target.matches('input[type="checkbox"]')) return;
      submitBtn.disabled =
        !container.querySelectorAll('input[type="checkbox"]:checked').length;
    });
  },

  /* -----------------------------
     Save action
     ----------------------------- */

  addContentToSeries() {
    const container = this.dialog.dialog;
    const submitBtn = this.dialog.getButton(0);

    const selectedIds = [
      ...container.querySelectorAll(
        '#dialog__contentSelector input[type="checkbox"]:checked'
      )
    ].map(cb => cb.value);

    submitBtn.disabled = true;
    submitBtn.textContent = 'Saving…';

    fetch(`${Inachis.prefix}/ax/contentSelector/save`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({
        seriesId: easymde.options.autosave.uniqueId,
        ids: selectedIds
      })
    })
      .then(res => res.text())
      .then(data => {
        if (data === 'Saved') {
          submitBtn.innerHTML =
            '<span class="material-icons">done</span> Content added';
          setTimeout(() => location.reload(), 5000);
        } else {
          submitBtn.textContent = 'No changes saved';
          submitBtn.disabled = false;
        }
      })
      .catch(() => {
        submitBtn.textContent = 'Failed to save';
        submitBtn.classList.add('button--negative');
        setTimeout(() => {
          submitBtn.disabled = false;
          submitBtn.classList.remove('button--negative');
          submitBtn.textContent = 'Attach to series';
        }, 1200);
      });
  }
};

document.addEventListener('DOMContentLoaded', () => {
  window.Inachis.ContentSelectorDialog.init();
});
