<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_wali_login();

$user = current_user();
$id = (int)($_GET['id'] ?? 0);
$app = require __DIR__ . '/../../config/app.php';

// Pastikan anak memang milik wali ini
$st = $pdo->prepare(
    'SELECT s.*, sw.hubungan
     FROM siswa_wali sw
     JOIN siswa s ON s.id = sw.siswa_id
     WHERE sw.user_id = ? AND s.id = ?
     LIMIT 1'
);
$st->execute([$user['id'], $id]);
$anak = $st->fetch();

if (!$anak) {
    http_response_code(404);
    die('Data anak tidak ditemukan atau bukan milik Anda.');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($anak['nama']) ?> | Portal Wali</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-beta3/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-body-tertiary">
<nav class="navbar navbar-dark bg-success">
  <div class="container">
    <a class="navbar-brand" href="/wali/">&larr; Kembali</a>
    <span class="text-white"><?= e($user['name']) ?></span>
  </div>
</nav>

<main class="container py-4">
  <div class="card shadow-sm">
    <div class="card-header bg-white">
      <h4 class="mb-0"><?= e($anak['nama']) ?>
        <span class="badge text-bg-<?= $anak['status']==='aktif'?'success':($anak['status']==='lulus'?'primary':'secondary') ?>">
          <?= e(ucfirst($anak['status'])) ?>
        </span>
      </h4>
    </div>
    <div class="card-body">
  <div class="mb-4">
    <?php if (!empty($anak['foto_url'])): ?>
      <img src="/uploads/<?= e($anak['foto_url']) ?>"
           class="rounded shadow-sm"
           style="width:140px;height:140px;object-fit:cover"
           alt="<?= e($anak['nama']) ?>">
    <?php else: ?>
      <div class="bg-secondary text-white rounded d-flex align-items-center justify-content-center"
           style="width:140px;height:140px">
        <i class="bi bi-person" style="font-size:3rem"></i>
      </div>
    <?php endif; ?>
  </div>
  <div class="row g-3">
        <div class="col-md-6"><strong>NIS</strong><br><?= e($anak['nis'] ?: '-') ?></div>
        <div class="col-md-6"><strong>Jenis Kelamin</strong><br><?= $anak['jenis_kelamin']==='L'?'Laki-laki':'Perempuan' ?></div>
        <div class="col-md-6"><strong>Tanggal Lahir</strong><br><?= e($anak['tanggal_lahir'] ?: '-') ?></div>
        <div class="col-md-6"><strong>Hubungan Anda</strong><br><?= e($anak['hubungan'] ?: 'Orang tua') ?></div>
        <div class="col-md-6"><strong>Nama Ortu (data master)</strong><br><?= e($anak['nama_ortu'] ?: '-') ?></div>
        <div class="col-md-6"><strong>No. HP Ortu</strong><br><?= e($anak['no_hp_ortu'] ?: '-') ?></div>
        <div class="col-12"><strong>Alamat</strong><br><?= e($anak['alamat'] ?: '-') ?></div>
        <?php if ($anak['catatan']): ?>
        <div class="col-12"><strong>Catatan</strong><br><?= e($anak['catatan']) ?></div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</main>
</body>
</html>