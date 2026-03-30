const passwordInput = document.querySelector('[data-password]');
const confirmInput = document.querySelector('[data-confirm-password]');
const strengthText = document.querySelector('[data-password-strength]');
const matchText = document.querySelector('[data-password-match]');

const getStrength = (value) => {
    let score = 0;
    if (value.length >= 8) score += 1;
    if (/[A-Z]/.test(value)) score += 1;
    if (/[0-9]/.test(value)) score += 1;
    if (/[^A-Za-z0-9]/.test(value)) score += 1;

    if (score <= 1) return 'Weak';
    if (score <= 3) return 'Moderate';
    return 'Strong';
};

const updateSignupState = () => {
    if (passwordInput && strengthText) {
        strengthText.textContent = `Password strength: ${getStrength(passwordInput.value)}`;
    }

    if (passwordInput && confirmInput && matchText) {
        if (!confirmInput.value) {
            matchText.textContent = 'Passwords must match.';
        } else {
            matchText.textContent = passwordInput.value === confirmInput.value ? 'Passwords match.' : 'Passwords do not match.';
        }
    }
};

passwordInput?.addEventListener('input', updateSignupState);
confirmInput?.addEventListener('input', updateSignupState);
updateSignupState();
