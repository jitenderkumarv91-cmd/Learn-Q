<?php

declare(strict_types=1);

require __DIR__ . '/../includes/bootstrap.php';
require_admin();

$studentNameColumn = table_has_column('students', 'name_cipher') ? 'name_cipher' : 'name';
$studentNameEncrypted = $studentNameColumn === 'name_cipher';
$studentEmailColumn = table_has_column('students', 'email_cipher') ? 'email_cipher' : 'email';
$studentEmailEncrypted = $studentEmailColumn === 'email_cipher';
$adminNameColumn = table_has_column('admins', 'name_cipher') ? 'name_cipher' : 'name';
$adminNameEncrypted = $adminNameColumn === 'name_cipher';

$parseDate = static function (string $value): ?DateTimeImmutable {
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

    if (!$date || $date->format('Y-m-d') !== $value) {
        return null;
    }

    return $date;
};

if (is_post()) {
    verify_csrf();

    if (isset($_POST['delete_logs_by_range'])) {
        $startDateValue = trim((string) ($_POST['start_date'] ?? ''));
        $endDateValue = trim((string) ($_POST['end_date'] ?? ''));
        $startDate = $parseDate($startDateValue);
        $endDate = $parseDate($endDateValue);

        if ($startDate === null || $endDate === null) {
            flash('error', 'Select a valid start date and end date.');
            redirect('admin/logs.php');
        }

        if ($startDate > $endDate) {
            flash('error', 'The start date must be before or equal to the end date.');
            redirect('admin/logs.php');
        }

        $deleteStatement = db()->prepare(
            'DELETE FROM logs
             WHERE created_at >= :start_at
             AND created_at < :end_at'
        );
        $deleteStatement->execute([
            'start_at' => $startDate->format('Y-m-d 00:00:00'),
            'end_at' => $endDate->modify('+1 day')->format('Y-m-d 00:00:00'),
        ]);
        $deletedCount = (int) $deleteStatement->rowCount();

        log_action(
            'logs_deleted_by_range',
            'Deleted ' . $deletedCount . ' log entries between ' . $startDateValue . ' and ' . $endDateValue . '.',
            0,
            current_path_with_query()
        );
        flash(
            $deletedCount > 0 ? 'success' : 'error',
            $deletedCount > 0
                ? 'Deleted ' . $deletedCount . ' log(s) for the selected date range.'
                : 'No logs were found in the selected date range.'
        );
        redirect('admin/logs.php');
    }

    if (isset($_POST['delete_logs_by_student'])) {
        $studentId = (int) ($_POST['student_id'] ?? 0);

        if ($studentId <= 0) {
            flash('error', 'Select a student whose logs should be deleted.');
            redirect('admin/logs.php');
        }

        $studentStatement = db()->prepare('SELECT * FROM students WHERE id = :id LIMIT 1');
        $studentStatement->execute(['id' => $studentId]);
        $studentRecord = $studentStatement->fetch();

        if (!$studentRecord) {
            flash('error', 'Student record not found.');
            redirect('admin/logs.php');
        }

        $student = hydrate_student($studentRecord);
        $deleteStatement = db()->prepare('DELETE FROM logs WHERE student_id = :student_id');
        $deleteStatement->execute(['student_id' => $studentId]);
        $deletedCount = (int) $deleteStatement->rowCount();

        log_action(
            'student_logs_deleted',
            'Deleted ' . $deletedCount . ' log entries for student ' . $student['name'] . '.',
            0,
            current_path_with_query()
        );
        flash(
            $deletedCount > 0 ? 'success' : 'error',
            $deletedCount > 0
                ? 'Deleted ' . $deletedCount . ' log(s) for ' . $student['name'] . '.'
                : 'No logs were found for the selected student.'
        );
        redirect('admin/logs.php');
    }
}

$studentOptionsStatement = db()->query(
    "SELECT s.id, s.{$studentNameColumn} AS student_name_value, s.{$studentEmailColumn} AS student_email_value, COUNT(l.id) AS log_count
     FROM students s
     INNER JOIN logs l ON l.student_id = s.id
     GROUP BY s.id
     HAVING COUNT(l.id) > 0
     ORDER BY log_count DESC, s.created_at DESC"
);
$studentsWithLogs = [];

