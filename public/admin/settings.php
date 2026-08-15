<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/flash.php';
require_once __DIR__ . '/../../includes/helpers.php';

require_login();
// Opsional: hanya superadmin
// if (!is_superadmin()) { set_flash('danger', 'Akses ditolak.'); redirect('admin/'); }

$pageTitle = 'Pengaturan';
$error = '';
$success = '';
global $pdo;

$keys = [
    'smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass',
    'smtp_from', 'smtp_from_name', 'smtp_secure',
    'telegram_bot_token', 'telegram_chat_id',
];

// Simpan SMTP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_smtp'])) {
    foreach (['smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'smtp_from', 'smtp_from_name', 'smtp_secure'] as $k) {
        $val = trim($_POST[$k] ?? '');
        if ($k === 'smtp_pass' && $val === '') {
            continue;
        }
        set_setting($k, $val);
    }
    set_flash('success', 'Pengaturan SMTP berhasil disimpan.');
    redirect('admin/settings.php');
}

// Simpan Telegram
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_telegram'])) {
    set_setting('telegram_bot_token', trim($_POST['telegram_bot_token'] ?? ''));
    set_setting('telegram_chat_id', trim($_POST['telegram_chat_id'] ?? ''));
    set_flash('success', 'Pengaturan Telegram berhasil disimpan.');
    redirect('admin/settings.php');
}

// Test email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_email'])) {
    $to = trim($_POST['test_to'] ?? '');
    if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email tujuan test tidak valid.';
    } else {
        $result = send_smtp_mail(
            $to,
            'Test Email - Bimba KSR',
            "Halo,\n\nIni adalah email percobaan dari aplikasi Bimba KSR.\n\nJika Anda menerima email ini, konfigurasi SMTP sudah benar.\n\nWaktu: " . date('d/m/Y H:i:s')
        );
        if ($result === true) {
            $success = 'Email test berhasil dikirim ke ' . $to;
        } else {
            $error = $result;
        }
    }
}

// Test Telegram
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_telegram'])) {
    $result = send_telegram(
        "<b>Test Notifikasi Bimba KSR</b>\n\n" .
        "Jika Anda menerima pesan ini, konfigurasi Telegram sudah benar.\n" .
        "Waktu: " . date('d/m/Y H:i:s')
    );
    if ($result === true) {
        $success = 'Pesan test Telegram berhasil dikirim.';
    } else {
        $error = $result;
    }
}

$cfg = [];
foreach ($keys as $k) {
    $cfg[$k] = get_setting($k, match ($k) {
        'smtp_port'      => '587',
        'smtp_secure'    => 'tls',
        'smtp_from_name' => 'Bimba KSR',
        default          => '',
    });
}

ob_start();
?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success"><?= e($success) ?></div>
<?php endif; ?>

