<?php
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/flash.php';
require_once __DIR__ . '/../../../includes/helpers.php';
require_login();

$pageTitle = 'Tambah Wali Murid';
global $pdo;
$error = '';

$allSiswa = $pdo->query('SELECT id, nis, nama, status FROM siswa ORDER BY nama')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $siswaIds = array_map('intval', $_POST['siswa_ids'] ?? []);
    $hubungan = trim($_POST['hubungan'] ?? 'Orang tua') ?: 'Orang tua';

    if ($name === '' || $email === '') {
        $error = 'Nama dan email wajib.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email tidak valid.';
    } elseif (strlen($password) < 8) {
        $error = 'Password minimal 8 karakter.';
    } else {
        $cek = $pdo->prepare('SELECT id FROM users WHERE email=? LIMIT 1');
        $cek->execute([$email]);
        if ($cek->fetch()) {
            $error = 'Email sudah terpakai.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $pdo->prepare(
                'INSERT INTO users (name, email, password, role, is_active) VALUES (?,?,?,?,?)'
            )->execute([$name, $email, $hash, 'wali_murid', $is_active]);
            $uid = (int)$pdo->lastInsertId();

            $ins = $pdo->prepare('INSERT IGNORE INTO siswa_wali (user_id, siswa_id, hubungan) VALUES (?,?,?)');
            foreach ($siswaIds as $sid) {
                if ($sid > 0) $ins->execute([$uid, $sid, $hubungan]);
            }

            set_flash('success', 'Akun wali dibuat.');
            redirect('admin/wali/');
        }
    }
}

ob_start();
?>
<div class="row justify-content-center">
  <div class="col-md-8">
    <div class="card">
      <div class="card-body">
        <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
        <form method="post">
          <div class="mb-3">
            <label class="form-label">Nama *</label>
            <input name="name" class="form-control" required value="<?= e($_POST['name'] ?? '') ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Email (login) *</label>
            <input type="email" name="email" class="form-control" required value="<?= e($_POST['email'] ?? '') ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Password *</label>
            <input type="text" name="password" class="form-control" required minlength="8" placeholder="min. 8 karakter">
          </div>
          <div class="mb-3">
            <label class="form-label">Hubungan ke anak</label>
            <input name="hubungan" class="form-control" value="<?= e($_POST['hubungan'] ?? 'Orang tua') ?>" placeholder="Ayah / Ibu / Wali">
          </div>
          <div class="mb-3">
            <label class="form-label">Tautkan Anak</label>
            <select name="siswa_ids[]" class="form-select" multiple size="8">
              <?php foreach ($allSiswa as $s): ?>
                <option value="<?= (int)$s['id'] ?>">
                  <?= e($s['nama']) ?> (<?= e($s['nis'] ?: '-') ?>) — <?= e($s['status']) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <div class="form-text">Ctrl+klik untuk pilih beberapa anak</div>
          </div>
          <div class="form-check mb-3">
            <input type="checkbox" name="is_active" class="form-check-input" id="aktif" checked>
            <label class="form-check-label" for="aktif">Aktif</label>
          </div>
          <button class="btn btn-primary">Simpan</button>
          <a href="/admin/wali/" class="btn btn-secondary">Batal</a>
        </form>
      </div>
    </div>
  </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../../includes/layout_admin.php';