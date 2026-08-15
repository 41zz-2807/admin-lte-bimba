<?php
/**
 * Konfigurasi Database
 * Sesuaikan dengan MySQL yang sudah ada
 */

return [
    'host'     => getenv('DB_HOST') ?: 'host.docker.internal',
    'port'     => getenv('DB_PORT') ?: '3306',
    'dbname'   => getenv('DB_NAME') ?: 'bimba_ksr',
    'username' => getenv('DB_USER') ?: 'root',
    'password' => getenv('DB_PASS') ?: '',
    'charset'  => 'utf8mb4',
];