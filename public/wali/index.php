<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_wali_login();

$user = current_user();
$anakList = wali_get_anak();
$app = require __DIR__ . '/../../config/app.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Portal Wali Murid | <?= e($app['name']) ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-beta3/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-body-tertiary">
<nav class="navbar navbar-expand-lg navbar-dark bg-success">
  <div class="container">
    <a class="navbar-brand" href="/wali/"><?= e($app['name']) ?> — Wali Murid</a>
    <div class="d-flex align-items-center gap-3 text-white">
      <span><i class="bi bi-person-circle"></i> <?= e($user['name']) ?></span>
      <a href="/wali/change-password.php" class="btn btn-sm btn-outline-light">Ganti Password</a>
      <a href="/wali/logout.php" class="btn btn-sm btn-light">Logout</a>
    </div>
  </div>
</nav>

<main class="container py-4">
  <h4 class="mb-3">Anak Saya</h4>

  <?php if (!$anakList): ?>
    <div class="alert alert-warning">Belum ada data anak yang ditautkan ke akun ini. Hubungi admin Bimba.</div>
  <?php else: ?>
    <div class="row g-3">
      <?php foreach ($anakList as $a): ?>
        <div class="col-md-6 col-lg-4">
          <div class="card h-100 shadow-sm">
            <div class="card-body">
  <div class="d-flex gap-3">
    <?php if (!empty($a['foto_url'])): ?>
      <img src="/uploads/<?= e($a['foto_url']) ?>"
           class="rounded"
           style="width:72px;height:72px;object-fit:cover;flex-shrink:0"
           alt="<?= e($a['nama']) ?>">
    <?php else: ?>
      <div class="bg-secondary text-white rounded d-flex align-items-center justify-content-center"
           style="width:72px;height:72px;flex-shrink:0">
        <i class="bi bi-person fs-3"></i>
      </div>
    <?php endif; ?>
    <div class="flex-grow-1">
      <div class="d-flex justify-content-between align-items-start">
        <h5 class="card-title mb-1"><?= e($a['nama']) ?></h5>
        <?php
          $badge = match($a['status']) {
            'aktif' => 'success',
            'lulus' => 'primary',
            default => 'secondary',
          };
        ?>
        <span class="badge text-bg-<?= $badge ?>"><?= e(ucfirst($a['status'])) ?></span>
      </div>
      <p class="text-muted small mb-2">
        NIS: <?= e($a['nis'] ?: '-') ?><br>
        Hubungan: <?= e($a['hubungan'] ?: 'Orang tua') ?>
      </p>
      <a href="/wali/anak.php?id=<?= (int)$a['id'] ?>" class="btn btn-sm btn-success">Lihat Profil</a>
      <a href="/wali/pembayaran.php" class="btn btn-sm btn-primary">Pembayaran</a>
    </div>
  </div>
</div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</main>
</body>
</html>