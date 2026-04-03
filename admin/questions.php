<?php

declare(strict_types=1);

require __DIR__ . '/../includes/bootstrap.php';
require_admin();

$filterCourseId = (int) ($_GET['course_id'] ?? 0);
$filterDifficulty = trim((string) ($_GET['difficulty'] ?? ''));
$filterDifficulty = in_array($filterDifficulty, ['beginner', 'intermediate', 'advanced'], true) ? $filterDifficulty : '';

if (is_post()) {
    verify_csrf();

    if (isset($_POST['save_question'])) {
        $questionId = (int) ($_POST['question_id'] ?? 0);
        $courseId = (int) ($_POST['course_id'] ?? 0);
        $difficulty = trim((string) ($_POST['difficulty'] ?? 'beginner'));
        $questionText = trim((string) ($_POST['question_text'] ?? ''));
        $optionA = trim((string) ($_POST['option_a'] ?? ''));
        $optionB = trim((string) ($_POST['option_b'] ?? ''));
        $optionC = trim((string) ($_POST['option_c'] ?? ''));
        $optionD = trim((string) ($_POST['option_d'] ?? ''));
        $correctOption = strtoupper(trim((string) ($_POST['correct_option'] ?? 'A')));
        $explanation = trim((string) ($_POST['explanation'] ?? ''));

        $errors = [];

        if ($courseId <= 0) {
            $errors[] = 'Select a course.';
        }

        if (!in_array($difficulty, ['beginner', 'intermediate', 'advanced'], true)) {
            $errors[] = 'Select a valid difficulty.';
        }

        if ($questionText === '' || $optionA === '' || $optionB === '' || $optionC === '' || $optionD === '') {
            $errors[] = 'Question and all four options are required.';
        }

        if (!in_array($correctOption, ['A', 'B', 'C', 'D'], true)) {
            $errors[] = 'Select a valid correct option.';
        }

        if ($errors === []) {
            if ($questionId > 0) {
                $statement = db()->prepare(
                    'UPDATE test_questions SET
                        course_id = :course_id,
                        difficulty = :difficulty,
                        question_text = :question_text,
                        option_a = :option_a,
                        option_b = :option_b,
                        option_c = :option_c,
                        option_d = :option_d,
                        correct_option = :correct_option,
                        explanation = :explanation,
                        updated_at = NOW()
                     WHERE id = :id'
                );
                $statement->execute([
                    'course_id' => $courseId,
                    'difficulty' => $difficulty,
                    'question_text' => $questionText,
                    'option_a' => $optionA,
                    'option_b' => $optionB,
                    'option_c' => $optionC,
                    'option_d' => $optionD,
                    'correct_option' => $correctOption,
                    'explanation' => $explanation,
                    'id' => $questionId,
                ]);
                log_action('question_updated', 'Updated question #' . $questionId . '.', 0, current_path_with_query());
                flash('success', 'Question updated successfully.');
            } else {
                $statement = db()->prepare(
                    'INSERT INTO test_questions (
                        course_id,
                        difficulty,
                        question_text,
                        option_a,
                        option_b,
                        option_c,
                        option_d,
                        correct_option,
                        explanation,
                        status,
                        created_at,
                        updated_at
                    ) VALUES (
                        :course_id,
                        :difficulty,
                        :question_text,
                        :option_a,
                        :option_b,
                        :option_c,
                        :option_d,
                        :correct_option,
                        :explanation,
                        1,
                        NOW(),
                        NOW()
                    )'
                );
                $statement->execute([
                    'course_id' => $courseId,
                    'difficulty' => $difficulty,
                    'question_text' => $questionText,
                    'option_a' => $optionA,
                    'option_b' => $optionB,
                    'option_c' => $optionC,
                    'option_d' => $optionD,
                    'correct_option' => $correctOption,
                    'explanation' => $explanation,
                ]);
                log_action('question_created', 'Added a new question for course ID ' . $courseId . '.', 0, current_path_with_query());
                flash('success', 'Question created successfully.');
            }

            redirect('admin/questions.php');
        }

        foreach ($errors as $error) {
            flash('error', $error);
        }
    }

    if (isset($_POST['delete_question'])) {
        $questionId = (int) ($_POST['question_id'] ?? 0);
        $questionStatement = db()->prepare(
            'SELECT tq.question_text, c.title AS course_title
             FROM test_questions tq
             INNER JOIN courses c ON c.id = tq.course_id
             WHERE tq.id = :id
             LIMIT 1'
        );
        $questionStatement->execute(['id' => $questionId]);
        $questionRecord = $questionStatement->fetch();

        if ($questionRecord) {
            db()->prepare('DELETE FROM test_questions WHERE id = :id')->execute(['id' => $questionId]);
            log_action(
                'question_deleted',
                'Deleted question #' . $questionId . ' from ' . $questionRecord['course_title'] . '.',
                0,
                current_path_with_query()
            );
            flash('success', 'Question deleted successfully.');
        } else {
            flash('error', 'Question record not found.');
        }

        redirect('admin/questions.php');
    }
}

