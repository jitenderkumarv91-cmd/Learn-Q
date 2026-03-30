<?php

declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

if (is_student_logged_in()) {
    log_action('student_logout', 'Student logged out.', 0, current_path_with_query());
}

if (is_admin_logged_in()) {
    log_action('admin_logout', 'Admin logged out.', 0, current_path_with_query());
}

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}

session_destroy();
redirect('login.php');
