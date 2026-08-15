<?php
/**
 * Helper functions
 */

function base_url(string $path = ''): string
{
    $app = require __DIR__ . '/../config/app.php';
    $url = rtrim($app['url'], '/');
    return $path ? $url . '/' . ltrim($path, '/') : $url;
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . base_url($path));
    exit;
}
function upload_bukti(array $file, string $subdir = 'bukti'): ?string
{
    if (empty($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed, true)) {
        return null;
    }

    // max 5 MB
    if ($file['size'] > 5 * 1024 * 1024) {
        return null;
    }

    $dir = __DIR__ . '/../public/uploads/' . $subdir;
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $name = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $path = $dir . '/' . $name;

    if (!move_uploaded_file($file['tmp_name'], $path)) {
        return null;
    }

    return $subdir . '/' . $name; // relatif dari /uploads
}

function get_setting(string $key, ?string $default = null): ?string
{
    global $pdo;
    static $cache = [];
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }
    $st = $pdo->prepare('SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1');
    $st->execute([$key]);
    $row = $st->fetch();
    $cache[$key] = $row ? $row['setting_value'] : $default;
    return $cache[$key];
}

function set_setting(string $key, ?string $value): void
{
    global $pdo;
    $st = $pdo->prepare(
        'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
    );
    $st->execute([$key, $value]);
}

/**
 * Kirim email via SMTP (socket, tanpa library)
 * Return true jika sukses, string error jika gagal
 */
function send_smtp_mail(string $to, string $subject, string $body, string $toName = ''): true|string
{
    $host = get_setting('smtp_host', '');
    $port = (int) get_setting('smtp_port', '587');
    $user = get_setting('smtp_user', '');
    $pass = get_setting('smtp_pass', '');
    $from = get_setting('smtp_from', $user);
    $fromName = get_setting('smtp_from_name', 'Bimba KSR');
    $secure = strtolower(get_setting('smtp_secure', 'tls')); // tls | ssl | none

    if ($host === '' || $from === '') {
        return 'SMTP belum dikonfigurasi. Isi Host dan From Email di Pengaturan.';
    }

    $errno = 0;
    $errstr = '';
    $prefix = ($secure === 'ssl') ? 'ssl://' : '';
    $socket = @fsockopen($prefix . $host, $port, $errno, $errstr, 15);
    if (!$socket) {
        return "Tidak bisa konek ke SMTP: {$errstr} ({$errno})";
    }

    $read = function () use ($socket) {
        $data = '';
        while ($str = fgets($socket, 515)) {
            $data .= $str;
            if (isset($str[3]) && $str[3] === ' ') break;
        }
        return $data;
    };
    $cmd = function (string $c) use ($socket, $read) {
        fputs($socket, $c . "\r\n");
        return $read();
    };

    $read(); // banner
    $cmd('EHLO localhost');

    if ($secure === 'tls') {
        $cmd('STARTTLS');
        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            fclose($socket);
            return 'Gagal mengaktifkan TLS.';
        }
        $cmd('EHLO localhost');
    }

    if ($user !== '' && $pass !== '') {
        $cmd('AUTH LOGIN');
        $cmd(base64_encode($user));
        $resp = $cmd(base64_encode($pass));
        if (strpos($resp, '235') === false) {
            fclose($socket);
            return 'Autentikasi SMTP gagal. Cek user/password.';
        }
    }

    $cmd('MAIL FROM:<' . $from . '>');
    $resp = $cmd('RCPT TO:<' . $to . '>');
    if (strpos($resp, '250') === false && strpos($resp, '251') === false) {
        fclose($socket);
        return 'RCPT TO ditolak: ' . trim($resp);
    }

    $cmd('DATA');
    $headers  = 'From: ' . ($fromName !== '' ? "{$fromName} <{$from}>" : $from) . "\r\n";
    $headers .= 'To: ' . ($toName !== '' ? "{$toName} <{$to}>" : $to) . "\r\n";
    $headers .= 'Subject: =?UTF-8?B?' . base64_encode($subject) . "?=\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $headers .= "Content-Transfer-Encoding: base64\r\n";
    $headers .= "\r\n";
    $headers .= chunk_split(base64_encode($body));
    $headers .= "\r\n.";

    $resp = $cmd($headers);
    $cmd('QUIT');
    fclose($socket);

    if (strpos($resp, '250') === false) {
        return 'Gagal kirim: ' . trim($resp);
    }
    return true;
}
/**
 * Kirim pesan Telegram via Bot API
 * Return true jika sukses, string error jika gagal
 */
function send_telegram(string $message): true|string
{
    $token  = get_setting('telegram_bot_token', '');
    $chatId = get_setting('telegram_chat_id', '');

    if ($token === '' || $chatId === '') {
        return 'Telegram belum dikonfigurasi. Isi Bot Token dan Chat ID di Pengaturan.';
    }

    $url = 'https://api.telegram.org/bot' . $token . '/sendMessage';
    $payload = json_encode([
        'chat_id'    => $chatId,
        'text'       => $message,
        'parse_mode' => 'HTML',
    ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
    ]);
    $response = curl_exec($ch);
    $errno    = curl_errno($ch);
    $error    = curl_error($ch);
    curl_close($ch);

    if ($errno) {
        return 'Gagal konek ke Telegram: ' . $error;
    }

    $data = json_decode($response, true);
    if (empty($data['ok'])) {
        return 'Telegram error: ' . ($data['description'] ?? $response);
    }
    return true;
}
/**
 * Upload gambar umum (landing page, dll)
 * Return path relatif dari /uploads atau null jika gagal
 */
function upload_image(array $file, string $subdir = 'landing', int $maxMb = 5): ?string
{
    if (empty($file['tmp_name']) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return null;
    }

    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed, true)) {
        return null;
    }

    if (($file['size'] ?? 0) > $maxMb * 1024 * 1024) {
        return null;
    }

    // Validasi MIME sederhana
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    $allowedMime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($mime, $allowedMime, true)) {
        return null;
    }

    $dir = __DIR__ . '/../public/uploads/' . $subdir;
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $name = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $path = $dir . '/' . $name;

    if (!move_uploaded_file($file['tmp_name'], $path)) {
        return null;
    }

    return $subdir . '/' . $name; // contoh: landing/20260815_xxx.jpg
}
?>