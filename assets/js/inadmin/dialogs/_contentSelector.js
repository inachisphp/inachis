import { Dialog } from '../components/dialog.js';

window.Inachis.ContentSelectorDialog = {
  dialog: null,
  offset: 0,
  limit: 25,
  searchTimeout: null,

  init() {
    document.addEventListener('click', e => {
      const link = e.target.closest('.content-selector__link');

      if (!link) {
        return;
      }

      e.preventDefault();
      this.open();
    });
  },

  open() {
    this.dialog?.close();

    clearTimeout(this.searchTimeout);
    this.searchTimeout = null;

    this.offset = 0;

    this.dialog = new Dialog({
      id: 'dialog__contentSelector',
      title: 'Choose content…',
      content: `
        <section class="ui-dialog-secondary-bar">
          <form class="search">
            <label for="ui-dialog-search-input">Search</label>
            <input
              class="text ui-icon-search"
              id="ui-dialog-search-input"
              placeholder="Filter by name"
              type="search"
            />
          </form>

          <p id="content-selector-summary">
            <span class="loader"></span>
          </p>
        </section>

        <form class="form">
          <div id="content-selector-results">
            <p>&nbsp;</p>
            <div class="loader"></div>
            <p>&nbsp;</p>
          </div>
        </form>
      `,
      buttons: [
        {
          text: 'Close',
          class: 'btn btn--outline',
          click() {
            this.close();
          }
        },
        {
          text: 'Attach to series',
          class: 'btn btn--primary',
          disabled: true,
          click: () => this.addContentToSeries()
        }
      ],
      onOpen: dialog => {
        document
          .querySelector('.fixed-bottom-bar')
          ?.classList.toggle('hidden');

        this.bindEvents(dialog);
        this.loadContentList(dialog);
      },
      onClose: () => {
        clearTimeout(this.searchTimeout);
        this.searchTimeout = null;

        document
          .querySelector('.fixed-bottom-bar')
          ?.classList.toggle('hidden');
      }
    });

    this.dialog.open();
  },

  /* -----------------------------
     Event wiring
     ----------------------------- */

  bindEvents(dialog) {
    const container = dialog.dialog;
    const search = container.querySelector('#ui-dialog-search-input');
    const results = container.querySelector('#content-selector-results');

    search?.addEventListener('input', () => {
      clearTimeout(this.searchTimeout);

      this.searchTimeout = setTimeout(() => {
        this.offset = 0;
        this.loadContentList(dialog);
      }, 500);
    });

    results?.addEventListener('click', e => {
      const link = e.target.closest('.pagination li a');

      if (!link) {
        return;
      }

      e.preventDefault();

      this.offset =
        (Number(link.textContent.trim()) - 1) * this.limit;

      this.loadContentList(dialog);
    });

    results?.addEventListener('change', e => {
      if (!e.target.matches('input[type="checkbox"]')) {
        return;
      }

      this.updateSubmitButton(dialog);
    });
  },

  /* -----------------------------
     Content loading
     ----------------------------- */

  loadContentList(dialog) {
    const search =
      dialog.dialog.querySelector('#ui-dialog-search-input');

    const results =
      dialog.dialog.querySelector('#content-selector-results');

    const summary =
      dialog.dialog.querySelector('#content-selector-summary');

    if (!results || !summary) {
      return;
    }

    const keyword = search?.value || '';

    results.innerHTML =
      '<p>&nbsp;</p><div class="loader"></div><p>&nbsp;</p>';

    fetch(`${Inachis.prefix}/ax/contentSelector/get`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
      },
      body: new URLSearchParams({
        offset: this.offset,
        limit: this.limit,
        seriesId: easymde.options.autosave.uniqueId,
        'filters[keyword]': keyword
      })
    })
      .then(res => {
        if (!res.ok) {
          throw new Error(`HTTP ${res.status}`);
        }

        return res.text();
      })
      .then(html => {
        const template = document.createElement('template');
        template.innerHTML = html.trim();

        const newSummary =
          template.content.querySelector('#content-selector-summary');

        const newResults =
          template.content.querySelector('#content-selector-list');

        if (newSummary) {
          summary.innerHTML = newSummary.innerHTML;
        }

        if (newResults) {
          results.innerHTML = newResults.innerHTML;
        } else {
          results.innerHTML =
            '<p role="alert">Failed to load content</p>';
        }

        this.updateSubmitButton(dialog);
      })
      .catch(() => {
        results.innerHTML =
          '<p role="alert">Failed to load content</p>';
      });
  },

  /* -----------------------------
     Button state
     ----------------------------- */

  updateSubmitButton(dialog) {
    const submitBtn =
      dialog.getFirstButtonByClass('btn--primary');

    if (!submitBtn) {
      return;
    }

    submitBtn.disabled =
      !dialog.dialog.querySelector(
        '#content-selector-results input[type="checkbox"]:checked'
      );
  },

  /* -----------------------------
     Save action
     ----------------------------- */

  addContentToSeries() {
    const container = this.dialog.dialog;
    const submitBtn =
      this.dialog.getFirstButtonByClass('btn--primary');

    const selectedIds = [
      ...container.querySelectorAll(
        '#content-selector-results input[type="checkbox"]:checked'
      )
    ].map(cb => cb.value);

    if (!selectedIds.length) {
      return;
    }

    submitBtn.disabled = true;
    submitBtn.textContent = 'Saving…';

    const params = new URLSearchParams();

    params.append(
      'seriesId',
      easymde.options.autosave.uniqueId
    );

    selectedIds.forEach(id => {
      params.append('ids[]', id);
    });

    fetch(`${Inachis.prefix}/ax/contentSelector/save`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
      },
      body: params
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
        submitBtn.classList.add('btn--danger');

        setTimeout(() => {
          submitBtn.disabled = false;
          submitBtn.classList.remove('btn--danger');
          submitBtn.textContent = 'Attach to series';
        }, 1200);
      });
  }
};

document.addEventListener('DOMContentLoaded', () => {
  window.Inachis.ContentSelectorDialog.init();
});
