<?php
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/flash.php';

require_login();

$pageTitle = 'Data Guru / Staff';
global $pdo;

if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $pdo->prepare('DELETE FROM guru_staff WHERE id = ?')->execute([$id]);
    set_flash('success', 'Data berhasil dihapus.');
    redirect('admin/guru/');
}

$q = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? '';

$sql = 'SELECT * FROM guru_staff WHERE 1=1';
$params = [];

if ($q !== '') {
    $sql .= ' AND (nama LIKE ? OR nip LIKE ? OR jabatan LIKE ? OR no_hp LIKE ? OR email LIKE ?)';
    $like = '%' . $q . '%';
    $params = array_merge($params, [$like, $like, $like, $like, $like]);
}
if (in_array($status, ['aktif', 'nonaktif'], true)) {
    $sql .= ' AND status = ?';
    $params[] = $status;
}
$sql .= ' ORDER BY nama ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

ob_start();
?>
<div class="mb-3 d-flex flex-wrap gap-2 justify-content-between">
    <div class="d-flex flex-wrap gap-2">
    <a href="/admin/guru/create.php" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Tambah Guru / Staff
    </a>
    <a href="/admin/guru/export.php" class="btn btn-success">
            <i class="bi bi-download me-1"></i> Download Excel
        </a>
        <a href="/admin/guru/import.php" class="btn btn-outline-success">
            <i class="bi bi-upload me-1"></i> Upload Excel
        </a>
    </div>
    <form method="get" class="d-flex flex-wrap gap-2">
        <input type="text" name="q" class="form-control form-control-sm" style="width:200px"
               placeholder="Cari nama / NIP / jabatan..." value="<?= e($q) ?>">
        <select name="status" class="form-select form-select-sm" style="width:130px">
            <option value="">Semua status</option>
            <option value="aktif" <?= $status === 'aktif' ? 'selected' : '' ?>>Aktif</option>
            <option value="nonaktif" <?= $status === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
        </select>
        <button type="submit" class="btn btn-sm btn-outline-secondary">Filter</button>
        <?php if ($q !== '' || $status !== ''): ?>
            <a href="/admin/guru/" class="btn btn-sm btn-outline-danger">Reset</a>
        <?php endif; ?>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Daftar Guru / Staff (<?= count($rows) ?>)</h3>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover table-striped mb-0">
            <thead>
                <tr>
                    <th style="width:40px">#</th>
                    <th>NIP</th>
                    <th>Nama</th>
                    <th>JK</th>
                    <th>Jabatan</th>
                    <th>No. HP</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th style="width:120px">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">Belum ada data.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $i => $r): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= e($r['nip'] ?: '-') ?></td>
                            <td><?= e($r['nama']) ?></td>
                            <td><?= $r['jenis_kelamin'] === 'L' ? 'L' : 'P' ?></td>
                            <td><?= e($r['jabatan'] ?: '-') ?></td>
                            <td><?= e($r['no_hp'] ?: '-') ?></td>
                            <td><?= e($r['email'] ?: '-') ?></td>
                            <td>
                                <span class="badge text-bg-<?= $r['status'] === 'aktif' ? 'success' : 'warning' ?>">
                                    <?= e(ucfirst($r['status'])) ?>
                                </span>
                            </td>
                            <td>
                                <a href="/admin/guru/edit.php?id=<?= (int)$r['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="?delete=<?= (int)$r['id'] ?>" class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('Yakin hapus data ini?')" title="Hapus">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../../includes/layout_admin.php';