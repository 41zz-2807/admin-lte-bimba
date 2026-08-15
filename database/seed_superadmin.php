<?php
/**
 * Seed Superadmin
 * Jalankan sekali: docker compose exec app php database/seed_superadmin.php
 */

require_once __DIR__ . '/../includes/db.php';

$email    = 'superadmin@bimba-ksr.local';
$password = 'Superadmin123!';   // GANTI setelah login pertama
$name     = 'Super Admin';
$role     = 'superadmin';

$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
$existing = $stmt->fetch();

if ($existing) {
    $upd = $pdo->prepare('UPDATE users SET name = ?, password = ?, role = ?, is_active = 1 WHERE id = ?');
    $upd->execute([$name, $hash, $role, $existing['id']]);
    echo "Superadmin sudah ada → password & role di-update.\n";
} else {
    $ins = $pdo->prepare('INSERT INTO users (name, email, password, role, is_active) VALUES (?, ?, ?, ?, 1)');
    $ins->execute([$name, $email, $hash, $role]);
    echo "Superadmin berhasil dibuat.\n";
}

echo "----------------------------------------\n";
echo "Email    : {$email}\n";
echo "Password : {$password}\n";
echo "Role     : {$role}\n";
echo "----------------------------------------\n";
echo "PENTING: Ganti password setelah login pertama!\n";