foreach ($studentOptionsStatement->fetchAll() as $studentRow) {
    $studentRow['name'] = selected_value($studentRow, 'student_name_value', $studentNameEncrypted);
    $studentRow['email'] = selected_value($studentRow, 'student_email_value', $studentEmailEncrypted);
    $studentsWithLogs[] = $studentRow;
}

$logStatement = db()->query(
    "SELECT l.*, s.{$studentNameColumn} AS student_name_value, a.{$adminNameColumn} AS admin_name_value
     FROM logs l
     LEFT JOIN students s ON s.id = l.student_id
     LEFT JOIN admins a ON a.id = l.admin_id
     ORDER BY l.created_at DESC
     LIMIT 120"
);
$logs = $logStatement->fetchAll();
$today = date('Y-m-d');
$weekdayLabels = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

$pageTitle = 'Activity Logs';
$pageStyles = ['assets/css/admin.css', 'admin/logs.css'];
$pageScripts = ['admin/logs.js'];
$currentAdminPage = 'logs';

require __DIR__ . '/../partials/header.php';
require __DIR__ . '/../partials/admin_nav.php';
?>
<section class="admin-page-head">
    <div>
        <span class="eyebrow">Logs</span>
        <h1>Tracked platform activity</h1>
    </div>
</section>

<section class="admin-grid logs-tools">
    <article class="panel-card log-tool-card">
        <div class="panel-heading">
            <h2>Delete By Date Range</h2>
            <span>Clear all logs between two dates</span>
        </div>
        <p class="muted">This removes every log entry created from the selected start date through the selected end date.</p>
        <form
            method="post"
            class="admin-form"
            data-admin-confirm-form
            data-log-delete-mode="range"
            data-confirm-title="Delete Logs For This Date Range?"
            data-confirm-message="All log entries created in the selected period will be permanently removed."
            data-confirm-button="Delete Logs"
        >
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <label>
                <span>Start Date</span>
                <div
                    class="date-picker"
                    data-date-picker
                    data-picker-placeholder="Select start date"
                    data-empty-error="Choose the start date before continuing."
                    data-max-date="<?= e($today) ?>"
                >
                    <input type="hidden" name="start_date" data-date-picker-value>
                    <button class="date-picker__trigger" type="button" data-date-picker-trigger aria-haspopup="dialog" aria-expanded="false">
                        <span class="date-picker__trigger-copy">
                            <span class="date-picker__field-label">Styled Calendar</span>
                            <span class="date-picker__display" data-date-picker-display>Select start date</span>
                        </span>
                        <span class="date-picker__icon" aria-hidden="true">CAL</span>
                    </button>
                    <div class="date-picker__panel is-hidden" data-date-picker-panel role="dialog" aria-modal="false">
                        <div class="date-picker__nav">
                            <button type="button" data-date-picker-prev aria-label="Previous month">&lt;</button>
                            <div class="date-picker__month" data-date-picker-month></div>
                            <button type="button" data-date-picker-next aria-label="Next month">&gt;</button>
                        </div>
                        <div class="date-picker__weekdays">
                            <?php foreach ($weekdayLabels as $weekdayLabel): ?>
                                <span class="date-picker__weekday"><?= e($weekdayLabel) ?></span>
                            <?php endforeach; ?>
                        </div>
                        <div class="date-picker__grid" data-date-picker-grid></div>
                        <div class="date-picker__footer">
                            <button class="button button-ghost date-picker__clear" type="button" data-date-picker-clear>Clear</button>
                        </div>
                    </div>
                    <p class="date-picker__error is-hidden" data-date-picker-error>Choose the start date before continuing.</p>
                </div>
            </label>
            <label>
                <span>End Date</span>
                <div
                    class="date-picker"
                    data-date-picker
                    data-picker-placeholder="Select end date"
                    data-empty-error="Choose the end date before continuing."
                    data-max-date="<?= e($today) ?>"
                >
                    <input type="hidden" name="end_date" data-date-picker-value>
                    <button class="date-picker__trigger" type="button" data-date-picker-trigger aria-haspopup="dialog" aria-expanded="false">
                        <span class="date-picker__trigger-copy">
                            <span class="date-picker__field-label">Styled Calendar</span>
                            <span class="date-picker__display" data-date-picker-display>Select end date</span>
                        </span>
                        <span class="date-picker__icon" aria-hidden="true">CAL</span>
                    </button>
                    <div class="date-picker__panel is-hidden" data-date-picker-panel role="dialog" aria-modal="false">
                        <div class="date-picker__nav">
                            <button type="button" data-date-picker-prev aria-label="Previous month">&lt;</button>
                            <div class="date-picker__month" data-date-picker-month></div>
                            <button type="button" data-date-picker-next aria-label="Next month">&gt;</button>
                        </div>
                        <div class="date-picker__weekdays">
                            <?php foreach ($weekdayLabels as $weekdayLabel): ?>
                                <span class="date-picker__weekday"><?= e($weekdayLabel) ?></span>
                            <?php endforeach; ?>
                        </div>
                        <div class="date-picker__grid" data-date-picker-grid></div>
                        <div class="date-picker__footer">
                            <button class="button button-ghost date-picker__clear" type="button" data-date-picker-clear>Clear</button>
                        </div>
                    </div>
                    <p class="date-picker__error is-hidden" data-date-picker-error>Choose the end date before continuing.</p>
                </div>
            </label>
            <button class="button button-danger" type="submit" name="delete_logs_by_range" value="1">Delete Logs By Date</button>
        </form>
    </article>

    <article class="panel-card log-tool-card">
        <div class="panel-heading">
            <h2>Delete Student Logs</h2>
            <span>Clear logs for one student</span>
        </div>
        <p class="muted">Choose a student to remove only that student's activity logs while leaving other platform logs untouched.</p>
        <form
            method="post"
            class="admin-form"
            data-admin-confirm-form
            data-log-delete-mode="student"
            data-confirm-title="Delete This Student's Logs?"
            data-confirm-message="All activity logs linked to the selected student will be permanently removed."
            data-confirm-button="Delete Student Logs"
        >
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <label>
                <span>Student</span>
                <select name="student_id" <?= $studentsWithLogs === [] ? 'disabled' : 'required' ?>>
                    <option value="">Select Student</option>
                    <?php foreach ($studentsWithLogs as $student): ?>
                        <option value="<?= e((string) $student['id']) ?>"><?= e($student['name'] . ' • ' . $student['email'] . ' • ' . $student['log_count'] . ' logs') ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <?php if ($studentsWithLogs === []): ?>
                <p class="muted">There are no student-linked logs available to delete right now.</p>
            <?php endif; ?>
            <button class="button button-danger" type="submit" name="delete_logs_by_student" value="1" <?= $studentsWithLogs === [] ? 'disabled' : '' ?>>Delete Student Logs</button>
        </form>
    </article>
