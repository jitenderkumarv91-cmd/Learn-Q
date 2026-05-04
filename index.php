<?php

declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$search = trim((string) ($_GET['search'] ?? ''));
$catalogCourses = all_courses();
$courses = $catalogCourses;
$totalCourses = (int) db()->query('SELECT COUNT(*) FROM courses')->fetchColumn();
$totalQuestions = (int) db()->query('SELECT COUNT(*) FROM test_questions')->fetchColumn();
$totalStudents = (int) db()->query('SELECT COUNT(*) FROM students')->fetchColumn();
$defaultShowcaseCourses = [
    ['title' => 'HTML', 'slug' => 'html'],
    ['title' => 'Python', 'slug' => 'python'],
    ['title' => 'Linux', 'slug' => 'linux'],
    ['title' => 'Machine Learning', 'slug' => 'machine-learning'],
];
$showcasePool = [];
$showcaseCourses = [];
$showcaseSlots = [1, 2, 3, 4];
$showNavbarCourseSearch = true;
$navbarCourseSearchValue = $search;
$navbarCourseSuggestions = [];
$bodyClass = 'home-search-page';

foreach ($catalogCourses as $course) {
    $slug = (string) ($course['slug'] ?? '');

    if ($slug === '') {
        continue;
    }

    $courseTitle = course_display_title($course);

    $showcasePool[$slug] = [
        'title' => $courseTitle,
        'url' => site_url('course.php?slug=' . urlencode($slug)),
    ];

    $navbarCourseSuggestions[] = [
        'title' => $courseTitle,
        'url' => site_url('course.php?slug=' . urlencode($slug)),
        'meta' => trim((string) ($course['level'] ?? 'Course')),
    ];
}

foreach ($defaultShowcaseCourses as $defaultShowcaseCourse) {
    $slug = $defaultShowcaseCourse['slug'];

    if (!isset($showcasePool[$slug])) {
        $showcasePool[$slug] = [
            'title' => $defaultShowcaseCourse['title'],
            'url' => site_url('course.php?slug=' . urlencode($slug)),
        ];
    }
}

if ($showcasePool !== []) {
    $showcaseCourses = array_values($showcasePool);
    shuffle($showcaseCourses);
    $showcaseCourses = array_slice($showcaseCourses, 0, min(4, count($showcaseCourses)));
    shuffle($showcaseSlots);
}

$pageTitle = 'Home';
$pageStyles = ['index.css'];
$pageScripts = ['index.js'];

require __DIR__ . '/partials/header.php';
?>
<section class="hero-panel">
    <div class="hero-copy">
        <span class="eyebrow">LearnQ Experience</span>
        <h1>Learn technical skills through a calmer, sharper, more focused interface.</h1>
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
        <div class="info-card info-card-showcase">
            <div class="showcase-stage">
                <div class="showcase-ripple showcase-ripple-one"></div>
                <div class="showcase-ripple showcase-ripple-two"></div>
                <div class="showcase-beam"></div>
                <?php foreach ($showcaseCourses as $index => $showcaseCourse): ?>
                    <a
                        class="showcase-chip showcase-chip-<?= e((string) ($showcaseSlots[$index] ?? ($index + 1))) ?>"
                        href="<?= e((string) $showcaseCourse['url']) ?>"
                        data-full-title="<?= e((string) $showcaseCourse['title']) ?>"
                    ><span class="showcase-chip-label"><?= e((string) $showcaseCourse['title']) ?></span></a>
                <?php endforeach; ?>
                <div class="showcase-core">
                    <span class="showcase-kicker">Enrollment<br>Flow</span>
                    <strong>Read. Test. Grow.</strong>
                    <p>Pick a topic, build confidence quickly, and keep the momentum going.</p>
                    <div class="showcase-meter">
                        <span></span>
                    </div>
                </div>
            </div>
            <div class="showcase-track" aria-label="Learning journey preview">
                <span>Choose a topic</span>
                <span>Learn faster</span>
                <span>Check yourself</span>
                <span>Track progress</span>
            </div>
        </div>
    </aside>
</section>

<section class="course-section">
    <div class="section-heading">
        <div>
            <span class="eyebrow">Course Catalog</span>
            <h2>Browse the full LearnQ library</h2>
        </div>
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
