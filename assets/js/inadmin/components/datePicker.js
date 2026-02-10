export default class DatePicker {
  constructor(selector, options = {}) {
    this.input = document.querySelector(selector);
    if (!this.input) throw new Error(`DatePicker: No element found for selector "${selector}"`);

    this.options = {
      format: options.format || 'dd/mm/yyyy',
      onChange: options.onChange || (() => {}),
      minDate: options.minDate || null,
      maxDate: options.maxDate || null,
      showTodayButton: options.showTodayButton ?? true,
      todayButtonIcon: options.todayButtonIcon || 'today',
      locale: options.locale || navigator.language || 'en-GB',
      materialIcons: options.materialIcons || false,
    };

    this.isMobile = /Mobi|Android/i.test(navigator.userAgent);
    this.selectedDate = null;
    this.currentMonth = null;
    this.currentYear = null;
    this.yearPanelVisible = false;
    this.isAnimating = false;

    if (this.isMobile) {
      this.input.setAttribute('type', 'datetime-local');
      this.input.addEventListener('input', () => this.options.onChange(this.input.value));
    } else {
      this.input.setAttribute('type', 'text');
      this.createPicker();
      this.addEventListeners();
    }
  }

  parseInputDate() {
    const val = this.input.value.trim();
    if (!val) return null;
    let [datePart, timePart] = val.split(' ');
    const [day, month, year] = datePart.split('/').map(Number);
    if (!day || !month || !year) return null;

    const dt = new Date(year, month - 1, day);
    if (timePart) {
      const [h, m] = timePart.split(':').map(Number);
      dt.setHours(h || 0, m || 0);
    }
    return dt;
  }

  formatDate(dt) {
    const pad = (n) => n.toString().padStart(2, '0');
    let dateStr = `${pad(dt.getDate())}/${pad(dt.getMonth() + 1)}/${dt.getFullYear()}`;
    if (this.options.format.match(/H|i/)) {
      dateStr += ` ${pad(dt.getHours())}:${pad(dt.getMinutes())}`;
    }
    return dateStr;
  }

  createPicker() {
    const inputDate = this.parseInputDate();
    const today = new Date();
    this.selectedDate = inputDate || null;
    this.currentMonth = inputDate?.getMonth() ?? today.getMonth();
    this.currentYear = inputDate?.getFullYear() ?? today.getFullYear();

    const atMinMonth =
      this.options.minDate &&
      this.currentYear === this.options.minDate.getFullYear() &&
      this.currentMonth === this.options.minDate.getMonth();
    const atMaxMonth =
      this.options.maxDate &&
      this.currentYear === this.options.maxDate.getFullYear() &&
      this.currentMonth === this.options.maxDate.getMonth();

    this.picker = document.createElement('div');
    this.picker.className = 'datepicker-popup';
    this.picker.setAttribute('role', 'dialog');
    this.picker.setAttribute('aria-modal', 'true');
    this.picker.style.display = 'none';

    // Today Button (above month)
    if (this.options.showTodayButton) {
      this.todayButton = document.createElement('button');
      this.todayButton.className = 'datepicker-today-btn';
      if (this.options.materialIcons) {
        this.todayButton.innerHTML = `<span class="material-icons">${this.options.todayButtonIcon}</span> Today`;
      } else {
        this.todayButton.textContent = 'Today';
      }
      this.todayButton.addEventListener('click', () => {
        const today = new Date();
        if ((this.options.minDate && today < this.options.minDate) ||
            (this.options.maxDate && today > this.options.maxDate)) return;
        this.selectedDate = today;
        this.currentMonth = today.getMonth();
        this.currentYear = today.getFullYear();
        this.updateValue();
        this.hidePicker();
      });
      this.picker.appendChild(this.todayButton);
    }

    // Header
    this.header = document.createElement('div');
    this.header.className = 'datepicker-header';

    this.prevBtn = document.createElement('button');
    this.prevBtn.type = 'button';
    this.prevBtn.setAttribute('aria-label', 'Previous month');

    this.nextBtn = document.createElement('button');
    this.nextBtn.type = 'button';
    this.nextBtn.setAttribute('aria-label', 'Next month');

    if (this.options.materialIcons) {
      this.prevBtn.innerHTML = '<span class="material-icons" aria-hidden="true">chevron_left</span>';
      this.nextBtn.innerHTML = '<span class="material-icons" aria-hidden="true">chevron_right</span>';
    } else {
      this.prevBtn.textContent = '<';
      this.nextBtn.textContent = '>';
    }
    this.prevBtn.disabled = !!atMinMonth;
    this.nextBtn.disabled = !!atMaxMonth;

    this.monthLabelBtn = document.createElement('button');
    this.monthLabelBtn.type = 'button';
    this.monthLabelBtn.className = 'datepicker-month-label';
    this.monthLabelBtn.setAttribute('aria-label', 'Select month and year');
    this.monthLabelBtn.addEventListener('click', () => this.toggleYearPanel());

    this.header.appendChild(this.prevBtn);
    this.header.appendChild(this.monthLabelBtn);
    this.header.appendChild(this.nextBtn);
    this.picker.appendChild(this.header);

    // Calendar container
    this.calendarContainer = document.createElement('div');
    this.calendarContainer.className = 'datepicker-calendar-container';
    this.calendarContainer.style.position = 'relative';
    this.calendarContainer.style.overflow = 'hidden';

    this.calendar = document.createElement('table');
    this.calendar.className = 'datepicker-calendar';
    this.calendar.setAttribute('role', 'grid');

    this.calendarContainer.appendChild(this.calendar);
    this.picker.appendChild(this.calendarContainer);

    // Year panel
    this.yearPanel = document.createElement('div');
    this.yearPanel.className = 'datepicker-year-panel';
    this.yearPanel.style.display = 'none';
    this.picker.appendChild(this.yearPanel);

    // Time Picker (number inputs)
    if (this.options.format.match(/H|i/)) this.createTimePicker();

    document.body.appendChild(this.picker);
    this.renderCalendar();
  }

  addEventListeners() {
    const positionPicker = () => {
      const rect = this.input.getBoundingClientRect();
      this.picker.style.top = rect.bottom + window.scrollY + 'px';
      this.picker.style.left = rect.left + window.scrollX + 'px';
      this.picker.style.width = (rect.width + 90) + 'px';
    };

    this.input.addEventListener('focus', () => {
      positionPicker();
      this.picker.style.display = 'block';
    });

    document.addEventListener('click', (e) => {
      if (!this.picker.contains(e.target) && e.target !== this.input) this.hidePicker();
    });

    window.addEventListener('resize', positionPicker);

    this.prevBtn.addEventListener('click', () => this.changeMonth(-1));
    this.nextBtn.addEventListener('click', () => this.changeMonth(1));

    // Keyboard navigation
    this.picker.addEventListener('keydown', (e) => this.handleKeyboard(e));
  }

  hidePicker() {
    this.picker.style.display = 'none';
    this.yearPanelVisible = false;
    this.yearPanel.style.display = 'none';
    // this.input.focus();
  }

  toggleYearPanel() {
    this.yearPanelVisible = !this.yearPanelVisible;
    if (this.yearPanelVisible) {
      this.renderYearPanel();
      this.yearPanel.classList.add('visible');
      this.yearPanel.style.display = 'grid';
      this.calendarContainer.style.display = 'none';
    } else {
      this.yearPanel.classList.remove('visible');
      this.yearPanel.style.display = 'none';
      this.calendarContainer.style.display = 'block';
    }
  }

  renderTimePicker() {
    if (!this.timeContainer) return;

    const selectedH = this.selectedDate?.getHours() ?? 0;
    const selectedM = this.selectedDate?.getMinutes() ?? 0;

    this.hourInput.value = selectedH;
    this.minuteInput.value = selectedM;
  }

  renderYearPanel() {
    this.yearPanel.innerHTML = '';
    const startYear = this.currentYear - 5;
    const endYear = this.currentYear + 6;

    for (let y = startYear; y <= endYear; y++) {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.textContent = y;
      btn.className = 'year-btn';
      if (y === this.currentYear) btn.classList.add('selected');
      btn.addEventListener('click', () => {
        this.currentYear = y;
        this.yearPanelVisible = false;
        this.yearPanel.classList.remove('visible');
        this.yearPanel.style.display = 'none';
        this.calendarContainer.style.display = 'block';
        this.renderCalendar();
      });
      this.yearPanel.appendChild(btn);
    }
  }

  changeMonth(offset) {
    if (this.isAnimating) return;
    this.isAnimating = true;

    const oldTable = this.calendar;
    const direction = offset > 0 ? 1 : -1;

    this.currentMonth += offset;
    if (this.currentMonth < 0) { this.currentMonth = 11; this.currentYear--; }
    if (this.currentMonth > 11) { this.currentMonth = 0; this.currentYear++; }

    const newTable = this.createCalendarTable();
    newTable.style.position = 'absolute';
    newTable.style.top = 0;
    newTable.style.left = direction > 0 ? '100%' : '-100%';
    this.calendarContainer.appendChild(newTable);

    requestAnimationFrame(() => {
      oldTable.style.transition = 'transform 0.25s';
      newTable.style.transition = 'transform 0.25s';
      oldTable.style.transform = `translateX(${-100 * direction}%)`;
      newTable.style.transform = `translateX(0%)`;
    });

    setTimeout(() => {
      this.calendarContainer.removeChild(oldTable);
      newTable.style.position = 'static';
      newTable.style.transform = '';
      newTable.style.transition = '';
      this.calendar = newTable;
      this.isAnimating = false;
      this.updateMonthYearLabel();
    }, 250);
  }

  createCalendarTable() {
    const table = document.createElement('table');
    table.className = 'datepicker-calendar';
    table.setAttribute('role', 'grid');
    table.style.width = '100%';
    table.style.tableLayout = 'fixed';

    const headerRow = document.createElement('tr');
    ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'].forEach(d => {
      const th = document.createElement('th');
      th.textContent = d;
      headerRow.appendChild(th);
    });
    table.appendChild(headerRow);

    const firstDay = new Date(this.currentYear, this.currentMonth, 1).getDay();
    const daysInMonth = new Date(this.currentYear, this.currentMonth + 1, 0).getDate();
    let row = document.createElement('tr');

    for (let i = 0; i < firstDay; i++) row.appendChild(document.createElement('td'));

    for (let date = 1; date <= daysInMonth; date++) {
      const cell = document.createElement('td');
      cell.tabIndex = 0;
      cell.setAttribute('role', 'gridcell');
      cell.textContent = date;

      const today = new Date();
      if (date === today.getDate() &&
          this.currentMonth === today.getMonth() &&
          this.currentYear === today.getFullYear()) cell.classList.add('today');

      if (this.selectedDate &&
          date === this.selectedDate.getDate() &&
          this.currentMonth === this.selectedDate.getMonth() &&
          this.currentYear === this.selectedDate.getFullYear()) cell.classList.add('selected');

      const cellDate = new Date(this.currentYear, this.currentMonth, date);
      if ((this.options.minDate && cellDate < this.options.minDate) ||
          (this.options.maxDate && cellDate > this.options.maxDate)) {
        cell.setAttribute('disabled', true);
      } else {
        cell.addEventListener('click', () => {
          if (!this.selectedDate) this.selectedDate = new Date();
          this.selectedDate.setFullYear(this.currentYear, this.currentMonth, date);
          this.updateValue();
          this.hidePicker();
        });
      }

      row.appendChild(cell);
      if ((firstDay + date) % 7 === 0) {
        table.appendChild(row);
        row = document.createElement('tr');
      }
    }
    if (row.children.length > 0) table.appendChild(row);
    return table;
  }

  updateMonthYearLabel() {
    const monthNames = [];
    for (let m = 0; m < 12; m++)
      monthNames.push(new Intl.DateTimeFormat(this.options.locale, { month: 'long' }).format(new Date(this.currentYear, m)));
    this.monthLabelBtn.textContent = `${monthNames[this.currentMonth]} ${this.currentYear}`;
  }

  renderCalendar() {
    this.updateMonthYearLabel();
    const newTable = this.createCalendarTable();
    this.calendarContainer.replaceChild(newTable, this.calendar);
    this.calendar = newTable;

    this.renderTimePicker();
  }

  createTimePicker() {
    this.timeContainer = document.createElement('div');
    this.timeContainer.className = 'datepicker-time-container';

    // Hour input
    const hourLabel = document.createElement('label');
    hourLabel.textContent = 'H:';
    hourLabel.style.display = 'none';

    this.hourInput = document.createElement('input');
    this.hourInput.type = 'number';
    this.hourInput.min = 0;
    this.hourInput.max = 23;
    this.hourInput.value = this.selectedDate?.getHours() ?? 0;

    const timeDivider = document.createElement('span');
    timeDivider.textContent = ':';

    // Minute input
    const minuteLabel = document.createElement('label');
    minuteLabel.textContent = 'M:';
    minuteLabel.style.display = 'none';

    this.minuteInput = document.createElement('input');
    this.minuteInput.type = 'number';
    this.minuteInput.min = 0;
    this.minuteInput.max = 59;
    this.minuteInput.value = this.selectedDate?.getMinutes() ?? 0;

    this.timeContainer.appendChild(hourLabel);
    this.timeContainer.appendChild(this.hourInput);
    this.timeContainer.appendChild(timeDivider);
    this.timeContainer.appendChild(minuteLabel);
    this.timeContainer.appendChild(this.minuteInput);
    this.picker.appendChild(this.timeContainer);

    // Sync inputs
    this.hourInput.addEventListener('input', () => {
      if (!this.selectedDate) this.selectedDate = new Date();
      let val = parseInt(this.hourInput.value, 10);
      this.selectedDate.setHours(Math.min(23, Math.max(0, isNaN(val) ? 0 : val)));
      this.updateValue();
    });

    this.minuteInput.addEventListener('input', () => {
      if (!this.selectedDate) this.selectedDate = new Date();
      let val = parseInt(this.minuteInput.value, 10);
      this.selectedDate.setMinutes(Math.min(59, Math.max(0, isNaN(val) ? 0 : val)));
      this.updateValue();
    });
  }

  updateValue() {
    if (!this.selectedDate) return;
    this.input.value = this.formatDate(this.selectedDate);
    this.options.onChange(this.input.value);
    this.renderCalendar();
  }

  handleKeyboard(e) {
    if (!this.selectedDate) return;
    const day = this.selectedDate.getDate();
    let newDate = new Date(this.selectedDate);

    switch (e.key) {
      case 'ArrowLeft': newDate.setDate(day - 1); break;
      case 'ArrowRight': newDate.setDate(day + 1); break;
      case 'ArrowUp': newDate.setDate(day - 7); break;
      case 'ArrowDown': newDate.setDate(day + 7); break;
      case 'Enter': this.updateValue(); this.hidePicker(); return;
      case 'Escape': this.hidePicker(); return;
      default: return;
    }

    if ((this.options.minDate && newDate < this.options.minDate) ||
        (this.options.maxDate && newDate > this.options.maxDate)) return;

    this.selectedDate = newDate;
    this.currentMonth = newDate.getMonth();
    this.currentYear = newDate.getFullYear();
    this.renderCalendar();
    e.preventDefault();
  }
}
