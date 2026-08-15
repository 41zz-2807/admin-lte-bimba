<?php
require_once __DIR__ . '/../../../includes/auth.php';
require_login();

error_reporting(0);
ini_set('display_errors', '0');

global $pdo;

$rows = $pdo->query(
    'SELECT nip, nama, jenis_kelamin, jabatan, no_hp, email, alamat, status, catatan
     FROM guru_staff ORDER BY nama ASC'
)->fetchAll();

$filename = 'template_guru_staff_' . date('Ymd_His') . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

echo "\xEF\xBB\xBF";

$out = fopen('php://output', 'w');

$headers = [
    'nip',
    'nama',
    'jenis_kelamin',  // L atau P
    'jabatan',
    'no_hp',
    'email',
    'alamat',
    'status',         // aktif | nonaktif
    'catatan',
];
fputcsv($out, $headers, ',', '"', '\\');

foreach ($rows as $row) {
    fputcsv($out, [
        $row['nip'] ?? '',
        $row['nama'] ?? '',
        $row['jenis_kelamin'] ?? '',
        $row['jabatan'] ?? '',
        $row['no_hp'] ?? '',
        $row['email'] ?? '',
        $row['alamat'] ?? '',
        $row['status'] ?? 'aktif',
        $row['catatan'] ?? '',
    ], ',', '"', '\\');
}

fclose($out);
exit;