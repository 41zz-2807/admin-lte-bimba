<?php
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/flash.php';
require_once __DIR__ . '/../../../includes/helpers.php';
require_login();

global $pdo;
$app = require __DIR__ . '/../../../config/app.php';
$id  = (int) ($_GET['id'] ?? 0);

$st = $pdo->prepare(
    'SELECT t.*, s.nama AS nama_siswa, s.nis, s.email_ortu, s.nama_ortu
     FROM transaksi t
     LEFT JOIN siswa s ON s.id = t.siswa_id
     WHERE t.id = ? LIMIT 1'
);
$st->execute([$id]);
$row = $st->fetch();

if (!$row || strtolower((string) $row['kategori']) !== 'spp') {
    set_flash('danger', 'Kwitansi SPP tidak ditemukan.');
    redirect('admin/transaksi/');
}

// Generate kwitansi jika belum ada
if (empty($row['kode_verifikasi']) || empty($row['no_kwitansi'])) {
    $noKwitansi = 'KW/T/' . date('Ymd') . '/' . str_pad((string) $id, 4, '0', STR_PAD_LEFT);
    do {
        $kode = strtoupper(bin2hex(random_bytes(8)));
        $cek  = $pdo->prepare('SELECT id FROM transaksi WHERE kode_verifikasi = ? LIMIT 1');
        $cek->execute([$kode]);
    } while ($cek->fetch());

    $pdo->prepare('UPDATE transaksi SET no_kwitansi = ?, kode_verifikasi = ? WHERE id = ?')
        ->execute([$noKwitansi, $kode, $id]);
    $row['no_kwitansi']     = $noKwitansi;
    $row['kode_verifikasi'] = $kode;
}

$bulanNama = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
];

$det = $pdo->prepare(
    'SELECT bulan, tahun_ajaran, jumlah FROM transaksi_spp_bulan WHERE transaksi_id = ? ORDER BY bulan'
);
$det->execute([$id]);
$bulanRows = $det->fetchAll();
$labelBulan = '-';
if ($bulanRows) {
    $names = array_map(fn($r) => $bulanNama[(int) $r['bulan']] ?? $r['bulan'], $bulanRows);
    $ta = $bulanRows[0]['tahun_ajaran'] ?? '';
    $labelBulan = implode(', ', $names) . ($ta ? ' TA ' . $ta : '');
}

// Kirim email
if (isset($_GET['kirim_email'])) {
    $email = trim((string) ($row['email_ortu'] ?? ''));
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        set_flash('danger', 'Email ortu kosong / tidak valid.');
    } else {
        $namaOrtu  = trim((string) ($row['nama_ortu'] ?? '')) ?: 'Bapak/Ibu';
        $verifyUrl = rtrim($app['url'] ?? '', '/') . '/verifikasi-kwitansi.php?kode=' . urlencode($row['kode_verifikasi']);
        $body = "Halo {$namaOrtu},\n\n"
            . "Pembayaran SPP {$app['name']} telah dicatat.\n\n"
            . "Nama siswa   : " . ($row['nama_siswa'] ?? '-') . "\n"
            . "Tanggal bayar: {$row['tanggal']}\n"
            . "Jumlah       : Rp " . number_format((float) $row['jumlah'], 0, ',', '.') . "\n"
            . "Keterangan   : " . ($row['keterangan'] ?: '-') . "\n"
            . "No. Kwitansi : {$row['no_kwitansi']}\n"
            . "Kode verifikasi: {$row['kode_verifikasi']}\n\n"
            . "Cek keaslian:\n{$verifyUrl}\n\n"
            . "Terima kasih.\n{$app['name']}";

        $result = send_smtp_mail(
            $email,
            'Kwitansi SPP ' . $row['no_kwitansi'] . ' — ' . $app['name'],
            $body,
            $namaOrtu
        );
        if ($result === true) {
            set_flash('success', 'Email kwitansi terkirim ke ' . $email);
        } else {
            set_flash('danger', 'Gagal kirim email: ' . $result);
        }
    }
    redirect('admin/transaksi/kwitansi.php?id=' . $id);
}

