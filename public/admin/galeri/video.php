<?php
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/flash.php';
require_once __DIR__ . '/../../../includes/helpers.php';
require_login();
$pageTitle = 'Video';
global $pdo;
$id = (int)($_GET['id'] ?? 0);
$row = null;
if ($id) {
    $st=$pdo->prepare('SELECT * FROM galeri_video WHERE id=?'); $st->execute([$id]); $row=$st->fetch();
}
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $data = [
        trim($_POST['judul']??''),
        trim($_POST['youtube_id']??''),
        trim($_POST['views_text']??'')?:null,
        trim($_POST['waktu_text']??'')?:null,
        (int)($_POST['sort_order']??0),
        isset($_POST['is_active'])?1:0,
    ];
    if ($data[0]===''||$data[1]==='') {
        set_flash('danger','Judul & YouTube ID wajib');
    } elseif ($id) {
        $pdo->prepare('UPDATE galeri_video SET judul=?,youtube_id=?,views_text=?,waktu_text=?,sort_order=?,is_active=? WHERE id=?')
            ->execute([...$data,$id]);
        set_flash('success','Video diupdate');
    } else {
        $pdo->prepare('INSERT INTO galeri_video (judul,youtube_id,views_text,waktu_text,sort_order,is_active) VALUES (?,?,?,?,?,?)')
            ->execute($data);
        set_flash('success','Video ditambah');
    }
    redirect('admin/galeri/');
}
ob_start();
?>
<form method="post" class="card card-body">
  <div class="mb-3"><label>Judul *</label><input name="judul" class="form-control" required value="<?=e($row['judul']??'')?>"></div>
  <div class="mb-3"><label>YouTube ID * (contoh: dQw4w9WgXcQ)</label>
    <input name="youtube_id" class="form-control" required value="<?=e($row['youtube_id']??'')?>"></div>
  <div class="mb-3"><label>Views text</label><input name="views_text" class="form-control" placeholder="1.2K tayangan" value="<?=e($row['views_text']??'')?>"></div>
  <div class="mb-3"><label>Waktu text</label><input name="waktu_text" class="form-control" placeholder="1 minggu yang lalu" value="<?=e($row['waktu_text']??'')?>"></div>
  <div class="mb-3"><label>Sort</label><input type="number" name="sort_order" class="form-control" value="<?=(int)($row['sort_order']??0)?>"></div>
  <div class="form-check mb-3"><input type="checkbox" name="is_active" class="form-check-input" id="a" <?=!isset($row)||$row['is_active']?'checked':''?>>
    <label for="a">Aktif</label></div>
  <button class="btn btn-primary">Simpan</button>
  <a href="/admin/galeri/" class="btn btn-secondary">Batal</a>
</form>
<?php
$content=ob_get_clean();
require __DIR__.'/../../../includes/layout_admin.php';