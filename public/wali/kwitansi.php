<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_wali_login();

$user = current_user();
$id   = (int) ($_GET['id'] ?? 0);
$app  = require __DIR__ . '/../../config/app.php';
global $pdo;

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

$bulanNama = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
];

// Label bulan (support multi)
$labelBulan = '-';
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

// URL verifikasi publik
$kode = $row['kode_verifikasi'] ?? '';
$verifyUrl = rtrim($app['url'] ?? '', '/') . '/verifikasi-kwitansi.php?kode=' . urlencode($kode);

// QR via API publik (tanpa library)
$qrSrc = $kode !== ''
    ? 'https://api.qrserver.com/v1/create-qr-code/?size=140x140&ecc=M&data=' . urlencode($verifyUrl)
    : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Kwitansi <?= e($row['no_kwitansi']) ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-beta3/dist/css/adminlte.min.css">
  <style>
    @media print {
      .no-print { display: none !important; }
      body { background: #fff; }
    }
    .kwitansi-box { max-width: 640px; margin: 0 auto; }
    .qr-box { text-align: center; }
    .qr-box img { border: 1px solid #dee2e6; padding: 6px; background: #fff; }
    .kode-verifikasi {
      font-family: ui-monospace, monospace;
      letter-spacing: 1px;
      font-size: 0.9rem;
    }
  </style>
</head>
<body class="bg-white">
<div class="container py-4 kwitansi-box">
  <div class="d-flex justify-content-between mb-4 no-print">
    <a href="/wali/pembayaran.php" class="btn btn-secondary btn-sm">&larr; Kembali</a>
    <button onclick="window.print()" class="btn btn-primary btn-sm">Cetak</button>
  </div>

  <div class="border p-4">
    <div class="text-center mb-3">
      <h4 class="mb-0"><?= e($app['name']) ?></h4>
      <small class="text-muted">Kwitansi Pembayaran Resmi</small>
    </div>
    <hr>

    <div class="row">
      <div class="col-8">
        <table class="table table-borderless table-sm mb-0">
          <tr>
            <td width="42%">No. Kwitansi</td>
            <td><strong><?= e($row['no_kwitansi']) ?></strong></td>
          </tr>
          <tr>
            <td>Tanggal Bayar</td>
            <td><?= e($row['tanggal_bayar']) ?></td>
          </tr>
          <tr>
            <td>Nama Siswa</td>
            <td><?= e($row['nama_siswa']) ?> (<?= e($row['nis'] ?: '-') ?>)</td>
          </tr>
          <tr>
            <td>Jumlah</td>
            <td><strong>Rp <?= number_format((float)$row['jumlah'], 0, ',', '.') ?></strong></td>
          </tr>
          <tr>
            <td>Keterangan</td>
            <td><?= e($row['keterangan'] ?: '-') ?></td>
          </tr>
          <tr>
            <td>SPP / Periode</td>
            <td><?= e($labelBulan) ?></td>
          </tr>
          <tr>
            <td>Status</td>
            <td><span class="badge text-bg-success">Diterima</span></td>
          </tr>
          <tr>
            <td>Diverifikasi</td>
            <td><?= e($row['verified_at'] ?? '-') ?></td>
          </tr>
        </table>
      </div>
      <div class="col-4 qr-box">
        <?php if ($qrSrc): ?>
          <img src="<?= e($qrSrc) ?>" width="140" height="140" alt="QR Verifikasi">
          <div class="small text-muted mt-1">Scan untuk verifikasi</div>
          <div class="kode-verifikasi mt-1"><?= e($kode) ?></div>
        <?php else: ?>
          <div class="text-muted small">Kode verifikasi belum tersedia<br>(kwitansi lama)</div>
        <?php endif; ?>
      </div>
    </div>

    <hr>
    <p class="text-muted small mb-0">
      Keaslian kwitansi dapat dicek di:
      <span class="text-break"><?= e($verifyUrl) ?></span><br>
      Data yang sah hanya yang muncul di halaman verifikasi resmi.
    </p>
  </div>
</div>
</body>
</html>