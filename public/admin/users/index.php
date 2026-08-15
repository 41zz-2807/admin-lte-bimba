<?php
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/flash.php';

require_superadmin();

$pageTitle = 'Manajemen User';
global $pdo;

// Hapus user (kecuali diri sendiri)
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $me = current_user();

    if ($id === $me['id']) {
        set_flash('danger', 'Tidak bisa menghapus akun sendiri.');
    } else {
        $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
        $stmt->execute([$id]);
        set_flash('success', 'User berhasil dihapus.');
    }
    redirect('admin/users/');
}

// Toggle aktif/nonaktif
if (isset($_GET['toggle'])) {
    $id = (int) $_GET['toggle'];
    $me = current_user();

    if ($id === $me['id']) {
        set_flash('danger', 'Tidak bisa menonaktifkan akun sendiri.');
    } else {
        $stmt = $pdo->prepare('UPDATE users SET is_active = IF(is_active = 1, 0, 1) WHERE id = ?');
        $stmt->execute([$id]);
        set_flash('success', 'Status user berhasil diubah.');
    }
    redirect('admin/users/');
}

$users = $pdo->query('SELECT id, name, email, role, is_active, last_login_at, created_at FROM users ORDER BY id ASC')->fetchAll();

ob_start();
?>
<div class="mb-3">
    <a href="/admin/users/create.php" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Tambah User
    </a>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Daftar User</h3>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover table-striped mb-0">
            <thead>
                <tr>
                    <th style="width:50px">#</th>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Login Terakhir</th>
                    <th style="width:160px">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">Belum ada user.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $i => $u): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= e($u['name']) ?></td>
                            <td><?= e($u['email']) ?></td>
                            <td>
                                <?php if ($u['role'] === 'superadmin'): ?>
                                    <span class="badge text-bg-danger">Superadmin</span>
                                <?php else: ?>
                                    <span class="badge text-bg-secondary">Admin</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($u['is_active']): ?>
                                    <span class="badge text-bg-success">Aktif</span>
                                <?php else: ?>
                                    <span class="badge text-bg-warning">Nonaktif</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= $u['last_login_at'] ? e(date('d/m/Y H:i', strtotime($u['last_login_at']))) : '-' ?>
                            </td>
                            <td>
                                <a href="/admin/users/edit.php?id=<?= (int)$u['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="?toggle=<?= (int)$u['id'] ?>" class="btn btn-sm btn-outline-warning"
                                   onclick="return confirm('Ubah status user ini?')" title="Aktif/Nonaktif">
                                    <i class="bi bi-toggle-on"></i>
                                </a>
                                <?php if ((int)$u['id'] !== (int)current_user()['id']): ?>
                                    <a href="?delete=<?= (int)$u['id'] ?>" class="btn btn-sm btn-outline-danger"
                                       onclick="return confirm('Yakin hapus user ini?')" title="Hapus">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                <?php endif; ?>
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