<?php
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/flash.php';
require_login();
$pageTitle = 'Testimoni';
global $pdo;

if (isset($_GET['delete'])) {
    $pdo->prepare('DELETE FROM landing_testimoni WHERE id = ?')->execute([(int)$_GET['delete']]);
    set_flash('success', 'Testimoni dihapus.');
    redirect('admin/landing/testimoni.php');
}
if (isset($_GET['toggle'])) {
    $pdo->prepare('UPDATE landing_testimoni SET is_active = IF(is_active=1,0,1) WHERE id = ?')->execute([(int)$_GET['toggle']]);
    set_flash('success', 'Status diubah.');
    redirect('admin/landing/testimoni.php');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $nama = trim($_POST['nama'] ?? '');
    $relasi = trim($_POST['relasi'] ?? '');
    $isi = trim($_POST['isi'] ?? '');
    $sort = (int)($_POST['sort_order'] ?? 0);
    $aktif = isset($_POST['is_active']) ? 1 : 0;
    if ($nama === '' || $isi === '') {
        set_flash('danger', 'Nama dan isi wajib diisi.');
    } elseif ($id > 0) {
        $pdo->prepare('UPDATE landing_testimoni SET nama=?, relasi=?, isi=?, sort_order=?, is_active=? WHERE id=?')
            ->execute([$nama, $relasi, $isi, $sort, $aktif, $id]);
        set_flash('success', 'Testimoni diperbarui.');
    } else {
        $pdo->prepare('INSERT INTO landing_testimoni (nama, relasi, isi, sort_order, is_active) VALUES (?,?,?,?,?)')
            ->execute([$nama, $relasi, $isi, $sort, $aktif]);
        set_flash('success', 'Testimoni ditambahkan.');
    }
    redirect('admin/landing/testimoni.php');
}
$edit = null;
if (isset($_GET['edit'])) {
    $st = $pdo->prepare('SELECT * FROM landing_testimoni WHERE id = ?');
    $st->execute([(int)$_GET['edit']]);
    $edit = $st->fetch() ?: null;
}
$rows = $pdo->query('SELECT * FROM landing_testimoni ORDER BY sort_order ASC, id ASC')->fetchAll();

ob_start();
?>
<div class="mb-3">
    <a href="/admin/landing/" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Kembali</a>
</div>
<div class="row">
    <div class="col-md-5">
        <div class="card">
            <div class="card-header"><h3 class="card-title"><?= $edit ? 'Edit' : 'Tambah' ?> Testimoni</h3></div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
                    <div class="mb-3">
                        <label class="form-label">Nama</label>
                        <input type="text" name="nama" class="form-control" required value="<?= e($edit['nama'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Relasi</label>
                        <input type="text" name="relasi" class="form-control" placeholder="Ibunda dari Ananda ..."
                               value="<?= e($edit['relasi'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Isi Testimoni</label>
                        <textarea name="isi" class="form-control" rows="4" required><?= e($edit['isi'] ?? '') ?></textarea>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Urutan</label>
                            <input type="number" name="sort_order" class="form-control" value="<?= (int)($edit['sort_order'] ?? 0) ?>">
                        </div>
                        <div class="col-6 mb-3 d-flex align-items-end">
                            <div class="form-check">
                                <input type="checkbox" name="is_active" class="form-check-input" id="aktif"
                                       <?= !isset($edit['is_active']) || $edit['is_active'] ? 'checked' : '' ?>>
                                <label for="aktif" class="form-check-label">Aktif</label>
                            </div>
                        </div>
                    </div>
                    <button class="btn btn-primary" type="submit"><i class="bi bi-save me-1"></i> Simpan</button>
                    <?php if ($edit): ?><a href="/admin/landing/testimoni.php" class="btn btn-secondary">Batal</a><?php endif; ?>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-7">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Daftar (<?= count($rows) ?>)</h3></div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover mb-0">
                    <thead><tr><th>#</th><th>Nama</th><th>Isi</th><th>Status</th><th>Aksi</th></tr></thead>
                    <tbody>
                    <?php if (!$rows): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">Belum ada data.</td></tr>
                    <?php else: foreach ($rows as $i => $r): ?>
                        <tr>
                            <td><?= $i+1 ?></td>
                            <td><?= e($r['nama']) ?><br><small class="text-muted"><?= e($r['relasi'] ?? '') ?></small></td>
                            <td><?= e(mb_strimwidth($r['isi'], 0, 50, '…')) ?></td>
                            <td><a href="?toggle=<?= (int)$r['id'] ?>" class="badge text-bg-<?= $r['is_active']?'success':'secondary' ?>"><?= $r['is_active']?'Aktif':'Nonaktif' ?></a></td>
                            <td>
                                <a href="?edit=<?= (int)$r['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                <a href="?delete=<?= (int)$r['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus?')"><i class="bi bi-trash"></i></a>
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
