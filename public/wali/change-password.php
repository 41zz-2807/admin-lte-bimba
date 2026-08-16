<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_wali_login();

$user = current_user();
$app = require __DIR__ . '/../../config/app.php';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = $_POST['old_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    $st = $pdo->prepare('SELECT password FROM users WHERE id=?');
    $st->execute([$user['id']]);
    $row = $st->fetch();

    if (!$row || !password_verify($old, $row['password'])) {
        $error = 'Password lama salah.';
    } elseif (strlen($new) < 8) {
        $error = 'Password baru minimal 8 karakter.';
    } elseif ($new !== $confirm) {
        $error = 'Konfirmasi password tidak cocok.';
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $pdo->prepare('UPDATE users SET password=? WHERE id=?')->execute([$hash, $user['id']]);
        $success = 'Password berhasil diubah.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ganti Password | Portal Wali</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-beta3/dist/css/adminlte.min.css">
</head>
<body class="bg-body-tertiary">
<nav class="navbar navbar-dark bg-success mb-4">
  <div class="container">
    <a class="navbar-brand" href="/wali/">&larr; Kembali</a>
  </div>
</nav>
<main class="container" style="max-width:480px">
  <div class="card shadow-sm">
    <div class="card-body">
      <h5 class="mb-3">Ganti Password</h5>
      <?php if ($error): ?><div class="alert alert-danger py-2"><?= e($error) ?></div><?php endif; ?>
      <?php if ($success): ?><div class="alert alert-success py-2"><?= e($success) ?></div><?php endif; ?>
      <form method="post">
        <div class="mb-3"><label class="form-label">Password lama</label>
          <input type="password" name="old_password" class="form-control" required></div>
        <div class="mb-3"><label class="form-label">Password baru</label>
          <input type="password" name="new_password" class="form-control" required minlength="8"></div>
        <div class="mb-3"><label class="form-label">Ulangi password baru</label>
          <input type="password" name="confirm_password" class="form-control" required></div>
        <button class="btn btn-success">Simpan</button>
      </form>
    </div>
  </div>
</main>
</body>
</html>