<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db.php'; // pastikan $pdo tersedia

$app  = require __DIR__ . '/../config/app.php';
$kode = strtoupper(trim($_GET['kode'] ?? ''));
global $pdo;

$row = null;
$error = '';

if ($kode === '' || !preg_match('/^[A-F0-9]{16}$/', $kode)) {
    $error = 'Kode verifikasi tidak valid.';
} else {
    $st = $pdo->prepare(
        'SELECT k.no_kwitansi, k.jumlah, k.tanggal_bayar, k.keterangan, k.bulan_spp,
                k.bulan_list, k.tahun_ajaran, k.status, k.verified_at, k.kode_verifikasi,
                s.nama AS nama_siswa, s.nis
         FROM konfirmasi_bayar k
         JOIN siswa s ON s.id = k.siswa_id
         WHERE k.kode_verifikasi = ? AND k.status = \'diterima\'
         LIMIT 1'
    );
    $st->execute([$kode]);
    $row = $st->fetch();
    if (!$row) {
        $error = 'Kwitansi tidak ditemukan atau belum diverifikasi.';
    }
}

$bulanNama = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
];

$labelBulan = '-';
if ($row) {
    if (!empty($row['bulan_list'])) {
        $bl = array_map('intval', explode(',', $row['bulan_list']));
        $names = array_map(fn($b) => $bulanNama[$b] ?? $b, $bl);
        $labelBulan = implode(', ', $names);
        if (!empty($row['tahun_ajaran'])) {
            $labelBulan .= ' TA ' . $row['tahun_ajaran'];
        }
    } elseif (!empty($row['bulan_spp'])) {
        $labelBulan = ($bulanNama[(int)$row['bulan_spp']] ?? '') . ' ' . ($row['tahun_ajaran'] ?? '');
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Verifikasi Kwitansi | <?= e($app['name']) ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-beta3/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-body-tertiary">
<main class="container py-5" style="max-width:560px">
  <div class="card shadow-sm">
    <div class="card-body p-4">
      <div class="text-center mb-3">
        <i class="bi bi-shield-check display-5 text-success"></i>
        <h4 class="mt-2 mb-0">Verifikasi Kwitansi</h4>
        <p class="text-muted small mb-0"><?= e($app['name']) ?></p>
      </div>

      <?php if ($error): ?>
        <div class="alert alert-danger">
          <i class="bi bi-x-circle me-1"></i> <?= e($error) ?>
        </div>
        <p class="small text-muted mb-0">
          Jika Anda menerima kwitansi dengan kode ini, kemungkinan dokumen tidak sah
          atau belum diverifikasi admin.
        </p>
      <?php else: ?>
        <div class="alert alert-success py-2">
          <i class="bi bi-check-circle me-1"></i>
          <strong>Kwitansi sah</strong> — data sesuai catatan resmi.
        </div>
        <table class="table table-sm table-borderless mb-0">
          <tr>
            <td width="40%" class="text-muted">No. Kwitansi</td>
            <td><strong><?= e($row['no_kwitansi']) ?></strong></td>
          </tr>
          <tr>
            <td class="text-muted">Kode Verifikasi</td>
            <td><code><?= e($row['kode_verifikasi']) ?></code></td>
          </tr>
          <tr>
            <td class="text-muted">Tanggal Bayar</td>
            <td><?= e($row['tanggal_bayar']) ?></td>
          </tr>
          <tr>
            <td class="text-muted">Nama Siswa</td>
            <td><?= e($row['nama_siswa']) ?> (<?= e($row['nis'] ?: '-') ?>)</td>
          </tr>
          <tr>
            <td class="text-muted">Jumlah</td>
            <td><strong>Rp <?= number_format((float)$row['jumlah'], 0, ',', '.') ?></strong></td>
          </tr>
          <tr>
            <td class="text-muted">SPP / Periode</td>
            <td><?= e($labelBulan) ?></td>
          </tr>
          <tr>
            <td class="text-muted">Keterangan</td>
            <td><?= e($row['keterangan'] ?: '-') ?></td>
          </tr>
          <tr>
            <td class="text-muted">Diverifikasi</td>
            <td><?= e($row['verified_at'] ?? '-') ?></td>
          </tr>
        </table>
      <?php endif; ?>
    </div>
  </div>
  <p class="text-center text-muted small mt-3 mb-0">
    Halaman ini bersifat publik untuk pengecekan keaslian saja.
  </p>
</main>
</body>
</html>