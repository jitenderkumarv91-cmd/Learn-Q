<?php

declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
require_student();

$slug = trim((string) ($_GET['slug'] ?? ''));
$course = find_course_by_slug($slug);

if (!$course) {
    flash('error', 'The requested test could not be found.');
    redirect('index.php');
}

$courseId = (int) $course['id'];
$studentId = student_id();
$_SESSION['active_tests'] = $_SESSION['active_tests'] ?? [];

if (is_post()) {
    verify_csrf();

    if (isset($_POST['start_test'])) {
        if (isset($_SESSION['active_tests'][$courseId])) {
            flash('info', 'Finish the current attempt before selecting a different difficulty.');
            redirect('test.php?slug=' . urlencode($course['slug']));
        }

        $difficulty = trim((string) ($_POST['difficulty'] ?? 'beginner'));
        $difficulty = in_array($difficulty, ['beginner', 'intermediate', 'advanced'], true) ? $difficulty : 'beginner';
        $questions = fetch_question_set($courseId, $difficulty, 20);

        if (count($questions) < 20) {
            flash('error', 'This course does not yet have 20 questions for the selected difficulty. Ask the admin to add more questions.');
            redirect('test.php?slug=' . urlencode($course['slug']));
        }

        $preparedQuestions = [];

        foreach ($questions as $question) {
            $preparedQuestions[(int) $question['id']] = [
                'id' => (int) $question['id'],
                'question_text' => $question['question_text'],
                'option_a' => $question['option_a'],
                'option_b' => $question['option_b'],
                'option_c' => $question['option_c'],
                'option_d' => $question['option_d'],
                'correct_option' => $question['correct_option'],
                'explanation' => $question['explanation'],
            ];
        }

        $_SESSION['active_tests'][$courseId] = [
            'course_id' => $courseId,
            'difficulty' => $difficulty,
            'started_at' => time(),
            'questions' => $preparedQuestions,
            'answers' => [],
        ];

        log_action('test_started', 'Started test for ' . $course['title'] . ' at ' . $difficulty . ' level.', 0, current_path_with_query());
        flash('success', 'Test started. Difficulty is now locked until you submit all answers.');
        redirect('test.php?slug=' . urlencode($course['slug']));
    }

    if (isset($_POST['submit_test'])) {
        $activeTest = $_SESSION['active_tests'][$courseId] ?? null;

        if ($activeTest === null) {
            flash('error', 'No active test was found.');
            redirect('test.php?slug=' . urlencode($course['slug']));
        }

        if (count($activeTest['answers']) < 20) {
            flash('error', 'Answer all 20 questions before submitting the test.');
            redirect('test.php?slug=' . urlencode($course['slug']));
        }

        $correctAnswers = 0;

        foreach ($activeTest['answers'] as $answer) {
            if (!empty($answer['is_correct'])) {
                $correctAnswers++;
            }
        }

        $score = $correctAnswers;
        $durationSeconds = max(1, time() - (int) $activeTest['started_at']);
        $pdo = db();

        try {
            $pdo->beginTransaction();

            $attemptStatement = $pdo->prepare(
                'INSERT INTO test_attempts (
                    student_id,
                    course_id,
                    difficulty,
                    score,
                    total_questions,
                    correct_answers,
                    started_at,
                    submitted_at,
                    duration_seconds
                ) VALUES (
                    :student_id,
                    :course_id,
                    :difficulty,
                    :score,
                    :total_questions,
                    :correct_answers,
                    :started_at,
                    NOW(),
                    :duration_seconds
                )'
            );

            $attemptStatement->execute([
                'student_id' => $studentId,
                'course_id' => $courseId,
                'difficulty' => $activeTest['difficulty'],
                'score' => $score,
                'total_questions' => 20,
                'correct_answers' => $correctAnswers,
                'started_at' => date('Y-m-d H:i:s', (int) $activeTest['started_at']),
                'duration_seconds' => $durationSeconds,
            ]);

            $attemptId = (int) $pdo->lastInsertId();
            $answerStatement = $pdo->prepare(
                'INSERT INTO attempt_answers (
                    attempt_id,
                    question_id,
                    selected_option,
                    is_correct,
                    answered_at
                ) VALUES (
                    :attempt_id,
                    :question_id,
                    :selected_option,
                    :is_correct,
                    NOW()
                )'
            );

            foreach ($activeTest['questions'] as $questionId => $question) {
                $answer = $activeTest['answers'][$questionId];
                $answerStatement->execute([
                    'attempt_id' => $attemptId,
                    'question_id' => $questionId,
                    'selected_option' => $answer['selected_option'],
                    'is_correct' => $answer['is_correct'] ? 1 : 0,
                ]);
            }

            $pdo->commit();
            update_progress_after_attempt($studentId, $courseId, $score);
            log_action('test_submitted', 'Submitted ' . $course['title'] . ' test with score ' . $score . '/20.', $durationSeconds, current_path_with_query());
            unset($_SESSION['active_tests'][$courseId]);
            flash('success', 'Test submitted successfully. Your score is ' . $score . '/20.');
            redirect('test.php?slug=' . urlencode($course['slug']) . '&attempt_id=' . $attemptId);
        } catch (Throwable $throwable) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            flash('error', 'The test could not be submitted right now. Please try again.');
            redirect('test.php?slug=' . urlencode($course['slug']));
        }
    }
}

$activeTest = $_SESSION['active_tests'][$courseId] ?? null;
$latestAttempt = null;
$courseTitle = course_display_title($course);