$verifyUrl = rtrim($app['url'] ?? '', '/') . '/verifikasi-kwitansi.php?kode=' . urlencode($row['kode_verifikasi']);
$qrSrc = 'https://api.qrserver.com/v1/create-qr-code/?size=140x140&ecc=M&data=' . urlencode($verifyUrl);
$flash = function_exists('get_flash') ? get_flash() : null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kwitansi <?= e($row['no_kwitansi']) ?> | <?= e($app['name']) ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-beta3/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    body { background: #f4f6f9; }
    .kwitansi-box { max-width: 640px; margin: 0 auto; }
    .qr-box { text-align: center; }
    .qr-box img { border: 1px solid #dee2e6; padding: 6px; background: #fff; }
    .kode-verifikasi {
      font-family: ui-monospace, monospace;
      letter-spacing: 1px;
      font-size: 0.9rem;
    }
    @media print {
      body { background: #fff !important; }
      .no-print { display: none !important; }
      .kwitansi-box { max-width: 100%; }
      .border { border-color: #000 !important; }
    }
  </style>
</head>
<body>
<div class="container py-4 kwitansi-box">

  <div class="d-flex flex-wrap gap-2 mb-3 no-print">
    <a href="/admin/transaksi/detail.php?id=<?= (int) $id ?>" class="btn btn-secondary btn-sm">&larr; Detail</a>
    <a href="/admin/transaksi/" class="btn btn-outline-secondary btn-sm">Daftar Transaksi</a>
    <button type="button" onclick="window.print()" class="btn btn-primary btn-sm">
      <i class="bi bi-printer"></i> Cetak / Download PDF
    </button>
    <a href="?id=<?= (int) $id ?>&kirim_email=1" class="btn btn-success btn-sm"
       onclick="return confirm('Kirim kwitansi ke email ortu?')">
      <i class="bi bi-envelope"></i> Kirim Email
    </a>
  </div>

  <?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type']) ?> no-print"><?= e($flash['message']) ?></div>
  <?php endif; ?>

  <div class="border p-4 bg-white">
    <div class="text-center mb-3">
      <h4 class="mb-0"><?= e($app['name']) ?></h4>
      <small class="text-muted">Kwitansi Pembayaran SPP (Tunai / Admin)</small>
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
            <td><?= e($row['tanggal']) ?></td>
          </tr>
          <tr>
            <td>Nama Siswa</td>
            <td><?= e($row['nama_siswa'] ?? '-') ?> (<?= e($row['nis'] ?: '-') ?>)</td>
          </tr>
          <tr>
            <td>Jumlah</td>
            <td><strong>Rp <?= number_format((float) $row['jumlah'], 0, ',', '.') ?></strong></td>
          </tr>
          <tr>
            <td>SPP / Periode</td>
            <td><?= e($labelBulan) ?></td>
          </tr>
          <tr>
            <td>Keterangan</td>
            <td><?= e($row['keterangan'] ?: '-') ?></td>
          </tr>
          <tr>
            <td>Status</td>
            <td><span class="badge text-bg-success">Lunas</span></td>
          </tr>
        </table>
      </div>
      <div class="col-4 qr-box">
        <img src="<?= e($qrSrc) ?>" width="140" height="140" alt="QR Verifikasi">
        <div class="small text-muted mt-1">Scan untuk verifikasi</div>
        <div class="kode-verifikasi mt-1"><?= e($row['kode_verifikasi']) ?></div>
      </div>
    </div>
    <hr>
    <p class="text-muted small mb-0">
      Keaslian kwitansi dapat dicek di:<br>
      <span class="text-break"><?= e($verifyUrl) ?></span>
    </p>
  </div>
</div>
</body>
</html>