<div class="row">
    <!-- SMTP -->
    <div class="col-md-7">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="bi bi-envelope-gear me-1"></i> Pengaturan SMTP</h3>
            </div>
            <div class="card-body">
                <form method="post">
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label">SMTP Host</label>
                            <input type="text" name="smtp_host" class="form-control"
                                   placeholder="smtp.gmail.com" value="<?= e($cfg['smtp_host']) ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Port</label>
                            <input type="number" name="smtp_port" class="form-control"
                                   value="<?= e($cfg['smtp_port']) ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Enkripsi</label>
                        <select name="smtp_secure" class="form-select">
                            <option value="tls" <?= $cfg['smtp_secure'] === 'tls' ? 'selected' : '' ?>>TLS (port 587)</option>
                            <option value="ssl" <?= $cfg['smtp_secure'] === 'ssl' ? 'selected' : '' ?>>SSL (port 465)</option>
                            <option value="none" <?= $cfg['smtp_secure'] === 'none' ? 'selected' : '' ?>>None</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">SMTP Username</label>
                        <input type="text" name="smtp_user" class="form-control"
                               placeholder="email@gmail.com" value="<?= e($cfg['smtp_user']) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">SMTP Password</label>
                        <input type="password" name="smtp_pass" class="form-control"
                               placeholder="<?= $cfg['smtp_pass'] ? '•••••••• (isi untuk ganti)' : 'App Password' ?>">
                        <div class="form-text">Kosongkan jika tidak ingin mengubah password.</div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">From Email</label>
                            <input type="email" name="smtp_from" class="form-control"
                                   value="<?= e($cfg['smtp_from']) ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">From Name</label>
                            <input type="text" name="smtp_from_name" class="form-control"
                                   value="<?= e($cfg['smtp_from_name']) ?>">
                        </div>
                    </div>
                    <button type="submit" name="save_smtp" value="1" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> Simpan SMTP
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Test Email + Panduan Gmail -->
    <div class="col-md-5">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="bi bi-send me-1"></i> Test Kirim Email</h3>
            </div>
            <div class="card-body">
                <p class="text-muted small">Simpan pengaturan SMTP dulu, lalu kirim email percobaan.</p>
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Email Tujuan</label>
                        <input type="email" name="test_to" class="form-control" required
                               placeholder="emailanda@gmail.com"
                               value="<?= e($_POST['test_to'] ?? '') ?>">
                    </div>
                    <button type="submit" name="test_email" value="1" class="btn btn-success">
                        <i class="bi bi-send me-1"></i> Kirim Test Email
                    </button>
                </form>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header"><h3 class="card-title">Contoh Gmail</h3></div>
            <div class="card-body small">
                <ul class="mb-0">
                    <li>Host: <code>smtp.gmail.com</code></li>
                    <li>Port: <code>587</code> + TLS</li>
                    <li>Username: email Gmail Anda</li>
                    <li>Password: <strong>App Password</strong> (bukan password biasa)</li>
                    <li>Aktifkan 2FA di Google Account, lalu buat App Password</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="row mt-3">
    <!-- Telegram -->
    <div class="col-md-7">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="bi bi-telegram me-1"></i> Pengaturan Telegram</h3>
            </div>
            <div class="card-body">
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Bot Token</label>
                        <input type="text" name="telegram_bot_token" class="form-control"
                               placeholder="123456:ABC-DEF..."
                               value="<?= e($cfg['telegram_bot_token']) ?>">
                        <div class="form-text">Dapatkan dari @BotFather di Telegram.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Chat ID</label>
                        <input type="text" name="telegram_chat_id" class="form-control"
                               placeholder="-100xxxxxxxxxx atau ID pribadi"
                               value="<?= e($cfg['telegram_chat_id']) ?>">
                        <div class="form-text">Chat ID user / grup / channel tujuan notifikasi.</div>
                    </div>
                    <button type="submit" name="save_telegram" value="1" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> Simpan Telegram
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Test Telegram + Panduan -->
    <div class="col-md-5">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="bi bi-send me-1"></i> Test Telegram</h3>
            </div>
            <div class="card-body">
                <p class="text-muted small">Simpan Bot Token & Chat ID dulu, lalu kirim pesan percobaan.</p>
                <form method="post">
                    <button type="submit" name="test_telegram" value="1" class="btn btn-success">
                        <i class="bi bi-send me-1"></i> Kirim Test Telegram
                    </button>
                </form>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header"><h3 class="card-title">Cara setup cepat</h3></div>
            <div class="card-body small">
                <ol class="mb-0">
                    <li>Chat <code>@BotFather</code> → <code>/newbot</code> → salin <strong>token</strong></li>
                    <li>Tambahkan bot ke grup (jika pakai grup)</li>
                    <li>Kirim 1 pesan ke bot/grup, lalu buka:<br>
                        <code>https://api.telegram.org/botTOKEN/getUpdates</code>
                    </li>
                    <li>Ambil nilai <code>chat.id</code> dari hasil JSON</li>
                </ol>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../includes/layout_admin.php';