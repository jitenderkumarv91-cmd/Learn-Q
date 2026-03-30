<?php

declare(strict_types=1);

$appConfig = require __DIR__ . '/../config/app.php';
$databaseConfig = require __DIR__ . '/../config/database.php';

$GLOBALS['app_config'] = $appConfig;
$GLOBALS['database_config'] = $databaseConfig;

date_default_timezone_set($appConfig['timezone']);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name($appConfig['session_name']);
    session_start();
}

require_once __DIR__ . '/functions.php';

initialize_error_handling();
