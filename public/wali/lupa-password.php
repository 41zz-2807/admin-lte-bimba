<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/flash.php';

// Jika sudah login sebagai wali, tidak perlu di sini
if (is_logged_in() && is_wali()) {
    redirect('wali/');
}

$app   = require __DIR__ . '/../../config/app.php';
$error = '';
$info  = '';
$step  = 'request'; // request | reset
global $pdo;

$token = trim($_GET['token'] ?? '');

// ---- Proses set password baru (dari link email) ----
if ($token !== '') {
    $step = 'reset';
    $st = $pdo->prepare(
        'SELECT pr.*, u.email, u.name, u.role, u.is_active
         FROM password_resets pr
         JOIN users u ON u.id = pr.user_id
         WHERE pr.token = ? AND pr.used_at IS NULL
         LIMIT 1'
    );
    $st->execute([$token]);
    $row = $st->fetch();

    if (!$row || strtotime($row['expires_at']) < time()) {
        $error = 'Link reset tidak valid atau sudah kedaluwarsa. Silakan minta link baru.';
        $step  = 'request';
        $token = '';
    } elseif ($row['role'] !== 'wali_murid' || !(int)$row['is_active']) {
        $error = 'Akun tidak valid untuk reset password.';
        $step  = 'request';
        $token = '';
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_reset'])) {
        $new     = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (strlen($new) < 8) {
            $error = 'Password baru minimal 8 karakter.';
        } elseif ($new !== $confirm) {
            $error = 'Konfirmasi password tidak cocok.';
        } else {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')
                ->execute([$hash, (int)$row['user_id']]);
            $pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE id = ?')
                ->execute([(int)$row['id']]);

            set_flash('success', 'Password berhasil diubah. Silakan login.');
            redirect('login.php');
        }
    }
}

// ---- Proses minta link reset ----
if ($step === 'request' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_request'])) {
    $email = trim($_POST['email'] ?? '');

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Masukkan email yang valid.';
    } else {
        $st = $pdo->prepare(
            'SELECT id, name, email, role, is_active FROM users WHERE email = ? LIMIT 1'
        );
        $st->execute([$email]);
        $user = $st->fetch();

        // Selalu tampilkan pesan generik (hindari enumerasi email)
        $info = 'Jika email terdaftar sebagai wali murid, link reset password telah dikirim. Cek inbox/spam.';

        if ($user && $user['role'] === 'wali_murid' && (int)$user['is_active'] === 1) {
            $tokenNew = bin2hex(random_bytes(32));
            $expires  = date('Y-m-d H:i:s', time() + 3600); // 1 jam

            $pdo->prepare(
                'INSERT INTO password_resets (user_id, token, expires_at) VALUES (?,?,?)'
            )->execute([(int)$user['id'], $tokenNew, $expires]);

            $resetUrl = rtrim($app['url'] ?? '', '/') . '/wali/lupa-password.php?token=' . $tokenNew;
            $body = "Halo {$user['name']},\n\n"
                . "Anda meminta reset password Portal Wali {$app['name']}.\n\n"
                . "Klik link berikut (berlaku 1 jam):\n{$resetUrl}\n\n"
                . "Jika Anda tidak meminta ini, abaikan email ini.\n\n"
                . "Terima kasih.\n{$app['name']}";

            send_smtp_mail($email, 'Reset Password — ' . $app['name'], $body, $user['name']);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Lupa Password | <?= e($app['name']) ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-beta3/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    body {
      background: linear-gradient(135deg, #198754 0%, #0d6efd 100%);
      min-height: 100vh;
      display: flex; align-items: center; justify-content: center;
    }
    .card-box { max-width: 420px; width: 100%; }
  </style>
</head>
<body>
<div class="card-box px-3">
  <div class="card shadow-lg border-0">
    <div class="card-body p-4">
      <div class="text-center mb-3">
        <i class="bi bi-key display-5 text-success"></i>
        <h4 class="mt-2 mb-0">Lupa Password</h4>
        <p class="text-muted small mb-0">Portal Wali Murid</p>
      </div>

      <?php if ($error): ?>
        <div class="alert alert-danger py-2"><?= e($error) ?></div>
      <?php endif; ?>
      <?php if ($info): ?>
        <div class="alert alert-success py-2"><?= e($info) ?></div>
      <?php endif; ?>

      <?php if ($step === 'reset' && $token !== ''): ?>
        <p class="small text-muted">Buat password baru untuk <strong><?= e($row['email'] ?? '') ?></strong></p>
        <form method="post">
          <input type="hidden" name="do_reset" value="1">
          <div class="mb-3">
            <label class="form-label">Password baru</label>
            <input type="password" name="new_password" class="form-control" required minlength="8">
          </div>
          <div class="mb-3">
            <label class="form-label">Ulangi password</label>
            <input type="password" name="confirm_password" class="form-control" required minlength="8">
          </div>
          <button class="btn btn-success w-100">Simpan Password Baru</button>
        </form>
      <?php else: ?>
        <form method="post">
          <input type="hidden" name="do_request" value="1">
          <div class="mb-3">
            <label class="form-label">Email login wali</label>
            <input type="email" name="email" class="form-control" required
                   value="<?= e($_POST['email'] ?? '') ?>" autofocus>
          </div>
          <button class="btn btn-success w-100">Kirim Link Reset</button>
        </form>
      <?php endif; ?>

      <div class="text-center mt-3">
        <a href="/login.php" class="small text-muted">← Kembali ke login</a>
      </div>
    </div>
  </div>
</div>
</body>
</html>