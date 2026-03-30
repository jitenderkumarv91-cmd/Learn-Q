<?php

declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'About Us';
$pageStyles = ['about.css'];
$pageScripts = ['about.js'];

require __DIR__ . '/partials/header.php';
?>
<section class="content-hero">
    <span class="eyebrow">About LearnQ</span>
    <h1>Built for learners who prefer clarity, structure, and less interface noise.</h1>
    <p>LearnQ combines tutorial-style course reading, secure student accounts, live quiz feedback, and admin-grade management tools in one responsive learning portal.</p>
</section>

<section class="info-grid">
    <article class="info-panel">
        <h2>Learning Philosophy</h2>
        <p>Each course is delivered in a written format with sections, examples, and key takeaways so learners can move at their own pace and revisit material when needed.</p>
    </article>
    <article class="info-panel">
        <h2>Assessment Approach</h2>
        <p>Tests are always 20 questions long. Difficulty controls question selection, while the live feedback flow immediately tells students whether they chose the correct option.</p>
    </article>
    <article class="info-panel">
        <h2>Platform Controls</h2>
        <p>Students get dashboards, score history, and profile tools. Admins manage students, questions, logs, and incoming messages through a dedicated panel.</p>
    </article>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
