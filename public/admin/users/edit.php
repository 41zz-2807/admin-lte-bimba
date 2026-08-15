<?php
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/flash.php';

require_superadmin();

$pageTitle = 'Edit User';
$error = '';
global $pdo;

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    set_flash('danger', 'User tidak ditemukan.');
    redirect('admin/users/');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? 'admin';
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    // Superadmin tidak bisa nonaktifkan diri sendiri
    if ($id === (int) current_user()['id']) {
        $is_active = 1;
    }

    if ($name === '' || $email === '') {
        $error = 'Nama dan email wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (!in_array($role, ['superadmin', 'admin'], true)) {
        $error = 'Role tidak valid.';
    } elseif ($password !== '' && strlen($password) < 8) {
        $error = 'Password minimal 8 karakter (kosongkan jika tidak diubah).';
    } else {
        // Cek email unik (kecuali milik sendiri)
        $cek = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1');
        $cek->execute([$email, $id]);
        if ($cek->fetch()) {
            $error = 'Email sudah dipakai user lain.';
        } else {
            if ($password !== '') {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $upd = $pdo->prepare('UPDATE users SET name=?, email=?, password=?, role=?, is_active=? WHERE id=?');
                $upd->execute([$name, $email, $hash, $role, $is_active, $id]);
            } else {
                $upd = $pdo->prepare('UPDATE users SET name=?, email=?, role=?, is_active=? WHERE id=?');
                $upd->execute([$name, $email, $role, $is_active, $id]);
            }

            // Update session jika edit diri sendiri
            if ($id === (int) current_user()['id']) {
                $_SESSION['user']['name']  = $name;
                $_SESSION['user']['email'] = $email;
                $_SESSION['user']['role']  = $role;
            }

            set_flash('success', 'User berhasil diperbarui.');
            redirect('admin/users/');
        }
    }

    // isi ulang form jika error
    $user['name'] = $name;
    $user['email'] = $email;
    $user['role'] = $role;
    $user['is_active'] = $is_active;
}

ob_start();
?>
<div class="row justify-content-center">
    <div class="col-md-7">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Edit User</h3>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= e($error) ?></div>
                <?php endif; ?>

                <form method="post" action="">
                    <div class="mb-3">
                        <label class="form-label">Nama</label>
                        <input type="text" name="name" class="form-control" required
                               value="<?= e($user['name']) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required
                               value="<?= e($user['email']) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password Baru</label>
                        <input type="password" name="password" class="form-control" minlength="8">
                        <div class="form-text">Kosongkan jika tidak ingin mengubah password</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-select">
                            <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                            <option value="superadmin" <?= $user['role'] === 'superadmin' ? 'selected' : '' ?>>Superadmin</option>
                        </select>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" name="is_active" class="form-check-input" id="is_active"
                               <?= $user['is_active'] ? 'checked' : '' ?>
                               <?= $id === (int)current_user()['id'] ? 'disabled' : '' ?>>
                        <label class="form-check-label" for="is_active">Akun aktif</label>
                        <?php if ($id === (int)current_user()['id']): ?>
                            <div class="form-text">Tidak bisa menonaktifkan akun sendiri</div>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i> Update
                        </button>
                        <a href="/admin/users/" class="btn btn-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../../includes/layout_admin.php';