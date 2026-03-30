<?php

declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
require_student();

$student = current_student();
$studentId = student_id();

if (is_post()) {
    verify_csrf();

    $name = trim((string) ($_POST['name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $contact = trim((string) ($_POST['contact_number'] ?? ''));
    $age = trim((string) ($_POST['age'] ?? ''));
    $newPassword = (string) ($_POST['new_password'] ?? '');
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

    if ($newPassword !== '' && strlen($newPassword) < 8) {
        $errors[] = 'New password must be at least 8 characters long.';
    }

    if ($newPassword !== $confirmPassword) {
        $errors[] = 'New password and confirm password do not match.';
    }

    if (table_has_column('students', 'email_hash')) {
        $duplicateStatement = db()->prepare('SELECT id FROM students WHERE email_hash = :email_hash AND id != :id LIMIT 1');
        $duplicateStatement->execute([
            'email_hash' => lookup_hash($email),
            'id' => $studentId,
        ]);
    } else {
        $duplicateStatement = db()->prepare('SELECT id FROM students WHERE email = :email AND id != :id LIMIT 1');
        $duplicateStatement->execute([
            'email' => $email,
            'id' => $studentId,
        ]);
    }

    if ($duplicateStatement->fetch()) {
        $errors[] = 'Another student already uses this email address.';
    }

    if ($errors === []) {
        if (student_uses_encrypted_profile()) {
            $sql = 'UPDATE students SET
                        name_cipher = :name_cipher,
                        email_cipher = :email_cipher,
                        email_hash = :email_hash,
                        contact_cipher = :contact_cipher,
                        age_cipher = :age_cipher,
                        updated_at = NOW()';
            $params = [
                'name_cipher' => encrypt_value($name),
                'email_cipher' => encrypt_value($email),
                'email_hash' => lookup_hash($email),
                'contact_cipher' => encrypt_value($contact),
                'age_cipher' => encrypt_value($age),
                'id' => $studentId,
            ];
        } else {
            $sql = 'UPDATE students SET
                        name = :name,
                        email = :email,
                        contact_number = :contact_number,
                        age = :age,
                        updated_at = NOW()';
            $params = [
                'name' => $name,
                'email' => $email,
                'contact_number' => $contact,
                'age' => $age,
                'id' => $studentId,
            ];
        }

        if ($newPassword !== '') {
            $sql .= ', password_hash = :password_hash';
            $params['password_hash'] = hash_secret($newPassword);
        }

        $sql .= ' WHERE id = :id';

        $statement = db()->prepare($sql);
        $statement->execute($params);

        log_action('profile_updated', 'Student updated profile details.', 0, current_path_with_query());
        flash('success', 'Profile updated successfully.');
        redirect('profile.php');
    }

    foreach ($errors as $error) {
        flash('error', $error);
    }
}

$student = current_student();

$pageTitle = 'Profile';
$pageStyles = ['profile.css'];
$pageScripts = ['profile.js'];

require __DIR__ . '/partials/header.php';
?>
<section class="profile-layout">
    <div class="profile-summary">
        <span class="eyebrow">Profile</span>
        <h1><?= e($student['name']) ?></h1>
        <p><?= e($student['email']) ?></p>
        <div class="profile-chips">
            <span>Contact: <?= e($student['contact_number']) ?></span>
            <span>Age: <?= e($student['age']) ?></span>
            <span>Status: <?= e(ucfirst($student['status'])) ?></span>
        </div>
    </div>

    <div class="profile-card">
        <form method="post" class="profile-form">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

            <label>
                <span>Name</span>
                <input type="text" name="name" value="<?= e($student['name']) ?>" required>
            </label>
            <label>
                <span>Email Address</span>
                <input type="email" name="email" value="<?= e($student['email']) ?>" required>
            </label>
            <label>
                <span>Contact Number</span>
                <input type="text" name="contact_number" value="<?= e($student['contact_number']) ?>" required>
            </label>
            <label>
                <span>Age</span>
                <input type="number" name="age" min="10" max="100" value="<?= e($student['age']) ?>" required>
            </label>
            <label>
                <span>New Password</span>
                <input type="password" name="new_password" data-password>
            </label>
            <label>
                <span>Confirm New Password</span>
                <input type="password" name="confirm_password" data-confirm-password>
                <small data-password-match>Leave password fields blank if you do not want to change the password.</small>
            </label>
            <button class="button button-primary" type="submit">Save Changes</button>
        </form>
    </div>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
