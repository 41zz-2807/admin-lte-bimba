<?php
require_once __DIR__ . '/../../includes/auth.php';
require_login();

$pageTitle = 'Dashboard';
$user = current_user();
global $pdo;

// Hitung data real
$totalSiswa = (int) $pdo->query("SELECT COUNT(*) FROM siswa")->fetchColumn();
$siswaAktif = (int) $pdo->query("SELECT COUNT(*) FROM siswa WHERE status = 'aktif'")->fetchColumn();
$totalGuru  = (int) $pdo->query("SELECT COUNT(*) FROM guru_staff")->fetchColumn();
$guruAktif  = (int) $pdo->query("SELECT COUNT(*) FROM guru_staff WHERE status = 'aktif'")->fetchColumn();

// Transaksi bulan ini
$bulanIni = date('Y-m');
$st = $pdo->prepare("SELECT
    COALESCE(SUM(CASE WHEN jenis='pemasukan' THEN jumlah ELSE 0 END), 0) AS masuk,
    COALESCE(SUM(CASE WHEN jenis='pengeluaran' THEN jumlah ELSE 0 END), 0) AS keluar
  FROM transaksi WHERE DATE_FORMAT(tanggal, '%Y-%m') = ?");
$st->execute([$bulanIni]);
$keuangan = $st->fetch();
$totalMasuk  = (float) $keuangan['masuk'];
$totalKeluar = (float) $keuangan['keluar'];

ob_start();
?>
<div class="row">
    <div class="col-12 col-sm-6 col-md-3 d-flex">
        <div class="info-box w-100">
            <span class="info-box-icon text-bg-primary shadow-sm">
                <i class="bi bi-people-fill"></i>
            </span>
            <div class="info-box-content">
                <span class="info-box-text">Total Siswa</span>
                <span class="info-box-number"><?= $totalSiswa ?></span>
                <span class="info-box-more">Aktif: <?= $siswaAktif ?></span>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3 d-flex">
        <div class="info-box w-100">
            <span class="info-box-icon text-bg-success shadow-sm">
                <i class="bi bi-person-badge"></i>
            </span>
            <div class="info-box-content">
                <span class="info-box-text">Guru / Staff</span>
                <span class="info-box-number"><?= $totalGuru ?></span>
                <span class="info-box-more">Aktif: <?= $guruAktif ?></span>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3 d-flex">
        <div class="info-box w-100">
            <span class="info-box-icon text-bg-warning shadow-sm">
                <i class="bi bi-cash-stack"></i>
            </span>
            <div class="info-box-content">
                <span class="info-box-text">Pemasukan Bulan Ini</span>
                <span class="info-box-number">Rp <?= number_format($totalMasuk, 0, ',', '.') ?></span>
                <span class="info-box-more">&nbsp;</span>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3 d-flex">
        <div class="info-box w-100">
            <span class="info-box-icon text-bg-danger shadow-sm">
                <i class="bi bi-cash"></i>
            </span>
            <div class="info-box-content">
                <span class="info-box-text">Pengeluaran Bulan Ini</span>
                <span class="info-box-number">Rp <?= number_format($totalKeluar, 0, ',', '.') ?></span>
                <span class="info-box-more">&nbsp;</span>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Selamat Datang, <?= e($user['name']) ?>!</h3>
            </div>
            <div class="card-body">
                <p>
                    Anda login sebagai
                    <strong><?= is_superadmin() ? 'Superadmin' : 'Admin' ?></strong>
                    (<?= e($user['email']) ?>).
                </p>
                <?php if (is_superadmin()): ?>
                    <div class="alert alert-info mb-0">
                        Sebagai <strong>Superadmin</strong> Anda memiliki akses penuh untuk mengelola user dan semua fitur sistem.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../includes/layout_admin.php';