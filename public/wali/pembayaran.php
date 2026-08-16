<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/flash.php';
require_wali_login();

$user     = current_user();
$anakList = wali_get_anak();
$app      = require __DIR__ . '/../../config/app.php';
$error    = '';
global $pdo;

$bulanNama = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
];

// ---- Tahun ajaran & tarif dari tabel tahun_ajaran ----
$daftarTA = [];
try {
    $daftarTA = $pdo->query(
        'SELECT kode, tarif_spp, is_aktif FROM tahun_ajaran ORDER BY kode DESC'
    )->fetchAll();
} catch (PDOException $e) {
    $daftarTA = [];
}

$tahunAjaranList = [];
$tarifMap        = [];
$defaultTA       = null;

foreach ($daftarTA as $row) {
    $kode = $row['kode'];
    $tahunAjaranList[] = $kode;
    $tarifMap[$kode] = (float) $row['tarif_spp'];
    if ((int) $row['is_aktif'] === 1) {
        $defaultTA = $kode;
    }
}
if ($defaultTA === null && $tahunAjaranList) {
    $defaultTA = $tahunAjaranList[0];
}
if ($defaultTA === null) {
    $defaultTA = '';
}

function get_tarif_spp(string $ta): float
{
    global $tarifMap;
    return (float) ($tarifMap[$ta] ?? 0);
}

// ---- Anak dari halaman sebelumnya (?siswa_id=) ----
$siswaIdGet  = (int) ($_GET['siswa_id'] ?? 0);
$anakTerpilih = null;
if ($siswaIdGet > 0) {
    foreach ($anakList as $a) {
        if ((int) $a['id'] === $siswaIdGet) {
            $anakTerpilih = $a;
            break;
        }
    }
}

// Default TA: prioritaskan tahun_ajaran anak jika ada di daftar
if ($anakTerpilih && !empty($anakTerpilih['tahun_ajaran'])
    && in_array($anakTerpilih['tahun_ajaran'], $tahunAjaranList, true)
) {
    $defaultTA = $anakTerpilih['tahun_ajaran'];
}

