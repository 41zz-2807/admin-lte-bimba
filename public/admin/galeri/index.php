<?php
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/flash.php';
require_once __DIR__ . '/../../../includes/helpers.php';
require_login();
$pageTitle = 'Galeri';
global $pdo;

if (isset($_GET['delete_album'])) {
    $pdo->prepare('DELETE FROM galeri_album WHERE id=?')->execute([(int)$_GET['delete_album']]);
    set_flash('success','Album dihapus.');
    redirect('admin/galeri/');
}
if (isset($_GET['delete_video'])) {
    $pdo->prepare('DELETE FROM galeri_video WHERE id=?')->execute([(int)$_GET['delete_video']]);
    set_flash('success','Video dihapus.');
    redirect('admin/galeri/');
}
if (isset($_GET['toggle_album'])) {
    $pdo->prepare('UPDATE galeri_album SET is_active=IF(is_active=1,0,1) WHERE id=?')->execute([(int)$_GET['toggle_album']]);
    redirect('admin/galeri/');
}
if (isset($_GET['toggle_video'])) {
    $pdo->prepare('UPDATE galeri_video SET is_active=IF(is_active=1,0,1) WHERE id=?')->execute([(int)$_GET['toggle_video']]);
    redirect('admin/galeri/');
}

$albums = $pdo->query('SELECT a.*, (SELECT COUNT(*) FROM galeri_foto f WHERE f.album_id=a.id) AS jml_foto FROM galeri_album a ORDER BY a.sort_order ASC, a.id DESC')->fetchAll();
$videos = $pdo->query('SELECT * FROM galeri_video ORDER BY sort_order ASC, id DESC')->fetchAll();

ob_start();
?>
<div class="d-flex justify-content-between mb-3">
  <h4>Album Foto</h4>
  <a href="/admin/galeri/album.php" class="btn btn-primary btn-sm"><i class="bi bi-plus"></i> Tambah Album</a>
</div>
<div class="card mb-4">
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead><tr><th>Cover</th><th>Judul</th><th>Tanggal</th><th>Foto</th><th>Baru</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php foreach($albums as $a): ?>
        <tr>
          <td><?php if($a['cover_url']): ?><img src="/uploads/<?=e($a['cover_url'])?>" height="40"><?php endif; ?></td>
          <td><?=e($a['judul'])?></td>
          <td><?=$a['tanggal']?></td>
          <td><?=$a['jml_foto']?></td>
          <td><?=$a['is_baru']?'Ya':'-'?></td>
          <td><?=$a['is_active']?'Aktif':'Nonaktif'?></td>
          <td class="text-end">
            <a href="/admin/galeri/album.php?id=<?=$a['id']?>" class="btn btn-sm btn-outline-primary">Edit</a>
            <a href="/admin/galeri/foto.php?album_id=<?=$a['id']?>" class="btn btn-sm btn-outline-success">Foto</a>
            <a href="?toggle_album=<?=$a['id']?>" class="btn btn-sm btn-outline-secondary">Toggle</a>
            <a href="?delete_album=<?=$a['id']?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus?')">Hapus</a>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="d-flex justify-content-between mb-3">
  <h4>Video Dokumentasi</h4>
  <a href="/admin/galeri/video.php" class="btn btn-primary btn-sm"><i class="bi bi-plus"></i> Tambah Video</a>
</div>
<div class="card">
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead><tr><th>Judul</th><th>YouTube ID</th><th>Views</th><th>Waktu</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php foreach($videos as $v): ?>
        <tr>
          <td><?=e($v['judul'])?></td>
          <td><?=e($v['youtube_id'])?></td>
          <td><?=e($v['views_text'])?></td>
          <td><?=e($v['waktu_text'])?></td>
          <td><?=$v['is_active']?'Aktif':'Nonaktif'?></td>
          <td class="text-end">
            <a href="/admin/galeri/video.php?id=<?=$v['id']?>" class="btn btn-sm btn-outline-primary">Edit</a>
            <a href="?toggle_video=<?=$v['id']?>" class="btn btn-sm btn-outline-secondary">Toggle</a>
            <a href="?delete_video=<?=$v['id']?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus?')">Hapus</a>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../../includes/layout_admin.php';