<?php

declare(strict_types=1);

require __DIR__ . '/../includes/bootstrap.php';
require_admin();

if (is_post()) {
    verify_csrf();

    if (isset($_POST['delete_student'])) {
        $studentId = (int) ($_POST['student_id'] ?? 0);
        $studentStatement = db()->prepare('SELECT * FROM students WHERE id = :id LIMIT 1');
        $studentStatement->execute(['id' => $studentId]);
        $studentRecord = $studentStatement->fetch();

        if ($studentRecord) {
            $student = hydrate_student($studentRecord);
            db()->prepare('DELETE FROM students WHERE id = :id')->execute(['id' => $studentId]);
            log_action('student_deleted', 'Deleted student account for ' . $student['name'] . '.', 0, current_path_with_query());
            flash('success', 'Student deleted successfully.');
        } else {
            flash('error', 'Student record not found.');
        }

        redirect('admin/students.php');
    }
}

$studentStatement = db()->query(
    'SELECT s.*, COUNT(ta.id) AS tests_taken, COALESCE(MAX(ta.score), 0) AS best_score
     FROM students s
     LEFT JOIN test_attempts ta ON ta.student_id = s.id
     GROUP BY s.id
     ORDER BY s.created_at DESC'
);
$students = $studentStatement->fetchAll();

$pageTitle = 'Manage Students';
$pageStyles = ['assets/css/admin.css', 'assets/css/admin/students.css'];
$pageScripts = ['assets/js/admin/students.js'];
$currentAdminPage = 'students';

require __DIR__ . '/../partials/header.php';
require __DIR__ . '/../partials/admin_nav.php';
?>
<section class="admin-page-head">
    <div>
        <span class="eyebrow">Students</span>
        <h1>View and manage student records</h1>
    </div>
</section>

<section class="panel-card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Contact</th>
                    <th>Age</th>
                    <th>Tests</th>
                    <th>Best Score</th>
                    <th>Joined</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($students === []): ?>
                    <tr>
                        <td colspan="8" class="empty-state">No students are registered yet.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($students as $student): ?>
                    <?php $student = hydrate_student($student); ?>
                    <tr>
                        <td><?= e($student['name']) ?></td>
                        <td><?= e($student['email']) ?></td>
                        <td><?= e($student['contact_number']) ?></td>
                        <td><?= e($student['age']) ?></td>
                        <td><?= e((string) $student['tests_taken']) ?></td>
                        <td><?= e((string) $student['best_score']) ?>/20</td>
                        <td><?= e(date('d M Y', strtotime($student['created_at']))) ?></td>
                        <td>
                            <form
                                method="post"
                                data-admin-confirm-form
                                data-confirm-title="Delete This Student?"
                                data-confirm-message="The student account and related learning records will be permanently removed."
                                data-confirm-button="Delete Student"
                                data-confirm-subject="<?= e($student['name'] . ' • ' . $student['email']) ?>"
                            >
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="student_id" value="<?= e((string) $student['id']) ?>">
                                <button class="button button-danger" type="submit" name="delete_student" value="1">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<div class="confirm-modal is-hidden" data-confirm-modal aria-hidden="true">
    <div class="confirm-modal__backdrop" data-confirm-cancel></div>
    <div class="confirm-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="student-confirm-title">
        <span class="eyebrow">Styled Confirmation</span>
        <h2 id="student-confirm-title" data-confirm-title-target>Confirm Action</h2>
        <p class="confirm-modal__message" data-confirm-message-target></p>
        <p class="confirm-modal__subject is-hidden" data-confirm-subject-target></p>
        <div class="confirm-modal__actions">
            <button class="button button-ghost" type="button" data-confirm-cancel>Cancel</button>
            <button class="button button-danger" type="button" data-confirm-submit>Continue</button>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../partials/footer.php'; ?>
