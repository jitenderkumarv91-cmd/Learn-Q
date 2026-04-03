window.ScholarGrid?.trackPageDuration('/admin/messages.php', 'Admin message review visit.');

const deleteForms = document.querySelectorAll('[data-delete-message-form]');
const modal = document.querySelector('[data-confirm-modal]');
const modalSubject = document.querySelector('[data-confirm-subject]');
const confirmButton = document.querySelector('[data-confirm-submit]');
const cancelButtons = document.querySelectorAll('[data-confirm-cancel]');

let pendingForm = null;
let pendingSubmitter = null;
let allowSubmit = false;

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
    const subject = form.dataset.messageSubject?.trim();
    if (modalSubject) {
        modalSubject.textContent = subject ? `Selected message: ${subject}` : 'The selected message will be removed permanently.';
    }
    modal.classList.remove('is-hidden');
    modal.setAttribute('aria-hidden', 'false');
    confirmButton?.focus();
};

deleteForms.forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (allowSubmit) {
            allowSubmit = false;
            return;
        }

        event.preventDefault();
        const submitter = event.submitter || form.querySelector('button[name="delete_message"]');
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
