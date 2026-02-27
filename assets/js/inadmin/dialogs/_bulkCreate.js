import { Dialog } from '../components/dialog.js';
import DatePicker from '../components/datePicker.js';

window.Inachis.BulkCreateDialog = {
  dialog: null,
  submitButton: null,

  init() {
    document.addEventListener('click', e => {
      const link = e.target.closest('.bulk-create__link');
      if (link) {
        e.preventDefault();
        this.open();
      }
    });
  },

  open() {
    this.dialog?.close();

    this.dialog = new Dialog({
      id: 'dialog__bulkCreate',
      title: 'Bulk Create Posts',
      className: 'dialog--bulk-create',
      content: `
        <p>&nbsp;</p>
        <div class="loader"></div>
        <p>&nbsp;</p>
      `,
      buttons: [
        {
          text: 'Create',
          class: 'button button--positive',
          disabled: true,
          click: () => this.createPosts()
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
        this.loadForm(dialog);
      },
      onClose: () => {
        document.querySelector('.fixed-bottom-bar')?.classList.toggle('hidden');
      }
    });

    this.dialog.open();
  },

  loadForm(dialog) {
    fetch(`${window.Inachis.prefix}/ax/bulkCreate/get`, { method: 'POST' })
      .then(res => res.text())
      .then(html => {
        dialog.setContent(html);
        this.submitButton = dialog.getButton(0);
        this.initInputs(dialog.dialog);
      })
      .catch(() => {
        dialog.setContent('<p>Error loading form</p>');
      });
  },

  initInputs(container) {
    window.Inachis.Components.initTomSelect('.dialog');

    const bulkTitle = container.querySelector('#bulk_title');
    const startDate = container.querySelector('#bulk_startDate');
    const endDate = container.querySelector('#bulk_endDate');

    this.submitButton.disabled = true;

    [bulkTitle, startDate, endDate].forEach(el => {
      el?.addEventListener('input', () => this.validate(container));
    });
    const datepickers = document.querySelectorAll('#dialog__bulkCreate input[type=date]');
    datepickers.forEach(el => {
      new DatePicker(el, {
        format: 'dd/mm/yyyy',
        materialIcons: true,
        onChange: (formattedDate) => this.validate(container),
      });
    });
  },

  validate(container) {
    const title = container.querySelector('#bulk_title')?.value.trim();
    const start = container.querySelector('#bulk_startDate')?.value.trim();
    const end = container.querySelector('#bulk_endDate')?.value.trim();

    this.submitButton.disabled = !(title && start && end);
  },

  createPosts() {
    if (!this.submitButton) return;

    this.submitButton.disabled = true;
    this.submitButton.textContent = 'Creating…';

    const form = document.querySelector('#dialog__bulkCreate form');
    const payload = new URLSearchParams(new FormData(form));

    fetch(`${window.Inachis.prefix}/ax/bulkCreate/save`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: payload.toString()
    })
      .then(res => res.text())
      .then(data => {
        if (data === 'Saved') {
          this.submitButton.innerHTML = '✔ Created';
          setTimeout(() => location.reload(), 3000);
        } else {
          this.submitButton.disabled = false;
          this.submitButton.textContent = 'Create';
        }
      })
      .catch(() => {
        this.submitButton.textContent = 'Failed';
        setTimeout(() => {
          this.submitButton.disabled = false;
          this.submitButton.textContent = 'Create';
        }, 1200);
      });
  }
};