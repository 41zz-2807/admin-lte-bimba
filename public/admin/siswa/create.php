<?php
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/flash.php';
require_once __DIR__ . '/../../../includes/helpers.php';
require_login();

$pageTitle = 'Tambah Siswa';
$error = '';
$old = [
    'nis' => '', 'nama' => '', 'jenis_kelamin' => 'L',
    'tanggal_lahir' => '', 'nama_ortu' => '', 'no_hp_ortu' => '',
    'email_ortu' => '', 'alamat' => '', 'status' => 'aktif', 'catatan' => '',
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
        'catatan'       => trim($_POST['catatan'] ?? ''),
    ];

    if ($old['nama'] === '') {
        $error = 'Nama siswa wajib diisi.';
    } elseif (!in_array($old['jenis_kelamin'], ['L', 'P'], true)) {
        $error = 'Jenis kelamin tidak valid.';
    } elseif (!in_array($old['status'], ['aktif', 'nonaktif', 'lulus'], true)) {
        $error = 'Status tidak valid.';
    } elseif ($old['email_ortu'] !== '' && !filter_var($old['email_ortu'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email orang tua tidak valid.';
    } else {
        global $pdo;

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
            $ins = $pdo->prepare('INSERT INTO siswa
                (nis, nama, jenis_kelamin, tanggal_lahir, nama_ortu, no_hp_ortu, email_ortu, alamat, status, catatan, foto_url)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
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
                $old['catatan'] ?: null,
                $fotoUrl,
            ]);

            $siswaId = (int) $pdo->lastInsertId();
            $pesanWali = '';

            if (isset($_POST['buat_wali'])) {
                $waliEmail = trim($_POST['wali_email'] ?? '');
                $waliPass  = $_POST['wali_password'] ?? '';
                $waliNama  = trim($_POST['wali_nama'] ?? '') ?: ($old['nama_ortu'] ?: $old['nama'] . ' - Wali');
                $hubungan  = trim($_POST['wali_hubungan'] ?? 'Orang tua') ?: 'Orang tua';

                if ($waliEmail === '' || !filter_var($waliEmail, FILTER_VALIDATE_EMAIL)) {
                    $pesanWali = ' Siswa tersimpan, email wali tidak valid — akun tidak dibuat.';
                } elseif (strlen($waliPass) < 8) {
                    $pesanWali = ' Siswa tersimpan, password wali < 8 karakter — akun tidak dibuat.';
                } else {
                    $cek = $pdo->prepare('SELECT id, role FROM users WHERE email = ? LIMIT 1');
                    $cek->execute([$waliEmail]);
                    $exist = $cek->fetch();
                    if ($exist) {
                        if ($exist['role'] === 'wali_murid') {
                            $pdo->prepare('INSERT IGNORE INTO siswa_wali (user_id, siswa_id, hubungan) VALUES (?,?,?)')
                                ->execute([(int)$exist['id'], $siswaId, $hubungan]);
                            $pesanWali = ' Siswa tersimpan & ditautkan ke akun wali yang sudah ada.';
                        } else {
                            $pesanWali = ' Siswa tersimpan, email sudah dipakai akun non-wali.';
                        }
                    } else {
                        $hash = password_hash($waliPass, PASSWORD_DEFAULT);
                        $pdo->prepare('INSERT INTO users (name, email, password, role, is_active) VALUES (?,?,?,?,1)')
                            ->execute([$waliNama, $waliEmail, $hash, 'wali_murid']);
                        $uid = (int) $pdo->lastInsertId();
                        $pdo->prepare('INSERT INTO siswa_wali (user_id, siswa_id, hubungan) VALUES (?,?,?)')
                            ->execute([$uid, $siswaId, $hubungan]);
                        $pesanWali = ' Akun wali dibuat (' . $waliEmail . ').';
                    }
                }
            }

            set_flash('success', 'Data siswa berhasil ditambahkan.' . $pesanWali);
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

<hr>
<div class="card bg-light border mb-3">
  <div class="card-body">
    <div class="form-check mb-3">
      <input type="checkbox" class="form-check-input" name="buat_wali" id="buat_wali"
             onchange="document.getElementById('wali_fields').style.display=this.checked?'block':'none'">
      <label class="form-check-label fw-semibold" for="buat_wali">Buat akun portal Wali Murid</label>
    </div>
    <div id="wali_fields" style="display:none">
      <div class="row g-2">
        <div class="col-md-6">
          <label class="form-label">Nama Wali</label>
          <input type="text" name="wali_nama" class="form-control" placeholder="Kosongkan = nama ortu">
        </div>
        <div class="col-md-6">
          <label class="form-label">Hubungan</label>
          <input type="text" name="wali_hubungan" class="form-control" value="Orang tua" placeholder="Ayah / Ibu / Wali">
        </div>
        <div class="col-md-6">
          <label class="form-label">Email login *</label>
          <input type="email" name="wali_email" class="form-control" placeholder="ortu@email.com">
        </div>
        <div class="col-md-6">
          <label class="form-label">Password *</label>
          <input type="text" name="wali_password" class="form-control" minlength="8" placeholder="min. 8 karakter">
        </div>
      </div>
      <div class="form-text mt-1">Login di /wali/login.php</div>
    </div>
  </div>
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