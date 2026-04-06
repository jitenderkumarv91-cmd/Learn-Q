<?php

declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$prefillName = '';
$prefillEmail = '';

if (is_student_logged_in()) {
    $student = current_student();
    $prefillName = $student['name'];
    $prefillEmail = $student['email'];
}

if (is_post()) {
    verify_csrf();

    $name = trim((string) ($_POST['name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $subject = trim((string) ($_POST['subject'] ?? ''));
    $message = trim((string) ($_POST['message'] ?? ''));

    $errors = [];

    if ($name === '') {
        $errors[] = 'Name is required.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    }

    if ($subject === '') {
        $errors[] = 'Subject is required.';
    }

    if ($message === '') {
        $errors[] = 'Message is required.';
    }

    if ($errors === []) {
        $statement = db()->prepare(
            'INSERT INTO contact_messages (
                student_id,
                name_cipher,
                email_cipher,
                subject_cipher,
                message_cipher,
                status,
                created_at
            ) VALUES (
                :student_id,
                :name_cipher,
                :email_cipher,
                :subject_cipher,
                :message_cipher,
                :status,
                NOW()
            )'
        );

        $statement->execute([
            'student_id' => student_id(),
            'name_cipher' => encrypt_value($name),
            'email_cipher' => encrypt_value($email),
            'subject_cipher' => encrypt_value($subject),
            'message_cipher' => encrypt_value($message),
            'status' => 'new',
        ]);

        flash('success', 'Your message has been sent successfully.');
        redirect('contact.php');
    }

    foreach ($errors as $error) {
        flash('error', $error);
    }
}

$pageTitle = 'Contact Us';
$pageStyles = ['contact.css'];
$pageScripts = ['contact.js'];

require __DIR__ . '/partials/header.php';
?>
<section class="contact-layout">
    <div class="contact-copy">
        <span class="eyebrow">Contact Us</span>
        <h1>Need help, feedback, or a course update?</h1>
        <p>Use the form and the message will be stored in the database for admin review. Student messages can also be tied back to their account for faster support.</p>
        <div class="contact-points">
            <span>Email: learnqadmin@local.com</span>
            <span>Phone: +91 6387877013</span>
            <span>Hours: Mon-Sat, 9 AM to 6 PM</span>
        </div>
    </div>
    <div class="contact-card">
        <form method="post" class="contact-form">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <label>
                <span>Name</span>
                <input type="text" name="name" value="<?= e((string) ($_POST['name'] ?? $prefillName)) ?>" required>
            </label>
            <label>
                <span>Email Address</span>
                <input type="email" name="email" value="<?= e((string) ($_POST['email'] ?? $prefillEmail)) ?>" required>
            </label>
            <label>
                <span>Subject</span>
                <input type="text" name="subject" value="<?= e((string) ($_POST['subject'] ?? '')) ?>" required>
            </label>
            <label>
                <span>Message</span>
                <textarea name="message" rows="5" required><?= e((string) ($_POST['message'] ?? '')) ?></textarea>
            </label>
            <button class="button button-primary" type="submit">Send Message</button>
        </form>
    </div>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>

