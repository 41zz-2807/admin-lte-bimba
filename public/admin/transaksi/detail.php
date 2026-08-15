<?php
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/flash.php';

require_login();

$pageTitle = 'Detail Transaksi';
global $pdo;

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT t.*, s.nama AS nama_siswa
    FROM transaksi t LEFT JOIN siswa s ON s.id = t.siswa_id WHERE t.id = ?');
$stmt->execute([$id]);
$t = $stmt->fetch();

if (!$t) {
    set_flash('danger', 'Transaksi tidak ditemukan.');
    redirect('admin/transaksi/');
}

$spp = [];
if ($t['kategori'] === 'spp') {
    $st = $pdo->prepare('SELECT * FROM transaksi_spp_bulan WHERE transaksi_id = ? ORDER BY tahun_ajaran, bulan');
    $st->execute([$id]);
    $spp = $st->fetchAll();
}
$bulanNama = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',
              7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];

ob_start();
?>
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <h3 class="card-title">Detail Transaksi #<?= (int)$t['id'] ?></h3>
                <a href="/admin/transaksi/" class="btn btn-sm btn-secondary">Kembali</a>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <tr><th style="width:180px">Tanggal</th><td><?= e(date('d/m/Y', strtotime($t['tanggal']))) ?></td></tr>
                    <tr><th>Jenis</th>
                        <td><span class="badge text-bg-<?= $t['jenis']==='pemasukan'?'success':'danger' ?>"><?= e(ucfirst($t['jenis'])) ?></span></td>
                    </tr>
                    <tr><th>Kategori</th><td><?= e($t['kategori']) ?></td></tr>
                    <tr><th>Siswa</th><td><?= e($t['nama_siswa'] ?: '-') ?></td></tr>
                    <tr><th>Jumlah</th><td><strong>Rp <?= number_format((float)$t['jumlah'], 0, ',', '.') ?></strong></td></tr>
                    <tr><th>Keterangan</th><td><?= e($t['keterangan'] ?: '-') ?></td></tr>
                    <tr><th>Bukti</th>
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
                        <thead><tr><th>Bulan</th><th>Tahun Ajaran</th><th class="text-end">Jumlah</th></tr></thead>
                        <tbody>
                            <?php foreach ($spp as $d): ?>
                                <tr>
                                    <td><?= e($bulanNama[(int)$d['bulan']] ?? $d['bulan']) ?></td>
                                    <td><?= e($d['tahun_ajaran'] ?? '-') ?></td>
                                    <td class="text-end">Rp <?= number_format((float)$d['jumlah'], 0, ',', '.') ?></td>
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