$editQuestion = null;

if (!empty($_GET['edit'])) {
    $editStatement = db()->prepare('SELECT * FROM test_questions WHERE id = :id LIMIT 1');
    $editStatement->execute(['id' => (int) $_GET['edit']]);
    $editQuestion = $editStatement->fetch() ?: null;
}

$courses = course_options();
$sql = 'SELECT tq.*, c.title AS course_title FROM test_questions tq INNER JOIN courses c ON c.id = tq.course_id WHERE 1 = 1';
$params = [];

if ($filterCourseId > 0) {
    $sql .= ' AND tq.course_id = :course_id';
    $params['course_id'] = $filterCourseId;
}

if ($filterDifficulty !== '') {
    $sql .= ' AND tq.difficulty = :difficulty';
    $params['difficulty'] = $filterDifficulty;
}

$sql .= ' ORDER BY tq.updated_at DESC LIMIT 80';
$questionStatement = db()->prepare($sql);
$questionStatement->execute($params);
$questions = $questionStatement->fetchAll();

$pageTitle = 'Manage Questions';
$pageStyles = ['assets/css/admin.css', 'admin/questions.css'];
$pageScripts = ['admin/questions.js'];
$currentAdminPage = 'questions';

require __DIR__ . '/../partials/header.php';
require __DIR__ . '/../partials/admin_nav.php';
?>
<section class="admin-page-head">
    <div>
        <span class="eyebrow">Questions</span>
        <h1>Add, edit, and delete quiz questions</h1>
    </div>
</section>

