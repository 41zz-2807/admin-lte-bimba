<?php
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/flash.php';

require_login();

$pageTitle = 'Tambah Guru / Staff';
$error = '';
$old = [
    'nip' => '', 'nama' => '', 'jenis_kelamin' => 'L', 'jabatan' => '',
    'no_hp' => '', 'email' => '', 'alamat' => '', 'status' => 'aktif', 'catatan' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = [
        'nip'           => trim($_POST['nip'] ?? ''),
        'nama'          => trim($_POST['nama'] ?? ''),
        'jenis_kelamin' => $_POST['jenis_kelamin'] ?? 'L',
        'jabatan'       => trim($_POST['jabatan'] ?? ''),
        'no_hp'         => trim($_POST['no_hp'] ?? ''),
        'email'         => trim($_POST['email'] ?? ''),
        'alamat'        => trim($_POST['alamat'] ?? ''),
        'status'        => $_POST['status'] ?? 'aktif',
        'catatan'       => trim($_POST['catatan'] ?? ''),
    ];

    if ($old['nama'] === '') {
        $error = 'Nama wajib diisi.';
    } elseif (!in_array($old['jenis_kelamin'], ['L', 'P'], true)) {
        $error = 'Jenis kelamin tidak valid.';
    } elseif (!in_array($old['status'], ['aktif', 'nonaktif'], true)) {
        $error = 'Status tidak valid.';
    } elseif ($old['email'] !== '' && !filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } else {
        global $pdo;

        if ($old['nip'] !== '') {
            $cek = $pdo->prepare('SELECT id FROM guru_staff WHERE nip = ? LIMIT 1');
            $cek->execute([$old['nip']]);
            if ($cek->fetch()) {
                $error = 'NIP sudah terdaftar.';
            }
        }

        if ($error === '') {
            $ins = $pdo->prepare('INSERT INTO guru_staff
                (nip, nama, jenis_kelamin, jabatan, no_hp, email, alamat, status, catatan)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $ins->execute([
                $old['nip'] ?: null,
                $old['nama'],
                $old['jenis_kelamin'],
                $old['jabatan'] ?: null,
                $old['no_hp'] ?: null,
                $old['email'] ?: null,
                $old['alamat'] ?: null,
                $old['status'],
                $old['catatan'] ?: null,
            ]);

            set_flash('success', 'Data berhasil ditambahkan.');
            redirect('admin/guru/');
        }
    }
}

ob_start();
?>
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Tambah Guru / Staff</h3></div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= e($error) ?></div>
                <?php endif; ?>

                <form method="post" action="">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">NIP</label>
                            <input type="text" name="nip" class="form-control" value="<?= e($old['nip']) ?>">
                        </div>
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Nama <span class="text-danger">*</span></label>
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
                            <label class="form-label">Jabatan</label>
                            <select name="jabatan" class="form-select">
                                <option value="">-- Pilih Jabatan --</option>
                                <option value="Kepala Sekolah" <?= ($old['jabatan'] ?? '') === 'Kepala Sekolah' ? 'selected' : '' ?>>Kepala Sekolah</option>
                                <option value="Guru" <?= ($old['jabatan'] ?? '') === 'Guru' ? 'selected' : '' ?>>Guru</option>
                                <option value="Staff" <?= ($old['jabatan'] ?? '') === 'Staff' ? 'selected' : '' ?>>Staff</option>
                                <option value="Admin" <?= ($old['jabatan'] ?? '') === 'Admin' ? 'selected' : '' ?>>Admin</option>
                                <option value="Karyawan" <?= ($old['jabatan'] ?? '') === 'Karyawan' ? 'selected' : '' ?>>Karyawan</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="aktif" <?= $old['status'] === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                                <option value="nonaktif" <?= $old['status'] === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">No. HP</label>
                            <input type="text" name="no_hp" class="form-control" value="<?= e($old['no_hp']) ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="<?= e($old['email']) ?>">
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
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Simpan</button>
                        <a href="/admin/guru/" class="btn btn-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../../includes/layout_admin.php';