<?php
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/flash.php';

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'nis'           => trim($_POST['nis'] ?? ''),
        'nama'          => trim($_POST['nama'] ?? ''),
        'jenis_kelamin' => $_POST['jenis_kelamin'] ?? 'L',
        'tanggal_lahir' => $_POST['tanggal_lahir'] ?? '',
        'nama_ortu'     => trim($_POST['nama_ortu'] ?? ''),
        'no_hp_ortu'    => trim($_POST['no_hp_ortu'] ?? ''),
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
    } else {
        if ($data['nis'] !== '') {
            $cek = $pdo->prepare('SELECT id FROM siswa WHERE nis = ? AND id != ? LIMIT 1');
            $cek->execute([$data['nis'], $id]);
            if ($cek->fetch()) {
                $error = 'NIS sudah dipakai siswa lain.';
            }
        }

        if ($error === '') {
            $upd = $pdo->prepare('UPDATE siswa SET
                nis=?, nama=?, jenis_kelamin=?, tanggal_lahir=?, nama_ortu=?, no_hp_ortu=?, alamat=?, status=?, catatan=?
                WHERE id=?');
            $upd->execute([
                $data['nis'] ?: null,
                $data['nama'],
                $data['jenis_kelamin'],
                $data['tanggal_lahir'] ?: null,
                $data['nama_ortu'] ?: null,
                $data['no_hp_ortu'] ?: null,
                $data['alamat'] ?: null,
                $data['status'],
                $data['catatan'] ?: null,
                $id,
            ]);

            set_flash('success', 'Data siswa berhasil diperbarui.');
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

                <form method="post" action="">
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
                            <input type="date" name="tanggal_lahir" class="form-control"
                                   value="<?= e($s['tanggal_lahir'] ?? '') ?>">
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
                        <label class="form-label">Alamat</label>
                        <textarea name="alamat" class="form-control" rows="2"><?= e($s['alamat'] ?? '') ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Catatan</label>
                        <textarea name="catatan" class="form-control" rows="2"><?= e($s['catatan'] ?? '') ?></textarea>
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