window.ScholarGrid?.trackPageDuration('/admin/questions.php', 'Admin question management visit.');

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

const resolveSubject = (form) => form.dataset.confirmSubject?.trim() || '';

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

window.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && modal && !modal.classList.contains('is-hidden')) {
        closeModal();
    }
});
