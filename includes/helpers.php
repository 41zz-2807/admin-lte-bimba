<?php
/**
 * Helper functions
 */

function base_url(string $path = ''): string
{
    $app = require __DIR__ . '/../config/app.php';
    $url = rtrim($app['url'], '/');
    return $path ? $url . '/' . ltrim($path, '/') : $url;
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . base_url($path));
    exit;
}
function upload_bukti(array $file, string $subdir = 'bukti'): ?string
{
    if (empty($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed, true)) {
        return null;
    }

    // max 5 MB
    if ($file['size'] > 5 * 1024 * 1024) {
        return null;
    }

    $dir = __DIR__ . '/../public/uploads/' . $subdir;
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $name = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $path = $dir . '/' . $name;

    if (!move_uploaded_file($file['tmp_name'], $path)) {
        return null;
    }

    return $subdir . '/' . $name; // relatif dari /uploads
}