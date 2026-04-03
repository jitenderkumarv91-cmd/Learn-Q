<?php

declare(strict_types=1);

require __DIR__ . '/../includes/bootstrap.php';
require_admin();

if (is_post()) {
    verify_csrf();

    if (isset($_POST['mark_resolved'])) {
        $messageId = (int) ($_POST['message_id'] ?? 0);
        db()->prepare("UPDATE contact_messages SET status = 'resolved' WHERE id = :id")->execute(['id' => $messageId]);
        log_action('message_resolved', 'Resolved contact message #' . $messageId . '.', 0, current_path_with_query());
        flash('success', 'Message marked as resolved.');
        redirect('admin/messages.php');
    }

    if (isset($_POST['delete_message'])) {
        $messageId = (int) ($_POST['message_id'] ?? 0);
        db()->prepare('DELETE FROM contact_messages WHERE id = :id')->execute(['id' => $messageId]);
        log_action('message_deleted', 'Deleted contact message #' . $messageId . '.', 0, current_path_with_query());
        flash('success', 'Message deleted successfully.');
        redirect('admin/messages.php');
    }
}

$messageStatement = db()->query('SELECT * FROM contact_messages ORDER BY created_at DESC');
$messages = $messageStatement->fetchAll();

$pageTitle = 'Contact Messages';
$pageStyles = ['assets/css/admin.css', 'admin/messages.css'];
$pageScripts = ['admin/messages.js'];
$currentAdminPage = 'messages';

require __DIR__ . '/../partials/header.php';
require __DIR__ . '/../partials/admin_nav.php';
?>
<section class="admin-page-head">
    <div>
        <span class="eyebrow">Messages</span>
        <h1>Review student and visitor inquiries</h1>
    </div>
</section>

<section class="message-grid">
    <?php foreach ($messages as $message): ?>
        <?php
            $name = array_key_exists('name_cipher', $message)
                ? decrypt_value((string) $message['name_cipher'])
                : (string) ($message['name'] ?? '');
            $email = array_key_exists('email_cipher', $message)
                ? decrypt_value((string) $message['email_cipher'])
                : (string) ($message['email'] ?? '');
            $subject = array_key_exists('subject_cipher', $message)
                ? decrypt_value((string) $message['subject_cipher'])
                : (string) ($message['subject'] ?? '');
            $body = array_key_exists('message_cipher', $message)
                ? decrypt_value((string) $message['message_cipher'])
                : (string) ($message['message'] ?? '');
        ?>
        <article class="panel-card message-card">
            <div class="message-top">
                <div>
                    <span class="course-pill <?= $message['status'] === 'resolved' ? 'is-resolved' : '' ?>"><?= e(ucfirst($message['status'])) ?></span>
                    <h2><?= e($subject) ?></h2>
                    <p><?= e($name) ?> • <?= e($email) ?></p>
                </div>
                <span><?= e(date('d M Y, h:i A', strtotime($message['created_at']))) ?></span>
            </div>
            <p><?= nl2br(e($body)) ?></p>
            <div class="message-actions">
                <?php if ($message['status'] !== 'resolved'): ?>
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="message_id" value="<?= e((string) $message['id']) ?>">
                        <button class="button button-secondary" type="submit" name="mark_resolved" value="1">Mark Resolved</button>
                    </form>
                <?php endif; ?>
                <form method="post" data-delete-message-form data-message-subject="<?= e($subject) ?>">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="message_id" value="<?= e((string) $message['id']) ?>">
                    <button class="button button-danger" type="submit" name="delete_message" value="1">Delete</button>
                </form>
            </div>
        </article>
    <?php endforeach; ?>
</section>

<div class="confirm-modal is-hidden" data-confirm-modal aria-hidden="true">
    <div class="confirm-modal__backdrop" data-confirm-cancel></div>
    <div class="confirm-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="delete-message-title">
        <span class="eyebrow">Styled Confirmation</span>
        <h2 id="delete-message-title">Delete This Message?</h2>
        <p class="confirm-modal__message">This action will permanently remove the selected message from the admin inbox.</p>
        <p class="confirm-modal__subject" data-confirm-subject></p>
        <div class="confirm-modal__actions">
            <button class="button button-ghost" type="button" data-confirm-cancel>Cancel</button>
            <button class="button button-danger" type="button" data-confirm-submit>Delete Message</button>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../partials/footer.php'; ?>

