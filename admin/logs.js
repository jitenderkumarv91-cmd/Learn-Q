window.ScholarGrid?.trackPageDuration('/admin/logs.php', 'Admin log review visit.');

const monthFormatter = new Intl.DateTimeFormat('en-US', {
    month: 'long',
    year: 'numeric',
});

const dateFormatter = new Intl.DateTimeFormat('en-US', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
});

const padNumber = (value) => String(value).padStart(2, '0');

const parseIsoDate = (value) => {
    if (!/^\d{4}-\d{2}-\d{2}$/.test(value)) {
        return null;
    }

    const [year, month, day] = value.split('-').map(Number);
    const date = new Date(year, month - 1, day);
    date.setHours(0, 0, 0, 0);

    if (
        date.getFullYear() !== year
        || date.getMonth() !== month - 1
        || date.getDate() !== day
    ) {
        return null;
    }

    return date;
};

const formatIsoDate = (date) => `${date.getFullYear()}-${padNumber(date.getMonth() + 1)}-${padNumber(date.getDate())}`;
const startOfMonth = (date) => new Date(date.getFullYear(), date.getMonth(), 1);
const isSameDay = (left, right) => left.getTime() === right.getTime();
const isSameMonth = (left, right) => left.getFullYear() === right.getFullYear() && left.getMonth() === right.getMonth();

const today = new Date();
today.setHours(0, 0, 0, 0);

let activePicker = null;

