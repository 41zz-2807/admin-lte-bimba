<?php
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/flash.php';
require_once __DIR__ . '/../../../includes/helpers.php';
require_login();

$pageTitle = 'Pengajar Landing';
global $pdo;

if (isset($_GET['delete'])) {
    $pdo->prepare('DELETE FROM landing_pengajar WHERE id = ?')->execute([(int) $_GET['delete']]);
    set_flash('success', 'Data pengajar dihapus.');
    redirect('admin/landing/pengajar.php');
}

if (isset($_GET['toggle'])) {
    $pdo->prepare('UPDATE landing_pengajar SET is_active = IF(is_active=1,0,1) WHERE id = ?')->execute([(int) $_GET['toggle']]);
    set_flash('success', 'Status diubah.');
    redirect('admin/landing/pengajar.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);

    $foto = trim($_POST['foto_url'] ?? '');
    if (!empty($_FILES['foto_file']['tmp_name'])) {
        $uploaded = upload_image($_FILES['foto_file'], 'landing');
        if ($uploaded === null) {
            set_flash('danger', 'Upload foto gagal (format/ukuran tidak valid).');
            redirect('admin/landing/pengajar.php' . ($id ? '?edit=' . $id : ''));
        }
        $foto = $uploaded;
    }

    $nama    = trim($_POST['nama'] ?? '');
    $jabatan = trim($_POST['jabatan'] ?? '');
    $desc    = trim($_POST['deskripsi'] ?? '');
    $warna   = trim($_POST['warna'] ?? 'text-brand-blue');
    $sort    = (int) ($_POST['sort_order'] ?? 0);
    $aktif   = isset($_POST['is_active']) ? 1 : 0;

    if ($nama === '') {
        set_flash('danger', 'Nama wajib diisi.');
    } elseif ($id > 0) {
        if ($foto === '' && empty($_FILES['foto_file']['tmp_name'])) {
            $old = $pdo->prepare('SELECT foto_url FROM landing_pengajar WHERE id = ?');
            $old->execute([$id]);
            $row = $old->fetch();
            $foto = $row['foto_url'] ?? null;
        }
        $pdo->prepare('UPDATE landing_pengajar SET nama=?, jabatan=?, deskripsi=?, foto_url=?, warna=?, sort_order=?, is_active=? WHERE id=?')
            ->execute([$nama, $jabatan ?: null, $desc ?: null, $foto ?: null, $warna, $sort, $aktif, $id]);
        set_flash('success', 'Pengajar diperbarui.');
    } else {
        $pdo->prepare('INSERT INTO landing_pengajar (nama, jabatan, deskripsi, foto_url, warna, sort_order, is_active) VALUES (?,?,?,?,?,?,?)')
            ->execute([$nama, $jabatan ?: null, $desc ?: null, $foto ?: null, $warna, $sort, $aktif]);
        set_flash('success', 'Pengajar ditambahkan.');
    }
    redirect('admin/landing/pengajar.php');
}

$edit = null;
if (isset($_GET['edit'])) {
    $st = $pdo->prepare('SELECT * FROM landing_pengajar WHERE id = ?');
    $st->execute([(int) $_GET['edit']]);
    $edit = $st->fetch() ?: null;
}

$rows = $pdo->query('SELECT * FROM landing_pengajar ORDER BY sort_order ASC, id ASC')->fetchAll();

$warnaOptions = [
    'text-brand-blue'   => 'Biru',
    'text-brand-yellow' => 'Kuning',
    'text-brand-green'  => 'Hijau',
    'text-brand-red'    => 'Merah',
];

function pengajar_img_src(?string $url): string
{
    if (!$url) return '';
    if (str_starts_with($url, 'http')) return $url;
    return '/uploads/' . ltrim($url, '/');
}

ob_start();
?>
<div class="mb-3">
    <a href="/admin/landing/" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Kembali</a>
</div>

<div class="row">
    <div class="col-md-5">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><?= $edit ? 'Edit Pengajar' : 'Tambah Pengajar' ?></h3>
            </div>
            <div class="card-body">
                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">

                    <div class="mb-3">
                        <label class="form-label">Nama <span class="text-danger">*</span></label>
                        <input type="text" name="nama" class="form-control" required value="<?= e($edit['nama'] ?? '') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Jabatan</label>
                        <input type="text" name="jabatan" class="form-control" placeholder="Kepala Sekolah / Wali Kelas A"
                               value="<?= e($edit['jabatan'] ?? '') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Deskripsi singkat</label>
                        <textarea name="deskripsi" class="form-control" rows="3"><?= e($edit['deskripsi'] ?? '') ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Foto</label>
                        <?php if (!empty($edit['foto_url'])): ?>
                            <div class="mb-2">
                                <img src="<?= e(pengajar_img_src($edit['foto_url'])) ?>" alt="preview"
                                     style="width:80px;height:80px;object-fit:cover;border-radius:8px">
                            </div>
                        <?php endif; ?>
                        <input type="file" name="foto_file" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif">
                        <div class="form-text">JPG/PNG/WebP max 5MB.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">URL Foto (opsional)</label>
                        <input type="text" name="foto_url" class="form-control" placeholder="https://..."
                               value="<?= e($edit['foto_url'] ?? '') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Warna jabatan</label>
                        <select name="warna" class="form-select">
                            <?php foreach ($warnaOptions as $val => $label): ?>
                                <option value="<?= e($val) ?>" <?= ($edit['warna'] ?? 'text-brand-blue') === $val ? 'selected' : '' ?>>
                                    <?= e($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Urutan</label>
                            <input type="number" name="sort_order" class="form-control"
                                   value="<?= (int)($edit['sort_order'] ?? 0) ?>">
                        </div>
                        <div class="col-6 mb-3 d-flex align-items-end">
                            <div class="form-check">
                                <input type="checkbox" name="is_active" class="form-check-input" id="aktif"
                                       <?= !isset($edit['is_active']) || !empty($edit['is_active']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="aktif">Aktif</label>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Simpan</button>
                    <?php if ($edit): ?>
                        <a href="/admin/landing/pengajar.php" class="btn btn-secondary">Batal</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Daftar Pengajar (<?= count($rows) ?>)</h3>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Foto</th>
                            <th>Nama</th>
                            <th>Urutan</th>
                            <th>Status</th>
                            <th style="width:110px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$rows): ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">Belum ada data.</td></tr>
                        <?php else: foreach ($rows as $i => $r): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td>
                                    <?php $src = pengajar_img_src($r['foto_url'] ?? ''); ?>
                                    <?php if ($src): ?>
                                        <img src="<?= e($src) ?>" alt="" style="width:40px;height:40px;object-fit:cover;border-radius:6px">
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?= e($r['nama']) ?></strong>
                                    <br><small class="text-muted"><?= e($r['jabatan'] ?? '') ?></small>
                                </td>
                                <td><?= (int) $r['sort_order'] ?></td>
                                <td>
                                    <a href="?toggle=<?= (int)$r['id'] ?>"
                                       class="badge text-bg-<?= $r['is_active'] ? 'success' : 'secondary' ?>">
                                        <?= $r['is_active'] ? 'Aktif' : 'Nonaktif' ?>
                                    </a>
                                </td>
                                <td>
                                    <a href="?edit=<?= (int)$r['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                    <a href="?delete=<?= (int)$r['id'] ?>" class="btn btn-sm btn-outline-danger"
                                       onclick="return confirm('Hapus data ini?')"><i class="bi bi-trash"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../../includes/layout_admin.php';