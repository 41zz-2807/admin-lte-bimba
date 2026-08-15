<?php
/**
 * Autentikasi & Role Helper
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

function attempt_login(string $email, string $password): bool
{
    global $pdo;

    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? AND is_active = 1 LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        return false;
    }

    $upd = $pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?');
    $upd->execute([$user['id']]);

    $_SESSION['user'] = [
        'id'    => (int) $user['id'],
        'name'  => $user['name'],
        'email' => $user['email'],
        'role'  => $user['role'],
    ];

    return true;
}

function logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function is_logged_in(): bool
{
    return isset($_SESSION['user']['id']);
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function has_role(string|array $roles): bool
{
    $user = current_user();
    if (!$user) {
        return false;
    }
    $roles = (array) $roles;
    return in_array($user['role'], $roles, true);
}

function is_superadmin(): bool
{
    return has_role('superadmin');
}

function require_login(): void
{
    if (!is_logged_in()) {
        redirect('admin/login.php');
    }
}

function require_role(string|array $roles): void
{
    require_login();
    if (!has_role($roles)) {
        http_response_code(403);
        die('Akses ditolak. Anda tidak memiliki izin untuk halaman ini.');
    }
}

function require_superadmin(): void
{
    require_role('superadmin');
}