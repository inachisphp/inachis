window.Inachis.DragDropTable = {
    tableBody: null,
    draggedRow: null,
    draggedClone: null,
    placeholder: null,
    offsetY: 0,
    rowRects: [],
    SCROLL_MARGIN: 60,
    MAX_SCROLL_SPEED: 20,

    _boundHandleDragging: null,
    _boundHandleDragEnd: null,
    _boundAutoScroll: null,

    init() {
        this.tableBody = document.querySelector('table.table--dragdrop tbody');
        if (this.tableBody) {
            this.tableBody.querySelectorAll('tr').forEach(this.addHandlers.bind(this));
        }
    },

    createPlaceholder(row) {
        const ph = document.createElement("tr");
        ph.classList.add("placeholder");
        ph.style.height = `${row.offsetHeight}px`;
        return ph;
    },

    computeRowRects() {
        this.rowRects = Array.from(this.tableBody.querySelectorAll("tr:not(.placeholder)")).map(row => ({
            row,
            top: row.getBoundingClientRect().top,
            height: row.getBoundingClientRect().height
        }));
    },

    handleDragStart(e) {
        if (!e.target.classList.contains("handle")) return;
        e.preventDefault();

        this.draggedRow = e.currentTarget;
        this.draggedRow.classList.add("dragging");

        const rect = this.draggedRow.getBoundingClientRect();
        this.offsetY = e.type.startsWith("touch") ? e.touches[0].clientY - rect.top : e.clientY - rect.top;

        // Create placeholder
        this.placeholder = this.createPlaceholder(this.draggedRow);
        this.tableBody.insertBefore(this.placeholder, this.draggedRow.nextSibling);

        // Create clone
        this.draggedClone = this.draggedRow.cloneNode(true);
        this.draggedClone.classList.add("dragged-clone", "lifted");
        this.draggedClone.style.width = `${rect.width}px`;
        this.draggedClone.style.left = `${rect.left}px`;
        this.draggedClone.style.top = `${rect.top}px`;
        this.draggedClone.style.height = `${rect.height}px`;
        document.body.appendChild(this.draggedClone);

        this.draggedRow.style.visibility = "hidden";

        this.computeRowRects();

        this._boundHandleDragging = this.handleDragging.bind(this);
        this._boundHandleDragEnd = this.handleDragEnd.bind(this);
        this._boundAutoScroll = this.autoScroll.bind(this);

        if (e.type.startsWith("touch")) {
            document.addEventListener("touchmove", this._boundHandleDragging, { passive: false });
            document.addEventListener("touchend", this._boundHandleDragEnd);
        } else {
            document.addEventListener("mousemove", this._boundHandleDragging);
            document.addEventListener("mouseup", this._boundHandleDragEnd);
        }

        requestAnimationFrame(this._boundAutoScroll);
    },

    handleDragging(e) {
        if (!this.draggedClone) return;
        e.preventDefault();

        const clientY = e.type.startsWith("touch") ? e.touches[0].clientY : e.clientY;
        this.draggedClone.style.top = `${clientY - this.offsetY}px`;

        let closest = { index: 0, distance: Infinity };
        this.rowRects.forEach(({ row, top, height }, i) => {
            if (row === this.draggedRow) return;
            const center = top + height / 2;
            const distance = Math.abs(clientY - center);
            if (distance < closest.distance) {
                closest = { index: i, distance };
            }
        });

        const refRow = this.rowRects[closest.index].row;
        const rect = refRow.getBoundingClientRect();
        if (clientY < rect.top + rect.height / 2) {
            this.tableBody.insertBefore(this.placeholder, refRow);
        } else {
            this.tableBody.insertBefore(this.placeholder, refRow.nextSibling);
        }

        this.rowRects.forEach(({ row }) => {
            if (row === this.draggedRow) return;
            const placeholderIndex = Array.from(this.tableBody.children).indexOf(this.placeholder);
            const rowIndex = Array.from(this.tableBody.children).indexOf(row);
            const offset = this.placeholder.offsetHeight * 0.1;
            row.style.transform = rowIndex >= placeholderIndex ? `translateY(${offset}px)` : `translateY(0)`;
        });
    },

    handleDragEnd() {
        if (!this.draggedRow || !this.placeholder) return;

        const row = this.draggedRow;
        const clone = this.draggedClone;

        this.tableBody.insertBefore(row, this.placeholder);
        row.style.visibility = "";
        row.classList.remove("dragging");
        row.classList.add("drop");
        row.addEventListener("animationend", () => row.classList.remove("drop"), { once: true });

        if (clone) {
            clone.classList.add("fade-out");
            clone.addEventListener("transitionend", () => clone.remove(), { once: true });
            this.draggedClone = null;
        }

        this.placeholder.remove();
        Array.from(this.tableBody.querySelectorAll("tr:not(.placeholder)")).forEach(row => row.style.transform = "");

        this.sendOrderUpdate();

        document.removeEventListener("mousemove", this._boundHandleDragging);
        document.removeEventListener("mouseup", this._boundHandleDragEnd);
        document.removeEventListener("touchmove", this._boundHandleDragging);
        document.removeEventListener("touchend", this._boundHandleDragEnd);

        this.draggedRow = null;
        this.placeholder = null;
        this.rowRects = [];
    },

    moveRow(row, direction) {
        const sibling = direction === -1 ? row.previousElementSibling : row.nextElementSibling;
        if (!sibling) return;
        this.tableBody.insertBefore(direction === -1 ? row : sibling, direction === -1 ? sibling : row);
        this.sendOrderUpdate();
    },

    addHandlers(row) {
        const actionsCol = document.querySelector('.table--dragdrop thead th:last-child');
        if (actionsCol.textContent === 'Actions') {
            actionsCol.remove();
            Array.from(this.tableBody.querySelectorAll("tr")).forEach(row => {
                const actionCell = row.querySelector("td:last-child");
                if (actionCell) actionCell.remove();
            });
        }
        row.addEventListener("mousedown", this.handleDragStart.bind(this));
        row.addEventListener("touchstart", this.handleDragStart.bind(this), { passive: false });

        // const moveUpBtn = row.querySelector(".move-up");
        // const moveDownBtn = row.querySelector(".move-down");
        // if (moveUpBtn) moveUpBtn.addEventListener("click", () => this.moveRow(row, -1));
        // if (moveDownBtn) moveDownBtn.addEventListener("click", () => this.moveRow(row, 1));
    },

    sendOrderUpdate() {
        const order = Array.from(this.tableBody.querySelectorAll("input.checkbox,input[type=hidden]")).map(input => input.value);
        fetch(window.Inachis.prefix + "/settings/navigation/reorder", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-Token": document.querySelector('meta[name="csrf-token"]')?.content || ""
            },
            body: JSON.stringify({ order })
        })
            .then(res => res.json())
            .then(data => console.log("Order updated:", data))
            .catch(err => console.error("Error updating order:", err));
    },

    autoScroll() {
        if (!this.draggedClone) return;

        const tableRect = this.tableBody.getBoundingClientRect();
        const cloneRect = this.draggedClone.getBoundingClientRect();
        let scrollAmount = 0;

        if (cloneRect.top < tableRect.top + this.SCROLL_MARGIN) {
            scrollAmount = -Math.min(this.MAX_SCROLL_SPEED, (tableRect.top + this.SCROLL_MARGIN - cloneRect.top) / 2);
        } else if (cloneRect.bottom > tableRect.bottom - this.SCROLL_MARGIN) {
            scrollAmount = Math.min(this.MAX_SCROLL_SPEED, (cloneRect.bottom - tableRect.bottom + this.SCROLL_MARGIN) / 2);
        }

        if (scrollAmount !== 0) {
            this.tableBody.scrollBy({ top: scrollAmount, behavior: 'auto' });
        }

        requestAnimationFrame(this._boundAutoScroll);
    }
};