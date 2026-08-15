<?php
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/flash.php';
require_once __DIR__ . '/../../../includes/helpers.php';
require_login();

$pageTitle = 'Hero Slider';
global $pdo;

if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $pdo->prepare('DELETE FROM landing_hero WHERE id = ?')->execute([$id]);
    set_flash('success', 'Slide dihapus.');
    redirect('admin/landing/hero.php');
}

if (isset($_GET['toggle'])) {
    $id = (int) $_GET['toggle'];
    $pdo->prepare('UPDATE landing_hero SET is_active = IF(is_active=1,0,1) WHERE id = ?')->execute([$id]);
    set_flash('success', 'Status slide diubah.');
    redirect('admin/landing/hero.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);

    $imageUrl = trim($_POST['image_url'] ?? '');
    if (!empty($_FILES['image_file']['tmp_name'])) {
        $uploaded = upload_image($_FILES['image_file'], 'landing');
        if ($uploaded === null) {
            set_flash('danger', 'Upload gambar gagal (format/ukuran tidak valid).');
            redirect('admin/landing/hero.php' . ($id ? '?edit=' . $id : ''));
        }
        $imageUrl = $uploaded;
    }

    $data = [
        trim($_POST['badge'] ?? '') ?: null,
        trim($_POST['title'] ?? ''),
        trim($_POST['description'] ?? '') ?: null,
        $imageUrl ?: null,
        trim($_POST['cta_text'] ?? '') ?: null,
        trim($_POST['cta_link'] ?? '') ?: null,
        (int) ($_POST['sort_order'] ?? 0),
        isset($_POST['is_active']) ? 1 : 0,
    ];

    if ($data[1] === '') {
        set_flash('danger', 'Judul wajib diisi.');
    } elseif ($id > 0) {
        if ($imageUrl === '' && empty($_FILES['image_file']['tmp_name'])) {
            $old = $pdo->prepare('SELECT image_url FROM landing_hero WHERE id = ?');
            $old->execute([$id]);
            $row = $old->fetch();
            $data[3] = $row['image_url'] ?? null;
        }
        $pdo->prepare('UPDATE landing_hero SET badge=?, title=?, description=?, image_url=?, cta_text=?, cta_link=?, sort_order=?, is_active=? WHERE id=?')
            ->execute([...$data, $id]);
        set_flash('success', 'Slide diperbarui.');
    } else {
        $pdo->prepare('INSERT INTO landing_hero (badge, title, description, image_url, cta_text, cta_link, sort_order, is_active) VALUES (?,?,?,?,?,?,?,?)')
            ->execute($data);
        set_flash('success', 'Slide ditambahkan.');
    }
    redirect('admin/landing/hero.php');
}

$edit = null;
if (isset($_GET['edit'])) {
    $st = $pdo->prepare('SELECT * FROM landing_hero WHERE id = ?');
    $st->execute([(int) $_GET['edit']]);
    $edit = $st->fetch() ?: null;
}

$rows = $pdo->query('SELECT * FROM landing_hero ORDER BY sort_order ASC, id ASC')->fetchAll();

function hero_img_src(?string $url): string
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
                <h3 class="card-title"><?= $edit ? 'Edit Slide' : 'Tambah Slide' ?></h3>
            </div>
            <div class="card-body">
                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">

                    <div class="mb-3">
                        <label class="form-label">Badge (opsional)</label>
                        <input type="text" name="badge" class="form-control" placeholder="PENDAFTARAN TELAH DIBUKA"
                               value="<?= e($edit['badge'] ?? '') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Judul <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" required
                               value="<?= e($edit['title'] ?? '') ?>">
                        <div class="form-text">Boleh HTML ringan, contoh: Belajar &lt;br&gt;Menyenangkan</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea name="description" class="form-control" rows="3"><?= e($edit['description'] ?? '') ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Gambar Slide</label>
                        <?php if (!empty($edit['image_url'])): ?>
                            <div class="mb-2">
                                <img src="<?= e(hero_img_src($edit['image_url'])) ?>" alt="preview"
                                     style="max-height:120px;border-radius:8px">
                            </div>
                        <?php endif; ?>
                        <input type="file" name="image_file" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif">
                        <div class="form-text">JPG/PNG/WebP, max 5MB.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">URL Gambar (opsional, jika tidak upload)</label>
                        <input type="text" name="image_url" class="form-control" placeholder="https://..."
                               value="<?= e($edit['image_url'] ?? '') ?>">
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Teks Tombol CTA</label>
                            <input type="text" name="cta_text" class="form-control" placeholder="Daftar Sekarang"
                                   value="<?= e($edit['cta_text'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Link CTA</label>
                            <input type="text" name="cta_link" class="form-control" placeholder="#fasilitas"
                                   value="<?= e($edit['cta_link'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Urutan</label>
                            <input type="number" name="sort_order" class="form-control"
                                   value="<?= (int)($edit['sort_order'] ?? 0) ?>">
                        </div>
                        <div class="col-md-6 mb-3 d-flex align-items-end">
                            <div class="form-check">
                                <input type="checkbox" name="is_active" class="form-check-input" id="aktif"
                                       <?= !isset($edit['is_active']) || !empty($edit['is_active']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="aktif">Aktif</label>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Simpan</button>
                    <?php if ($edit): ?>
                        <a href="/admin/landing/hero.php" class="btn btn-secondary">Batal</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Daftar Slide (<?= count($rows) ?>)</h3></div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Gambar</th>
                            <th>Judul</th>
                            <th>Urutan</th>
                            <th>Status</th>
                            <th style="width:110px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$rows): ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">Belum ada slide.</td></tr>
                        <?php else: foreach ($rows as $i => $r): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td>
                                    <?php $src = hero_img_src($r['image_url'] ?? ''); ?>
                                    <?php if ($src): ?>
                                        <img src="<?= e($src) ?>" alt="" style="width:56px;height:36px;object-fit:cover;border-radius:4px">
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?= e(mb_strimwidth(strip_tags($r['title']), 0, 40, '…')) ?></strong>
                                    <?php if ($r['badge']): ?><br><small class="text-muted"><?= e($r['badge']) ?></small><?php endif; ?>
                                </td>
                                <td><?= (int)$r['sort_order'] ?></td>
                                <td>
                                    <a href="?toggle=<?= (int)$r['id'] ?>" class="badge text-bg-<?= $r['is_active'] ? 'success' : 'secondary' ?>">
                                        <?= $r['is_active'] ? 'Aktif' : 'Nonaktif' ?>
                                    </a>
                                </td>
                                <td>
                                    <a href="?edit=<?= (int)$r['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                    <a href="?delete=<?= (int)$r['id'] ?>" class="btn btn-sm btn-outline-danger"
                                       onclick="return confirm('Hapus slide ini?')"><i class="bi bi-trash"></i></a>
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