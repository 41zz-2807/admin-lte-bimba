<?php
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/flash.php';
require_once __DIR__ . '/../../../includes/helpers.php';
require_login();
$pageTitle = 'Album';
global $pdo;
$id = (int)($_GET['id'] ?? 0);
$row = null;
if ($id) {
    $st = $pdo->prepare('SELECT * FROM galeri_album WHERE id=?');
    $st->execute([$id]);
    $row = $st->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cover = trim($_POST['cover_url'] ?? '');
    if (!empty($_FILES['cover_file']['tmp_name'])) {
        $up = upload_image($_FILES['cover_file'], 'galeri');
        if ($up) $cover = $up;
    }
    $data = [
        trim($_POST['judul'] ?? ''),
        $_POST['tanggal'] ?: null,
        $cover ?: null,
        isset($_POST['is_baru']) ? 1 : 0,
        (int)($_POST['sort_order'] ?? 0),
        isset($_POST['is_active']) ? 1 : 0,
    ];
    if ($data[0] === '') {
        set_flash('danger','Judul wajib.');
    } elseif ($id) {
        if (!$cover && $row) $data[2] = $row['cover_url'];
        $pdo->prepare('UPDATE galeri_album SET judul=?,tanggal=?,cover_url=?,is_baru=?,sort_order=?,is_active=? WHERE id=?')
            ->execute([...$data, $id]);
        set_flash('success','Album diupdate.');
    } else {
        $pdo->prepare('INSERT INTO galeri_album (judul,tanggal,cover_url,is_baru,sort_order,is_active) VALUES (?,?,?,?,?,?)')
            ->execute($data);
        set_flash('success','Album ditambah.');
    }
    redirect('admin/galeri/');
}

ob_start();
?>
<form method="post" enctype="multipart/form-data" class="card card-body">
  <div class="mb-3"><label class="form-label">Judul *</label>
    <input name="judul" class="form-control" required value="<?=e($row['judul']??'')?>"></div>
  <div class="mb-3"><label class="form-label">Tanggal</label>
    <input type="date" name="tanggal" class="form-control" value="<?=e($row['tanggal']??'')?>"></div>
  <div class="mb-3"><label class="form-label">Cover (upload)</label>
    <input type="file" name="cover_file" class="form-control" accept="image/*">
    <?php if(!empty($row['cover_url'])): ?><img src="/uploads/<?=e($row['cover_url'])?>" height="60" class="mt-2"><?php endif; ?>
  </div>
  <div class="mb-3"><label class="form-label">Atau URL cover</label>
    <input name="cover_url" class="form-control" value="<?=e($row['cover_url']??'')?>"></div>
  <div class="mb-3"><label class="form-label">Sort</label>
    <input type="number" name="sort_order" class="form-control" value="<?=(int)($row['sort_order']??0)?>"></div>
  <div class="form-check mb-2"><input type="checkbox" name="is_baru" class="form-check-input" id="baru" <?=!empty($row['is_baru'])?'checked':''?>>
    <label class="form-check-label" for="baru">Badge BARU</label></div>
  <div class="form-check mb-3"><input type="checkbox" name="is_active" class="form-check-input" id="aktif" <?=!isset($row)||$row['is_active']?'checked':''?>>
    <label class="form-check-label" for="aktif">Aktif</label></div>
  <button class="btn btn-primary">Simpan</button>
  <a href="/admin/galeri/" class="btn btn-secondary">Batal</a>
</form>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../../includes/layout_admin.php';