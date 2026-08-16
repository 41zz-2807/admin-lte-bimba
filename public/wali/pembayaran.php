<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/flash.php';
require_wali_login();

$user = current_user();
$anakList = wali_get_anak();
$app = require __DIR__ . '/../../config/app.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $siswaId = (int)($_POST['siswa_id'] ?? 0);
    $jumlah = (float)str_replace(['.', ','], ['', '.'], $_POST['jumlah'] ?? '0');
    $tanggal = $_POST['tanggal_bayar'] ?? date('Y-m-d');
    $ket = trim($_POST['keterangan'] ?? '');
    $bulan = (int)($_POST['bulan_spp'] ?? 0) ?: null;
    $ta = trim($_POST['tahun_ajaran'] ?? '') ?: null;

    // Pastikan anak milik wali ini
    $ids = array_column($anakList, 'id');
    if (!in_array($siswaId, $ids, true)) {
        $error = 'Anak tidak valid.';
    } elseif ($jumlah <= 0) {
        $error = 'Jumlah harus lebih dari 0.';
    } else {
        $bukti = null;
        if (!empty($_FILES['bukti']['tmp_name'])) {
            $bukti = upload_image($_FILES['bukti'], 'bukti_wali');
            // upload_image hanya image; untuk PDF perlu perluasan — sementara image dulu
            if (!$bukti) {
                // coba upload generik sederhana untuk pdf
                $f = $_FILES['bukti'];
                $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg','jpeg','png','gif','webp','pdf'], true) && $f['size'] <= 5*1024*1024) {
                    $dir = __DIR__ . '/../uploads/bukti_wali';
                    if (!is_dir($dir)) mkdir($dir, 0755, true);
                    $name = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                    if (move_uploaded_file($f['tmp_name'], $dir . '/' . $name)) {
                        $bukti = 'bukti_wali/' . $name;
                    }
                }
            }
        }
        if (!$bukti) {
            $error = 'Bukti transfer wajib diupload (jpg/png/pdf max 5MB).';
        } else {
            $pdo->prepare(
                'INSERT INTO konfirmasi_bayar
                 (user_id, siswa_id, jumlah, tanggal_bayar, keterangan, bukti, bulan_spp, tahun_ajaran, status)
                 VALUES (?,?,?,?,?,?,?,?,\'pending\')'
            )->execute([$user['id'], $siswaId, $jumlah, $tanggal, $ket ?: null, $bukti, $bulan, $ta]);
            set_flash('success', 'Konfirmasi terkirim. Menunggu verifikasi admin.');
            redirect('wali/pembayaran.php');
        }
    }
}

$riwayat = $pdo->prepare(
    'SELECT k.*, s.nama AS nama_siswa
     FROM konfirmasi_bayar k
     JOIN siswa s ON s.id = k.siswa_id
     WHERE k.user_id = ?
     ORDER BY k.created_at DESC'
);
$riwayat->execute([$user['id']]);
$rows = $riwayat->fetchAll();

$bulanNama = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',
              7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Konfirmasi Pembayaran | <?= e($app['name']) ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-beta3/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-body-tertiary">
<nav class="navbar navbar-dark bg-success mb-4">
  <div class="container">
    <a class="navbar-brand" href="/wali/">&larr; Portal Wali</a>
    <span class="text-white"><?= e($user['name']) ?></span>
  </div>
</nav>

<main class="container pb-5">
  <h4 class="mb-3">Konfirmasi Pembayaran</h4>

  <?php $flash = get_flash(); if ($flash): ?>
  <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
<?php endif; ?>
  <?php if ($error): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
  <?php endif; ?>

  <div class="card shadow-sm mb-4">
    <div class="card-header bg-white"><strong>Form Konfirmasi</strong></div>
    <div class="card-body">
      <form method="post" enctype="multipart/form-data">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Anak *</label>
            <select name="siswa_id" class="form-select" required>
              <option value="">— pilih —</option>
              <?php foreach ($anakList as $a): ?>
                <option value="<?= (int)$a['id'] ?>"><?= e($a['nama']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Jumlah (Rp) *</label>
            <input type="text" name="jumlah" class="form-control" required placeholder="500000">
          </div>
          <div class="col-md-4">
            <label class="form-label">Tanggal Bayar *</label>
            <input type="date" name="tanggal_bayar" class="form-control" required value="<?= date('Y-m-d') ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label">Bulan SPP</label>
            <select name="bulan_spp" class="form-select">
              <option value="">— bukan SPP / lain —</option>
              <?php foreach ($bulanNama as $n => $nm): ?>
                <option value="<?= $n ?>"><?= $nm ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Tahun Ajaran</label>
            <input type="text" name="tahun_ajaran" class="form-control" placeholder="2025/2026">
          </div>
          <div class="col-12">
            <label class="form-label">Keterangan</label>
            <input type="text" name="keterangan" class="form-control" placeholder="SPP Januari, dll">
          </div>
          <div class="col-12">
            <label class="form-label">Bukti Transfer * (jpg/png/pdf max 5MB)</label>
            <input type="file" name="bukti" class="form-control" accept="image/*,.pdf" required>
          </div>
          <div class="col-12">
            <button class="btn btn-success">Kirim Konfirmasi</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <h5 class="mb-3">Riwayat</h5>
  <div class="card shadow-sm">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr>
            <th>Tanggal</th><th>Anak</th><th>Jumlah</th><th>SPP</th><th>Status</th><th>Kwitansi</th>
          </tr>
        </thead>
        <tbody>
        <?php if (!$rows): ?>
          <tr><td colspan="6" class="text-center text-muted">Belum ada konfirmasi</td></tr>
        <?php else: foreach ($rows as $r): ?>
          <tr>
            <td><?= e($r['tanggal_bayar']) ?></td>
            <td><?= e($r['nama_siswa']) ?></td>
            <td>Rp <?= number_format($r['jumlah'], 0, ',', '.') ?></td>
            <td>
              <?php if ($r['bulan_spp']): ?>
                <?= $bulanNama[(int)$r['bulan_spp']] ?? $r['bulan_spp'] ?> <?= e($r['tahun_ajaran'] ?? '') ?>
              <?php else: ?>-<?php endif; ?>
            </td>
            <td>
              <?php
                $b = match($r['status']) {
                  'diterima' => 'success',
                  'ditolak' => 'danger',
                  default => 'warning',
                };
              ?>
              <span class="badge text-bg-<?= $b ?>"><?= e(ucfirst($r['status'])) ?></span>
            </td>
            <td>
              <?php if ($r['status'] === 'diterima' && $r['no_kwitansi']): ?>
                <a href="/wali/kwitansi.php?id=<?= (int)$r['id'] ?>" class="btn btn-sm btn-outline-primary">Lihat</a>
              <?php else: ?>-<?php endif; ?>
            </td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>
</body>
</html>