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
    :root {
      --pastel-pink: #ff9ec6;
      --pastel-blue: #7ec8f8;
      --pastel-yellow: #ffe66d;
      --pastel-green: #95e1a3;
      --pastel-purple: #c9a0ff;
      --pastel-orange: #ffb347;
    }

    * {
      box-sizing: border-box;
    }

    body {
      min-height: 100vh;
      margin: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(160deg, #a8e6ff 0%, #d4f0ff 25%, #ffe6f0 50%, #fff5d6 75%, #e0ffe8 100%);
      background-attachment: fixed;
      overflow-x: hidden;
      position: relative;
      font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    }

    /* Soft floating shapes (dunia anak) */
    .bg-decor {
      position: fixed;
      inset: 0;
      pointer-events: none;
      overflow: hidden;
      z-index: 0;
    }

    .bg-decor .shape {
      position: absolute;
      border-radius: 50%;
      opacity: 0.35;
      filter: blur(1px);
    }

    .bg-decor .s1 { width: 180px; height: 180px; background: var(--pastel-pink); top: 8%; left: 5%; animation: float 9s ease-in-out infinite; }
    .bg-decor .s2 { width: 120px; height: 120px; background: var(--pastel-yellow); top: 15%; right: 10%; animation: float 7s ease-in-out infinite 1s; }
    .bg-decor .s3 { width: 90px; height: 90px; background: var(--pastel-green); bottom: 20%; left: 12%; animation: float 8s ease-in-out infinite 0.5s; }
    .bg-decor .s4 { width: 150px; height: 150px; background: var(--pastel-purple); bottom: 10%; right: 8%; animation: float 10s ease-in-out infinite 1.5s; }
    .bg-decor .s5 { width: 70px; height: 70px; background: var(--pastel-orange); top: 45%; left: 3%; animation: float 6s ease-in-out infinite 2s; }
    .bg-decor .s6 { width: 100px; height: 100px; background: var(--pastel-blue); top: 60%; right: 4%; animation: float 8.5s ease-in-out infinite 0.8s; }

    @keyframes float {
      0%, 100% { transform: translateY(0) rotate(0deg); }
      50% { transform: translateY(-18px) rotate(6deg); }
    }

    /* Floating icons (buku, alat tulis, mainan, taman bermain) */
    .float-icon {
      position: absolute;
      font-size: 2rem;
      opacity: 0.55;
      animation: float 6s ease-in-out infinite;
      color: #5a4a7a;
      z-index: 1;
      pointer-events: none;
    }

    .float-icon.i1 { top: 12%; left: 8%; color: #e85d8e; animation-delay: 0s; font-size: 2.4rem; }
    .float-icon.i2 { top: 22%; right: 12%; color: #3a9bd9; animation-delay: 1.2s; font-size: 2.1rem; }
    .float-icon.i3 { bottom: 18%; left: 10%; color: #e6a800; animation-delay: 0.6s; font-size: 2.3rem; }
    .float-icon.i4 { bottom: 12%; right: 14%; color: #5cb85c; animation-delay: 1.8s; font-size: 2.2rem; }
    .float-icon.i5 { top: 48%; left: 4%; color: #9b59b6; animation-delay: 0.3s; font-size: 1.9rem; }
    .float-icon.i6 { top: 55%; right: 6%; color: #e67e22; animation-delay: 2.1s; font-size: 2rem; }
    .float-icon.i7 { top: 8%; left: 45%; color: #3498db; animation-delay: 1.5s; font-size: 1.8rem; }
    .float-icon.i8 { bottom: 8%; left: 40%; color: #e74c3c; animation-delay: 0.9s; font-size: 2.1rem; }

    /* Login card – bubble transparan */
    .login-wrapper {
      position: relative;
      z-index: 10;
      width: 100%;
      max-width: 420px;
      padding: 1rem;
    }

    .login-bubble {
      background: rgba(255, 255, 255, 0.28);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      border-radius: 48px;
      border: 2px solid rgba(255, 255, 255, 0.55);
      box-shadow:
        0 20px 50px rgba(100, 80, 150, 0.18),
        0 8px 20px rgba(255, 150, 180, 0.12),
        inset 0 1px 0 rgba(255, 255, 255, 0.7);
      overflow: hidden;
      position: relative;
    }

    /* Soft inner glow */
    .login-bubble::before {
      content: "";
      position: absolute;
      inset: 0;
      border-radius: 48px;
      background: radial-gradient(circle at 30% 20%, rgba(255, 255, 255, 0.45), transparent 55%);
      pointer-events: none;
    }

    .login-bubble .card-body {
      padding: 2.25rem 2rem 2rem;
      position: relative;
      z-index: 1;
    }

    .brand-icon {
      width: 72px;
      height: 72px;
      border-radius: 50%;
      background: linear-gradient(145deg, #ff9ec6, #7ec8f8);
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 0.75rem;
      box-shadow: 0 8px 20px rgba(255, 120, 160, 0.35);
      color: #fff;
      font-size: 2rem;
    }

    .brand-title {
      font-weight: 700;
      color: #4a3a6a;
      font-size: 1.45rem;
      letter-spacing: -0.02em;
    }

    .brand-sub {
      color: #7a6a9a;
      font-size: 0.95rem;
      margin-bottom: 0;
    }

    .form-label {
      color: #5a4a7a;
      font-weight: 600;
      font-size: 0.9rem;
    }

    .form-control {
      border-radius: 999px;
      border: 2px solid rgba(255, 255, 255, 0.7);
      background: rgba(255, 255, 255, 0.55);
      padding: 0.7rem 1.15rem;
      color: #3a2a5a;
      transition: all 0.2s ease;
    }

    .form-control:focus {
      background: rgba(255, 255, 255, 0.85);
      border-color: #ff9ec6;
      box-shadow: 0 0 0 3px rgba(255, 158, 198, 0.35);
      outline: none;
    }

    .form-control::placeholder {
      color: #a090b0;
    }

    .btn-masuk {
      border-radius: 999px;
      border: none;
      padding: 0.75rem 1.25rem;
      font-weight: 700;
      font-size: 1.05rem;
      background: linear-gradient(135deg, #ff7eb3 0%, #7ec8f8 50%, #95e1a3 100%);
      background-size: 200% 200%;
      color: #fff;
      box-shadow: 0 8px 22px rgba(255, 120, 160, 0.4);
      transition: all 0.3s ease;
      letter-spacing: 0.02em;
    }

    .btn-masuk:hover {
      background-position: 100% 0;
      transform: translateY(-2px);
      box-shadow: 0 12px 28px rgba(255, 120, 160, 0.5);
      color: #fff;
    }

    .btn-masuk:active {
      transform: translateY(0);
    }

    .alert {
      border-radius: 20px;
      border: none;
      background: rgba(255, 100, 120, 0.18);
      color: #b33a4a;
      font-size: 0.9rem;
    }

    .link-back, .link-lupa {
      color: #6a5a8a;
      text-decoration: none;
      font-size: 0.88rem;
      transition: color 0.2s;
    }

    .link-back:hover, .link-lupa:hover {
      color: #e85d8e;
    }

    /* Small decorative icons under the form */
    .kids-icons {
      display: flex;
      justify-content: center;
      gap: 0.85rem;
      margin-top: 1.25rem;
      opacity: 0.7;
    }

    .kids-icons i {
      font-size: 1.35rem;
      color: #7a6a9a;
    }

    .kids-icons i:nth-child(1) { color: #e85d8e; }
    .kids-icons i:nth-child(2) { color: #3a9bd9; }
    .kids-icons i:nth-child(3) { color: #e6a800; }
    .kids-icons i:nth-child(4) { color: #5cb85c; }
    .kids-icons i:nth-child(5) { color: #9b59b6; }

    @media (max-width: 480px) {
      .login-bubble {
        border-radius: 36px;
      }
      .login-bubble .card-body {
        padding: 1.75rem 1.35rem 1.5rem;
      }
      .float-icon {
        font-size: 1.5rem !important;
      }
    }
  </style>
</head>
<body>
  <!-- Soft pastel blobs -->
  <div class="bg-decor">
    <div class="shape s1"></div>
    <div class="shape s2"></div>
    <div class="shape s3"></div>
    <div class="shape s4"></div>
    <div class="shape s5"></div>
    <div class="shape s6"></div>
  </div>

  <!-- Floating kids icons: buku, pensil, balon, bintang, mainan, taman bermain -->
  <i class="bi bi-book float-icon i1"></i>
  <i class="bi bi-pencil float-icon i2"></i>
  <i class="bi bi-balloon float-icon i3"></i>
  <i class="bi bi-stars float-icon i4"></i>
  <i class="bi bi-palette float-icon i5"></i>
  <i class="bi bi-emoji-smile float-icon i6"></i>
  <i class="bi bi-puzzle float-icon i7"></i>
  <i class="bi bi-heart float-icon i8"></i>

  <div class="login-wrapper">
    <div class="login-bubble">
      <div class="card-body">
        <div class="text-center mb-4">
          <div class="brand-icon">
            <i class="bi bi-emoji-smile"></i>
          </div>
          <h3 class="brand-title mt-2 mb-1"><?= e($app['name']) ?></h3>
          <p class="brand-sub">Selamat datang di dunia belajar yang ceria ✨</p>
        </div>

        <?php if ($error): ?>
          <div class="alert alert-danger py-2 mb-3"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post">
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" required autofocus
                   placeholder="Masukkan email Anda"
                   value="<?= e($_POST['email'] ?? '') ?>">
          </div>
          <div class="mb-4">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required
                   placeholder="Masukkan password">
          </div>
          <button type="submit" class="btn btn-masuk w-100">Masuk</button>
        </form>

        <div class="text-center mt-3">
          <a href="/" class="link-back">← Kembali ke website</a>
        </div>
        <div class="text-center mt-2">
          <a href="/wali/lupa-password.php" class="link-lupa">Lupa password? (Portal Wali)</a>
        </div>

        <!-- Ikon kecil dunia anak -->
        <div class="kids-icons">
          <i class="bi bi-book" title="Buku"></i>
          <i class="bi bi-pencil" title="Alat tulis"></i>
          <i class="bi bi-balloon" title="Mainan"></i>
          <i class="bi bi-tree" title="Taman bermain"></i>
          <i class="bi bi-stars" title="Bintang"></i>
        </div>
      </div>
    </div>
  </div>
</body>
</html>