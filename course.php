<?php

declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
require_student();

$slug = trim((string) ($_GET['slug'] ?? ''));
$course = find_course_by_slug($slug);

if (!$course) {
    fail_with_error('course_not_found', '', 404);
}

$studentId = student_id();
record_course_visit($studentId, (int) $course['id']);
log_action('course_opened', 'Opened course: ' . $course['title'], 0, current_path_with_query());

$relatedCourses = personalized_related_courses($studentId, $course, 3);
$courseTitle = course_display_title($course);

$pageTitle = $courseTitle;
$pageStyles = ['assets/css/pages/course.css'];
$pageScripts = ['assets/js/pages/course.js'];

require __DIR__ . '/partials/header.php';
?>
<section class="course-hero">
    <div class="course-hero-main">
        <span class="eyebrow">Written Course</span>
        <h1 data-course-logo-target data-course-slug="<?= e($course['slug']) ?>"><?= e($courseTitle) ?></h1>
        <p>Learn procedural programming, memory basics, pointers, functions, arrays, and low-level control.</p>
        <div class="course-inline-meta">
            <span>Intermediate</span>
            <span aria-hidden="true">|</span>
            <span>36 min reading time</span>
            <span aria-hidden="true">|</span>
            <a class="course-inline-test-link" href="<?= e(site_url('test.php?slug=' . urlencode($course['slug']))) ?>">Take Course Test</a>
        </div>
    </div>
</section>

<section class="course-layout">
    <article class="course-content prose-block<?= $course['slug'] === 'c' ? ' c-course-highlight' : '' ?>">
        <?= $course['content_html'] ?>
        <div class="course-bottom-cta">
            <a class="text-link strong-link" href="<?= e(site_url('test.php?slug=' . urlencode($course['slug']))) ?>">Take the 20-question <?= e($courseTitle) ?> test</a>
        </div>
    </article>

    <aside class="course-sidebar">
        <div class="side-card">
            <h2>Reading Goals</h2>
            <ul>
                <li>Understand the core ideas behind <?= e($courseTitle) ?>.</li>
                <li>Review the examples before starting the assessment.</li>
                <li>Use the quiz to measure concept retention at your preferred difficulty.</li>
            </ul>
        </div>
        <div class="side-card">
            <h2>Related Courses</h2>
            <?php if ($relatedCourses === []): ?>
                <p class="muted">No personalized recommendations are available yet. Explore more courses to help LearnQ shape your next suggestions.</p>
            <?php else: ?>
                <?php foreach ($relatedCourses as $relatedCourse): ?>
                    <?php $relatedCourseTitle = course_display_title($relatedCourse); ?>
                    <div class="related-course-item">
                        <a class="side-link" data-course-logo-target data-course-slug="<?= e($relatedCourse['slug']) ?>" href="<?= e(site_url('course.php?slug=' . urlencode($relatedCourse['slug']))) ?>"><?= e($relatedCourseTitle) ?></a>
                        <p class="side-link-note"><?= e($relatedCourse['recommendation_reason']) ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </aside>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
