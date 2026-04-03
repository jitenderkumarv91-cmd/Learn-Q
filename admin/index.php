<?php

declare(strict_types=1);

require __DIR__ . '/../includes/bootstrap.php';
require_admin();

$studentCount = (int) db()->query('SELECT COUNT(*) FROM students')->fetchColumn();
$courseCount = (int) db()->query('SELECT COUNT(*) FROM courses')->fetchColumn();
$questionCount = (int) db()->query('SELECT COUNT(*) FROM test_questions')->fetchColumn();
$attemptCount = (int) db()->query('SELECT COUNT(*) FROM test_attempts')->fetchColumn();
$messageCount = (int) db()->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'new'")->fetchColumn();
$studentNameColumn = table_has_column('students', 'name_cipher') ? 'name_cipher' : 'name';
$studentNameEncrypted = $studentNameColumn === 'name_cipher';
$adminNameColumn = table_has_column('admins', 'name_cipher') ? 'name_cipher' : 'name';
$adminNameEncrypted = $adminNameColumn === 'name_cipher';

$logStatement = db()->query(
    "SELECT l.*, s.{$studentNameColumn} AS student_name_value, a.{$adminNameColumn} AS admin_name_value
     FROM logs l
     LEFT JOIN students s ON s.id = l.student_id
     LEFT JOIN admins a ON a.id = l.admin_id
     ORDER BY l.created_at DESC
     LIMIT 8"
);
$latestLogs = $logStatement->fetchAll();

$pageTitle = 'Admin Overview';
$pageStyles = ['assets/css/admin.css', 'admin/index.css'];
$pageScripts = ['admin/index.js'];
$currentAdminPage = 'overview';

require __DIR__ . '/../partials/header.php';
require __DIR__ . '/../partials/admin_nav.php';
?>
<section class="admin-hero">
    <div>
        <span class="eyebrow">Admin Panel</span>
        <h1>Manage learners, questions, logs, and support requests.</h1>
        <p>This panel centralizes the operational side of the learning platform.</p>
    </div>
</section>

<section class="metric-grid admin-metrics">
    <article class="metric-card"><strong><?= e((string) $studentCount) ?></strong><span>Students</span></article>
    <article class="metric-card"><strong><?= e((string) $courseCount) ?></strong><span>Courses</span></article>
    <article class="metric-card"><strong><?= e((string) $questionCount) ?></strong><span>Questions</span></article>
    <article class="metric-card"><strong><?= e((string) $attemptCount) ?></strong><span>Attempts</span></article>
    <article class="metric-card"><strong><?= e((string) $messageCount) ?></strong><span>New Messages</span></article>
</section>

<section class="dashboard-grid admin-grid">
    <div class="panel-card">
        <div class="panel-heading">
            <h2>Quick Actions</h2>
            <span>Common administrative tasks</span>
        </div>
        <div class="stack-list action-links">
            <a class="stack-item" href="<?= e(site_url('admin/students.php')) ?>"><strong>Manage Students</strong><span>Review student records and delete accounts when required.</span></a>
            <a class="stack-item" href="<?= e(site_url('admin/questions.php')) ?>"><strong>Manage Questions</strong><span>Add, edit, remove, and assign questions to difficulty levels.</span></a>
            <a class="stack-item" href="<?= e(site_url('admin/logs.php')) ?>"><strong>View Logs</strong><span>Inspect tracked actions, timestamps, and time spent.</span></a>
            <a class="stack-item" href="<?= e(site_url('admin/messages.php')) ?>"><strong>Review Messages</strong><span>Respond to new contact form submissions.</span></a>
        </div>
    </div>

    <div class="panel-card span-two">
        <div class="panel-heading">
            <h2>Recent Activity</h2>
            <span>Latest logs from students and admins</span>
        </div>
        <div class="timeline-list">
            <?php foreach ($latestLogs as $entry): ?>
                <?php
                    $actorName = selected_value($entry, 'student_name_value', $studentNameEncrypted);

                    if ($actorName === '') {
                        $actorName = selected_value($entry, 'admin_name_value', $adminNameEncrypted);
                    }

                    if ($actorName === '') {
                        $actorName = 'System';
                    }
                ?>
                <article class="timeline-item">
                    <div>
                        <strong><?= e($actorName) ?></strong>
                        <p><?= e(ucwords(str_replace('_', ' ', $entry['action']))) ?> • <?= e($entry['details'] ?: 'No details recorded.') ?></p>
                    </div>
                    <div class="timeline-meta">
                        <span><?= e(date('d M Y, h:i A', strtotime($entry['created_at']))) ?></span>
                        <span><?= e((string) $entry['duration_seconds']) ?> sec</span>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php require __DIR__ . '/../partials/footer.php'; ?>

