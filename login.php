<?php

declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

if (is_student_logged_in()) {
    redirect('dashboard.php');
}

if (is_admin_logged_in()) {
    redirect('admin/index.php');
}

$selectedRole = trim((string) ($_GET['role'] ?? $_POST['role'] ?? 'student'));
$selectedRole = in_array($selectedRole, ['student', 'admin'], true) ? $selectedRole : 'student';
$nextPath = sanitize_next_path($_GET['next'] ?? $_POST['next'] ?? '/dashboard.php');

if ($selectedRole === 'student' && str_starts_with($nextPath, '/admin/')) {
    $nextPath = '/dashboard.php';
}

if (is_post()) {
    verify_csrf();

    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        flash('error', 'Email and password are required.');
    } else {
        if ($selectedRole === 'student') {
            if (table_has_column('students', 'email_hash')) {
                $statement = db()->prepare('SELECT * FROM students WHERE email_hash = :email_hash LIMIT 1');
                $statement->execute(['email_hash' => lookup_hash($email)]);
            } else {
                $statement = db()->prepare('SELECT * FROM students WHERE email = :email LIMIT 1');
                $statement->execute(['email' => $email]);
            }

            $record = $statement->fetch();

            if ($record && record_password_matches($record, $password) && record_is_active($record)) {
                session_regenerate_id(true);
                $_SESSION['role'] = 'student';
                $_SESSION['student_id'] = (int) $record['id'];
                unset($_SESSION['admin_id']);

                if (table_has_column('students', 'last_login_at')) {
                    db()->prepare('UPDATE students SET last_login_at = NOW() WHERE id = :id')->execute([
                        'id' => (int) $record['id'],
                    ]);
                }

                log_action('student_login', 'Student logged in successfully.', 0, current_path_with_query());
                redirect(ltrim($nextPath, '/'));
            }
        } else {
            if (table_has_column('admins', 'email_hash')) {
                $statement = db()->prepare('SELECT * FROM admins WHERE email_hash = :email_hash LIMIT 1');
                $statement->execute(['email_hash' => lookup_hash($email)]);
            } else {
                $statement = db()->prepare('SELECT * FROM admins WHERE email = :email LIMIT 1');
                $statement->execute(['email' => $email]);
            }

            $record = $statement->fetch();

            if ($record && record_password_matches($record, $password) && record_is_active($record)) {
                session_regenerate_id(true);
                $_SESSION['role'] = 'admin';
                $_SESSION['admin_id'] = (int) $record['id'];
                unset($_SESSION['student_id']);

                if (table_has_column('admins', 'last_login_at')) {
                    db()->prepare('UPDATE admins SET last_login_at = NOW() WHERE id = :id')->execute([
                        'id' => (int) $record['id'],
                    ]);
                }

                log_action('admin_login', 'Admin logged in successfully.', 0, current_path_with_query());
                redirect('admin/index.php');
            }
        }

        flash('error', 'Invalid credentials or inactive account.');
    }
}

$pageTitle = 'Login';
$pageStyles = ['assets/css/pages/auth-network.css', 'login.css'];
$pageScripts = ['assets/js/pages/auth-network.js', 'login.js'];
$bodyClass = 'auth-screen-page';

require __DIR__ . '/partials/header.php';
?>
<section class="auth-layout auth-single auth-network-panel" data-auth-network>
    <div class="auth-network-canvas-shell" aria-hidden="true">
        <canvas class="auth-network-canvas" data-network-canvas></canvas>
    </div>
    <div class="auth-card auth-network-card">
        <div class="auth-card-head">
            <span class="eyebrow">Secure Sign In</span>
            <h1>Login</h1>
        </div>
        <form method="post" class="auth-form">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="next" value="<?= e($nextPath) ?>">

            <div class="role-switch" data-role-switch>
                <button type="button" class="role-chip <?= $selectedRole === 'student' ? 'is-active' : '' ?>" data-role="student">Student</button>
                <button type="button" class="role-chip <?= $selectedRole === 'admin' ? 'is-active' : '' ?>" data-role="admin">Admin</button>
            </div>

            <input type="hidden" name="role" value="<?= e($selectedRole) ?>" data-role-input>

            <label>
                <span>Email Address</span>
                <input type="email" name="email" value="<?= e((string) ($_POST['email'] ?? '')) ?>" required>
            </label>
            <label>
                <span>Password</span>
                <input type="password" name="password" required>
            </label>
            <button class="button button-primary button-block" type="submit">Login</button>
        </form>
    </div>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>