if (!empty($_GET['attempt_id'])) {
    $attemptStatement = db()->prepare(
        'SELECT ta.*, c.title
         FROM test_attempts ta
         INNER JOIN courses c ON c.id = ta.course_id
         WHERE ta.id = :attempt_id
           AND ta.student_id = :student_id
           AND ta.course_id = :course_id
         LIMIT 1'
    );
    $attemptStatement->execute([
        'attempt_id' => (int) $_GET['attempt_id'],
        'student_id' => $studentId,
        'course_id' => $courseId,
    ]);
    $latestAttempt = $attemptStatement->fetch() ?: null;
}

$pageTitle = $courseTitle . ' Test';
$pageStyles = ['test.css'];
$pageScripts = ['test.js'];

require __DIR__ . '/partials/header.php';
?>
<section class="test-hero">
    <div>
        <span class="eyebrow">Course Assessment</span>
        <h1>
            <span data-course-logo-target data-course-slug="<?= e($course['slug']) ?>"><?= e($courseTitle) ?></span>
            <span class="test-heading-subline">Test</span>
        </h1>
        <p>Each difficulty level always serves 20 questions. Once you start, the chosen difficulty remains locked until submission so the attempt stays consistent.</p>
    </div>
    <div class="test-hero-meta">
        <?php if ($activeTest): ?>
            <span class="status-chip is-locked">Difficulty Locked: <?= e(difficulty_badge($activeTest['difficulty'])) ?></span>
        <?php else: ?>
            <span class="status-chip">Choose difficulty and begin</span>
        <?php endif; ?>
        <a class="text-link" href="<?= e(site_url('course.php?slug=' . urlencode($course['slug']))) ?>">Back to Course</a>
    </div>
</section>

<?php if ($latestAttempt): ?>
    <section class="result-panel">
        <article class="result-card">
            <h2>Latest Result</h2>
            <div class="result-metrics">
                <span><strong><?= e((string) $latestAttempt['score']) ?>/20</strong> Score</span>
                <span><strong><?= e(difficulty_badge($latestAttempt['difficulty'])) ?></strong> Difficulty</span>
                <span><strong><?= e((string) $latestAttempt['duration_seconds']) ?> sec</strong> Duration</span>
            </div>
        </article>
    </section>
<?php endif; ?>

<?php if (!$activeTest): ?>
    <section class="test-start-card">
        <h2>Start Your Test</h2>
        <p>Select a difficulty level. The total question count remains 20 for every difficulty.</p>
        <form method="post" class="test-start-form">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <label>
                <span>Difficulty</span>
                <select name="difficulty" required>
                    <option value="beginner">Beginner</option>
                    <option value="intermediate">Intermediate</option>
                    <option value="advanced">Advanced</option>
                </select>
            </label>
            <button class="button button-primary" type="submit" name="start_test" value="1">Start Test</button>
        </form>
    </section>
<?php else: ?>
    <?php $answers = $activeTest['answers']; ?>
    <section class="test-progress-bar">
        <div>
            <strong data-answer-count><?= e((string) count($answers)) ?></strong>
            <span>of 20 answered</span>
        </div>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <button class="button button-primary" type="submit" name="submit_test" value="1" data-submit-test>Submit Test</button>
        </form>
    </section>

    <section class="question-stack" data-test-root data-course-id="<?= e((string) $courseId) ?>">
        <?php $number = 1; ?>
        <?php foreach ($activeTest['questions'] as $questionId => $question): ?>
            <?php $savedAnswer = $answers[$questionId] ?? null; ?>
            <article class="question-card" data-question-card data-question-id="<?= e((string) $questionId) ?>" data-answered="<?= $savedAnswer ? '1' : '0' ?>">
                <div class="question-head">
                    <span>Question <?= e((string) $number) ?></span>
                    <strong><?= e(difficulty_badge($activeTest['difficulty'])) ?></strong>
                </div>
                <h3><?= e($question['question_text']) ?></h3>
                <div class="option-list">
                    <?php foreach (['A' => $question['option_a'], 'B' => $question['option_b'], 'C' => $question['option_c'], 'D' => $question['option_d']] as $optionKey => $optionText): ?>
                        <?php $isSelected = $savedAnswer && $savedAnswer['selected_option'] === $optionKey; ?>
                        <label class="option-item <?= $isSelected ? 'is-selected' : '' ?> <?= $savedAnswer ? 'is-locked' : '' ?>" data-option-wrapper>
                            <input
                                type="radio"
                                name="question_<?= e((string) $questionId) ?>"
                                value="<?= e($optionKey) ?>"
                                data-answer-input
                                <?= $isSelected ? 'checked' : '' ?>
                                <?= $savedAnswer ? 'disabled' : '' ?>
                            >
                            <span class="option-badge"><?= e($optionKey) ?></span>
                            <span><?= e($optionText) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <div class="feedback-box <?= $savedAnswer ? ($savedAnswer['is_correct'] ? 'is-correct' : 'is-wrong') : 'is-hidden' ?>" data-feedback-box>
                    <?php if ($savedAnswer): ?>
                        <strong><?= $savedAnswer['is_correct'] ? 'Correct answer selected.' : 'Incorrect answer selected.' ?></strong>
                        <p><?= e($savedAnswer['explanation']) ?></p>
                    <?php endif; ?>
                </div>
            </article>
            <?php $number++; ?>
        <?php endforeach; ?>
    </section>
<?php endif; ?>
<?php require __DIR__ . '/partials/footer.php'; ?>


