<?php
require_once __DIR__ . '/../includes/auth.php';

if (is_logged_in()) {
    $role = current_user()['role'] ?? '';
    if ($role === 'wali_murid') {
        redirect('wali/');
    }
    redirect('admin/');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Email dan password wajib diisi.';
    } elseif (attempt_login($email, $password)) {
        $role = current_user()['role'] ?? '';
        if ($role === 'wali_murid') {
            redirect('wali/');
        }
        if ($role === 'admin' || $role === 'superadmin') {
            redirect('admin/');
        }
        logout();
        $error = 'Role akun tidak dikenali.';
    } else {
        $error = 'Email atau password salah, atau akun tidak aktif.';
    }
}

$app = require __DIR__ . '/../config/app.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login | <?= e($app['name']) ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-beta3/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    body {
      background: linear-gradient(135deg, #0d6efd 0%, #6610f2 100%);
      min-height: 100vh;
      display: flex; align-items: center; justify-content: center;
    }
    .login-card { max-width: 400px; width: 100%; }
  </style>
</head>
<body>
<div class="login-card">
  <div class="card shadow-lg border-0">
    <div class="card-body p-4 p-md-5">
      <div class="text-center mb-4">
        <i class="bi bi-box-arrow-in-right display-4 text-primary"></i>
        <h3 class="mt-2 mb-0"><?= e($app['name']) ?></h3>
        <p class="text-muted">Login</p>
      </div>
      <?php if ($error): ?>
        <div class="alert alert-danger py-2"><?= e($error) ?></div>
      <?php endif; ?>
      <form method="post">
        <div class="mb-3">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control" required autofocus
                 value="<?= e($_POST['email'] ?? '') ?>">
        </div>
        <div class="mb-3">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-control" required>
        </div>
        <button class="btn btn-primary w-100">Masuk</button>
      </form>
      <div class="text-center mt-3">
        <a href="/" class="text-muted small">← Kembali ke website</a>
      </div>
      <div class="text-center mt-2">
            <a href="/wali/lupa-password.php" class="small">Lupa password? (Portal Wali)</a>
        </div>
    </div>
  </div>
</div>
</body>
</html>