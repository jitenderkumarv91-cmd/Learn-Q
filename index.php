<?php

declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$search = trim((string) ($_GET['search'] ?? ''));
$courses = all_courses($search);
$totalCourses = (int) db()->query('SELECT COUNT(*) FROM courses')->fetchColumn();
$totalQuestions = (int) db()->query('SELECT COUNT(*) FROM test_questions')->fetchColumn();
$totalStudents = (int) db()->query('SELECT COUNT(*) FROM students')->fetchColumn();

$pageTitle = 'Home';
$pageStyles = ['index.css'];
$pageScripts = ['index.js'];

require __DIR__ . '/partials/header.php';
?>
<section class="search-panel">
    <form method="get" class="search-form">
        <div class="section-heading">
            <label for="course-search" class="search-label">Find the right course faster</label>
            <p>Search by topic and jump straight into the written lesson or assessment.</p>
        </div>
        <div class="search-row">
            <input
                id="course-search"
                name="search"
                type="search"
                value="<?= e($search) ?>"
                placeholder="Search HTML, Python, Linux, Ethical Hacking..."
                data-course-search
            >
            <button class="button button-primary" type="submit">Search</button>
        </div>
    </form>
</section>

<section class="hero-panel">
    <div class="hero-copy">
        <span class="eyebrow">LearnQ Experience</span>
        <h1>Learn technical skills through a calmer, sharper, more focused interface.</h1>
        <p>LearnQ brings together clean reading layouts, responsive assessments, and progress tracking so students can move through technical subjects without distraction.</p>
        <div class="hero-stats">
            <article>
                <strong><?= e((string) $totalCourses) ?></strong>
                <span>Guided tracks</span>
            </article>
            <article>
                <strong><?= e((string) $totalQuestions) ?></strong>
                <span>Quiz prompts</span>
            </article>
            <article>
                <strong><?= e((string) $totalStudents) ?></strong>
                <span>Active learners</span>
            </article>
        </div>
    </div>
    <aside class="hero-side">
        <div class="info-card">
            <h2>Why learners can use LearnQ more easily</h2>
            <ul>
                <li>Clear written lessons help learners focus on concepts without unnecessary distraction.</li>
                <li>Each course includes guided reading and a direct path to a structured test.</li>
                <li>Instant answer feedback helps students understand mistakes while the topic is still fresh.</li>
                <li>The dashboard keeps scores, recent activity, and course progress visible in one place.</li>
            </ul>
        </div>
    </aside>
</section>

<section class="course-section">
    <div class="section-heading">
        <div>
            <span class="eyebrow">Course Catalog</span>
            <h2>Browse the full LearnQ library</h2>
        </div>
        <p><?= $search !== '' ? e('Showing results for "' . $search . '"') : 'Every card gives learners a fast read on level, reading time, and next action.' ?></p>
    </div>

    <div class="course-grid" data-course-grid>
        <?php foreach ($courses as $course): ?>
            <?php $courseTitle = course_display_title($course); ?>
            <article class="course-card" data-course-card data-title="<?= e(strtolower($courseTitle)) ?>">
                <span class="course-pill"><?= e($course['level']) ?></span>
                <h3 data-course-logo-target data-course-slug="<?= e($course['slug']) ?>"><?= e($courseTitle) ?></h3>
                <p><?= e($course['short_description']) ?></p>
                <div class="course-meta">
                    <span><?= e((string) $course['estimated_minutes']) ?> mins reading</span>
                    <span>20-question assessment</span>
                </div>
                <div class="course-actions">
                    <a class="button button-primary" href="<?= e(site_url('course.php?slug=' . urlencode($course['slug']))) ?>">Read Course</a>
                    <a class="text-link" href="<?= e(site_url('test.php?slug=' . urlencode($course['slug']))) ?>">Take Test</a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <p class="empty-state is-hidden" data-empty-state>No courses match your search.</p>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
