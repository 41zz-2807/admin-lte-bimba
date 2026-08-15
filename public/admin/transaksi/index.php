<?php
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/flash.php';

require_login();

$pageTitle = 'Transaksi';
global $pdo;

if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $row = $pdo->prepare('SELECT bukti FROM transaksi WHERE id = ?');
    $row->execute([$id]);
    $t = $row->fetch();
    if ($t) {
        $pdo->prepare('DELETE FROM transaksi_spp_bulan WHERE transaksi_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM transaksi WHERE id = ?')->execute([$id]);
        if (!empty($t['bukti'])) {
            $f = __DIR__ . '/../../../uploads/' . $t['bukti'];
            if (is_file($f)) {
                @unlink($f);
            }
        }
        set_flash('success', 'Transaksi dihapus.');
    }
    redirect('admin/transaksi/');
}

$jenis    = $_GET['jenis'] ?? '';
$kategori = trim($_GET['kategori'] ?? '');
$dari     = $_GET['dari'] ?? '';
$sampai   = $_GET['sampai'] ?? '';
$q        = trim($_GET['q'] ?? '');

$sql = 'SELECT t.*, s.nama AS nama_siswa
        FROM transaksi t
        LEFT JOIN siswa s ON s.id = t.siswa_id
        WHERE 1=1';
$params = [];

if (in_array($jenis, ['pemasukan', 'pengeluaran'], true)) {
    $sql .= ' AND t.jenis = ?';
    $params[] = $jenis;
}
if ($kategori !== '') {
    $sql .= ' AND t.kategori = ?';
    $params[] = $kategori;
}
if ($dari !== '') {
    $sql .= ' AND t.tanggal >= ?';
    $params[] = $dari;
}
if ($sampai !== '') {
    $sql .= ' AND t.tanggal <= ?';
    $params[] = $sampai;
}
if ($q !== '') {
    $sql .= ' AND (t.keterangan LIKE ? OR s.nama LIKE ?)';
    $like = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
}
$sql .= ' ORDER BY t.tanggal DESC, t.id DESC LIMIT 200';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

// ringkasan filter
$sumSql = 'SELECT
    COALESCE(SUM(CASE WHEN jenis="pemasukan" THEN jumlah ELSE 0 END),0) AS total_masuk,
    COALESCE(SUM(CASE WHEN jenis="pengeluaran" THEN jumlah ELSE 0 END),0) AS total_keluar
  FROM transaksi WHERE 1=1';
$sumParams = [];
if (in_array($jenis, ['pemasukan', 'pengeluaran'], true)) {
    $sumSql .= ' AND jenis = ?';
    $sumParams[] = $jenis;
}
if ($kategori !== '') {
    $sumSql .= ' AND kategori = ?';
    $sumParams[] = $kategori;
}
if ($dari !== '') {
    $sumSql .= ' AND tanggal >= ?';
    $sumParams[] = $dari;
}
if ($sampai !== '') {
    $sumSql .= ' AND tanggal <= ?';
    $sumParams[] = $sampai;
}
$st = $pdo->prepare($sumSql);
$st->execute($sumParams);
$ringkas = $st->fetch();

ob_start();
?>
<div class="mb-3">
    <a href="/admin/transaksi/create.php" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Tambah Transaksi
    </a>
</div>

<div class="row mb-3">
    <div class="col-md-4">
        <div class="info-box">
            <span class="info-box-icon text-bg-success"><i class="bi bi-arrow-down-circle"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Total Pemasukan</span>
                <span class="info-box-number">Rp <?= number_format((float)$ringkas['total_masuk'], 0, ',', '.') ?></span>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="info-box">
            <span class="info-box-icon text-bg-danger"><i class="bi bi-arrow-up-circle"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Total Pengeluaran</span>
                <span class="info-box-number">Rp <?= number_format((float)$ringkas['total_keluar'], 0, ',', '.') ?></span>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="info-box">
            <span class="info-box-icon text-bg-primary"><i class="bi bi-wallet2"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Saldo (filter)</span>
                <span class="info-box-number">
                    Rp <?= number_format((float)$ringkas['total_masuk'] - (float)$ringkas['total_keluar'], 0, ',', '.') ?>
                </span>
            </div>
        </div>
    </div>
</div>

<form method="get" class="card mb-3">
    <div class="card-body row g-2 align-items-end">
        <div class="col-md-2">
            <label class="form-label">Jenis</label>
            <select name="jenis" class="form-select form-select-sm">
                <option value="">Semua</option>
                <option value="pemasukan" <?= $jenis === 'pemasukan' ? 'selected' : '' ?>>Pemasukan</option>
                <option value="pengeluaran" <?= $jenis === 'pengeluaran' ? 'selected' : '' ?>>Pengeluaran</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Kategori</label>
            <input type="text" name="kategori" class="form-control form-control-sm" value="<?= e($kategori) ?>" placeholder="spp, gaji...">
        </div>
        <div class="col-md-2">
            <label class="form-label">Dari</label>
            <input type="date" name="dari" class="form-control form-control-sm" value="<?= e($dari) ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label">Sampai</label>
            <input type="date" name="sampai" class="form-control form-control-sm" value="<?= e($sampai) ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label">Cari</label>
            <input type="text" name="q" class="form-control form-control-sm" value="<?= e($q) ?>" placeholder="ket / siswa">
        </div>
        <div class="col-md-2">
            <button class="btn btn-sm btn-outline-secondary w-100">Filter</button>
        </div>
    </div>
</form>

<div class="card">
    <div class="card-header"><h3 class="card-title">Daftar Transaksi</h3></div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover table-striped mb-0">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Jenis</th>
                    <th>Kategori</th>
                    <th>Siswa</th>
                    <th>Keterangan</th>
                    <th class="text-end">Jumlah</th>
                    <th>Bukti</th>
                    <th style="width:100px">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">Belum ada transaksi.</td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><?= e(date('d/m/Y', strtotime($r['tanggal']))) ?></td>
                            <td>
                                <span class="badge text-bg-<?= $r['jenis'] === 'pemasukan' ? 'success' : 'danger' ?>">
                                    <?= e(ucfirst($r['jenis'])) ?>
                                </span>
                            </td>
                            <td><?= e($r['kategori']) ?></td>
                            <td><?= e($r['nama_siswa'] ?: '-') ?></td>
                            <td><?= e(mb_strimwidth($r['keterangan'] ?? '', 0, 40, '...')) ?></td>
                            <td class="text-end">Rp <?= number_format((float)$r['jumlah'], 0, ',', '.') ?></td>
                            <td>
                                <?php if ($r['bukti']): ?>
                                    <a href="/uploads/<?= e($r['bukti']) ?>" target="_blank" class="btn btn-sm btn-outline-info">
                                        <i class="bi bi-paperclip"></i>
                                    </a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="/admin/transaksi/detail.php?id=<?= (int)$r['id'] ?>" class="btn btn-sm btn-outline-primary" title="Detail">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="?delete=<?= (int)$r['id'] ?>" class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('Hapus transaksi ini?')" title="Hapus">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../../includes/layout_admin.php';