<section class="admin-split-grid">
    <div class="panel-card">
        <div class="panel-heading">
            <h2><?= $editQuestion ? 'Edit Question' : 'Add Question' ?></h2>
            <span>Assign each question to a course and difficulty level</span>
        </div>
        <form method="post" class="admin-form">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="question_id" value="<?= e((string) ($editQuestion['id'] ?? 0)) ?>">

            <label>
                <span>Course</span>
                <select name="course_id" required>
                    <option value="">Select Course</option>
                    <?php foreach ($courses as $course): ?>
                        <option value="<?= e((string) $course['id']) ?>" <?= (int) ($editQuestion['course_id'] ?? 0) === (int) $course['id'] ? 'selected' : '' ?>><?= e($course['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>Difficulty</span>
                <select name="difficulty" required>
                    <?php foreach (['beginner', 'intermediate', 'advanced'] as $difficulty): ?>
                        <option value="<?= e($difficulty) ?>" <?= ($editQuestion['difficulty'] ?? '') === $difficulty ? 'selected' : '' ?>><?= e(difficulty_badge($difficulty)) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>Question Text</span>
                <textarea name="question_text" rows="4" required><?= e((string) ($editQuestion['question_text'] ?? '')) ?></textarea>
            </label>
            <label><span>Option A</span><input type="text" name="option_a" value="<?= e((string) ($editQuestion['option_a'] ?? '')) ?>" required></label>
            <label><span>Option B</span><input type="text" name="option_b" value="<?= e((string) ($editQuestion['option_b'] ?? '')) ?>" required></label>
            <label><span>Option C</span><input type="text" name="option_c" value="<?= e((string) ($editQuestion['option_c'] ?? '')) ?>" required></label>
            <label><span>Option D</span><input type="text" name="option_d" value="<?= e((string) ($editQuestion['option_d'] ?? '')) ?>" required></label>
            <label>
                <span>Correct Option</span>
                <select name="correct_option" required>
                    <?php foreach (['A', 'B', 'C', 'D'] as $option): ?>
                        <option value="<?= e($option) ?>" <?= ($editQuestion['correct_option'] ?? 'A') === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>Explanation</span>
                <textarea name="explanation" rows="3"><?= e((string) ($editQuestion['explanation'] ?? '')) ?></textarea>
            </label>
            <button class="button button-primary" type="submit" name="save_question" value="1"><?= $editQuestion ? 'Update Question' : 'Add Question' ?></button>
        </form>
    </div>

    <div class="panel-card">
        <div class="panel-heading">
            <h2>Question Bank</h2>
            <span>Filter by course or difficulty</span>
        </div>
        <form method="get" class="filter-row">
            <select name="course_id">
                <option value="0">All Courses</option>
                <?php foreach ($courses as $course): ?>
                    <option value="<?= e((string) $course['id']) ?>" <?= $filterCourseId === (int) $course['id'] ? 'selected' : '' ?>><?= e($course['title']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="difficulty">
                <option value="">All Difficulty</option>
                <option value="beginner" <?= $filterDifficulty === 'beginner' ? 'selected' : '' ?>>Beginner</option>
                <option value="intermediate" <?= $filterDifficulty === 'intermediate' ? 'selected' : '' ?>>Intermediate</option>
                <option value="advanced" <?= $filterDifficulty === 'advanced' ? 'selected' : '' ?>>Advanced</option>
            </select>
            <button class="button button-secondary" type="submit">Filter</button>
        </form>
        <div class="question-bank">
            <?php if ($questions === []): ?>
                <p class="empty-state">No questions found for the current filters.</p>
            <?php endif; ?>
            <?php foreach ($questions as $question): ?>
                <article class="question-admin-card">
                    <div>
                        <span class="course-pill"><?= e($question['course_title']) ?> • <?= e(difficulty_badge($question['difficulty'])) ?></span>
                        <h3><?= e($question['question_text']) ?></h3>
                        <p>Correct Option: <?= e($question['correct_option']) ?></p>
                    </div>
                    <div class="question-actions">
                        <a class="button button-secondary" href="<?= e(site_url('admin/questions.php?edit=' . urlencode((string) $question['id']))) ?>">Edit</a>
                        <form
                            method="post"
                            data-admin-confirm-form
                            data-confirm-title="Delete This Question?"
                            data-confirm-message="This question will be permanently removed from the course question bank."
                            data-confirm-button="Delete Question"
                            data-confirm-subject="<?= e($question['course_title'] . ' • ' . $question['question_text']) ?>"
                        >
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="question_id" value="<?= e((string) $question['id']) ?>">
                            <button class="button button-danger" type="submit" name="delete_question" value="1">Delete</button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<div class="confirm-modal is-hidden" data-confirm-modal aria-hidden="true">
    <div class="confirm-modal__backdrop" data-confirm-cancel></div>
    <div class="confirm-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="question-confirm-title">
        <span class="eyebrow">Styled Confirmation</span>
        <h2 id="question-confirm-title" data-confirm-title-target>Confirm Action</h2>
        <p class="confirm-modal__message" data-confirm-message-target></p>
        <p class="confirm-modal__subject is-hidden" data-confirm-subject-target></p>
        <div class="confirm-modal__actions">
            <button class="button button-ghost" type="button" data-confirm-cancel>Cancel</button>
            <button class="button button-danger" type="button" data-confirm-submit>Continue</button>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../partials/footer.php'; ?>

