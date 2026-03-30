<?php

declare(strict_types=1);

require __DIR__ . '/../includes/bootstrap.php';
require_student();

if (!is_post()) {
    fail_with_error('request_not_allowed', 'This answer-check endpoint only accepts POST requests.', 405);
}

verify_csrf();

$courseId = (int) ($_POST['course_id'] ?? 0);
$questionId = (int) ($_POST['question_id'] ?? 0);
$selectedOption = strtoupper(trim((string) ($_POST['selected_option'] ?? '')));

if (!in_array($selectedOption, ['A', 'B', 'C', 'D'], true)) {
    fail_with_error('validation_error', 'The selected answer option is invalid.', 422);
}

$activeTest = $_SESSION['active_tests'][$courseId] ?? null;

if ($activeTest === null || !isset($activeTest['questions'][$questionId])) {
    fail_with_error('test_session', 'The active test session for this question could not be found.', 404);
}

$question = $activeTest['questions'][$questionId];

header('Content-Type: application/json');

if (isset($activeTest['answers'][$questionId])) {
    $existing = $activeTest['answers'][$questionId];
    echo json_encode([
        'success' => true,
        'locked' => true,
        'selected_option' => $existing['selected_option'],
        'is_correct' => $existing['is_correct'],
        'feedback' => $existing['is_correct'] ? 'Correct answer selected.' : 'Incorrect answer selected.',
        'explanation' => $existing['explanation'],
    ]);
    exit;
}

$isCorrect = $question['correct_option'] === $selectedOption;
$explanation = $question['explanation'] ?: ($isCorrect ? 'Correct answer selected.' : 'Review the course content and try the next question carefully.');

$_SESSION['active_tests'][$courseId]['answers'][$questionId] = [
    'selected_option' => $selectedOption,
    'is_correct' => $isCorrect,
    'explanation' => $explanation,
];

echo json_encode([
    'success' => true,
    'locked' => true,
    'selected_option' => $selectedOption,
    'is_correct' => $isCorrect,
    'feedback' => $isCorrect ? 'Correct answer selected.' : 'Incorrect answer selected.',
    'explanation' => $explanation,
]);
