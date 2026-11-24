/**
 * Interactive controller for the export UI workflow.
 */
window.Inachis = window.Inachis || {};

window.Inachis.Export = (function () {
    'use strict';

    let ajaxEndpoint = '';
    let selectedIds = new Set();

    /**
     * Initialize event bindings and scope visibilities.
     */
    function init(endpointUrl) {
        ajaxEndpoint = endpointUrl;

        const hiddenInput = document.getElementById('selectedIds');
        if (hiddenInput && hiddenInput.value) {
            hiddenInput.value.split(',').filter(Boolean).forEach(id => selectedIds.add(id));
        }

        bindContentTypeToggle();
        bindScopeToggle();
        bindSearchInput();

        // Initial state render
        updateContentTypeVisibility();
        updateScopeVisibility();
    }

    function bindContentTypeToggle() {
        const radios = document.querySelectorAll('input[name="content_type"]');
        radios.forEach(radio => {
            radio.addEventListener('change', () => {
                updateContentTypeVisibility();
                resetSelection();
            });
        });
    }

    function bindScopeToggle() {
        const scopeRadios = document.querySelectorAll('input[name="scope"]');
        scopeRadios.forEach(radio => {
            radio.addEventListener('change', () => {
                updateScopeVisibility();
            });
        });
    }

    function updateContentTypeVisibility() {
        const selectedType = getSelectedContentType();

        // Toggle domain-specific scope fieldsets
        document.querySelectorAll('fieldset[data-content-type]').forEach(fieldset => {
            const isMatch = fieldset.getAttribute('data-content-type') === selectedType;
            fieldset.hidden = !isMatch;
            fieldset.setAttribute('aria-hidden', (!isMatch).toString());
        });

        // Toggle filter fieldset (only available for pages/posts)
        const filterOptions = document.getElementById('filter__options');
        if (filterOptions) {
            const isPost = selectedType === 'post';
            filterOptions.hidden = !isPost;
            filterOptions.setAttribute('aria-hidden', (!isPost).toString());
        }

        updateScopeVisibility();
    }

    function updateScopeVisibility() {
        const selectedType = getSelectedContentType();
        const activeScopeRadio = document.querySelector(`fieldset[data-content-type="${selectedType}"] input[name="scope"]:checked`)
            || document.querySelector('input[name="scope"]:checked');

        const scope = activeScopeRadio ? activeScopeRadio.value : 'all';
        const manualOptions = document.getElementById('manual__options');

        if (manualOptions) {
            const isManual = scope === 'manual';
            manualOptions.hidden = !isManual;
            manualOptions.setAttribute('aria-hidden', (!isManual).toString());

            if (isManual) {
                fetchManualItems();
            }
        }
    }

    function bindSearchInput() {
        const searchInput = document.getElementById('filter__keyword');
        if (!searchInput) return;

        let debounceTimer;
        searchInput.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                fetchManualItems();
            }, 300);
        });
    }

    function getSelectedContentType() {
        const checked = document.querySelector('input[name="content_type"]:checked');
        return checked ? checked.value : 'post';
    }

    function fetchManualItems() {
        const container = document.getElementById('manual__table-container');
        const keywordInput = document.getElementById('filter__keyword');
        if (!container) return;

        const domain = getSelectedContentType();
        const keyword = keywordInput ? keywordInput.value : '';

        container.innerHTML = '<p class="loading">Loading items…</p>';

        const url = new URL(ajaxEndpoint, window.location.origin);
        url.searchParams.append('domain', domain);
        url.searchParams.append('keyword', keyword);

        fetch(url)
            .then(res => res.json())
            .then(data => renderManualTable(data.items || []))
            .catch(() => {
                container.innerHTML = '<p class="error">Failed to load content for selection.</p>';
            });
    }

    function renderManualTable(items) {
        const container = document.getElementById('manual__table-container');
        if (!items.length) {
            container.innerHTML = '<p>No items found matching criteria.</p>';
            return;
        }

        let html = `
            <table class="table table--compact table--striped">
                <thead>
                    <tr>
                        <th width="40"><input type="checkbox" id="select-all-manual"></th>
                        <th>Title</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
        `;

        items.forEach(item => {
            const isChecked = selectedIds.has(item.id.toString()) ? 'checked' : '';
            html += `
                <tr>
                    <td>
                        <input type="checkbox" class="manual-item-checkbox" value="${item.id}" ${isChecked}>
                    </td>
                    <td><strong>${escapeHtml(item.title)}</strong></td>
                    <td><code>${escapeHtml(item.subtitle)}</code></td>
                </tr>
            `;
        });

        html += '</tbody></table>';
        container.innerHTML = html;

        bindTableEvents();
        updateSelectedCounter();
    }

    function bindTableEvents() {
        const selectAll = document.getElementById('select-all-manual');
        const itemCheckboxes = document.querySelectorAll('.manual-item-checkbox');

        if (selectAll) {
            selectAll.addEventListener('change', (e) => {
                itemCheckboxes.forEach(cb => {
                    cb.checked = e.target.checked;
                    toggleSelection(cb.value, cb.checked);
                });
                updateSelectedCounter();
            });
        }

        itemCheckboxes.forEach(cb => {
            cb.addEventListener('change', () => {
                toggleSelection(cb.value, cb.checked);
                updateSelectedCounter();
            });
        });
    }

    function toggleSelection(id, add) {
        if (add) {
            selectedIds.add(id.toString());
        } else {
            selectedIds.delete(id.toString());
        }
        syncHiddenInput();
    }

    function resetSelection() {
        selectedIds.clear();
        syncHiddenInput();
        updateSelectedCounter();
    }

    function syncHiddenInput() {
        const hiddenInput = document.getElementById('selectedIds');
        if (hiddenInput) {
            hiddenInput.value = Array.from(selectedIds).join(',');
        }
    }

    function updateSelectedCounter() {
        const counterAdmonition = document.querySelector('#manual__options .admonition__info');
        if (counterAdmonition) {
            counterAdmonition.classList.toggle('start-hidden', selectedIds.size === 0);
            const strong = counterAdmonition.querySelector('strong');
            if (strong) {
                strong.textContent = selectedIds.size.toString();
            }
        }
    }

    function escapeHtml(str) {
        return (str || '').replace(/[&<>"']/g, m => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[m]));
    }

    return { init };
})();
