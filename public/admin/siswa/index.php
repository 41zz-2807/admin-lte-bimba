<?php
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/flash.php';

require_login();

$pageTitle = 'Data Siswa';
global $pdo;

// Hapus
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = $pdo->prepare('DELETE FROM siswa WHERE id = ?');
    $stmt->execute([$id]);
    set_flash('success', 'Data siswa berhasil dihapus.');
    redirect('admin/siswa/');
}

// Filter sederhana
$q = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? '';

$sql = 'SELECT * FROM siswa WHERE 1=1';
$params = [];

if ($q !== '') {
    $sql .= ' AND (nama LIKE ? OR nis LIKE ? OR nama_ortu LIKE ? OR no_hp_ortu LIKE ? OR email_ortu LIKE ?)';
    $like = '%' . $q . '%';
    $params = array_merge($params, [$like, $like, $like, $like, $like]);
}
if (in_array($status, ['aktif', 'nonaktif', 'lulus'], true)) {
    $sql .= ' AND status = ?';
    $params[] = $status;
}
$sql .= ' ORDER BY nama ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$siswa = $stmt->fetchAll();

ob_start();
?>
<div class="mb-3 d-flex flex-wrap gap-2 justify-content-between">
    <div class="d-flex flex-wrap gap-2">
    <a href="/admin/siswa/create.php" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Tambah Siswa
    </a>
    <a href="/admin/siswa/export.php" class="btn btn-success">
            <i class="bi bi-download me-1"></i> Download Excel
        </a>
        <a href="/admin/siswa/import.php" class="btn btn-outline-success">
            <i class="bi bi-upload me-1"></i> Upload Excel
        </a>
    </div>
    <form method="get" class="d-flex flex-wrap gap-2">
        <input type="text" name="q" class="form-control form-control-sm" style="width:200px"
               placeholder="Cari nama / NIS / ortu / email..." value="<?= e($q) ?>">
        <select name="status" class="form-select form-select-sm" style="width:130px">
            <option value="">Semua status</option>
            <option value="aktif" <?= $status === 'aktif' ? 'selected' : '' ?>>Aktif</option>
            <option value="nonaktif" <?= $status === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
            <option value="lulus" <?= $status === 'lulus' ? 'selected' : '' ?>>Lulus</option>
        </select>
        <button type="submit" class="btn btn-sm btn-outline-secondary">Filter</button>
        <?php if ($q !== '' || $status !== ''): ?>
            <a href="/admin/siswa/" class="btn btn-sm btn-outline-danger">Reset</a>
        <?php endif; ?>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Daftar Siswa (<?= count($siswa) ?>)</h3>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover table-striped mb-0">
            <thead>
                <tr>
                    <th style="width:40px">#</th>
                    <th style="width:56px">Foto</th>
                    <th>NIS</th>
                    <th>Nama</th>
                    <th>JK</th>
                    <th>Tgl Lahir</th>
                    <th>Nama Ortu</th>
                    <th>No. HP Ortu</th>
                    <th>Email Ortu</th>
                    <th>Status</th>
                    <th style="width:120px">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($siswa)): ?>
                    <tr>
                        <td colspan="11" class="text-center text-muted py-4">Belum ada data siswa.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($siswa as $i => $s): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td>
        <?php if (!empty($s['foto_url'])): ?>
            <img src="/uploads/<?= e($s['foto_url']) ?>"
                 alt=""
                 style="width:40px;height:40px;object-fit:cover;border-radius:6px">
        <?php else: ?>
            <div class="bg-secondary text-white d-flex align-items-center justify-content-center rounded"
                 style="width:40px;height:40px;font-size:14px">
                <i class="bi bi-person"></i>
            </div>
        <?php endif; ?>
    </td>
                            <td><?= e($s['nis'] ?: '-') ?></td>
                            <td><?= e($s['nama']) ?></td>
                            <td><?= $s['jenis_kelamin'] === 'L' ? 'L' : 'P' ?></td>
                            <td><?= $s['tanggal_lahir'] ? e(date('d/m/Y', strtotime($s['tanggal_lahir']))) : '-' ?></td>
                            <td><?= e($s['nama_ortu'] ?: '-') ?></td>
                            <td><?= e($s['no_hp_ortu'] ?: '-') ?></td>
                            <td><?= e($s['email_ortu'] ?: '-') ?></td>
                            <td>
                                <?php
                                $badge = match ($s['status']) {
                                    'aktif'    => 'success',
                                    'nonaktif' => 'warning',
                                    'lulus'    => 'info',
                                    default    => 'secondary',
                                };
                                ?>
                                <span class="badge text-bg-<?= $badge ?>"><?= e(ucfirst($s['status'])) ?></span>
                            </td>
                            <td>
                                <a href="/admin/siswa/edit.php?id=<?= (int)$s['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="?delete=<?= (int)$s['id'] ?>" class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('Yakin hapus data siswa ini?')" title="Hapus">
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