const roleInput = document.querySelector('[data-role-input]');
const roleButtons = document.querySelectorAll('[data-role-switch] [data-role]');

roleButtons.forEach((button) => {
    button.addEventListener('click', () => {
        const role = button.dataset.role;
        if (!roleInput || !role) {
            return;
        }

        roleInput.value = role;
        roleButtons.forEach((chip) => chip.classList.toggle('is-active', chip === button));
    });
});
