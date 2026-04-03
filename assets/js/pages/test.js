const testRoot = document.querySelector('[data-test-root]');
const answerCount = document.querySelector('[data-answer-count]');
const submitButton = document.querySelector('[data-submit-test]');

if (testRoot && answerCount && submitButton) {
    const courseId = testRoot.dataset.courseId;
    const csrfToken = window.ScholarGrid?.csrfToken || '';
    const endpoint = window.ScholarGrid?.buildUrl('ajax/check_answer.php') || '/ajax/check_answer.php';

    const updateProgress = () => {
        const answered = testRoot.querySelectorAll('[data-question-card][data-answered="1"]').length;
        answerCount.textContent = String(answered);
        submitButton.disabled = answered < 20;
        submitButton.textContent = answered < 20 ? `Submit Test (${answered}/20)` : 'Submit Test';
    };

    const setFeedback = (card, payload) => {
        const box = card.querySelector('[data-feedback-box]');
        if (!box) {
            return;
        }

        box.classList.remove('is-hidden', 'is-correct', 'is-wrong');
        box.classList.add(payload.is_correct ? 'is-correct' : 'is-wrong');
        box.replaceChildren();

        const title = document.createElement('strong');
        title.textContent = payload.feedback;
        const body = document.createElement('p');
        body.textContent = payload.explanation;

        box.append(title, body);
    };

    testRoot.addEventListener('change', async (event) => {
        const input = event.target.closest('[data-answer-input]');
        if (!input) {
            return;
        }

        const card = input.closest('[data-question-card]');
        if (!card || card.dataset.answered === '1') {
            return;
        }

        const questionId = card.dataset.questionId;
        const formData = new FormData();
        formData.append('csrf_token', csrfToken);
        formData.append('course_id', courseId || '0');
        formData.append('question_id', questionId || '0');
        formData.append('selected_option', input.value);

        card.querySelectorAll('[data-answer-input]').forEach((field) => {
            field.disabled = true;
        });

        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
            });
            const payload = await response.json();

            if (!response.ok || !payload.success) {
                if (payload.redirect_url) {
                    window.location.assign(payload.redirect_url);
                    return;
                }

                throw new Error(payload.message || payload.error?.message || 'Unable to check answer.');
            }

            card.dataset.answered = '1';
            card.querySelectorAll('[data-option-wrapper]').forEach((wrapper) => {
                const optionInput = wrapper.querySelector('[data-answer-input]');
                if (!optionInput) {
                    return;
                }

                wrapper.classList.add('is-locked');
                wrapper.classList.toggle('is-selected', optionInput.value === payload.selected_option);
                if (optionInput.value === payload.selected_option) {
                    wrapper.classList.add(payload.is_correct ? 'is-correct' : 'is-wrong');
                }
            });

            setFeedback(card, payload);
            updateProgress();
        } catch (error) {
            card.querySelectorAll('[data-answer-input]').forEach((field) => {
                field.disabled = false;
            });
            window.alert(error.message || 'Unable to verify the answer.');
        }
    });

    updateProgress();
}
