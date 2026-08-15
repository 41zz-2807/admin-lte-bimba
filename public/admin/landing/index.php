<?php
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/flash.php';
require_login();

$pageTitle = 'Kelola Landing Page';
global $pdo;

$counts = [
    'hero'      => (int) $pdo->query('SELECT COUNT(*) FROM landing_hero')->fetchColumn(),
    'fasilitas' => (int) $pdo->query('SELECT COUNT(*) FROM landing_fasilitas')->fetchColumn(),
    'pengajar'  => (int) $pdo->query('SELECT COUNT(*) FROM landing_pengajar')->fetchColumn(),
    'testimoni' => (int) $pdo->query('SELECT COUNT(*) FROM landing_testimoni')->fetchColumn(),
];

ob_start();
?>
<div class="row g-3">
    <div class="col-md-3">
        <a href="/admin/landing/hero.php" class="text-decoration-none">
            <div class="card h-100 hover-shadow">
                <div class="card-body text-center">
                    <i class="bi bi-images display-5 text-primary"></i>
                    <h5 class="mt-3 mb-1">Hero Slider</h5>
                    <p class="text-muted mb-0"><?= $counts['hero'] ?> slide</p>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="/admin/landing/fasilitas.php" class="text-decoration-none">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="bi bi-grid-1x2 display-5 text-success"></i>
                    <h5 class="mt-3 mb-1">Fasilitas</h5>
                    <p class="text-muted mb-0"><?= $counts['fasilitas'] ?> item</p>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="/admin/landing/pengajar.php" class="text-decoration-none">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="bi bi-people display-5 text-warning"></i>
                    <h5 class="mt-3 mb-1">Pengajar</h5>
                    <p class="text-muted mb-0"><?= $counts['pengajar'] ?> orang</p>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="/admin/landing/testimoni.php" class="text-decoration-none">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="bi bi-chat-quote display-5 text-danger"></i>
                    <h5 class="mt-3 mb-1">Testimoni</h5>
                    <p class="text-muted mb-0"><?= $counts['testimoni'] ?> item</p>
                </div>
            </div>
        </a>
    </div>
</div>

<div class="alert alert-info mt-4 mb-0">
    <i class="bi bi-info-circle me-1"></i>
    Kelola konten beranda di sini. Perubahan langsung tampil di
    <a href="/" target="_blank">halaman depan</a>.
    Jika data kosong, beranda memakai konten default.
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../../includes/layout_admin.php';
