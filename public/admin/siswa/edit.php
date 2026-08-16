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
        'catatan'       => trim($_POST['catatan'] ?? ''),
    ];

    if ($data['nama'] === '') {
        $error = 'Nama siswa wajib diisi.';
    } elseif (!in_array($data['jenis_kelamin'], ['L', 'P'], true)) {
        $error = 'Jenis kelamin tidak valid.';
    } elseif (!in_array($data['status'], ['aktif', 'nonaktif', 'lulus'], true)) {
        $error = 'Status tidak valid.';
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
            $upd = $pdo->prepare('UPDATE siswa SET
                nis=?, nama=?, jenis_kelamin=?, tanggal_lahir=?, nama_ortu=?, no_hp_ortu=?, email_ortu=?, alamat=?, status=?, catatan=?, foto_url=?
                WHERE id=?');
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
                $data['catatan'] ?: null,
                $fotoUrl,
                $id,
            ]);

            $pesanWali = '';
            if (isset($_POST['buat_wali'])) {
                $waliEmail = trim($_POST['wali_email'] ?? '');
                $waliPass  = $_POST['wali_password'] ?? '';
                $waliNama  = trim($_POST['wali_nama'] ?? '') ?: ($data['nama_ortu'] ?: $data['nama'] . ' - Wali');
                $hubungan  = trim($_POST['wali_hubungan'] ?? 'Orang tua') ?: 'Orang tua';

                if ($waliEmail === '' || !filter_var($waliEmail, FILTER_VALIDATE_EMAIL)) {
                    $pesanWali = ' Email wali tidak valid — akun tidak dibuat.';
                } elseif (strlen($waliPass) < 8) {
                    $pesanWali = ' Password wali < 8 karakter — akun tidak dibuat.';
                } else {
                    $cek = $pdo->prepare('SELECT id, role FROM users WHERE email = ? LIMIT 1');
                    $cek->execute([$waliEmail]);
                    $exist = $cek->fetch();
                    if ($exist) {
                        if ($exist['role'] === 'wali_murid') {
                            $pdo->prepare('INSERT IGNORE INTO siswa_wali (user_id, siswa_id, hubungan) VALUES (?,?,?)')
                                ->execute([(int)$exist['id'], $id, $hubungan]);
                            $pesanWali = ' Ditautkan ke akun wali yang sudah ada.';
                        } else {
                            $pesanWali = ' Email sudah dipakai akun non-wali.';
                        }
                    } else {
                        $hash = password_hash($waliPass, PASSWORD_DEFAULT);
                        $pdo->prepare('INSERT INTO users (name, email, password, role, is_active) VALUES (?,?,?,?,1)')
                            ->execute([$waliNama, $waliEmail, $hash, 'wali_murid']);
                        $uid = (int) $pdo->lastInsertId();
                        $pdo->prepare('INSERT INTO siswa_wali (user_id, siswa_id, hubungan) VALUES (?,?,?)')
                            ->execute([$uid, $id, $hubungan]);
                        $pesanWali = ' Akun wali dibuat (' . $waliEmail . ').';
                    }
                }
            }

            set_flash('success', 'Data siswa berhasil diperbarui.' . $pesanWali);
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
<input type="email" name="email_ortu" class="form-control" placeholder="contoh@email.com" value="<?= e($s['email_ortu'] ?? '') ?>">
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
    <h6 class="mb-2">Portal Wali Murid</h6>
    <?php if ($waliList): ?>
      <ul class="mb-3">
        <?php foreach ($waliList as $w): ?>
          <li>
            <?= e($w['name']) ?> — <?= e($w['email']) ?> (<?= e($w['hubungan']) ?>)
            <span class="badge text-bg-<?= $w['is_active'] ? 'success' : 'secondary' ?>"><?= $w['is_active'] ? 'Aktif' : 'Nonaktif' ?></span>
            <a href="/admin/wali/edit.php?id=<?= (int)$w['id'] ?>" class="small">Kelola</a>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <p class="text-muted small mb-2">Belum ada akun wali tertaut.</p>
    <?php endif; ?>

    <div class="form-check mb-2">
      <input type="checkbox" class="form-check-input" name="buat_wali" id="buat_wali"
             onchange="document.getElementById('wali_fields').style.display=this.checked?'block':'none'">
      <label class="form-check-label fw-semibold" for="buat_wali">Buat / tautkan akun wali</label>
    </div>
    <div id="wali_fields" style="display:none">
      <div class="row g-2">
        <div class="col-md-6">
          <label class="form-label">Nama Wali</label>
          <input type="text" name="wali_nama" class="form-control">
        </div>
        <div class="col-md-6">
          <label class="form-label">Hubungan</label>
          <input type="text" name="wali_hubungan" class="form-control" value="Orang tua">
        </div>
        <div class="col-md-6">
          <label class="form-label">Email login *</label>
          <input type="email" name="wali_email" class="form-control">
        </div>
        <div class="col-md-6">
          <label class="form-label">Password *</label>
          <input type="text" name="wali_password" class="form-control" minlength="8">
        </div>
      </div>
    </div>
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