<?php
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/flash.php';
require_once __DIR__ . '/../../../includes/helpers.php';
require_login();

$pageTitle = 'Akun Wali Murid';
global $pdo;

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare('DELETE FROM users WHERE id=? AND role=\'wali_murid\'')->execute([$id]);
    set_flash('success', 'Akun wali dihapus.');
    redirect('admin/wali/');
}

if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $pdo->prepare('UPDATE users SET is_active=IF(is_active=1,0,1) WHERE id=? AND role=\'wali_murid\'')->execute([$id]);
    set_flash('success', 'Status diubah.');
    redirect('admin/wali/');
}

$rows = $pdo->query(
    "SELECT u.*,
            (SELECT COUNT(*) FROM siswa_wali sw WHERE sw.user_id = u.id) AS jml_anak
     FROM users u
     WHERE u.role = 'wali_murid'
     ORDER BY u.name ASC"
)->fetchAll();

ob_start();
?>
<div class="mb-3">
  <a href="/admin/wali/create.php" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Tambah Wali</a>
</div>
<div class="card">
  <div class="card-header"><h3 class="card-title">Daftar Wali Murid (<?= count($rows) ?>)</h3></div>
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead>
        <tr>
          <th>#</th><th>Nama</th><th>Email</th><th>Anak</th><th>Status</th><th>Login terakhir</th><th></th>
        </tr>
      </thead>
      <tbody>
      <?php if (!$rows): ?>
        <tr><td colspan="7" class="text-center text-muted py-4">Belum ada akun wali</td></tr>
      <?php else: foreach ($rows as $i => $u): ?>
        <tr>
          <td><?= $i+1 ?></td>
          <td><?= e($u['name']) ?></td>
          <td><?= e($u['email']) ?></td>
          <td><?= (int)$u['jml_anak'] ?> anak</td>
          <td>
            <span class="badge text-bg-<?= $u['is_active']?'success':'secondary' ?>">
              <?= $u['is_active']?'Aktif':'Nonaktif' ?>
            </span>
          </td>
          <td><?= e($u['last_login_at'] ?? '-') ?></td>
          <td class="text-nowrap">
            <a href="/admin/wali/edit.php?id=<?= (int)$u['id'] ?>" class="btn btn-sm btn-outline-primary">Edit / Anak</a>
            <a href="?toggle=<?= (int)$u['id'] ?>" class="btn btn-sm btn-outline-secondary">Toggle</a>
            <a href="?delete=<?= (int)$u['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus akun wali ini?')">Hapus</a>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../../includes/layout_admin.php';