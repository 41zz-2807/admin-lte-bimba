<?php
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/flash.php';
require_once __DIR__ . '/../../../includes/helpers.php';
require_login();
$pageTitle = 'Konfirmasi Pembayaran Wali';
global $pdo;
$admin = current_user();

$bulanNama = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',
              7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];

// Terima
if (isset($_GET['terima'])) {
    $id = (int)$_GET['terima'];
    $st = $pdo->prepare('SELECT * FROM konfirmasi_bayar WHERE id=? AND status=\'pending\' LIMIT 1');
    $st->execute([$id]);
    $k = $st->fetch();
    if ($k) {
        $pdo->beginTransaction();
        try {
            // Insert transaksi
            $ins = $pdo->prepare(
                'INSERT INTO transaksi (jenis, kategori, siswa_id, jumlah, tanggal, keterangan, bukti, user_id)
                 VALUES (\'pemasukan\', ?, ?, ?, ?, ?, ?, ?)'
            );
            $kategori = $k['bulan_spp'] ? 'SPP' : 'lain';
            $ins->execute([
                $kategori,
                $k['siswa_id'],
                $k['jumlah'],
                $k['tanggal_bayar'],
                $k['keterangan'],
                $k['bukti'],
                $admin['id'],
            ]);
            $transaksiId = (int)$pdo->lastInsertId();

            if ($k['bulan_spp'] && $k['tahun_ajaran']) {
                $pdo->prepare(
                    'INSERT INTO transaksi_spp_bulan (transaksi_id, siswa_id, bulan, tahun_ajaran, jumlah)
                     VALUES (?,?,?,?,?)
                     ON DUPLICATE KEY UPDATE jumlah=VALUES(jumlah), transaksi_id=VALUES(transaksi_id)'
                )->execute([$transaksiId, $k['siswa_id'], $k['bulan_spp'], $k['tahun_ajaran'], $k['jumlah']]);
            }

            $noKwitansi = 'KW/' . date('Ymd') . '/' . str_pad((string)$id, 4, '0', STR_PAD_LEFT);

            $pdo->prepare(
                'UPDATE konfirmasi_bayar SET status=\'diterima\', transaksi_id=?, no_kwitansi=?, verified_by=?, verified_at=NOW() WHERE id=?'
            )->execute([$transaksiId, $noKwitansi, $admin['id'], $id]);

            $pdo->commit();
            set_flash('success', 'Pembayaran diterima. Kwitansi: ' . $noKwitansi);
        } catch (Exception $e) {
            $pdo->rollBack();
            set_flash('danger', 'Gagal: ' . $e->getMessage());
        }
    }
    redirect('admin/konfirmasi-bayar/');
}

// Tolak
if (isset($_GET['tolak'])) {
    $id = (int)$_GET['tolak'];
    $pdo->prepare(
        'UPDATE konfirmasi_bayar SET status=\'ditolak\', verified_by=?, verified_at=NOW() WHERE id=? AND status=\'pending\''
    )->execute([$admin['id'], $id]);
    set_flash('success', 'Pembayaran ditolak.');
    redirect('admin/konfirmasi-bayar/');
}

$filter = $_GET['status'] ?? 'pending';
$sql = 'SELECT k.*, s.nama AS nama_siswa, u.name AS nama_wali
        FROM konfirmasi_bayar k
        JOIN siswa s ON s.id = k.siswa_id
        JOIN users u ON u.id = k.user_id
        WHERE 1=1';
$params = [];
if (in_array($filter, ['pending','diterima','ditolak'], true)) {
    $sql .= ' AND k.status = ?';
    $params[] = $filter;
}
$sql .= ' ORDER BY k.created_at DESC';
$st = $pdo->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll();

ob_start();
?>
<div class="mb-3 d-flex gap-2">
  <a href="?status=pending" class="btn btn-sm <?= $filter==='pending'?'btn-warning':'btn-outline-warning' ?>">Pending</a>
  <a href="?status=diterima" class="btn btn-sm <?= $filter==='diterima'?'btn-success':'btn-outline-success' ?>">Diterima</a>
  <a href="?status=ditolak" class="btn btn-sm <?= $filter==='ditolak'?'btn-danger':'btn-outline-danger' ?>">Ditolak</a>
  <a href="?status=all" class="btn btn-sm btn-outline-secondary">Semua</a>
</div>
<div class="card">
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead>
        <tr>
          <th>Tgl</th><th>Wali</th><th>Anak</th><th>Jumlah</th><th>SPP</th><th>Bukti</th><th>Status</th><th></th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= e($r['tanggal_bayar']) ?></td>
          <td><?= e($r['nama_wali']) ?></td>
          <td><?= e($r['nama_siswa']) ?></td>
          <td>Rp <?= number_format($r['jumlah'], 0, ',', '.') ?></td>
          <td>
            <?php if ($r['bulan_spp']): ?>
              <?= $bulanNama[(int)$r['bulan_spp']] ?? '' ?> <?= e($r['tahun_ajaran'] ?? '') ?>
            <?php else: ?>-<?php endif; ?>
          </td>
          <td>
            <?php if ($r['bukti']): ?>
              <a href="/uploads/<?= e($r['bukti']) ?>" target="_blank">Lihat</a>
            <?php endif; ?>
          </td>
          <td><span class="badge text-bg-<?= $r['status']==='diterima'?'success':($r['status']==='ditolak'?'danger':'warning') ?>"><?= e($r['status']) ?></span></td>
          <td class="text-nowrap">
            <?php if ($r['status'] === 'pending'): ?>
              <a href="?terima=<?= (int)$r['id'] ?>" class="btn btn-sm btn-success" onclick="return confirm('Terima pembayaran ini?')">Terima</a>
              <a href="?tolak=<?= (int)$r['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Tolak?')">Tolak</a>
            <?php elseif ($r['no_kwitansi']): ?>
              <small><?= e($r['no_kwitansi']) ?></small>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?>
        <tr><td colspan="8" class="text-center text-muted">Tidak ada data</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../../includes/layout_admin.php';