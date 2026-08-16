<?php
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/flash.php';
require_once __DIR__ . '/../../../includes/helpers.php';
require_login();

$pageTitle = 'Edit Wali Murid';
global $pdo;
$error = '';

$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare('SELECT * FROM users WHERE id=? AND role=\'wali_murid\' LIMIT 1');
$st->execute([$id]);
$user = $st->fetch();
if (!$user) {
    set_flash('danger', 'Akun tidak ditemukan.');
    redirect('admin/wali/');
}

$allSiswa = $pdo->query('SELECT id, nis, nama, status FROM siswa ORDER BY nama')->fetchAll();
$linked = $pdo->prepare('SELECT siswa_id, hubungan FROM siswa_wali WHERE user_id=?');
$linked->execute([$id]);
$linkedRows = $linked->fetchAll();
$linkedIds = array_column($linkedRows, 'siswa_id');
$hubunganDefault = $linkedRows[0]['hubungan'] ?? 'Orang tua';

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
    } elseif ($password !== '' && strlen($password) < 8) {
        $error = 'Password minimal 8 karakter (kosongkan jika tidak diubah).';
    } else {
        $cek = $pdo->prepare('SELECT id FROM users WHERE email=? AND id!=? LIMIT 1');
        $cek->execute([$email, $id]);
        if ($cek->fetch()) {
            $error = 'Email sudah terpakai.';
        } else {
            if ($password !== '') {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $pdo->prepare('UPDATE users SET name=?, email=?, password=?, is_active=? WHERE id=?')
                    ->execute([$name, $email, $hash, $is_active, $id]);
            } else {
                $pdo->prepare('UPDATE users SET name=?, email=?, is_active=? WHERE id=?')
                    ->execute([$name, $email, $is_active, $id]);
            }

            // Sync anak
            $pdo->prepare('DELETE FROM siswa_wali WHERE user_id=?')->execute([$id]);
            $ins = $pdo->prepare('INSERT INTO siswa_wali (user_id, siswa_id, hubungan) VALUES (?,?,?)');
            foreach ($siswaIds as $sid) {
                if ($sid > 0) $ins->execute([$id, $sid, $hubungan]);
            }

            set_flash('success', 'Data wali diperbarui.');
            redirect('admin/wali/');
        }
    }
    $user['name'] = $name;
    $user['email'] = $email;
    $user['is_active'] = $is_active;
    $linkedIds = $siswaIds;
    $hubunganDefault = $hubungan;
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
            <input name="name" class="form-control" required value="<?= e($user['name']) ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Email (login) *</label>
            <input type="email" name="email" class="form-control" required value="<?= e($user['email']) ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Password baru</label>
            <input type="text" name="password" class="form-control" minlength="8" placeholder="Kosongkan jika tidak diubah">
          </div>
          <div class="mb-3">
            <label class="form-label">Hubungan ke anak</label>
            <input name="hubungan" class="form-control" value="<?= e($hubunganDefault) ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Tautkan Anak</label>
            <select name="siswa_ids[]" class="form-select" multiple size="8">
              <?php foreach ($allSiswa as $s): ?>
                <option value="<?= (int)$s['id'] ?>" <?= in_array((int)$s['id'], array_map('intval', $linkedIds), true) ? 'selected' : '' ?>>
                  <?= e($s['nama']) ?> (<?= e($s['nis'] ?: '-') ?>) — <?= e($s['status']) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <div class="form-text">Ctrl+klik untuk pilih/lepas beberapa anak</div>
          </div>
          <div class="form-check mb-3">
            <input type="checkbox" name="is_active" class="form-check-input" id="aktif" <?= $user['is_active'] ? 'checked' : '' ?>>
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