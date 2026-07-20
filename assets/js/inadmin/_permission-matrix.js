window.Inachis.PermissionMatrix = {
    implications: {},

    init() {
        const matrix = document.querySelector('.permission-matrix');
        if (!matrix) {
            return;
        }

        this.initialiseSelectedState(matrix);
        this.bindCheckboxes(matrix);
        this.bindRowToggles(matrix);
        this.bindColumnToggles(matrix);
        this.bindFilter(matrix);
    },

    initialiseSelectedState(matrix) {
        const self = this;
        matrix
            .querySelectorAll('.permission-matrix__checkbox')
            .forEach(function (checkbox) {
                self.updateCellState(checkbox);

                if (checkbox.checked) {
                    self.applyImplications(
                        checkbox.dataset.resource,
                        checkbox.dataset.action,
                        matrix
                    );
                }
            });
    },

    bindCheckboxes(matrix) {
        const self = this;
        matrix.addEventListener('change', function (event) {
            const checkbox = event.target;
            if (!checkbox.classList.contains('permission-matrix__checkbox')) {
                return;
            }

            self.updateCellState(checkbox);
            if (checkbox.checked) {

                self.applyImplications(
                    checkbox.dataset.resource,
                    checkbox.dataset.action,
                    matrix
                );
            } else {

                self.removeImpliedState(
                    checkbox.dataset.resource,
                    matrix
                );
            }
        });
    },

    applyImplications(resource, action, matrix) {
        const self = this;
        const impliedActions = this.implications[action] ?? [];
        impliedActions.forEach(function (impliedAction) {
            const checkbox = self.getPermissionCheckbox(
                matrix,
                resource,
                impliedAction
            );
            if (!checkbox) {
                return;
            }

            if (!checkbox.checked) {
                checkbox.checked = true;
                checkbox.dataset.implied = 'true';
            }

            self.updateCellState(checkbox);
        });
    },

    removeImpliedState(resource, matrix) {
        const self = this;
        matrix
            .querySelectorAll(
                '.permission-matrix__checkbox[data-resource="' +
                resource +
                '"]'
            )
            .forEach(function (checkbox) {
                if (checkbox.dataset.implied !== 'true') {
                    return;
                }
                checkbox.checked = false;
                delete checkbox.dataset.implied;
                self.updateCellState(checkbox);
            });
    },

    bindRowToggles(matrix) {
        const self = this;
        matrix
            .querySelectorAll('.permission-matrix__toggle-row')
            .forEach(function (button) {
                button.addEventListener('click', function () {
                    const resource = button.dataset.row;
                    const boxes = matrix.querySelectorAll(
                        '.permission-matrix__checkbox[data-resource="' +
                        resource +
                        '"]'
                    );
                    const enable =
                        !Array.from(boxes).every(box => box.checked);
                    boxes.forEach(function (checkbox) {
                        checkbox.checked = enable;
                        delete checkbox.dataset.implied;
                        self.updateCellState(checkbox);

                    });
                    if (enable) {
                        boxes.forEach(function (checkbox) {

                            self.applyImplications(
                                checkbox.dataset.resource,
                                checkbox.dataset.action,
                                matrix
                            );

                        });

                    }

                });

            });

    },

    bindColumnToggles(matrix) {
        const self = this;
        matrix
            .querySelectorAll('.permission-matrix__toggle-col')
            .forEach(function (button) {
                button.addEventListener('click', function () {
                    const action = button.dataset.col;
                    const boxes = matrix.querySelectorAll(
                        '.permission-matrix__checkbox[data-action="' +
                        action +
                        '"]'
                    );

                    const enable =
                        !Array.from(boxes).every(box => box.checked);
                    boxes.forEach(function (checkbox) {
                        checkbox.checked = enable;
                        delete checkbox.dataset.implied;
                        self.updateCellState(checkbox);
                        if (enable) {
                            self.applyImplications(
                                checkbox.dataset.resource,
                                checkbox.dataset.action,
                                matrix
                            );
                        }
                    });
                });
            });
    },

    bindFilter(matrix) {
        const filter = document.getElementById('permission-filter');
        if (!filter) {
            return;
        }

        filter.addEventListener('input', function () {
            const term = filter.value.trim().toLowerCase();
            matrix
                .querySelectorAll('tbody tr')
                .forEach(function (row) {
                    const label = row.querySelector('th[scope="row"]');
                    if (!label) {
                        return;
                    }

                    row.hidden =
                        term !== '' &&
                        !label.textContent
                            .toLowerCase()
                            .includes(term);
                });
        });
    },

    updateCellState(checkbox) {
        const cell = checkbox.closest('td');
        if (!cell) {
            return;
        }

        cell.classList.toggle(
            'permission--selected',
            checkbox.checked
        );
        cell.classList.toggle(
            'permission--implied',
            checkbox.dataset.implied === 'true'
        );

    },

    getPermissionCheckbox(matrix, resource, action) {
        return matrix.querySelector(
            '.permission-matrix__checkbox' +
            '[data-resource="' + resource + '"]' +
            '[data-action="' + action + '"]'
        );
    }
};
