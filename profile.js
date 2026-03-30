const profilePassword = document.querySelector('[data-password]');
const profileConfirm = document.querySelector('[data-confirm-password]');
const profileMatch = document.querySelector('[data-password-match]');

const updateProfilePasswordState = () => {
    if (!profileMatch) {
        return;
    }

    if (!profilePassword?.value && !profileConfirm?.value) {
        profileMatch.textContent = 'Leave password fields blank if you do not want to change the password.';
        return;
    }

    profileMatch.textContent = profilePassword?.value === profileConfirm?.value ? 'New passwords match.' : 'New passwords do not match.';
};

profilePassword?.addEventListener('input', updateProfilePasswordState);
profileConfirm?.addEventListener('input', updateProfilePasswordState);
updateProfilePasswordState();
window.ScholarGrid?.trackPageDuration('/profile.php', 'Student profile visit.');