</section>

<section class="panel-card">
    <div class="panel-heading">
        <h2>Recent Activity</h2>
        <span>Latest 120 records</span>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Actor</th>
                    <th>Action</th>
                    <th>Details</th>
                    <th>Page</th>
                    <th>Duration</th>
                    <th>Time</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($logs === []): ?>
                    <tr>
                        <td colspan="6" class="empty-state">No logs are available right now.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($logs as $entry): ?>
                    <?php
                        $actorName = selected_value($entry, 'student_name_value', $studentNameEncrypted);

                        if ($actorName === '') {
                            $actorName = selected_value($entry, 'admin_name_value', $adminNameEncrypted);
                        }

                        if ($actorName === '') {
                            $actorName = 'System';
                        }
                    ?>
                    <tr>
                        <td><?= e($actorName) ?></td>
                        <td><?= e(ucwords(str_replace('_', ' ', $entry['action']))) ?></td>
                        <td><?= e($entry['details']) ?></td>
                        <td><?= e($entry['page_url']) ?></td>
                        <td><?= e((string) $entry['duration_seconds']) ?> sec</td>
                        <td><?= e(date('d M Y, h:i A', strtotime($entry['created_at']))) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<div class="confirm-modal is-hidden" data-confirm-modal aria-hidden="true">
    <div class="confirm-modal__backdrop" data-confirm-cancel></div>
    <div class="confirm-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="logs-confirm-title">
        <span class="eyebrow">Styled Confirmation</span>
        <h2 id="logs-confirm-title" data-confirm-title-target>Confirm Action</h2>
        <p class="confirm-modal__message" data-confirm-message-target></p>
        <p class="confirm-modal__subject is-hidden" data-confirm-subject-target></p>
        <div class="confirm-modal__actions">
            <button class="button button-ghost" type="button" data-confirm-cancel>Cancel</button>
            <button class="button button-danger" type="button" data-confirm-submit>Continue</button>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../partials/footer.php'; ?>

