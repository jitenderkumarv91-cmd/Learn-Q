const searchInput = document.querySelector('[data-course-search]');
const cards = [...document.querySelectorAll('[data-course-card]')];
const emptyState = document.querySelector('[data-empty-state]');
const dropdown = document.querySelector('[data-search-dropdown]');
const suggestions = [...document.querySelectorAll('[data-search-suggestion]')];
const searchSummary = document.querySelector('[data-search-summary]');
const defaultSummaryText = searchSummary?.textContent?.trim() || '';

if (searchInput && cards.length) {
    const closeDropdown = () => {
        dropdown?.classList.add('is-hidden');
    };

    const updateSearchState = () => {
        const rawTerm = searchInput.value.trim();
        const term = rawTerm.toLowerCase();
        let visibleCount = 0;
        let visibleSuggestionCount = 0;

        cards.forEach((card) => {
            const title = card.dataset.title || '';
            const isVisible = term === '' || title.includes(term);
            card.classList.toggle('is-hidden', !isVisible);

            if (isVisible) {
                visibleCount += 1;
            }
        });

        suggestions.forEach((suggestion) => {
            const title = suggestion.dataset.title || '';
            const shouldShow = term !== '' && title.includes(term) && visibleSuggestionCount < 6;
            suggestion.classList.toggle('is-hidden', !shouldShow);

            if (shouldShow) {
                visibleSuggestionCount += 1;
            }
        });

        dropdown?.classList.toggle('is-hidden', term === '' || visibleSuggestionCount === 0);
        emptyState?.classList.toggle('is-hidden', visibleCount !== 0);

        if (!searchSummary) {
            return;
        }

        if (rawTerm === '') {
            searchSummary.textContent = defaultSummaryText;
        } else if (visibleCount === 0) {
            searchSummary.textContent = `No courses match "${rawTerm}".`;
        } else {
            searchSummary.textContent = `Showing ${visibleCount} match${visibleCount === 1 ? '' : 'es'} for "${rawTerm}".`;
        }
    };

    searchInput.addEventListener('input', updateSearchState);
    searchInput.addEventListener('focus', updateSearchState);
    searchInput.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeDropdown();
            searchInput.blur();
        }
    });

    document.addEventListener('click', (event) => {
        const target = event.target;

        if (target === searchInput || dropdown?.contains(target)) {
            return;
        }

        closeDropdown();
    });

    updateSearchState();
}
