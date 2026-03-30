<?php

declare(strict_types=1);

require __DIR__ . '/../includes/bootstrap.php';

if (!is_post()) {
    fail_with_error('request_not_allowed', 'This activity logging endpoint only accepts POST requests.', 405);
}

verify_csrf();

if (!is_student_logged_in() && !is_admin_logged_in()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'No authenticated actor.']);
    exit;
}

$page = substr(trim((string) ($_POST['page'] ?? '')), 0, 255);
$seconds = (int) ($_POST['seconds'] ?? 0);
$details = trim((string) ($_POST['details'] ?? ''));

if ($seconds >= 5 && $seconds <= 14400 && $page !== '') {
    log_action('time_spent', $details !== '' ? $details : 'Tracked page duration.', $seconds, $page);
}

header('Content-Type: application/json');
echo json_encode(['success' => true]);
