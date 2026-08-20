window.Inachis.SwitchSelector = {
    create(select, options = {}) {
        if (!(select instanceof HTMLSelectElement)) {
            throw new Error('createSwitchSelector must be called with a select element.');
        }

        if (select.options.length < 2) {
            throw new Error('SwitchSelector requires a select with at least two options.');
        }

        const defaults = {
            labelsPlacement: 'both', // both | none
            onChange: null
        };

        const state = {
            select,
            options: { ...defaults, ...options },

            wrapper: null,
            button: null,
            labelsContainer: null,

            labels: [],
            stops: [],

            stateCount: select.options.length
        };

        init();

        return {
            destroy
        };

        // ---------------------------------------------------------------------
        // Initialisation
        // ---------------------------------------------------------------------

        function init() {
            createElements();
            bindEvents();
            syncFromSelect();
        }

        // ---------------------------------------------------------------------
        // DOM creation
        // ---------------------------------------------------------------------

        function createElements() {
            select.classList.add('switch-selector-input');

            state.wrapper = document.createElement('span');
            state.wrapper.className = 'switch-selector';

            state.button = document.createElement('button');
            state.button.type = 'button';
            state.button.className = 'switch-selector-control';

            state.button.setAttribute('role', 'slider');
            state.button.setAttribute('aria-orientation', 'horizontal');
            state.button.setAttribute('aria-valuemin', '0');
            state.button.setAttribute('aria-valuemax', state.stateCount - 1);

            state.button.style.setProperty(
                '--state-count',
                state.stateCount
            );

            createStops();

            if (state.options.labelsPlacement !== 'none') {
                createLabels();
                state.wrapper.appendChild(state.labelsContainer);
            }

            state.wrapper.appendChild(state.button);

            select.insertAdjacentElement(
                'afterend',
                state.wrapper
            );
        }

        function createLabels() {
            state.labelsContainer = document.createElement('span');
            state.labelsContainer.className =
                'switch-selector-labels';

            [...select.options].forEach((option, index) => {
                const label = createLabel(
                    option.text,
                    index
                );

                state.labels.push(label);
                state.labelsContainer.appendChild(label);
            });
        }

        function createLabel(text, index) {
            const label = document.createElement('span');

            label.className = 'switch-selector-label';
            label.textContent = text;
            label.tabIndex = 0;

            label.addEventListener(
                'click',
                createLabelClickHandler(index)
            );

            label.addEventListener(
                'keydown',
                createLabelKeyDownHandler(index)
            );

            return label;
        }

        function createStops() {
            for (let i = 0; i < state.stateCount; i++) {
                const stop = document.createElement('span');

                stop.className = 'switch-selector-stop';

                stop.style.left =
                    `calc(12px + (${i} * var(--switch-step)))`;

                state.button.appendChild(stop);
                state.stops.push(stop);
            }
        }

        // ---------------------------------------------------------------------
        // Event binding
        // ---------------------------------------------------------------------

        function bindEvents() {
            state.button.addEventListener(
                'click',
                onButtonClick
            );

            state.button.addEventListener(
                'keydown',
                onButtonKeyDown
            );

            state.select.addEventListener(
                'change',
                syncFromSelect
            );
        }

        function createLabelClickHandler(index) {
            return function onLabelClick() {
                if (isDisabled()) {
                    return;
                }

                setValue(index);

                state.button.focus();
            };
        }

        function createLabelKeyDownHandler(index) {
            return function onLabelKeyDown(e) {
                if (e.key !== ' ' && e.key !== 'Enter') {
                    return;
                }

                e.preventDefault();

                if (isDisabled()) {
                    return;
                }

                setValue(index);

                state.button.focus();
            };
        }

        function onButtonClick() {
            next();
        }

        function onButtonKeyDown(e) {
            switch (e.key) {
                case 'ArrowRight':
                case 'ArrowUp':
                    e.preventDefault();
                    next();
                    break;

                case 'ArrowLeft':
                case 'ArrowDown':
                    e.preventDefault();
                    previous();
                    break;

                case 'Home':
                    e.preventDefault();
                    setValue(0);
                    break;

                case 'End':
                    e.preventDefault();
                    setValue(state.stateCount - 1);
                    break;

                case ' ':
                case 'Enter':
                    e.preventDefault();
                    next();
                    break;
            }
        }

                // ---------------------------------------------------------------------
        // State management
        // ---------------------------------------------------------------------

        function next() {
            if (isDisabled()) return;

            let index = state.select.selectedIndex + 1;

            if (index >= state.stateCount) {
                index = 0;
            }

            setValue(index);
        }

        function previous() {
            if (isDisabled()) return;

            let index = state.select.selectedIndex - 1;

            if (index < 0) {
                index = state.stateCount - 1;
            }

            setValue(index);
        }

        function setValue(index) {
            if (isDisabled()) return;

            if (index === state.select.selectedIndex) return;

            state.select.selectedIndex = index;

            state.select.dispatchEvent(
                new Event('change', { bubbles: true })
            );
        }

        function syncFromSelect() {
            const index = state.select.selectedIndex;
            const option = state.select.options[index];

            const travel = parseInt(
                getComputedStyle(state.button)
                    .getPropertyValue('--switch-step')
            ) || 20;

            state.button.style.setProperty(
                '--thumb-position',
                `calc(${index} * var(--switch-step))`
            );

            state.button.setAttribute('aria-valuenow', index);
            state.button.setAttribute('aria-valuetext', option.text);

            state.wrapper.classList.toggle(
                'disabled',
                isDisabled()
            );

            state.labels.forEach((label, i) => {
                label.classList.toggle(
                    'active',
                    i === index
                );
            });

            state.stops.forEach((stop, i) => {
                stop.classList.toggle(
                    'active',
                    i === index
                );
            });

            if (typeof state.options.onChange === 'function') {
                state.options.onChange.call(
                    state.select,
                    index,
                    option.value,
                    option.text
                );
            }
        }

        function isDisabled() {
            return state.select.disabled;
        }

        function destroy() {
            state.button.removeEventListener('click', onButtonClick);
            state.button.removeEventListener('keydown', onButtonKeyDown);
            state.select.removeEventListener('change', syncFromSelect);

            state.wrapper.remove();

            state.select.classList.remove('switch-selector-input');
        }
    }
};
