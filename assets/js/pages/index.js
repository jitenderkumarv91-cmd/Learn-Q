const searchInput = document.querySelector('[data-course-search]');
const cards = [...document.querySelectorAll('[data-course-card]')];
const emptyState = document.querySelector('[data-empty-state]');

if (searchInput && cards.length) {
    const filterCards = () => {
        const term = searchInput.value.trim().toLowerCase();
        let visibleCount = 0;

        cards.forEach((card) => {
            const title = card.dataset.title || '';
            const isVisible = term === '' || title.includes(term);
            card.classList.toggle('is-hidden', !isVisible);
            if (isVisible) {
                visibleCount += 1;
            }
        });

        if (emptyState) {
            emptyState.classList.toggle('is-hidden', visibleCount !== 0);
        }
    };

    searchInput.addEventListener('input', filterCards);
    filterCards();
}
