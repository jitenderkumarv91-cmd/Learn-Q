<?php

declare(strict_types=1);

return [
    'host' => getenv('DB_HOST') ?: 'mysql-cb50e23-anonymous10210-1947.j.aivencloud.com',
    'port' => getenv('DB_PORT') ?: '15741',
    'database' => getenv('DB_NAME') ?: 'scholargrid',
    'username' => getenv('DB_USER') ?: 'avnadmin',
    'password' => getenv('DB_PASS') ?: 'AVNS_vvudM5ictmai_v_4jA8',
    'charset' => 'utf8mb4',
    'ssl_mode' => getenv('DB_SSL_MODE') ?: 'required',
    'ssl_ca' => getenv('DB_SSL_CA') ?: (__DIR__ . '/../certs/aiven-ca.pem'),
    'ssl_verify_server_cert' => filter_var(getenv('DB_SSL_VERIFY_SERVER_CERT') ?: 'true', FILTER_VALIDATE_BOOL),
];
