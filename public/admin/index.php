<?php
require_once __DIR__ . '/../../includes/auth.php';
require_login();

$pageTitle = 'Dashboard';
$user = current_user();

ob_start();
?>
<div class="row">
    <div class="col-12 col-sm-6 col-md-3">
        <div class="info-box">
            <span class="info-box-icon text-bg-primary shadow-sm"><i class="bi bi-people-fill"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Total Siswa</span>
                <span class="info-box-number">0</span>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="info-box">
            <span class="info-box-icon text-bg-success shadow-sm"><i class="bi bi-person-badge"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Guru / Staff</span>
                <span class="info-box-number">0</span>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="info-box">
            <span class="info-box-icon text-bg-warning shadow-sm"><i class="bi bi-calendar-event"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Kegiatan Hari Ini</span>
                <span class="info-box-number">0</span>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="info-box">
            <span class="info-box-icon text-bg-danger shadow-sm"><i class="bi bi-exclamation-triangle"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Pending</span>
                <span class="info-box-number">0</span>
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