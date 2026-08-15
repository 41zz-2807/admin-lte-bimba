<?php
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/flash.php';

require_login();

$pageTitle = 'Edit Guru / Staff';
$error = '';
global $pdo;

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM guru_staff WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$r = $stmt->fetch();

if (!$r) {
    set_flash('danger', 'Data tidak ditemukan.');
    redirect('admin/guru/');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
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

    if ($data['nama'] === '') {
        $error = 'Nama wajib diisi.';
    } elseif (!in_array($data['jenis_kelamin'], ['L', 'P'], true)) {
        $error = 'Jenis kelamin tidak valid.';
    } elseif (!in_array($data['status'], ['aktif', 'nonaktif'], true)) {
        $error = 'Status tidak valid.';
    } elseif ($data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } else {
        if ($data['nip'] !== '') {
            $cek = $pdo->prepare('SELECT id FROM guru_staff WHERE nip = ? AND id != ? LIMIT 1');
            $cek->execute([$data['nip'], $id]);
            if ($cek->fetch()) {
                $error = 'NIP sudah dipakai data lain.';
            }
        }

        if ($error === '') {
            $upd = $pdo->prepare('UPDATE guru_staff SET
                nip=?, nama=?, jenis_kelamin=?, jabatan=?, no_hp=?, email=?, alamat=?, status=?, catatan=?
                WHERE id=?');
            $upd->execute([
                $data['nip'] ?: null,
                $data['nama'],
                $data['jenis_kelamin'],
                $data['jabatan'] ?: null,
                $data['no_hp'] ?: null,
                $data['email'] ?: null,
                $data['alamat'] ?: null,
                $data['status'],
                $data['catatan'] ?: null,
                $id,
            ]);

            set_flash('success', 'Data berhasil diperbarui.');
            redirect('admin/guru/');
        }
    }

    $r = array_merge($r, $data);
}

ob_start();
?>
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Edit Guru / Staff</h3></div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= e($error) ?></div>
                <?php endif; ?>

                <form method="post" action="">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">NIP</label>
                            <input type="text" name="nip" class="form-control" value="<?= e($r['nip'] ?? '') ?>">
                        </div>
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Nama <span class="text-danger">*</span></label>
                            <input type="text" name="nama" class="form-control" required value="<?= e($r['nama']) ?>">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Jenis Kelamin</label>
                            <select name="jenis_kelamin" class="form-select">
                                <option value="L" <?= ($r['jenis_kelamin'] ?? '') === 'L' ? 'selected' : '' ?>>Laki-laki</option>
                                <option value="P" <?= ($r['jenis_kelamin'] ?? '') === 'P' ? 'selected' : '' ?>>Perempuan</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Jabatan</label>
                             <select name="jabatan" class="form-select">
                                    <option value="">-- Pilih Jabatan --</option>
                                    <option value="Kepala Sekolah" <?= ($r['jabatan'] ?? '') === 'Kepala Sekolah' ? 'selected' : '' ?>>Kepala Sekolah</option>
                                    <option value="Guru" <?= ($r['jabatan'] ?? '') === 'Guru' ? 'selected' : '' ?>>Guru</option>
                                    <option value="Staff" <?= ($r['jabatan'] ?? '') === 'Staff' ? 'selected' : '' ?>>Staff</option>
                                    <option value="Admin" <?= ($r['jabatan'] ?? '') === 'Admin' ? 'selected' : '' ?>>Admin</option>
                                    <option value="Karyawan" <?= ($r['jabatan'] ?? '') === 'Karyawan' ? 'selected' : '' ?>>Karyawan</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="aktif" <?= ($r['status'] ?? '') === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                                <option value="nonaktif" <?= ($r['status'] ?? '') === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">No. HP</label>
                            <input type="text" name="no_hp" class="form-control" value="<?= e($r['no_hp'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="<?= e($r['email'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Alamat</label>
                        <textarea name="alamat" class="form-control" rows="2"><?= e($r['alamat'] ?? '') ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Catatan</label>
                        <textarea name="catatan" class="form-control" rows="2"><?= e($r['catatan'] ?? '') ?></textarea>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Update</button>
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