<?php

declare(strict_types=1);

$pageTitle = $pageTitle ?? app_config('app_name');
$pageStyles = $pageStyles ?? [];
$bodyClass = $bodyClass ?? '';
$flashes = get_flashes();
$student = current_student();
$admin = current_admin();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <meta name="app-url" content="<?= e(site_url('')) ?>">
    <title><?= e($pageTitle) ?> | <?= e(app_config('app_name')) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(site_url('assets/css/global.css')) ?>">
    <?php foreach ($pageStyles as $style): ?>
        <link rel="stylesheet" href="<?= e(site_url($style)) ?>">
    <?php endforeach; ?>
</head>
<body class="<?= e($bodyClass) ?>">
    <div class="site-shell">
        <header class="site-header">
            <nav class="navbar">
                <div class="brand">
                <img class="brand-mark" src="<?= e(site_url('Image/logo.png')) ?>" alt="">
                    <span class="brand-copy">
                        <strong><?= e(app_config('app_name')) ?></strong>
                        <small>Modern learning, clearly delivered</small>
                    </span>
                </div>  
                <button class="nav-toggle" type="button" aria-label="Toggle menu" data-nav-toggle>
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
                <div class="nav-links" data-nav-menu>
                    <a href="<?= e(site_url('index.php')) ?>" class="<?= page_is_active('index.php') ? 'is-active' : '' ?>">Home</a>
                    <a href="<?= e(site_url('about.php')) ?>" class="<?= page_is_active('about.php') ? 'is-active' : '' ?>">About Us</a>
                    <a href="<?= e(site_url('contact.php')) ?>" class="<?= page_is_active('contact.php') ? 'is-active' : '' ?>">Contact Us</a>
                    <?php if ($student): ?>
                        <a href="<?= e(site_url('dashboard.php')) ?>" class="<?= page_is_active('dashboard.php') ? 'is-active' : '' ?>">Dashboard</a>
                        <a href="<?= e(site_url('profile.php')) ?>" class="<?= page_is_active('profile.php') ? 'is-active' : '' ?>">Profile</a>
                        <a class="button button-secondary" href="<?= e(site_url('logout.php')) ?>">Logout</a>
                    <?php elseif ($admin): ?>
                        <a href="<?= e(site_url('admin/index.php')) ?>" class="<?= page_is_active('admin/') ? 'is-active' : '' ?>">Admin Panel</a>
                        <a class="button button-secondary" href="<?= e(site_url('logout.php')) ?>">Logout</a>
                    <?php else: ?>
                        <a class="button button-ghost" href="<?= e(site_url('login.php')) ?>">Login</a>
                        <a class="button button-primary" href="<?= e(site_url('signup.php')) ?>">Signup</a>
                    <?php endif; ?>
                </div>
            </nav>
            <?php if ($flashes !== []): ?>
                <div class="flash-stack">
                    <?php foreach ($flashes as $flash): ?>
                        <div class="flash flash-<?= e($flash['type']) ?>">
                            <?= e($flash['message']) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </header>
        <main class="page-shell">