const pickerInstances = Array.from(document.querySelectorAll('[data-date-picker]')).map((root) => {
    const hiddenInput = root.querySelector('[data-date-picker-value]');
    const trigger = root.querySelector('[data-date-picker-trigger]');
    const displayTarget = root.querySelector('[data-date-picker-display]');
    const panel = root.querySelector('[data-date-picker-panel]');
    const monthTarget = root.querySelector('[data-date-picker-month]');
    const grid = root.querySelector('[data-date-picker-grid]');
    const prevButton = root.querySelector('[data-date-picker-prev]');
    const nextButton = root.querySelector('[data-date-picker-next]');
    const clearButton = root.querySelector('[data-date-picker-clear]');
    const errorTarget = root.querySelector('[data-date-picker-error]');

    if (!hiddenInput || !trigger || !displayTarget || !panel || !monthTarget || !grid || !prevButton || !nextButton) {
        return null;
    }

    const maxDate = parseIsoDate(root.dataset.maxDate?.trim() || '') || today;
    const maxMonth = startOfMonth(maxDate);
    const placeholder = root.dataset.pickerPlaceholder?.trim() || 'Select a date';
    const emptyError = root.dataset.emptyError?.trim() || 'Select a date before continuing.';
    let selectedDate = parseIsoDate(hiddenInput.value.trim());
    let viewDate = startOfMonth(selectedDate || maxDate);

    const setError = (message = '') => {
        const hasError = message.trim() !== '';
        root.classList.toggle('is-invalid', hasError);

        if (errorTarget) {
            errorTarget.textContent = hasError ? message : emptyError;
            errorTarget.classList.toggle('is-hidden', !hasError);
        }
    };

    const syncDisplay = () => {
        displayTarget.textContent = selectedDate ? dateFormatter.format(selectedDate) : placeholder;
        root.classList.toggle('has-value', Boolean(selectedDate));
    };

    const close = () => {
        root.classList.remove('is-open');
        panel.classList.add('is-hidden');
        trigger.setAttribute('aria-expanded', 'false');

        if (activePicker === api) {
            activePicker = null;
        }
    };

    const render = () => {
        monthTarget.textContent = monthFormatter.format(viewDate);
        grid.replaceChildren();

        const monthStart = startOfMonth(viewDate);
        const gridStart = new Date(monthStart);
        gridStart.setDate(monthStart.getDate() - monthStart.getDay());

        for (let offset = 0; offset < 42; offset += 1) {
            const cellDate = new Date(gridStart);
            cellDate.setDate(gridStart.getDate() + offset);
            cellDate.setHours(0, 0, 0, 0);

            const dayButton = document.createElement('button');
            dayButton.type = 'button';
            dayButton.className = 'date-picker__day';
            dayButton.dataset.dateValue = formatIsoDate(cellDate);
            dayButton.textContent = String(cellDate.getDate());

            if (!isSameMonth(cellDate, viewDate)) {
                dayButton.classList.add('is-outside-month');
            }

            if (isSameDay(cellDate, today)) {
                dayButton.classList.add('is-today');
            }

            if (selectedDate && isSameDay(cellDate, selectedDate)) {
                dayButton.classList.add('is-selected');
            }

            if (cellDate > maxDate) {
                dayButton.disabled = true;
            }

            grid.appendChild(dayButton);
        }

        nextButton.disabled = startOfMonth(new Date(viewDate.getFullYear(), viewDate.getMonth() + 1, 1)).getTime() > maxMonth.getTime();
    };

    const open = () => {
        if (activePicker && activePicker !== api) {
            activePicker.close();
        }

        activePicker = api;
        root.classList.add('is-open');
        panel.classList.remove('is-hidden');
        trigger.setAttribute('aria-expanded', 'true');
        render();
    };

    trigger.addEventListener('click', () => {
        if (root.classList.contains('is-open')) {
            close();
            return;
        }

        open();
    });

    prevButton.addEventListener('click', () => {
        viewDate = startOfMonth(new Date(viewDate.getFullYear(), viewDate.getMonth() - 1, 1));
        render();
    });

    nextButton.addEventListener('click', () => {
        if (nextButton.disabled) {
            return;
        }

        viewDate = startOfMonth(new Date(viewDate.getFullYear(), viewDate.getMonth() + 1, 1));
        render();
    });

    grid.addEventListener('click', (event) => {
        const dayButton = event.target.closest('[data-date-value]');

        if (!(dayButton instanceof HTMLButtonElement) || dayButton.disabled) {
            return;
        }

        const nextDate = parseIsoDate(dayButton.dataset.dateValue || '');

        if (!nextDate) {
            return;
        }

        selectedDate = nextDate;
        hiddenInput.value = formatIsoDate(nextDate);
        viewDate = startOfMonth(nextDate);
        setError('');
        syncDisplay();
        render();
        close();
    });

    clearButton?.addEventListener('click', () => {
        selectedDate = null;
        hiddenInput.value = '';
        setError('');
        syncDisplay();
        render();
        trigger.focus();
    });

    syncDisplay();
    render();

    const api = {
        root,
        close,
        focus: () => trigger.focus(),
        getValue: () => hiddenInput.value.trim(),
        setError,
    };

    return api;
}).filter(Boolean);

const findPicker = (element) => pickerInstances.find((picker) => picker.root === element);

const validateDatePickers = (form) => {
    const formPickers = Array.from(form.querySelectorAll('[data-date-picker]'))
        .map((element) => findPicker(element))
        .filter(Boolean);

    let firstInvalidPicker = null;

    formPickers.forEach((picker) => {
        if (picker.getValue()) {
            picker.setError('');
            return;
        }

        picker.setError(picker.root.dataset.emptyError?.trim() || 'Select a date before continuing.');

        if (!firstInvalidPicker) {
            firstInvalidPicker = picker;
        }
    });

    if (firstInvalidPicker) {
        firstInvalidPicker.focus();
        return false;
    }

    if (form.dataset.logDeleteMode === 'range') {
        const startDate = parseIsoDate(form.querySelector('[name="start_date"]')?.value || '');
        const endDate = parseIsoDate(form.querySelector('[name="end_date"]')?.value || '');

        if (startDate && endDate && startDate > endDate) {
            formPickers.forEach((picker) => {
                picker.setError('The start date must be on or before the end date.');
            });
            formPickers[0]?.focus();
            return false;
        }
    }

    return true;
};

