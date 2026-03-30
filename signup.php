<?php

declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

if (is_student_logged_in()) {
    redirect('dashboard.php');
}

if (is_admin_logged_in()) {
    redirect('admin/index.php');
}

if (is_post()) {
    verify_csrf();

    $name = trim((string) ($_POST['name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $contact = trim((string) ($_POST['contact_number'] ?? ''));
    $age = trim((string) ($_POST['age'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    $errors = [];

    if ($name === '') {
        $errors[] = 'Name is required.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    }

    if (!preg_match('/^[0-9]{10,15}$/', $contact)) {
        $errors[] = 'Enter a valid contact number with 10 to 15 digits.';
    }

    if (!ctype_digit($age) || (int) $age < 10 || (int) $age > 100) {
        $errors[] = 'Enter a valid age between 10 and 100.';
    }

    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'Password and confirm password do not match.';
    }

    if (table_has_column('students', 'email_hash')) {
        $existingStudent = db()->prepare('SELECT id FROM students WHERE email_hash = :email_hash LIMIT 1');
        $existingStudent->execute(['email_hash' => lookup_hash($email)]);
    } else {
        $existingStudent = db()->prepare('SELECT id FROM students WHERE email = :email LIMIT 1');
        $existingStudent->execute(['email' => $email]);
    }

    if ($existingStudent->fetch()) {
        $errors[] = 'An account with this email already exists.';
    }

    if ($errors === []) {
        if (student_uses_encrypted_profile()) {
            $statement = db()->prepare(
                'INSERT INTO students (
                    name_cipher,
                    email_cipher,
                    email_hash,
                    contact_cipher,
                    age_cipher,
                    password_hash,
                    status,
                    created_at,
                    updated_at
                ) VALUES (
                    :name_cipher,
                    :email_cipher,
                    :email_hash,
                    :contact_cipher,
                    :age_cipher,
                    :password_hash,
                    :status,
                    NOW(),
                    NOW()
                )'
            );

            $statement->execute([
                'name_cipher' => encrypt_value($name),
                'email_cipher' => encrypt_value($email),
                'email_hash' => lookup_hash($email),
                'contact_cipher' => encrypt_value($contact),
                'age_cipher' => encrypt_value($age),
                'password_hash' => hash_secret($password),
                'status' => 'active',
            ]);
        } else {
            $statement = db()->prepare(
                'INSERT INTO students (
                    name,
                    email,
                    contact_number,
                    age,
                    password_hash,
                    status,
                    created_at,
                    updated_at
                ) VALUES (
                    :name,
                    :email,
                    :contact_number,
                    :age,
                    :password_hash,
                    :status,
                    NOW(),
                    NOW()
                )'
            );

            $statement->execute([
                'name' => $name,
                'email' => $email,
                'contact_number' => $contact,
                'age' => $age,
                'password_hash' => hash_secret($password),
                'status' => 'active',
            ]);
        }

        $studentId = (int) db()->lastInsertId();
        log_action('student_registered', 'Student registration completed.', 0, '/signup.php', $studentId, null);
        flash('success', 'Registration completed. Please log in to continue.');
        redirect('login.php');
    }

    foreach ($errors as $error) {
        flash('error', $error);
    }
}

$pageTitle = 'Signup';
$pageStyles = ['signup.css'];
$pageScripts = ['signup.js'];

require __DIR__ . '/partials/header.php';
?>
<section class="auth-layout signup-layout">
    <div class="auth-copy">
        <span class="eyebrow">Create Your Account</span>
        <h1>Join LearnQ and build your personal learning trail.</h1>
    </div>

    <div class="auth-card wide-card">
        <form method="post" class="auth-form split-form" data-signup-form>
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

            <label>
                <span>Name</span>
                <input type="text" name="name" value="<?= e((string) ($_POST['name'] ?? '')) ?>" required>
            </label>
            <label>
                <span>Email Address</span>
                <input type="email" name="email" value="<?= e((string) ($_POST['email'] ?? '')) ?>" required>
            </label>
            <label>
                <span>Contact Number</span>
                <input type="text" name="contact_number" value="<?= e((string) ($_POST['contact_number'] ?? '')) ?>" required>
            </label>
            <label>
                <span>Age</span>
                <input type="number" name="age" min="10" max="100" value="<?= e((string) ($_POST['age'] ?? '')) ?>" required>
            </label>
            <label>
                <span>Password</span>
                <input type="password" name="password" required data-password>
                <small data-password-strength>Password strength: waiting for input</small>
            </label>
            <label>
                <span>Confirm Password</span>
                <input type="password" name="confirm_password" required data-confirm-password>
                <small data-password-match>Passwords must match.</small>
            </label>

            <button class="button button-primary button-block" type="submit">Create Account</button>
        </form>
    </div>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
