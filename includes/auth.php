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
    // Blokir wali masuk panel admin
    $role = current_user()['role'] ?? '';
    if ($role === 'wali_murid') {
        redirect('wali/');
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
function is_wali(): bool
{
    return has_role('wali_murid');
}

function is_admin_area(): bool
{
    return has_role(['superadmin', 'admin']);
}

function require_wali_login(): void
{
    if (!is_logged_in()) {
        redirect('wali/login.php');
    }
    if (!is_wali()) {
        // admin yang nyasar ke portal wali
        redirect('admin/');
    }
}

function require_admin_login(): void
{
    if (!is_logged_in()) {
        redirect('admin/login.php');
    }
    if (!is_admin_area()) {
        // wali yang nyasar ke admin
        redirect('wali/');
    }
}

/**
 * Ambil daftar anak milik wali yang sedang login
 */
function wali_get_anak(): array
{
    global $pdo;
    $user = current_user();
    if (!$user || !is_wali()) {
        return [];
    }
    $st = $pdo->prepare(
        'SELECT s.*, sw.hubungan
         FROM siswa_wali sw
         JOIN siswa s ON s.id = sw.siswa_id
         WHERE sw.user_id = ?
         ORDER BY s.status ASC, s.nama ASC'
    );
    $st->execute([$user['id']]);
    return $st->fetchAll();
}
?>