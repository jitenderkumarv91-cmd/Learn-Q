const copyButton = document.querySelector('[data-copy-reference]');
const referenceText = document.querySelector('[data-error-reference]');

copyButton?.addEventListener('click', async () => {
    const reference = referenceText?.textContent?.trim();
    if (!reference) {
        return;
    }

    try {
        await navigator.clipboard.writeText(reference);
        copyButton.textContent = 'Reference Copied';
        window.setTimeout(() => {
            copyButton.textContent = 'Copy Reference';
        }, 1800);
    } catch (error) {
        copyButton.textContent = 'Copy Failed';
        window.setTimeout(() => {
            copyButton.textContent = 'Copy Reference';
        }, 1800);
    }
});