const confirmForms = document.querySelectorAll('[data-admin-confirm-form]');
const modal = document.querySelector('[data-confirm-modal]');
const titleTarget = document.querySelector('[data-confirm-title-target]');
const messageTarget = document.querySelector('[data-confirm-message-target]');
const subjectTarget = document.querySelector('[data-confirm-subject-target]');
const confirmButton = document.querySelector('[data-confirm-submit]');
const cancelButtons = document.querySelectorAll('[data-confirm-cancel]');

let pendingForm = null;
let pendingSubmitter = null;
let allowSubmit = false;

const resolveSubject = (form) => {
    const mode = form.dataset.logDeleteMode;

    if (mode === 'range') {
        const startDate = parseIsoDate(form.querySelector('[name="start_date"]')?.value || '');
        const endDate = parseIsoDate(form.querySelector('[name="end_date"]')?.value || '');

        if (startDate && endDate) {
            return `Selected range: ${dateFormatter.format(startDate)} to ${dateFormatter.format(endDate)}`;
        }
    }

    if (mode === 'student') {
        const select = form.querySelector('[name="student_id"]');
        const option = select?.options?.[select.selectedIndex];

        if (option && option.value) {
            return `Selected student: ${option.textContent.trim()}`;
        }
    }

    return form.dataset.confirmSubject?.trim() || '';
};

const closeModal = () => {
    if (!modal) {
        return;
    }

    modal.classList.add('is-hidden');
    modal.setAttribute('aria-hidden', 'true');
    pendingForm = null;
    pendingSubmitter = null;
    allowSubmit = false;
};

const openModal = (form, submitter) => {
    if (!modal) {
        return;
    }

    pendingForm = form;
    pendingSubmitter = submitter;
    allowSubmit = false;

    const title = form.dataset.confirmTitle?.trim() || 'Confirm Action';
    const message = form.dataset.confirmMessage?.trim() || 'This action cannot be undone.';
    const buttonLabel = form.dataset.confirmButton?.trim() || 'Continue';
    const subject = resolveSubject(form);

    if (titleTarget) {
        titleTarget.textContent = title;
    }

    if (messageTarget) {
        messageTarget.textContent = message;
    }

    if (subjectTarget) {
        if (subject) {
            subjectTarget.textContent = subject;
            subjectTarget.classList.remove('is-hidden');
        } else {
            subjectTarget.textContent = '';
            subjectTarget.classList.add('is-hidden');
        }
    }

    if (confirmButton) {
        confirmButton.textContent = buttonLabel;
    }

    modal.classList.remove('is-hidden');
    modal.setAttribute('aria-hidden', 'false');
    confirmButton?.focus();
};

confirmForms.forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (!modal) {
            return;
        }

        if (allowSubmit) {
            allowSubmit = false;
            return;
        }

        if (!validateDatePickers(form)) {
            event.preventDefault();
            return;
        }

        event.preventDefault();
        const submitter = event.submitter || form.querySelector('button[type="submit"], input[type="submit"]');
        openModal(form, submitter);
    });
});

confirmButton?.addEventListener('click', () => {
    if (!pendingForm) {
        return;
    }

    const form = pendingForm;
    const submitter = pendingSubmitter;
    allowSubmit = true;

    if (submitter instanceof HTMLButtonElement || submitter instanceof HTMLInputElement) {
        form.requestSubmit(submitter);
    } else {
        form.requestSubmit();
    }

    closeModal();
});

cancelButtons.forEach((button) => {
    button.addEventListener('click', closeModal);
});

document.addEventListener('click', (event) => {
    if (activePicker && !activePicker.root.contains(event.target)) {
        activePicker.close();
    }
});

window.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        if (activePicker) {
            activePicker.close();
            return;
        }

        if (modal && !modal.classList.contains('is-hidden')) {
            closeModal();
        }
    }
});
