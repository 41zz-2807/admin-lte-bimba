<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_wali_login();

$user = current_user();
$id = (int)($_GET['id'] ?? 0);
$app = require __DIR__ . '/../../config/app.php';

$st = $pdo->prepare(
    'SELECT k.*, s.nama AS nama_siswa, s.nis
     FROM konfirmasi_bayar k
     JOIN siswa s ON s.id = k.siswa_id
     WHERE k.id = ? AND k.user_id = ? AND k.status = \'diterima\'
     LIMIT 1'
);
$st->execute([$id, $user['id']]);
$row = $st->fetch();
if (!$row) {
    http_response_code(404);
    die('Kwitansi tidak ditemukan.');
}

$bulanNama = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',
              7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Kwitansi <?= e($row['no_kwitansi']) ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-beta3/dist/css/adminlte.min.css">
  <style>@media print { .no-print { display:none } }</style>
</head>
<body class="bg-white">
<div class="container py-4" style="max-width:640px">
  <div class="d-flex justify-content-between mb-4 no-print">
    <a href="/wali/pembayaran.php" class="btn btn-secondary btn-sm">&larr; Kembali</a>
    <button onclick="window.print()" class="btn btn-primary btn-sm">Cetak</button>
  </div>
  <div class="border p-4">
    <div class="text-center mb-3">
      <h4 class="mb-0"><?= e($app['name']) ?></h4>
      <small class="text-muted">Kwitansi Pembayaran</small>
    </div>
    <hr>
    <table class="table table-borderless table-sm">
      <tr><td width="40%">No. Kwitansi</td><td><strong><?= e($row['no_kwitansi']) ?></strong></td></tr>
      <tr><td>Tanggal Bayar</td><td><?= e($row['tanggal_bayar']) ?></td></tr>
      <tr><td>Nama Siswa</td><td><?= e($row['nama_siswa']) ?> (<?= e($row['nis'] ?: '-') ?>)</td></tr>
      <tr><td>Jumlah</td><td><strong>Rp <?= number_format($row['jumlah'], 0, ',', '.') ?></strong></td></tr>
      <tr><td>Keterangan</td><td><?= e($row['keterangan'] ?: '-') ?></td></tr>
      <?php if ($row['bulan_spp']): ?>
      <tr><td>SPP</td><td><?= $bulanNama[(int)$row['bulan_spp']] ?? '' ?> <?= e($row['tahun_ajaran'] ?? '') ?></td></tr>
      <?php endif; ?>
      <tr><td>Status</td><td><span class="badge text-bg-success">Diterima</span></td></tr>
    </table>
    <p class="text-muted small mb-0">Diverifikasi: <?= e($row['verified_at'] ?? '') ?></p>
  </div>
</div>
</body>
</html>