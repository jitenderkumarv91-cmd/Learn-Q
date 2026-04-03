<?php

declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
require_student();

$student = current_student();
$studentId = student_id();

$statsStatement = db()->prepare(
    'SELECT
        COUNT(*) AS total_tests,
        COALESCE(MAX(score), 0) AS best_score,
        COALESCE(ROUND(AVG(score), 1), 0) AS average_score
     FROM test_attempts
     WHERE student_id = :student_id'
);
$statsStatement->execute(['student_id' => $studentId]);
$stats = $statsStatement->fetch() ?: ['total_tests' => 0, 'best_score' => 0, 'average_score' => 0];

$courseCountStatement = db()->prepare('SELECT COUNT(*) FROM student_course_progress WHERE student_id = :student_id');
$courseCountStatement->execute(['student_id' => $studentId]);
$coursesStarted = (int) $courseCountStatement->fetchColumn();

$attemptStatement = db()->prepare(
    'SELECT ta.*, c.title, c.slug
     FROM test_attempts ta
     INNER JOIN courses c ON c.id = ta.course_id
     WHERE ta.student_id = :student_id
     ORDER BY ta.submitted_at DESC
     LIMIT 6'
);
$attemptStatement->execute(['student_id' => $studentId]);
$recentAttempts = $attemptStatement->fetchAll();

$progressStatement = db()->prepare(
    'SELECT scp.*, c.title, c.slug
     FROM student_course_progress scp
     INNER JOIN courses c ON c.id = scp.course_id
     WHERE scp.student_id = :student_id
     ORDER BY scp.last_viewed_at DESC
     LIMIT 6'
);
$progressStatement->execute(['student_id' => $studentId]);
$recentCourses = $progressStatement->fetchAll();

$logStatement = db()->prepare(
    'SELECT action, details, duration_seconds, created_at, page_url
     FROM logs
     WHERE student_id = :student_id
     ORDER BY created_at DESC
     LIMIT 8'
);
$logStatement->execute(['student_id' => $studentId]);
$recentLogs = $logStatement->fetchAll();

$pageTitle = 'Dashboard';
$pageStyles = ['assets/css/pages/dashboard.css'];
$pageScripts = ['assets/js/pages/dashboard.js'];

require __DIR__ . '/partials/header.php';
?>
<section class="dashboard-hero">
    <div>
        <span class="eyebrow">Student Dashboard</span>
        <h1>Welcome back, <?= e($student['name']) ?>.</h1>
        <p>Track course momentum, monitor quiz performance, and review recent learning activity from a single control center.</p>
    </div>
    <a class="button button-primary" href="<?= e(site_url('index.php')) ?>">Browse More Courses</a>
</section>

<section class="metric-grid">
    <article class="metric-card">
        <strong><?= e((string) $coursesStarted) ?></strong>
        <span>Courses in motion</span>
    </article>
    <article class="metric-card">
        <strong><?= e((string) $stats['total_tests']) ?></strong>
        <span>Tests completed</span>
    </article>
    <article class="metric-card">
        <strong><?= e((string) $stats['best_score']) ?>/20</strong>
        <span>Best score</span>
    </article>
    <article class="metric-card">
        <strong><?= e((string) $stats['average_score']) ?>/20</strong>
        <span>Average score</span>
    </article>
</section>

<section class="dashboard-grid">
    <div class="panel-card">
        <div class="panel-heading">
            <h2>Recent Test Attempts</h2>
            <span>Latest scores across all difficulty levels</span>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Course</th>
                        <th>Difficulty</th>
                        <th>Score</th>
                        <th>Submitted</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recentAttempts === []): ?>
                        <tr><td colspan="4">No tests attempted yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($recentAttempts as $attempt): ?>
                            <?php $attemptTitle = course_display_title($attempt); ?>
                            <tr>
                                <td><a data-course-logo-target data-course-slug="<?= e($attempt['slug']) ?>" href="<?= e(site_url('course.php?slug=' . urlencode($attempt['slug']))) ?>"><?= e($attemptTitle) ?></a></td>
                                <td><?= e(difficulty_badge($attempt['difficulty'])) ?></td>
                                <td><?= e((string) $attempt['score']) ?>/20</td>
                                <td><?= e(date('d M Y, h:i A', strtotime($attempt['submitted_at']))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="panel-card">
        <div class="panel-heading">
            <h2>Course Activity</h2>
            <span>Recently opened courses and saved progress</span>
        </div>
        <div class="stack-list">
            <?php if ($recentCourses === []): ?>
                <p class="muted">Start a course to see your activity here.</p>
            <?php else: ?>
                <?php foreach ($recentCourses as $courseProgress): ?>
                    <?php $courseProgressTitle = course_display_title($courseProgress); ?>
                    <article class="stack-item">
                        <div>
                            <h3 data-course-logo-target data-course-slug="<?= e($courseProgress['slug']) ?>"><?= e($courseProgressTitle) ?></h3>
                            <p>Best score <?= e((string) $courseProgress['best_score']) ?>/20 | Tests <?= e((string) $courseProgress['total_tests']) ?></p>
                        </div>
                        <a class="text-link" href="<?= e(site_url('course.php?slug=' . urlencode($courseProgress['slug']))) ?>">Open</a>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="panel-card span-two">
        <div class="panel-heading">
            <h2>Recent Activity Log</h2>
            <span>Tracked actions, timestamps, and durations</span>
        </div>
        <div class="timeline-list">
            <?php if ($recentLogs === []): ?>
                <p class="muted">Your activity log will populate after you start exploring courses and tests.</p>
            <?php else: ?>
                <?php foreach ($recentLogs as $entry): ?>
                    <article class="timeline-item">
                        <div>
                            <strong><?= e(ucwords(str_replace('_', ' ', $entry['action']))) ?></strong>
                            <p><?= e($entry['details'] ?: 'No additional details recorded.') ?></p>
                        </div>
                        <div class="timeline-meta">
                            <span><?= e(date('d M Y, h:i A', strtotime($entry['created_at']))) ?></span>
                            <span><?= e((string) $entry['duration_seconds']) ?> sec</span>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
