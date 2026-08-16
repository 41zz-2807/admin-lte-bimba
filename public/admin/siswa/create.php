<?php
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/flash.php';
require_once __DIR__ . '/../../../includes/helpers.php';
require_login();

$pageTitle = 'Tambah Siswa';
$error = '';
global $pdo;

// Daftar tahun ajaran
$daftarTA = [];
try {
    $daftarTA = $pdo->query(
        'SELECT kode, tarif_spp, is_aktif FROM tahun_ajaran ORDER BY kode DESC'
    )->fetchAll();
} catch (PDOException $e) {
    $daftarTA = [];
}

$defaultTA = '';
foreach ($daftarTA as $ta) {
    if ((int)$ta['is_aktif'] === 1) {
        $defaultTA = $ta['kode'];
        break;
    }
}
if ($defaultTA === '' && $daftarTA) {
    $defaultTA = $daftarTA[0]['kode'];
}

$old = [
    'nis' => '', 'nama' => '', 'jenis_kelamin' => 'L',
    'tanggal_lahir' => '', 'nama_ortu' => '', 'no_hp_ortu' => '',
    'email_ortu' => '', 'alamat' => '', 'status' => 'aktif',
    'tahun_ajaran' => $defaultTA, 'catatan' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = [
        'nis'           => trim($_POST['nis'] ?? ''),
        'nama'          => trim($_POST['nama'] ?? ''),
        'jenis_kelamin' => $_POST['jenis_kelamin'] ?? 'L',
        'tanggal_lahir' => $_POST['tanggal_lahir'] ?? '',
        'nama_ortu'     => trim($_POST['nama_ortu'] ?? ''),
        'no_hp_ortu'    => trim($_POST['no_hp_ortu'] ?? ''),
        'email_ortu'    => trim($_POST['email_ortu'] ?? ''),
        'alamat'        => trim($_POST['alamat'] ?? ''),
        'status'        => $_POST['status'] ?? 'aktif',
        'tahun_ajaran'  => trim($_POST['tahun_ajaran'] ?? ''),
        'catatan'       => trim($_POST['catatan'] ?? ''),
    ];

    $taValid = array_column($daftarTA, 'kode');

    if ($old['nama'] === '') {
        $error = 'Nama siswa wajib diisi.';
    } elseif (!in_array($old['jenis_kelamin'], ['L', 'P'], true)) {
        $error = 'Jenis kelamin tidak valid.';
    } elseif (!in_array($old['status'], ['aktif', 'nonaktif', 'lulus'], true)) {
        $error = 'Status tidak valid.';
    } elseif ($old['tahun_ajaran'] !== '' && !in_array($old['tahun_ajaran'], $taValid, true)) {
        $error = 'Tahun ajaran tidak valid.';
    } elseif ($old['email_ortu'] !== '' && !filter_var($old['email_ortu'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email orang tua tidak valid.';
    } else {
        if ($old['nis'] !== '') {
            $cek = $pdo->prepare('SELECT id FROM siswa WHERE nis = ? LIMIT 1');
            $cek->execute([$old['nis']]);
            if ($cek->fetch()) {
                $error = 'NIS sudah terdaftar.';
            }
        }

        $fotoUrl = null;
        if ($error === '' && !empty($_FILES['foto']['tmp_name'])) {
            $up = upload_image($_FILES['foto'], 'siswa');
            if ($up === null) {
                $error = 'Upload foto gagal (format/ukuran tidak valid).';
            } else {
                $fotoUrl = $up;
            }
        }

        if ($error === '') {
            $ins = $pdo->prepare(
                'INSERT INTO siswa
                 (nis, nama, jenis_kelamin, tanggal_lahir, nama_ortu, no_hp_ortu, email_ortu,
                  alamat, status, tahun_ajaran, catatan, foto_url)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?)'
            );
            $ins->execute([
                $old['nis'] ?: null,
                $old['nama'],
                $old['jenis_kelamin'],
                $old['tanggal_lahir'] ?: null,
                $old['nama_ortu'] ?: null,
                $old['no_hp_ortu'] ?: null,
                $old['email_ortu'] ?: null,
                $old['alamat'] ?: null,
                $old['status'],
                $old['tahun_ajaran'] ?: null,
                $old['catatan'] ?: null,
                $fotoUrl,
            ]);

            $siswaId = (int) $pdo->lastInsertId();

            // Auto buat / tautkan akun wali + kirim email
            $waliResult = ensure_wali_for_siswa(
                $siswaId,
                $old['nama'],
                $old['nama_ortu'] ?: null,
                $old['email_ortu'] ?: null,
                'Orang tua'
            );

            set_flash('success', 'Data siswa berhasil ditambahkan.' . $waliResult['message']);
            redirect('admin/siswa/');
        }
    }
}

ob_start();
?>
<div class="row justify-content-center">
<div class="col-md-8">
<div class="card">
<div class="card-header"><h3 class="card-title">Tambah Siswa</h3></div>
<div class="card-body">
<?php if ($error): ?>
<div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>

<?php if (!$daftarTA): ?>
<div class="alert alert-warning">
  Belum ada tahun ajaran. Isi dulu di
  <a href="/admin/settings.php">Pengaturan → Tahun Ajaran & Tarif SPP</a>.
</div>
<?php endif; ?>

<form method="post" action="" enctype="multipart/form-data">
<div class="row">
<div class="col-md-4 mb-3">
<label class="form-label">NIS</label>
<input type="text" name="nis" class="form-control" value="<?= e($old['nis']) ?>">
</div>
<div class="col-md-8 mb-3">
<label class="form-label">Nama Siswa <span class="text-danger">*</span></label>
<input type="text" name="nama" class="form-control" required value="<?= e($old['nama']) ?>">
</div>
</div>
<div class="row">
<div class="col-md-4 mb-3">
<label class="form-label">Jenis Kelamin</label>
<select name="jenis_kelamin" class="form-select">
<option value="L" <?= $old['jenis_kelamin'] === 'L' ? 'selected' : '' ?>>Laki-laki</option>
<option value="P" <?= $old['jenis_kelamin'] === 'P' ? 'selected' : '' ?>>Perempuan</option>
</select>
</div>
<div class="col-md-4 mb-3">
<label class="form-label">Tanggal Lahir</label>
<input type="date" name="tanggal_lahir" class="form-control" value="<?= e($old['tanggal_lahir']) ?>">
</div>
<div class="col-md-4 mb-3">
<label class="form-label">Status</label>
<select name="status" class="form-select">
<option value="aktif" <?= $old['status'] === 'aktif' ? 'selected' : '' ?>>Aktif</option>
<option value="nonaktif" <?= $old['status'] === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
<option value="lulus" <?= $old['status'] === 'lulus' ? 'selected' : '' ?>>Lulus</option>
</select>
</div>
</div>

<div class="row">
<div class="col-md-6 mb-3">
<label class="form-label">Tahun Ajaran</label>
<select name="tahun_ajaran" class="form-select">
  <option value="">— pilih —</option>
  <?php foreach ($daftarTA as $ta): ?>
    <option value="<?= e($ta['kode']) ?>"
      <?= $old['tahun_ajaran'] === $ta['kode'] ? 'selected' : '' ?>>
      <?= e($ta['kode']) ?>
      <?php if ((float)$ta['tarif_spp'] > 0): ?>
        — Rp <?= number_format((float)$ta['tarif_spp'], 0, ',', '.') ?>/bln
      <?php endif; ?>
      <?= (int)$ta['is_aktif'] === 1 ? ' (aktif)' : '' ?>
    </option>
  <?php endforeach; ?>
</select>
</div>
</div>

<div class="row">
<div class="col-md-6 mb-3">
<label class="form-label">Nama Orang Tua / Wali</label>
<input type="text" name="nama_ortu" class="form-control" value="<?= e($old['nama_ortu']) ?>">
</div>
<div class="col-md-6 mb-3">
<label class="form-label">No. HP Orang Tua</label>
<input type="text" name="no_hp_ortu" class="form-control" value="<?= e($old['no_hp_ortu']) ?>">
</div>
</div>
<div class="mb-3">
<label class="form-label">Email Orang Tua</label>
<input type="email" name="email_ortu" class="form-control" placeholder="contoh@email.com" value="<?= e($old['email_ortu']) ?>">
<div class="form-text">
  Jika diisi: sistem otomatis buat / tautkan akun portal wali dan kirim email login.
  Kosongkan jika belum perlu akun wali.
</div>
</div>
<div class="mb-3">
<label class="form-label">Alamat</label>
<textarea name="alamat" class="form-control" rows="2"><?= e($old['alamat']) ?></textarea>
</div>
<div class="mb-3">
<label class="form-label">Catatan</label>
<textarea name="catatan" class="form-control" rows="2"><?= e($old['catatan']) ?></textarea>
</div>
<div class="mb-3">
<label class="form-label">Foto Anak</label>
<input type="file" name="foto" class="form-control" accept="image/*">
</div>

<div class="d-flex gap-2">
<button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Simpan</button>
<a href="/admin/siswa/" class="btn btn-secondary">Batal</a>
</div>
</form>
</div>
</div>
</div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../../includes/layout_admin.php';