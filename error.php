<?php

declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$error = get_error_page_payload();
http_response_code((int) ($error['status_code'] ?? 500));

$pageTitle = $error['title'] ?? 'Error';
$backUrl = (string) ($error['back_url'] ?? '/index.php');
$canRetry = $backUrl !== '' && !str_contains($backUrl, 'error.php');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | <?= e(app_config('app_name')) ?></title>
    <link rel="stylesheet" href="<?= e(site_url('assets/css/global.css')) ?>">
    <link rel="stylesheet" href="<?= e(site_url('assets/css/pages/error.css')) ?>">
</head>
<body class="error-body">
    <main class="error-shell">
        <section class="error-card">
            <div class="error-top">
                <span class="eyebrow">Dedicated Error Page</span>
                <span class="error-status">HTTP <?= e((string) ($error['status_code'] ?? 500)) ?></span>
            </div>
            <h1><?= e((string) ($error['title'] ?? 'Unexpected Application Error')) ?></h1>
            <p class="error-message"><?= e((string) ($error['message'] ?? 'An error interrupted the request.')) ?></p>

            <div class="error-meta">
                <article>
                    <strong>Error Type</strong>
                    <span><?= e((string) ($error['type'] ?? 'server_error')) ?></span>
                </article>
                <article>
                    <strong>Reference</strong>
                    <span data-error-reference><?= e((string) ($error['reference'] ?? 'N/A')) ?></span>
                </article>
                <article>
                    <strong>Generated</strong>
                    <span><?= e((string) ($error['created_at'] ?? date('Y-m-d H:i:s'))) ?></span>
                </article>
            </div>

            <section class="solution-panel">
                <h2>Potential Solutions</h2>
                <ol>
                    <?php foreach (($error['solutions'] ?? []) as $solution): ?>
                        <li><?= e((string) $solution) ?></li>
                    <?php endforeach; ?>
                </ol>
            </section>

            <div class="error-actions">
                <a class="button button-primary" href="<?= e(site_url('index.php')) ?>">Back to Home</a>
                <?php if ($canRetry): ?>
                    <a class="button button-secondary" href="<?= e(site_url(ltrim($backUrl, '/'))) ?>">Try Previous Page</a>
                <?php endif; ?>
                <button class="button button-ghost" type="button" data-copy-reference>Copy Reference</button>
            </div>
        </section>
    </main>
    <script src="<?= e(site_url('assets/js/pages/error.js')) ?>"></script>
</body>
</html>
