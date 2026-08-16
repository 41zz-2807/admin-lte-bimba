<?php
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/flash.php';
require_once __DIR__ . '/../../../includes/helpers.php';
require_login();
$pageTitle = 'Foto Album';
global $pdo;
$album_id = (int)($_GET['album_id'] ?? 0);
$st = $pdo->prepare('SELECT * FROM galeri_album WHERE id=?');
$st->execute([$album_id]);
$album = $st->fetch();
if (!$album) { set_flash('danger','Album tidak ada'); redirect('admin/galeri/'); }

if (isset($_GET['delete'])) {
    $pdo->prepare('DELETE FROM galeri_foto WHERE id=? AND album_id=?')->execute([(int)$_GET['delete'], $album_id]);
    set_flash('success','Foto dihapus');
    redirect('admin/galeri/foto.php?album_id='.$album_id);
}

if ($_SERVER['REQUEST_METHOD']==='POST' && !empty($_FILES['fotos']['name'][0])) {
    $count=0;
    foreach ($_FILES['fotos']['name'] as $i=>$name) {
        if ($_FILES['fotos']['error'][$i]!==UPLOAD_ERR_OK) continue;
        $file = [
            'name'=>$_FILES['fotos']['name'][$i],
            'type'=>$_FILES['fotos']['type'][$i],
            'tmp_name'=>$_FILES['fotos']['tmp_name'][$i],
            'error'=>$_FILES['fotos']['error'][$i],
            'size'=>$_FILES['fotos']['size'][$i],
        ];
        $up = upload_image($file, 'galeri');
        if ($up) {
            $pdo->prepare('INSERT INTO galeri_foto (album_id,image_url) VALUES (?,?)')->execute([$album_id,$up]);
            $count++;
        }
    }
    set_flash('success',"$count foto diupload");
    redirect('admin/galeri/foto.php?album_id='.$album_id);
}

$fotos = $pdo->prepare('SELECT * FROM galeri_foto WHERE album_id=? ORDER BY sort_order,id');
$fotos->execute([$album_id]);
$fotos = $fotos->fetchAll();

ob_start();
?>
<a href="/admin/galeri/" class="btn btn-sm btn-secondary mb-3">&larr; Kembali</a>
<h4><?=e($album['judul'])?> — <?=count($fotos)?> foto</h4>
<form method="post" enctype="multipart/form-data" class="card card-body mb-3">
  <label class="form-label">Upload foto (bisa banyak)</label>
  <input type="file" name="fotos[]" class="form-control" accept="image/*" multiple required>
  <button class="btn btn-primary mt-2">Upload</button>
</form>
<div class="row g-2">
<?php foreach($fotos as $f): ?>
  <div class="col-6 col-md-3 col-lg-2">
    <div class="card">
      <img src="/uploads/<?=e($f['image_url'])?>" class="card-img-top" style="height:120px;object-fit:cover">
      <div class="card-body p-2 text-center">
        <a href="?album_id=<?=$album_id?>&delete=<?=$f['id']?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus?')">Hapus</a>
      </div>
    </div>
  </div>
<?php endforeach; ?>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../../includes/layout_admin.php';