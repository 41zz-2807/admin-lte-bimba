<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db.php'; // pastikan $pdo tersedia

$app  = require __DIR__ . '/../config/app.php';
$kode = strtoupper(trim($_GET['kode'] ?? ''));
global $pdo;

$row = null;
$source = '';

if ($kode === '' || !preg_match('/^[A-F0-9]{16}$/', $kode)) {
    $error = 'Kode verifikasi tidak valid.';
} else {
    // 1) Cek konfirmasi wali
    $st = $pdo->prepare(
        'SELECT k.no_kwitansi, k.jumlah, k.tanggal_bayar AS tanggal, k.keterangan,
                k.bulan_spp, k.bulan_list, k.tahun_ajaran, k.status, k.verified_at,
                k.kode_verifikasi, s.nama AS nama_siswa, s.nis
         FROM konfirmasi_bayar k
         JOIN siswa s ON s.id = k.siswa_id
         WHERE k.kode_verifikasi = ? AND k.status = \'diterima\'
         LIMIT 1'
    );
    $st->execute([$kode]);
    $row = $st->fetch();
    if ($row) {
        $source = 'wali';
    } else {
        // 2) Cek transaksi admin (cash)
        $st2 = $pdo->prepare(
            'SELECT t.no_kwitansi, t.jumlah, t.tanggal, t.keterangan, t.kode_verifikasi,
                    s.nama AS nama_siswa, s.nis, t.id AS transaksi_id
             FROM transaksi t
             LEFT JOIN siswa s ON s.id = t.siswa_id
             WHERE t.kode_verifikasi = ? AND t.kategori = \'spp\'
             LIMIT 1'
        );
        $st2->execute([$kode]);
        $row = $st2->fetch();
        if ($row) {
            $source = 'admin';
            $row['status'] = 'diterima';
            $row['verified_at'] = $row['tanggal'];
            // ambil bulan dari detail
            $d = $pdo->prepare('SELECT bulan, tahun_ajaran FROM transaksi_spp_bulan WHERE transaksi_id = ? ORDER BY bulan');
            $d->execute([(int)$row['transaksi_id']]);
            $bl = $d->fetchAll();
            if ($bl) {
                $row['bulan_list'] = implode(',', array_column($bl, 'bulan'));
                $row['tahun_ajaran'] = $bl[0]['tahun_ajaran'];
            }
        } else {
            $error = 'Kwitansi tidak ditemukan atau belum diverifikasi.';
        }
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
            <td><?= e($row['tanggal'] ?? $row['tanggal_bayar'] ?? '-') ?></td>
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