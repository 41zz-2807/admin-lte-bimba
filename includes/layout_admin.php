<?php
/**
 * Layout Admin (AdminLTE 4)
 * Cara pakai:
 *   $pageTitle = 'Judul';
 *   $content = '... html konten ...';
 *   require __DIR__ . '/../includes/layout_admin.php';
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/flash.php';

require_login();

$app  = require __DIR__ . '/../config/app.php';
$user = current_user();
$flash = get_flash();

// Path aktif untuk menu (opsional)
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Admin') ?> | <?= e($app['name']) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-beta3/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
<div class="app-wrapper">

    <!-- Navbar -->
    <nav class="app-header navbar navbar-expand bg-body">
        <div class="container-fluid">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button">
                        <i class="bi bi-list"></i>
                    </a>
                </li>
                <li class="nav-item d-none d-md-block">
                    <a href="/" class="nav-link" target="_blank">Lihat Website</a>
                </li>
            </ul>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link" data-bs-toggle="dropdown" href="#">
                        <i class="bi bi-person-circle"></i>
                        <?= e($user['name']) ?>
                        <?php if (is_superadmin()): ?>
                            <span class="badge text-bg-danger ms-1">Superadmin</span>
                        <?php else: ?>
                            <span class="badge text-bg-secondary ms-1">Admin</span>
                        <?php endif; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="/admin/change-password.php">
                                <i class="bi bi-key me-2"></i>Ganti Password
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="/admin/logout.php">
                                <i class="bi bi-box-arrow-right me-2"></i>Logout
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Sidebar -->
    <aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
        <div class="sidebar-brand">
            <a href="/admin/" class="brand-link">
                <span class="brand-text fw-light"><?= e($app['name']) ?></span>
            </a>
        </div>
        <div class="sidebar-wrapper">
            <nav class="mt-2">
                <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu">
    <li class="nav-item">
        <a href="/admin/" class="nav-link <?= str_ends_with($currentPath, '/admin/') || str_ends_with($currentPath, '/admin') ? 'active' : '' ?>">
            <i class="nav-icon bi bi-speedometer2"></i>
            <p>Dashboard</p>
        </a>
    </li>

    <li class="nav-item">
        <a href="#" class="nav-link">
            <i class="nav-icon bi bi-people"></i>
            <p>
                Data Master
                <i class="nav-arrow bi bi-chevron-right"></i>
            </p>
        </a>
        <ul class="nav nav-treeview">
            <li class="nav-item">
                <a href="/admin/siswa/" class="nav-link <?= str_contains($currentPath, '/admin/siswa') ? 'active' : '' ?>">
                    <i class="nav-icon bi bi-circle"></i>
                    <p>Siswa</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="/admin/guru/" class="nav-link <?= str_contains($currentPath, '/admin/guru') ? 'active' : '' ?>">
                    <i class="nav-icon bi bi-circle"></i>
                    <p>Guru / Staff</p>
                </a>
            </li>
        </ul>
    </li>

    <li class="nav-item">
        <a href="/admin/transaksi/" class="nav-link <?= str_contains($currentPath, '/admin/transaksi') ? 'active' : '' ?>">
            <i class="nav-icon bi bi-cash-stack"></i>
            <p>Transaksi</p>
        </a>
    </li>
    <li class="nav-item">
        <a href="/admin/landing/" class="nav-link <?= str_contains($currentPath, '/admin/landing') ? 'active' : '' ?>">
            <i class="nav-icon bi bi-window"></i>
            <p>Landing Page</p>
        </a>
    </li>
    <li class="nav-item">
        <a href="#" class="nav-link">
            <i class="nav-icon bi bi-calendar3"></i>
            <p>Jadwal</p>
        </a>
    </li>

    <li class="nav-item">
        <a href="#" class="nav-link">
            <i class="nav-icon bi bi-file-earmark-bar-graph"></i>
            <p>Laporan</p>
        </a>
    </li>

    <?php if (is_superadmin()): ?>
    <li class="nav-header">SUPERADMIN</li>
    <li class="nav-item">
        <a href="/admin/users/" class="nav-link <?= str_contains($currentPath, '/admin/users') ? 'active' : '' ?>">
            <i class="nav-icon bi bi-shield-lock"></i>
            <p>Manajemen User</p>
        </a>
    </li>
    <li class="nav-item">
        <a href="/admin/change-password.php" class="nav-link <?= str_contains($currentPath, 'change-password') ? 'active' : '' ?>">
            <i class="nav-icon bi bi-key"></i>
            <p>Ganti Password</p>
        </a>
    </li>
    <?php endif; ?>
        <li class="nav-item">
        <a href="/admin/settings.php" class="nav-link <?= str_contains($currentPath, '/admin/settings') ? 'active' : '' ?>">
            <i class="nav-icon bi bi-gear"></i>
            <p>Pengaturan</p>
        </a>
    </li>
</ul>
                </ul>
            </nav>
        </div>
    </aside>

    <!-- Main -->
    <main class="app-main">
        <div class="app-content-header">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-sm-6">
                        <h3 class="mb-0"><?= e($pageTitle ?? 'Admin') ?></h3>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-end">
                            <li class="breadcrumb-item"><a href="/admin/">Home</a></li>
                            <li class="breadcrumb-item active"><?= e($pageTitle ?? '') ?></li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <div class="app-content">
            <div class="container-fluid">
                <?php if ($flash): ?>
                    <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show" role="alert">
                        <?= e($flash['message']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?= $content ?? '' ?>
            </div>
        </div>
    </main>

    <footer class="app-footer">
        <div class="float-end d-none d-sm-inline">Versi <?= e($app['version'] ?? '0.2.0') ?></div>
        <strong>&copy; <?= date('Y') ?> <?= e($app['name']) ?>.</strong>
    </footer>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-beta3/dist/js/adminlte.min.js"></script>
</body>
</html>