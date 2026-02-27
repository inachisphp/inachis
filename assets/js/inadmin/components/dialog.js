export class Dialog {
  constructor(options = {}) {
    this.options = {
      id: `dialog-${crypto.randomUUID()}`,
      title: '',
      className: '',
      width: 'clamp(380px, 75vw, 900px)',
      view: '',
      modal: true,
      buttons: [],
      content: '',
      onOpen: null,
      onClose: null,
      ...options
    };

    this.triggerEl = document.activeElement;
    this._build();
    this._bindEvents();
  }

  _build() {
    // Overlay
    if (this.options.modal) {
      this.overlay = document.createElement('div');
      this.overlay.className = 'dialog-overlay';
    }

    // Dialog
    this.dialog = document.createElement('div');
    this.dialog.id = this.options.id;
    this.dialog.className = `dialog ui-dialog ${this.options.className}`;
    this.dialog.setAttribute('role', 'dialog');
    this.dialog.setAttribute('aria-modal', 'true');

    this.dialog.innerHTML = `
      <div class="dialog-content" style="width:${this.options.width}">
        <header class="dialog-header">
          <h2 class="dialog-title">${this.options.title}</h2>
          <button class="dialog-close" aria-label="Close">&times;</button>
        </header>
        <div class="dialog-body">
          ${this.options.content}
        </div>
        <footer class="dialog-footer"></footer>
      </div>
    `;

    document.body.append(this.overlay ?? '', this.dialog);

    this.body = this.dialog.querySelector('.dialog-body');
    this.footer = this.dialog.querySelector('.dialog-footer');
    this.closeBtn = this.dialog.querySelector('.dialog-close');

    this._renderButtons();
  }

  _renderButtons() {
    this.footer.innerHTML = '';
    this.buttons = [];

    this.options.buttons.forEach(btn => {
      const b = document.createElement('button');
      b.type = 'button';
      b.textContent = btn.text ?? 'Button';
      if (btn.class) b.className = btn.class;
      if (btn.disabled) b.disabled = true;

      if (btn.click) {
        b.addEventListener('click', e => btn.click.call(this, e));
      }

      this.footer.appendChild(b);
      this.buttons.push(b);
    });
  }

  _bindEvents() {
    this.closeBtn.addEventListener('click', () => this.close());
    this.overlay?.addEventListener('click', () => this.close());

    this._onKeydown = e => {
      if (e.key === 'Escape') this.close();
      if (e.key === 'Tab') this._trapFocus(e);
    };
  }

  open() {
    document.addEventListener('keydown', this._onKeydown);
    document.body.classList.add('dialog-open');

    this.dialog.hidden = false;
    this.overlay && (this.overlay.hidden = false);

    this._cacheFocusable();
    this.firstFocusable?.focus();

    this.options.onOpen?.(this);
  }

  close() {
    document.removeEventListener('keydown', this._onKeydown);

    this.dialog.remove();
    this.overlay?.remove();

    document.body.classList.remove('dialog-open');
    this.triggerEl?.focus();

    this.options.onClose?.();
  }

  setContent(html) {
    this.body.innerHTML = html;
  }

  getButton(index = 0) {
    return this.buttons[index] ?? null;
  }

  _cacheFocusable() {
    const selectors =
      'button,[href],input,select,textarea,[tabindex]:not([tabindex="-1"])';
    this.focusables = [...this.dialog.querySelectorAll(selectors)];
    this.firstFocusable = this.focusables[0];
    this.lastFocusable = this.focusables.at(-1);
  }

  _trapFocus(e) {
    if (!this.focusables.length) return;

    if (e.shiftKey && document.activeElement === this.firstFocusable) {
      e.preventDefault();
      this.lastFocusable.focus();
    } else if (!e.shiftKey && document.activeElement === this.lastFocusable) {
      e.preventDefault();
      this.firstFocusable.focus();
    }
  }
}
