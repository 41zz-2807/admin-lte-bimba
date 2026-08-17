<?php
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/flash.php';
require_once __DIR__ . '/../../../includes/helpers.php';

require_login();

$pageTitle = 'Detail Transaksi';
global $pdo;

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare(
    'SELECT t.*, s.nama AS nama_siswa, s.email_ortu, s.nama_ortu, s.nis
     FROM transaksi t
     LEFT JOIN siswa s ON s.id = t.siswa_id
     WHERE t.id = ?'
);
$stmt->execute([$id]);
$t = $stmt->fetch();

if (!$t) {
    set_flash('danger', 'Transaksi tidak ditemukan.');
    redirect('admin/transaksi/');
}

$isSpp = strtolower((string) $t['kategori']) === 'spp';

$spp = [];
if ($isSpp) {
    $st = $pdo->prepare(
        'SELECT * FROM transaksi_spp_bulan WHERE transaksi_id = ? ORDER BY tahun_ajaran, bulan'
    );
    $st->execute([$id]);
    $spp = $st->fetchAll();
}

$bulanNama = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
];

// Pastikan kwitansi ada (untuk data SPP lama)
if ($isSpp && (empty($t['no_kwitansi']) || empty($t['kode_verifikasi']))) {
    $noKwitansi = 'KW/T/' . date('Ymd') . '/' . str_pad((string) $id, 4, '0', STR_PAD_LEFT);
    do {
        $kode = strtoupper(bin2hex(random_bytes(8)));
        $cek  = $pdo->prepare('SELECT id FROM transaksi WHERE kode_verifikasi = ? LIMIT 1');
        $cek->execute([$kode]);
    } while ($cek->fetch());

    try {
        $pdo->prepare(
            'UPDATE transaksi SET no_kwitansi = ?, kode_verifikasi = ? WHERE id = ?'
        )->execute([$noKwitansi, $kode, $id]);
        $t['no_kwitansi']     = $noKwitansi;
        $t['kode_verifikasi'] = $kode;
    } catch (PDOException $e) {
        // kolom belum ada
    }
}

// Kirim ulang email dari halaman detail
if ($isSpp && isset($_GET['kirim_email'])) {
    $email = trim((string) ($t['email_ortu'] ?? ''));
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        set_flash('danger', 'Email ortu kosong / tidak valid.');
    } elseif (empty($t['kode_verifikasi'])) {
        set_flash('danger', 'Kode verifikasi belum tersedia. Jalankan migrasi kolom transaksi dulu.');
    } else {
        $app      = require __DIR__ . '/../../../config/app.php';
        $namaOrtu = trim((string) ($t['nama_ortu'] ?? '')) ?: 'Bapak/Ibu';
        $verifyUrl = rtrim($app['url'] ?? '', '/') . '/verifikasi-kwitansi.php?kode=' . urlencode($t['kode_verifikasi']);

        $body = "Halo {$namaOrtu},\n\n"
            . "Pembayaran SPP {$app['name']} telah dicatat.\n\n"
            . "Nama siswa   : " . ($t['nama_siswa'] ?? '-') . "\n"
            . "Tanggal bayar: {$t['tanggal']}\n"
            . "Jumlah       : Rp " . number_format((float) $t['jumlah'], 0, ',', '.') . "\n"
            . "Keterangan   : " . ($t['keterangan'] ?: '-') . "\n"
            . "No. Kwitansi : " . ($t['no_kwitansi'] ?? '-') . "\n"
            . "Kode verifikasi: {$t['kode_verifikasi']}\n\n"
            . "Cek keaslian:\n{$verifyUrl}\n\n"
            . "Terima kasih.\n{$app['name']}";

        $result = send_smtp_mail(
            $email,
            'Kwitansi SPP ' . ($t['no_kwitansi'] ?? '') . ' — ' . $app['name'],
            $body,
            $namaOrtu
        );

        if ($result === true) {
            set_flash('success', 'Email kwitansi terkirim ke ' . $email);
        } else {
            set_flash('danger', 'Gagal kirim email: ' . $result);
        }
    }
    redirect('admin/transaksi/detail.php?id=' . $id);
}

ob_start();
?>
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h3 class="card-title mb-0">Detail Transaksi #<?= (int) $t['id'] ?></h3>
                <div class="d-flex flex-wrap gap-2">
                    <?php if ($isSpp): ?>
                        <a href="/admin/transaksi/kwitansi.php?id=<?= (int) $id ?>"
                           class="btn btn-sm btn-primary">
                            <i class="bi bi-receipt me-1"></i> Lihat / Cetak Kwitansi
                        </a>
                        <a href="?id=<?= (int) $id ?>&kirim_email=1"
                           class="btn btn-sm btn-success"
                           onclick="return confirm('Kirim kwitansi ke email orang tua?')">
                            <i class="bi bi-envelope me-1"></i> Kirim Email Kwitansi
                        </a>
                    <?php endif; ?>
                    <a href="/admin/transaksi/" class="btn btn-sm btn-secondary">Kembali</a>
                </div>
            </div>
            <div class="card-body">
                <?php $flash = get_flash(); if ($flash): ?>
                    <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
                <?php endif; ?>

                <table class="table table-bordered">
                    <tr>
                        <th style="width:180px">Tanggal</th>
                        <td><?= e(date('d/m/Y', strtotime($t['tanggal']))) ?></td>
                    </tr>
                    <tr>
                        <th>Jenis</th>
                        <td>
                            <span class="badge text-bg-<?= $t['jenis'] === 'pemasukan' ? 'success' : 'danger' ?>">
                                <?= e(ucfirst($t['jenis'])) ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>Kategori</th>
                        <td><?= e($t['kategori']) ?></td>
                    </tr>
                    <tr>
                        <th>Siswa</th>
                        <td><?= e($t['nama_siswa'] ?: '-') ?></td>
                    </tr>
                    <tr>
                        <th>Jumlah</th>
                        <td><strong>Rp <?= number_format((float) $t['jumlah'], 0, ',', '.') ?></strong></td>
                    </tr>
                    <tr>
                        <th>Keterangan</th>
                        <td><?= e($t['keterangan'] ?: '-') ?></td>
                    </tr>
                    <?php if ($isSpp && !empty($t['no_kwitansi'])): ?>
                        <tr>
                            <th>No. Kwitansi</th>
                            <td><code><?= e($t['no_kwitansi']) ?></code></td>
                        </tr>
                        <tr>
                            <th>Kode Verifikasi</th>
                            <td><code><?= e($t['kode_verifikasi'] ?? '-') ?></code></td>
                        </tr>
                    <?php endif; ?>
                    <tr>
                        <th>Bukti</th>
                        <td>
                            <?php if ($t['bukti']): ?>
                                <a href="/uploads/<?= e($t['bukti']) ?>" target="_blank">Lihat / Download</a>
                            <?php else: ?>-<?php endif; ?>
                        </td>
                    </tr>
                </table>

                <?php if ($spp): ?>
                    <h5 class="mt-3">Rincian Bulan SPP</h5>
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>Bulan</th>
                                <th>Tahun Ajaran</th>
                                <th class="text-end">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($spp as $d): ?>
                                <tr>
                                    <td><?= e($bulanNama[(int) $d['bulan']] ?? $d['bulan']) ?></td>
                                    <td><?= e($d['tahun_ajaran'] ?? '-') ?></td>
                                    <td class="text-end">
                                        Rp <?= number_format((float) $d['jumlah'], 0, ',', '.') ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../../includes/layout_admin.php';