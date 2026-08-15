<?php
/**
 * Frontend - Halaman Utama Bimba KSR
 * Menggunakan Bootstrap 5
 */

require_once __DIR__ . '/../includes/helpers.php';
$app = require __DIR__ . '/../config/app.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($app['name']) ?> - Beranda</title>

    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        :root {
            --primary: #0d6efd;
        }
        .hero {
            background: linear-gradient(135deg, #0d6efd 0%, #6610f2 100%);
            color: #fff;
            padding: 5rem 0;
        }
        .feature-icon {
            font-size: 2.5rem;
            color: var(--primary);
        }
        footer {
            background: #212529;
            color: #adb5bd;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="/">
                <i class="bi bi-mortarboard-fill me-2"></i><?= e($app['name']) ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link active" href="/">Beranda</a></li>
                    <li class="nav-item"><a class="nav-link" href="#tentang">Tentang</a></li>
                    <li class="nav-item"><a class="nav-link" href="#layanan">Layanan</a></li>
                    <li class="nav-item"><a class="nav-link" href="#kontak">Kontak</a></li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-outline-light btn-sm ms-lg-2 px-3" href="/admin/">Admin</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero -->
    <section class="hero text-center">
        <div class="container">
            <h1 class="display-4 fw-bold mb-3">Selamat Datang di <?= e($app['name']) ?></h1>
            <p class="lead mb-4">Kerangka awal aplikasi siap dikembangkan.</p>
            <a href="#layanan" class="btn btn-light btn-lg px-4">Lihat Layanan</a>
        </div>
    </section>

    <!-- Tentang -->
    <section id="tentang" class="py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h2 class="fw-bold mb-3">Tentang Aplikasi</h2>
                    <p class="text-muted">
                        Ini adalah kerangka awal (boilerplate) untuk aplikasi <strong>Bimba KSR</strong>.
                        Frontend menggunakan Bootstrap 5, sedangkan dashboard admin menggunakan AdminLTE 4.
                    </p>
                    <ul class="list-unstyled">
                        <li><i class="bi bi-check-circle-fill text-success me-2"></i>PHP 8.4 + Apache</li>
                        <li><i class="bi bi-check-circle-fill text-success me-2"></i>Docker Compose (port 8181)</li>
                        <li><i class="bi bi-check-circle-fill text-success me-2"></i>Siap terhubung ke MySQL yang sudah ada</li>
                    </ul>
                </div>
                <div class="col-lg-6 text-center">
                    <i class="bi bi-laptop display-1 text-primary opacity-50"></i>
                </div>
            </div>
        </div>
    </section>

    <!-- Layanan / Fitur -->
    <section id="layanan" class="py-5 bg-light">
        <div class="container">
            <h2 class="text-center fw-bold mb-5">Fitur Utama</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon mb-3"><i class="bi bi-people-fill"></i></div>
                            <h5 class="card-title">Manajemen Data</h5>
                            <p class="card-text text-muted">Kelola data siswa, guru, dan kegiatan melalui dashboard admin.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon mb-3"><i class="bi bi-calendar-check"></i></div>
                            <h5 class="card-title">Jadwal & Kegiatan</h5>
                            <p class="card-text text-muted">Atur jadwal kegiatan dan absensi dengan mudah.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon mb-3"><i class="bi bi-graph-up"></i></div>
                            <h5 class="card-title">Laporan</h5>
                            <p class="card-text text-muted">Generate laporan dan statistik secara real-time.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Kontak -->
    <section id="kontak" class="py-5">
        <div class="container text-center">
            <h2 class="fw-bold mb-3">Hubungi Kami</h2>
            <p class="text-muted mb-4">Silakan hubungi admin untuk informasi lebih lanjut.</p>
            <a href="mailto:admin@bimba-ksr.local" class="btn btn-primary">
                <i class="bi bi-envelope me-2"></i>Email Admin
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-4">
        <div class="container text-center">
            <p class="mb-0">&copy; <?= date('Y') ?> <?= e($app['name']) ?>. Versi <?= e($app['version']) ?></p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>