// ---- POST ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $siswaId   = (int) ($_POST['siswa_id'] ?? 0);
    $tanggal   = $_POST['tanggal_bayar'] ?? date('Y-m-d');
    $ket       = trim($_POST['keterangan'] ?? '');
    $ta        = trim($_POST['tahun_ajaran'] ?? $defaultTA);
    $bulanList = array_map('intval', $_POST['bulan'] ?? []);
    $bulanList = array_values(array_filter($bulanList, fn($b) => $b >= 1 && $b <= 12));
    $bulanList = array_unique($bulanList);
    sort($bulanList);

    $ids = array_column($anakList, 'id');

    if (!$tahunAjaranList) {
        $error = 'Belum ada tahun ajaran. Hubungi admin.';
    } elseif (!in_array($siswaId, $ids, true)) {
        $error = 'Anak tidak valid.';
    } elseif (empty($bulanList)) {
        $error = 'Pilih minimal satu bulan.';
    } elseif (!in_array($ta, $tahunAjaranList, true)) {
        $error = 'Tahun ajaran tidak valid.';
    } else {
        $tarif = get_tarif_spp($ta);
        if ($tarif <= 0) {
            $error = 'Tarif SPP untuk tahun ajaran ' . $ta . ' belum diatur admin.';
        } else {
            $stLunas = $pdo->prepare(
                'SELECT bulan FROM transaksi_spp_bulan WHERE siswa_id = ? AND tahun_ajaran = ?'
            );
            $stLunas->execute([$siswaId, $ta]);
            $lunas = array_map('intval', $stLunas->fetchAll(PDO::FETCH_COLUMN));

            $bulanOk = array_values(array_filter($bulanList, fn($b) => !in_array($b, $lunas, true)));
            if (empty($bulanOk)) {
                $error = 'Semua bulan yang dipilih sudah lunas.';
            } else {
                $jumlah    = $tarif * count($bulanOk);
                $namaBulan = array_map(fn($b) => $bulanNama[$b], $bulanOk);
                $ketAuto   = 'SPP ' . implode(', ', $namaBulan) . ' TA ' . $ta;
                if ($ket !== '') {
                    $ketAuto .= ' — ' . $ket;
                }

                $bukti = null;
                if (!empty($_FILES['bukti']['tmp_name'])) {
                    $bukti = upload_image($_FILES['bukti'], 'bukti_wali');
                    if (!$bukti) {
                        $f   = $_FILES['bukti'];
                        $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
                        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'], true)
                            && $f['size'] <= 5 * 1024 * 1024
                        ) {
                            $dir = __DIR__ . '/../uploads/bukti_wali';
                            if (!is_dir($dir)) {
                                mkdir($dir, 0755, true);
                            }
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
                    $bulanCsv     = implode(',', $bulanOk);
                    $bulanPertama = $bulanOk[0];

                    try {
                        $pdo->prepare(
                            'INSERT INTO konfirmasi_bayar
                             (user_id, siswa_id, jumlah, tanggal_bayar, keterangan, bukti,
                              bulan_spp, tahun_ajaran, bulan_list, status)
                             VALUES (?,?,?,?,?,?,?,?,?,\'pending\')'
                        )->execute([
                            $user['id'], $siswaId, $jumlah, $tanggal, $ketAuto, $bukti,
                            $bulanPertama, $ta, $bulanCsv,
                        ]);
                    } catch (PDOException $e) {
                        $pdo->prepare(
                            'INSERT INTO konfirmasi_bayar
                             (user_id, siswa_id, jumlah, tanggal_bayar, keterangan, bukti,
                              bulan_spp, tahun_ajaran, status)
                             VALUES (?,?,?,?,?,?,?,?,\'pending\')'
                        )->execute([
                            $user['id'], $siswaId, $jumlah, $tanggal, $ketAuto, $bukti,
                            $bulanPertama, $ta,
                        ]);
                    }

                    set_flash(
                        'success',
                        'Konfirmasi terkirim (' . count($bulanOk) . ' bulan, total Rp ' .
                        number_format($jumlah, 0, ',', '.') . '). Menunggu verifikasi admin.'
                    );
                    // Kembali ke form anak yang sama
                    $redir = 'wali/pembayaran.php';
                    if ($siswaId > 0) {
                        $redir .= '?siswa_id=' . $siswaId;
                    }
                    redirect($redir);
                }
            }
        }
    }
}

// ---- Riwayat ----
$riwayat = $pdo->prepare(
    'SELECT k.*, s.nama AS nama_siswa
     FROM konfirmasi_bayar k
     JOIN siswa s ON s.id = k.siswa_id
     WHERE k.user_id = ?
     ORDER BY k.created_at DESC'
);
$riwayat->execute([$user['id']]);
$rows = $riwayat->fetchAll();

// Bulan lunas: anak terpilih (atau anak pertama) + TA default
$siswaUntukLunas = $anakTerpilih['id'] ?? ($anakList[0]['id'] ?? 0);
$lunasDefault = [];
if ($siswaUntukLunas > 0 && $defaultTA !== '') {
    $st = $pdo->prepare(
        'SELECT bulan FROM transaksi_spp_bulan WHERE siswa_id = ? AND tahun_ajaran = ?'
    );
    $st->execute([$siswaUntukLunas, $defaultTA]);
    $lunasDefault = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Konfirmasi Pembayaran | <?= e($app['name']) ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-beta3/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    .bulan-lunas { opacity: .55; }
    #totalDisplay { font-size: 1.25rem; font-weight: 700; color: #198754; }
  </style>
</head>
<body class="bg-body-tertiary">
<nav class="navbar navbar-dark bg-success mb-4">
  <div class="container">
    <a class="navbar-brand" href="/wali/">&larr; Portal Wali</a>
    <span class="text-white"><?= e($user['name']) ?></span>
  </div>
</nav>

<main class="container pb-5">
  <h4 class="mb-3">Konfirmasi Pembayaran SPP</h4>

  <?php $flash = get_flash(); if ($flash): ?>
    <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
  <?php endif; ?>

  <?php if (!$tahunAjaranList): ?>
    <div class="alert alert-warning">
      Belum ada tahun ajaran yang diatur. Silakan hubungi admin.
    </div>
  <?php else: ?>

  <div class="card shadow-sm mb-4">
    <div class="card-header bg-white"><strong>Form Konfirmasi</strong></div>
    <div class="card-body">
      <form method="post" enctype="multipart/form-data" id="formBayar">
        <div class="row g-3">

          <div class="col-md-6">
            <label class="form-label">Anak *</label>
            <?php if ($anakTerpilih): ?>
              <input type="hidden" name="siswa_id" id="siswa_id" value="<?= (int)$anakTerpilih['id'] ?>">
              <input type="text" class="form-control" value="<?= e($anakTerpilih['nama']) ?>" readonly>
            <?php else: ?>
              <select name="siswa_id" id="siswa_id" class="form-select" required>
                <option value="">— pilih —</option>
                <?php foreach ($anakList as $a): ?>
                  <option value="<?= (int)$a['id'] ?>"><?= e($a['nama']) ?></option>
                <?php endforeach; ?>
              </select>
            <?php endif; ?>
          </div>

          <div class="col-md-6">
            <label class="form-label">Tahun Ajaran *</label>
            <select name="tahun_ajaran" id="tahun_ajaran" class="form-select" required>
              <?php foreach ($tahunAjaranList as $ta): ?>
                <option value="<?= e($ta) ?>"
                        data-tarif="<?= (float)$tarifMap[$ta] ?>"
                        <?= $ta === $defaultTA ? 'selected' : '' ?>>
                  <?= e($ta) ?>
                  <?php if ($tarifMap[$ta] > 0): ?>
                    — Rp <?= number_format($tarifMap[$ta], 0, ',', '.') ?>/bln
                  <?php else: ?>
                    — (tarif belum diatur)
                  <?php endif; ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-4">
            <label class="form-label">Tanggal Bayar *</label>
            <input type="date" name="tanggal_bayar" class="form-control" required value="<?= date('Y-m-d') ?>">
          </div>

          <div class="col-md-4">
            <label class="form-label">Tarif SPP / bulan</label>
            <input type="text" id="tarifDisplay" class="form-control" readonly
                   value="Rp <?= number_format($tarifMap[$defaultTA] ?? 0, 0, ',', '.') ?>">
          </div>

          <div class="col-md-4">
            <label class="form-label">Total Bayar</label>
            <div id="totalDisplay" class="form-control-plaintext">Rp 0</div>
            <input type="hidden" name="jumlah" id="jumlah" value="0">
          </div>

          <div class="col-12">
            <label class="form-label">Pilih Bulan *</label>
            <div class="row" id="listBulan">
              <?php foreach ($bulanNama as $num => $nama): ?>
                <?php $sudah = in_array($num, $lunasDefault, true); ?>
                <div class="col-6 col-md-3 mb-2">
                  <div class="form-check <?= $sudah ? 'bulan-lunas' : '' ?>">
                    <input class="form-check-input bulan-cb" type="checkbox"
                           name="bulan[]" value="<?= $num ?>" id="bln<?= $num ?>"
                           <?= $sudah ? 'disabled' : '' ?>>
                    <label class="form-check-label" for="bln<?= $num ?>">
                      <?= $nama ?>
                      <?php if ($sudah): ?>
                        <span class="badge text-bg-success">Lunas</span>
                      <?php endif; ?>
                    </label>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
            <div class="form-text">Bulan yang sudah lunas tidak bisa dipilih lagi.</div>
          </div>

          <div class="col-12">
            <label class="form-label">Keterangan (opsional)</label>
            <input type="text" name="keterangan" class="form-control" placeholder="Catatan tambahan">
          </div>

          <div class="col-12">
            <label class="form-label">Bukti Transfer * (jpg/png/pdf max 5MB)</label>
            <input type="file" name="bukti" class="form-control" accept="image/*,.pdf" required>
          </div>

          <div class="col-12">
            <button type="submit" class="btn btn-success" id="btnKirim">
              <i class="bi bi-send"></i> Kirim Konfirmasi
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <?php endif; ?>

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
              <?php
              if (!empty($r['bulan_list'])) {
                  $bl = array_map('intval', explode(',', $r['bulan_list']));
                  $names = array_map(fn($b) => $bulanNama[$b] ?? $b, $bl);
                  echo e(implode(', ', $names) . ' ' . ($r['tahun_ajaran'] ?? ''));
              } elseif ($r['bulan_spp']) {
                  echo e(($bulanNama[(int)$r['bulan_spp']] ?? $r['bulan_spp']) . ' ' . ($r['tahun_ajaran'] ?? ''));
              } else {
                  echo e($r['keterangan'] ?? '-');
              }
              ?>
            </td>
            <td>
              <?php
              $b = match ($r['status']) {
                  'diterima' => 'success',
                  'ditolak'  => 'danger',
                  default    => 'warning',
              };
              ?>
              <span class="badge text-bg-<?= $b ?>"><?= e(ucfirst($r['status'])) ?></span>
            </td>
            <td>
              <?php if ($r['status'] === 'diterima' && !empty($r['no_kwitansi'])): ?>
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

<script>
(function () {
  const tarifMap  = <?= json_encode($tarifMap, JSON_UNESCAPED_UNICODE) ?>;
  const taSelect  = document.getElementById('tahun_ajaran');
  const tarifDisp = document.getElementById('tarifDisplay');
  const totalDisp = document.getElementById('totalDisplay');
  const jumlahInp = document.getElementById('jumlah');

  if (!taSelect) return;

  const cbs = () => document.querySelectorAll('.bulan-cb:not(:disabled)');

  function fmt(n) {
    return 'Rp ' + Math.round(n).toLocaleString('id-ID');
  }

  function hitung() {
    const ta    = taSelect.value;
    const tarif = parseFloat(tarifMap[ta] || 0);
    tarifDisp.value = fmt(tarif);

    let count = 0;
    cbs().forEach(cb => { if (cb.checked) count++; });
    const total = tarif * count;
    totalDisp.textContent = fmt(total);
    jumlahInp.value = total;
  }

  taSelect.addEventListener('change', hitung);
  document.getElementById('listBulan').addEventListener('change', hitung);
  hitung();
})();
</script>
</body>
</html>