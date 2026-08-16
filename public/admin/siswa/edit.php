<?php
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/flash.php';
require_once __DIR__ . '/../../../includes/helpers.php';
require_login();

$pageTitle = 'Edit Siswa';
$error = '';
global $pdo;

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM siswa WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$s = $stmt->fetch();
if (!$s) {
    set_flash('danger', 'Data siswa tidak ditemukan.');
    redirect('admin/siswa/');
}

// Daftar tahun ajaran
$daftarTA = [];
try {
    $daftarTA = $pdo->query(
        'SELECT kode, tarif_spp, is_aktif FROM tahun_ajaran ORDER BY kode DESC'
    )->fetchAll();
} catch (PDOException $e) {
    $daftarTA = [];
}

$waliLinked = $pdo->prepare(
    'SELECT u.id, u.name, u.email, u.is_active, sw.hubungan
     FROM siswa_wali sw
     JOIN users u ON u.id = sw.user_id
     WHERE sw.siswa_id = ? AND u.role = \'wali_murid\''
);
$waliLinked->execute([$id]);
$waliList = $waliLinked->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
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

    if ($data['nama'] === '') {
        $error = 'Nama siswa wajib diisi.';
    } elseif (!in_array($data['jenis_kelamin'], ['L', 'P'], true)) {
        $error = 'Jenis kelamin tidak valid.';
    } elseif (!in_array($data['status'], ['aktif', 'nonaktif', 'lulus'], true)) {
        $error = 'Status tidak valid.';
    } elseif ($data['tahun_ajaran'] !== '' && !in_array($data['tahun_ajaran'], $taValid, true)) {
        $error = 'Tahun ajaran tidak valid.';
    } elseif ($data['email_ortu'] !== '' && !filter_var($data['email_ortu'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email orang tua tidak valid.';
    } else {
        if ($data['nis'] !== '') {
            $cek = $pdo->prepare('SELECT id FROM siswa WHERE nis = ? AND id != ? LIMIT 1');
            $cek->execute([$data['nis'], $id]);
            if ($cek->fetch()) {
                $error = 'NIS sudah dipakai siswa lain.';
            }
        }

        $fotoUrl = $s['foto_url'] ?? null;
        if ($error === '' && !empty($_FILES['foto']['tmp_name'])) {
            $up = upload_image($_FILES['foto'], 'siswa');
            if ($up === null) {
                $error = 'Upload foto gagal (format/ukuran tidak valid).';
            } else {
                $fotoUrl = $up;
            }
        }

        if ($error === '') {
            $upd = $pdo->prepare(
                'UPDATE siswa SET
                 nis=?, nama=?, jenis_kelamin=?, tanggal_lahir=?, nama_ortu=?, no_hp_ortu=?,
                 email_ortu=?, alamat=?, status=?, tahun_ajaran=?, catatan=?, foto_url=?
                 WHERE id=?'
            );
            $upd->execute([
                $data['nis'] ?: null,
                $data['nama'],
                $data['jenis_kelamin'],
                $data['tanggal_lahir'] ?: null,
                $data['nama_ortu'] ?: null,
                $data['no_hp_ortu'] ?: null,
                $data['email_ortu'] ?: null,
                $data['alamat'] ?: null,
                $data['status'],
                $data['tahun_ajaran'] ?: null,
                $data['catatan'] ?: null,
                $fotoUrl,
                $id,
            ]);

            // Auto buat / tautkan akun wali (jika email diisi)
            $waliResult = ensure_wali_for_siswa(
                $id,
                $data['nama'],
                $data['nama_ortu'] ?: null,
                $data['email_ortu'] ?: null,
                'Orang tua'
            );

            set_flash('success', 'Data siswa berhasil diperbarui.' . $waliResult['message']);
            redirect('admin/siswa/');
        }
    }
    $s = array_merge($s, $data);
}

ob_start();
?>
<div class="row justify-content-center">
<div class="col-md-8">
<div class="card">
<div class="card-header"><h3 class="card-title">Edit Siswa</h3></div>
<div class="card-body">
<?php if ($error): ?>
<div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>

<form method="post" action="" enctype="multipart/form-data">
<div class="row">
<div class="col-md-4 mb-3">
<label class="form-label">NIS</label>
<input type="text" name="nis" class="form-control" value="<?= e($s['nis'] ?? '') ?>">
</div>
<div class="col-md-8 mb-3">
<label class="form-label">Nama Siswa <span class="text-danger">*</span></label>
<input type="text" name="nama" class="form-control" required value="<?= e($s['nama']) ?>">
</div>
</div>
<div class="row">
<div class="col-md-4 mb-3">
<label class="form-label">Jenis Kelamin</label>
<select name="jenis_kelamin" class="form-select">
<option value="L" <?= ($s['jenis_kelamin'] ?? '') === 'L' ? 'selected' : '' ?>>Laki-laki</option>
<option value="P" <?= ($s['jenis_kelamin'] ?? '') === 'P' ? 'selected' : '' ?>>Perempuan</option>
</select>
</div>
<div class="col-md-4 mb-3">
<label class="form-label">Tanggal Lahir</label>
<input type="date" name="tanggal_lahir" class="form-control" value="<?= e($s['tanggal_lahir'] ?? '') ?>">
</div>
<div class="col-md-4 mb-3">
<label class="form-label">Status</label>
<select name="status" class="form-select">
<option value="aktif" <?= ($s['status'] ?? '') === 'aktif' ? 'selected' : '' ?>>Aktif</option>
<option value="nonaktif" <?= ($s['status'] ?? '') === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
<option value="lulus" <?= ($s['status'] ?? '') === 'lulus' ? 'selected' : '' ?>>Lulus</option>
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
      <?= ($s['tahun_ajaran'] ?? '') === $ta['kode'] ? 'selected' : '' ?>>
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
<input type="text" name="nama_ortu" class="form-control" value="<?= e($s['nama_ortu'] ?? '') ?>">
</div>
<div class="col-md-6 mb-3">
<label class="form-label">No. HP Orang Tua</label>
<input type="text" name="no_hp_ortu" class="form-control" value="<?= e($s['no_hp_ortu'] ?? '') ?>">
</div>
</div>
<div class="mb-3">
<label class="form-label">Email Orang Tua</label>
<input type="email" name="email_ortu" class="form-control" placeholder="contoh@email.com"
       value="<?= e($s['email_ortu'] ?? '') ?>">
<div class="form-text">
  Jika diisi: sistem otomatis buat / tautkan akun portal wali dan kirim email.
  Kosongkan jika belum perlu.
</div>
</div>
<div class="mb-3">
<label class="form-label">Alamat</label>
<textarea name="alamat" class="form-control" rows="2"><?= e($s['alamat'] ?? '') ?></textarea>
</div>
<div class="mb-3">
<label class="form-label">Catatan</label>
<textarea name="catatan" class="form-control" rows="2"><?= e($s['catatan'] ?? '') ?></textarea>
</div>
<div class="mb-3">
<label class="form-label">Foto Anak</label>
<input type="file" name="foto" class="form-control" accept="image/*">
<?php if (!empty($s['foto_url'])): ?>
<img src="/uploads/<?= e($s['foto_url']) ?>" height="80" class="mt-2 rounded">
<?php endif; ?>
</div>

<hr>
<div class="card bg-light border mb-3">
  <div class="card-body">
    <h6 class="mb-2">Portal Wali Murid (tertaut)</h6>
    <?php if ($waliList): ?>
      <ul class="mb-0">
        <?php foreach ($waliList as $w): ?>
          <li>
            <?= e($w['name']) ?> — <?= e($w['email']) ?> (<?= e($w['hubungan']) ?>)
            <span class="badge text-bg-<?= $w['is_active'] ? 'success' : 'secondary' ?>">
              <?= $w['is_active'] ? 'Aktif' : 'Nonaktif' ?>
            </span>
            <a href="/admin/wali/edit.php?id=<?= (int)$w['id'] ?>" class="small">Kelola</a>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <p class="text-muted small mb-0">Belum ada akun wali tertaut. Isi email ortu lalu simpan untuk membuat/menautkan.</p>
    <?php endif; ?>
  </div>
</div>

<div class="d-flex gap-2">
<button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Update</button>
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