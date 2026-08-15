<?php
/**
 * Konfigurasi Aplikasi Bimba KSR
 */

return [
    'name'    => getenv('APP_NAME') ?: 'Bimba KSR',
    'url'     => getenv('APP_URL') ?: 'http://localhost:8181',
    'env'     => getenv('APP_ENV') ?: 'local',
    'version' => '0.1.0',
];