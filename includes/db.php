<?php
/**
 * Koneksi Database PDO
 */

$config = require __DIR__ . '/../config/database.php';

$dsn = sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=%s',
    $config['host'],
    $config['port'],
    $config['dbname'],
    $config['charset']
);

try {
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    // Untuk development: tampilkan error. Production: log saja.
    if ((getenv('APP_ENV') ?: 'local') === 'local') {
        die('Koneksi database gagal: ' . $e->getMessage());
    }
    die('Terjadi kesalahan sistem.');
}
?>