<?php
require_once __DIR__ . '/../../../includes/auth.php';
require_login();

// Jangan tampilkan error/warning ke output (bikin CSV rusak)
error_reporting(0);
ini_set('display_errors', '0');

global $pdo;

$siswa = $pdo->query(
    'SELECT nis, nama, jenis_kelamin, tanggal_lahir, nama_ortu, no_hp_ortu, alamat, status, catatan
     FROM siswa ORDER BY nama ASC'
)->fetchAll();

$filename = 'template_siswa_' . date('Ymd_His') . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// BOM supaya Excel baca UTF-8 dengan benar
echo "\xEF\xBB\xBF";

$out = fopen('php://output', 'w');

// Header kolom
$headers = [
    'nis',
    'nama',
    'jenis_kelamin',
    'tanggal_lahir',
    'nama_ortu',
    'no_hp_ortu',
    'alamat',
    'status',
    'catatan',
];

// PHP 8.4: parameter $escape wajib diisi
fputcsv($out, $headers, ',', '"', '\\');

foreach ($siswa as $row) {
    fputcsv($out, [
        $row['nis'] ?? '',
        $row['nama'] ?? '',
        $row['jenis_kelamin'] ?? '',
        $row['tanggal_lahir'] ?? '',
        $row['nama_ortu'] ?? '',
        $row['no_hp_ortu'] ?? '',
        $row['alamat'] ?? '',
        $row['status'] ?? 'aktif',
        $row['catatan'] ?? '',
    ], ',', '"', '\\');
}

fclose($out);
exit;