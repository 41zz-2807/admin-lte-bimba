<?php
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/flash.php';

require_login();

$pageTitle = 'Upload Data Siswa';
$error = '';
$success_count = 0;
$skip_count = 0;
$errors_detail = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_FILES['file']['tmp_name'])) {
        $error = 'Pilih file CSV terlebih dahulu.';
    } else {
        $file = $_FILES['file'];
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, ['csv', 'txt'], true)) {
            $error = 'Format harus CSV. Di Excel: File → Save As → CSV UTF-8.';
        } elseif ($file['error'] !== UPLOAD_ERR_OK) {
            $error = 'Gagal upload file.';
        } else {
            global $pdo;

            $handle = fopen($file['tmp_name'], 'r');
            if (!$handle) {
                $error = 'Tidak bisa membaca file.';
            } else {
                // Baca header
                $header = fgetcsv($handle);
                if ($header === false) {
                    $error = 'File kosong atau tidak valid.';
                } else {
                    // Hapus BOM jika ada
                    $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0] ?? '');

                    $expected = ['nis', 'nama', 'jenis_kelamin', 'tanggal_lahir', 'nama_ortu', 'no_hp_ortu', 'alamat', 'status', 'catatan'];
                    $map = [];
                    foreach ($expected as $col) {
                        $idx = array_search($col, $header, true);
                        if ($idx === false) {
                            $error = 'Kolom wajib tidak ditemukan: ' . $col . '. Download template dulu.';
                            break;
                        }
                        $map[$col] = $idx;
                    }

                    if ($error === '') {
                        $line = 1; // header = baris 1
                        $ins = $pdo->prepare('INSERT INTO siswa
                            (nis, nama, jenis_kelamin, tanggal_lahir, nama_ortu, no_hp_ortu, alamat, status, catatan)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');

                        while (($row = fgetcsv($handle)) !== false) {
                            $line++;

                            // Skip baris kosong
                            if (count(array_filter($row, fn($v) => trim((string)$v) !== '')) === 0) {
                                continue;
                            }

                            $nama = trim($row[$map['nama']] ?? '');
                            if ($nama === '') {
                                $skip_count++;
                                $errors_detail[] = "Baris {$line}: nama kosong, dilewati.";
                                continue;
                            }

                            $jk = strtoupper(trim($row[$map['jenis_kelamin']] ?? 'L'));
                            if (!in_array($jk, ['L', 'P'], true)) {
                                $jk = 'L';
                            }

                            $status = strtolower(trim($row[$map['status']] ?? 'aktif'));
                            if (!in_array($status, ['aktif', 'nonaktif', 'lulus'], true)) {
                                $status = 'aktif';
                            }

                            $nis = trim($row[$map['nis']] ?? '');
                            $tgl = trim($row[$map['tanggal_lahir']] ?? '');
                            // Normalisasi tanggal Excel (bisa 20/05/2018 atau 2018-05-20)
                            if ($tgl !== '') {
                                $ts = strtotime(str_replace('/', '-', $tgl));
                                $tgl = $ts ? date('Y-m-d', $ts) : null;
                            } else {
                                $tgl = null;
                            }

                            // Cek NIS duplikat
                            if ($nis !== '') {
                                $cek = $pdo->prepare('SELECT id FROM siswa WHERE nis = ? LIMIT 1');
                                $cek->execute([$nis]);
                                if ($cek->fetch()) {
                                    $skip_count++;
                                    $errors_detail[] = "Baris {$line}: NIS {$nis} sudah ada, dilewati.";
                                    continue;
                                }
                            }

                            try {
                                $ins->execute([
                                    $nis ?: null,
                                    $nama,
                                    $jk,
                                    $tgl,
                                    trim($row[$map['nama_ortu']] ?? '') ?: null,
                                    trim($row[$map['no_hp_ortu']] ?? '') ?: null,
                                    trim($row[$map['alamat']] ?? '') ?: null,
                                    $status,
                                    trim($row[$map['catatan']] ?? '') ?: null,
                                ]);
                                $success_count++;
                            } catch (Exception $e) {
                                $skip_count++;
                                $errors_detail[] = "Baris {$line}: gagal simpan.";
                            }
                        }

                        if ($success_count > 0) {
                            set_flash('success', "Berhasil import {$success_count} data siswa." . ($skip_count ? " Dilewati: {$skip_count}." : ''));
                            redirect('admin/siswa/');
                        } elseif ($error === '') {
                            $error = 'Tidak ada data yang berhasil diimport.' . ($skip_count ? " Dilewati: {$skip_count}." : '');
                        }
                    }
                }
                fclose($handle);
            }
        }
    }
}

ob_start();
?>
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Upload Data Siswa (Excel/CSV)</h3>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= e($error) ?></div>
                <?php endif; ?>

                <?php if (!empty($errors_detail)): ?>
                    <div class="alert alert-warning">
                        <strong>Detail:</strong>
                        <ul class="mb-0 small">
                            <?php foreach (array_slice($errors_detail, 0, 20) as $d): ?>
                                <li><?= e($d) ?></li>
                            <?php endforeach; ?>
                            <?php if (count($errors_detail) > 20): ?>
                                <li>... dan <?= count($errors_detail) - 20 ?> lainnya</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="alert alert-info">
                    <ol class="mb-0">
                        <li>Download template dulu lewat tombol <strong>Download Excel</strong>.</li>
                        <li>Isi data di Excel (jangan ubah nama kolom header).</li>
                        <li>Simpan sebagai <strong>CSV UTF-8</strong> (File → Save As → CSV UTF-8).</li>
                        <li>Upload file di form bawah ini.</li>
                    </ol>
                    <hr>
                    <small>
                        <strong>jenis_kelamin:</strong> L atau P &nbsp;|&nbsp;
                        <strong>tanggal_lahir:</strong> YYYY-MM-DD (contoh 2018-05-20) &nbsp;|&nbsp;
                        <strong>status:</strong> aktif / nonaktif / lulus
                    </small>
                </div>

                <form method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">File CSV</label>
                        <input type="file" name="file" class="form-control" accept=".csv,text/csv" required>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-upload me-1"></i> Upload & Import
                        </button>
                        <a href="/admin/siswa/" class="btn btn-secondary">Kembali</a>
                        <a href="/admin/siswa/export.php" class="btn btn-outline-primary">
                            <i class="bi bi-download me-1"></i> Download Template
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../../includes/layout_admin.php';    