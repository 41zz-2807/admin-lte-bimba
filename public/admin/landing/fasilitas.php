<?php
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/flash.php';
require_login();

$pageTitle = 'Fasilitas Landing';
global $pdo;

if (isset($_GET['delete'])) {
    $pdo->prepare('DELETE FROM landing_fasilitas WHERE id = ?')->execute([(int) $_GET['delete']]);
    set_flash('success', 'Fasilitas dihapus.');
    redirect('admin/landing/fasilitas.php');
}

if (isset($_GET['toggle'])) {
    $pdo->prepare('UPDATE landing_fasilitas SET is_active = IF(is_active=1,0,1) WHERE id = ?')->execute([(int) $_GET['toggle']]);
    set_flash('success', 'Status diubah.');
    redirect('admin/landing/fasilitas.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id    = (int) ($_POST['id'] ?? 0);
    $icon  = trim($_POST['icon'] ?? 'fa-star');
    $color = trim($_POST['color'] ?? 'bg-brand-blue');
    $title = trim($_POST['title'] ?? '');
    $desc  = trim($_POST['description'] ?? '');
    $sort  = (int) ($_POST['sort_order'] ?? 0);
    $aktif = isset($_POST['is_active']) ? 1 : 0;

    if ($title === '') {
        set_flash('danger', 'Judul wajib diisi.');
    } elseif ($id > 0) {
        $pdo->prepare('UPDATE landing_fasilitas SET icon=?, color=?, title=?, description=?, sort_order=?, is_active=? WHERE id=?')
            ->execute([$icon, $color, $title, $desc, $sort, $aktif, $id]);
        set_flash('success', 'Fasilitas diperbarui.');
    } else {
        $pdo->prepare('INSERT INTO landing_fasilitas (icon, color, title, description, sort_order, is_active) VALUES (?,?,?,?,?,?)')
            ->execute([$icon, $color, $title, $desc, $sort, $aktif]);
        set_flash('success', 'Fasilitas ditambahkan.');
    }
    redirect('admin/landing/fasilitas.php');
}

$edit = null;
if (isset($_GET['edit'])) {
    $st = $pdo->prepare('SELECT * FROM landing_fasilitas WHERE id = ?');
    $st->execute([(int) $_GET['edit']]);
    $edit = $st->fetch() ?: null;
}

$rows = $pdo->query('SELECT * FROM landing_fasilitas ORDER BY sort_order ASC, id ASC')->fetchAll();

$iconOptions = [
    'fa-puzzle-piece' => 'Puzzle (Metode Bermain)',
    'fa-palette'      => 'Palette (Kreativitas)',
    'fa-tree'         => 'Pohon (Outing)',
    'fa-shield-halved'=> 'Perisai (Keamanan)',
    'fa-book'         => 'Buku',
    'fa-music'        => 'Musik',
    'fa-futbol'       => 'Olahraga',
    'fa-heart'        => 'Hati',
    'fa-star'         => 'Bintang',
    'fa-graduation-cap' => 'Wisuda',
];

$colorOptions = [
    'bg-brand-blue'   => 'Biru',
    'bg-brand-yellow' => 'Kuning',
    'bg-brand-green'  => 'Hijau',
    'bg-brand-red'    => 'Merah',
];

ob_start();
?>
<div class="mb-3">
    <a href="/admin/landing/" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Kembali</a>
</div>

<div class="row">
    <div class="col-md-5">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><?= $edit ? 'Edit Fasilitas' : 'Tambah Fasilitas' ?></h3>
            </div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">

                    <div class="mb-3">
                        <label class="form-label">Judul <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" required
                               value="<?= e($edit['title'] ?? '') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea name="description" class="form-control" rows="3"><?= e($edit['description'] ?? '') ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Icon (Font Awesome)</label>
                        <select name="icon" class="form-select">
                            <?php foreach ($iconOptions as $val => $label): ?>
                                <option value="<?= e($val) ?>" <?= ($edit['icon'] ?? '') === $val ? 'selected' : '' ?>>
                                    <?= e($label) ?> (<?= e($val) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Bisa juga ketik manual class FA, contoh: fa-bus</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Warna Icon</label>
                        <select name="color" class="form-select">
                            <?php foreach ($colorOptions as $val => $label): ?>
                                <option value="<?= e($val) ?>" <?= ($edit['color'] ?? 'bg-brand-blue') === $val ? 'selected' : '' ?>>
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
                        <a href="/admin/landing/fasilitas.php" class="btn btn-secondary">Batal</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Daftar Fasilitas (<?= count($rows) ?>)</h3>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Icon</th>
                            <th>Judul</th>
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
                                <td><code><?= e($r['icon']) ?></code></td>
                                <td>
                                    <strong><?= e($r['title']) ?></strong>
                                    <br><small class="text-muted"><?= e(mb_strimwidth($r['description'] ?? '', 0, 40, '…')) ?></small>
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
                                       onclick="return confirm('Hapus fasilitas ini?')"><i class="bi bi-trash"></i></a>
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
