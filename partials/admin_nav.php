<?php

declare(strict_types=1);

$currentAdminPage = $currentAdminPage ?? 'overview';
?>
<nav class="admin-subnav">
    <a class="<?= $currentAdminPage === 'overview' ? 'is-active' : '' ?>" href="<?= e(site_url('admin/index.php')) ?>">Overview</a>
    <a class="<?= $currentAdminPage === 'students' ? 'is-active' : '' ?>" href="<?= e(site_url('admin/students.php')) ?>">Students</a>
    <a class="<?= $currentAdminPage === 'questions' ? 'is-active' : '' ?>" href="<?= e(site_url('admin/questions.php')) ?>">Questions</a>
    <a class="<?= $currentAdminPage === 'logs' ? 'is-active' : '' ?>" href="<?= e(site_url('admin/logs.php')) ?>">Logs</a>
    <a class="<?= $currentAdminPage === 'messages' ? 'is-active' : '' ?>" href="<?= e(site_url('admin/messages.php')) ?>">Messages</a>
</nav>
