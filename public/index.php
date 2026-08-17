<?php
/**
 * Frontend - Beranda Bimba KSR
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

$app = require __DIR__ . '/../config/app.php';
$appName = $app['name'] ?? 'Bimba KSR';

$slides = [];
$fasilitas = [];
$gurus = [];

try {
    $slides = $pdo->query(
        'SELECT * FROM landing_hero WHERE is_active = 1 ORDER BY sort_order ASC, id ASC'
    )->fetchAll();
} catch (Exception $e) {
    $slides = [];
}

try {
    $fasilitas = $pdo->query(
        'SELECT * FROM landing_fasilitas WHERE is_active = 1 ORDER BY sort_order ASC, id ASC'
    )->fetchAll();
} catch (Exception $e) {
    $fasilitas = [];
}

try {
    $gurus = $pdo->query(
        'SELECT * FROM landing_pengajar WHERE is_active = 1 ORDER BY sort_order ASC, id ASC'
    )->fetchAll();
} catch (Exception $e) {
    $gurus = [];
}

if (!$slides) {
    $slides = [
        [
            'image_url'   => 'https://images.unsplash.com/photo-1441974231531-c6227db76b6e?w=1600&q=80',
            'badge'       => 'PENDAFTARAN TELAH DIBUKA',
            'title'       => 'Belajar Menyenangkan<br>di Bimba Bintang Prestasi',
            'description' => 'Membangun fondasi kecerdasan dan karakter anak usia dini melalui metode bermain sambil belajar.',
            'cta_text'    => 'Daftar Sekarang',
            'cta_link'    => '#fasilitas',
        ],
        [
            'image_url'   => 'https://images.unsplash.com/photo-1596367407372-96cb885046ad?w=1600&q=80',
            'badge'       => '',
            'title'       => 'Fasilitas Bermain<br><span class="text-brand-yellow">yang Edukatif</span>',
            'description' => 'Ruang kelas yang nyaman, aman, dan penuh warna untuk memicu kreativitas anak.',
            'cta_text'    => '',
            'cta_link'    => '',
        ],
        [
            'image_url'   => 'https://images.unsplash.com/photo-1503454537195-1dcabb73ffb9?w=1600&q=80',
            'badge'       => '',
            'title'       => 'Tenaga Pengajar<br><span class="text-brand-yellow">Profesional &amp; Sabar</span>',
            'description' => 'Dibimbing oleh guru-guru tersertifikasi yang memahami psikologi perkembangan anak.',
            'cta_text'    => '',
            'cta_link'    => '',
        ],
    ];
}

if (!$fasilitas) {
    $fasilitas = [
        ['icon' => 'fa-puzzle-piece', 'color' => 'bg-brand-blue',   'title' => 'Metode Bermain',    'description' => 'Belajar membaca, menulis, dan berhitung melalui permainan yang interaktif dan menyenangkan.'],
        ['icon' => 'fa-palette',      'color' => 'bg-brand-yellow', 'title' => 'Ruang Kreativitas',  'description' => 'Ruangan khusus yang dilengkapi alat menggambar, mewarnai, dan kerajinan tangan.'],
        ['icon' => 'fa-tree',         'color' => 'bg-brand-green',  'title' => 'Outing Class',       'description' => 'Kegiatan luar ruangan secara berkala untuk mengenalkan anak pada alam dan lingkungan sekitar.'],
        ['icon' => 'fa-shield-halved','color' => 'bg-brand-red',    'title' => 'Keamanan Terjamin',  'description' => 'Area sekolah dilengkapi CCTV dan pengawasan ketat saat jam penjemputan siswa.'],
    ];
}

if (!$gurus) {
    $gurus = [
        ['foto_url' => 'https://picsum.photos/seed/guru1/400/400', 'nama' => 'Bunda Sarah, S.Pd', 'jabatan' => 'Kepala Sekolah', 'warna' => 'text-brand-blue',   'deskripsi' => 'Berpengalaman 10 tahun di dunia pendidikan anak usia dini.'],
        ['foto_url' => 'https://picsum.photos/seed/guru2/400/400', 'nama' => 'Bunda Nisa',        'jabatan' => 'Wali Kelas A',    'warna' => 'text-brand-yellow', 'deskripsi' => 'Ahli dalam metode pengajaran motorik halus anak.'],
        ['foto_url' => 'https://picsum.photos/seed/guru3/400/400', 'nama' => 'Bunda Rina',        'jabatan' => 'Wali Kelas B',    'warna' => 'text-brand-green',  'deskripsi' => 'Sangat sabar dan telaten dalam mengajarkan dasar calistung.'],
        ['foto_url' => 'https://picsum.photos/seed/guru4/400/400', 'nama' => 'Kak Dimas',         'jabatan' => 'Guru Kesenian',   'warna' => 'text-brand-red',    'deskripsi' => 'Mengasah bakat seni, menggambar, dan bernyanyi anak.'],
    ];
}

$tests = [
    ['isi' => 'Sejak masuk Bimba KSR, anak saya jadi lebih mandiri dan berani bersosialisasi. Guru-gurunya sangat ramah dan komunikatif.', 'nama' => 'Ibu Fitri',   'relasi' => 'Ibunda dari Ananda Rafa'],
    ['isi' => 'Aplikasi website nya sangat membantu! Saya bisa langsung pantau pembayaran SPP dan nilai raport anak dari HP kapan saja.', 'nama' => 'Bapak Hendra', 'relasi' => 'Ayahanda dari Ananda Putri'],
    ['isi' => 'Lingkungannya bersih, aman, dan permainannya edukatif. Anak saya selalu semangat kalau disuruh berangkat ke sekolah.', 'nama' => 'Ibu Dewi',    'relasi' => 'Ibunda dari Ananda Rio'],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($appName) ?> - Beranda</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            red: '#EF4444',
                            yellow: '#F59E0B',
                            blue: '#3B82F6',
                            green: '#10B981',
                            pink: '#FF9EC6',
                            purple: '#C9A0FF',
                            sky: '#7EC8F8'
                        }
                    }
                }
            }
        }
    </script>
    <style>
        :root {
            --pastel-pink: #ff9ec6;
            --pastel-blue: #7ec8f8;
            --pastel-yellow: #ffe66d;
            --pastel-green: #95e1a3;
            --pastel-purple: #c9a0ff;
            --pastel-orange: #ffb347;
        }

        body {
            background: linear-gradient(180deg, #f0f9ff 0%, #fff7ed 40%, #fdf2f8 100%);
            background-attachment: fixed;
        }

        /* Floating decor shapes */
        .bg-decor {
            position: fixed;
            inset: 0;
            pointer-events: none;
            overflow: hidden;
            z-index: 0;
        }
        .bg-decor .shape {
            position: absolute;
            border-radius: 50%;
            opacity: 0.22;
            filter: blur(2px);
        }
        .bg-decor .s1 { width: 220px; height: 220px; background: var(--pastel-pink); top: 12%; left: -40px; animation: float 10s ease-in-out infinite; }
        .bg-decor .s2 { width: 160px; height: 160px; background: var(--pastel-yellow); top: 40%; right: -30px; animation: float 8s ease-in-out infinite 1s; }
        .bg-decor .s3 { width: 120px; height: 120px; background: var(--pastel-green); bottom: 15%; left: 8%; animation: float 9s ease-in-out infinite 0.5s; }
        .bg-decor .s4 { width: 180px; height: 180px; background: var(--pastel-purple); bottom: 5%; right: 10%; animation: float 11s ease-in-out infinite 1.5s; }

        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-16px) rotate(5deg); }
        }

        /* Floating kids icons */
        .float-icon {
            position: fixed;
            font-size: 1.75rem;
            opacity: 0.35;
            animation: float 7s ease-in-out infinite;
            z-index: 1;
            pointer-events: none;
            color: #6b5b8a;
        }
        .float-icon.i1 { top: 18%; left: 3%; color: #e85d8e; animation-delay: 0s; }
        .float-icon.i2 { top: 28%; right: 4%; color: #3a9bd9; animation-delay: 1s; }
        .float-icon.i3 { top: 55%; left: 2%; color: #e6a800; animation-delay: 0.4s; }
        .float-icon.i4 { top: 70%; right: 3%; color: #5cb85c; animation-delay: 1.6s; }
        .float-icon.i5 { top: 85%; left: 12%; color: #9b59b6; animation-delay: 0.8s; }

        @media (max-width: 768px) {
            .float-icon, .bg-decor .shape { display: none; }
        }

        .slide { opacity: 0; pointer-events: none; transition: opacity 0.8s ease-in-out; }
        .slide.active { opacity: 1; pointer-events: auto; }

        .nav-pill {
            transition: all 0.2s ease;
        }
        .nav-pill:hover {
            color: #e85d8e;
        }

        .btn-login {
            background: linear-gradient(135deg, #ff7eb3 0%, #7ec8f8 100%);
            box-shadow: 0 6px 18px rgba(255, 120, 160, 0.35);
        }
        .btn-login:hover {
            filter: brightness(1.05);
            transform: translateY(-1px);
        }

        .card-kids {
            border-radius: 1.25rem;
            border: 2px solid rgba(255, 255, 255, 0.8);
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(6px);
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }
        .card-kids:hover {
            transform: translateY(-6px);
            box-shadow: 0 16px 32px rgba(100, 80, 150, 0.12);
        }

        .icon-bubble {
            border-radius: 1.25rem;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
        }

        .section-title {
            position: relative;
            display: inline-block;
        }
        .section-title::after {
            content: "";
            display: block;
            width: 64px;
            height: 5px;
            margin: 0.5rem auto 0;
            border-radius: 999px;
            background: linear-gradient(90deg, #ff9ec6, #7ec8f8, #95e1a3);
        }

        .cta-band {
            background: linear-gradient(135deg, #7ec8f8 0%, #c9a0ff 50%, #ff9ec6 100%);
        }

        footer.kids-footer {
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            border-top: 4px solid #ffe66d;
        }
    </style>
</head>
<body class="font-sans text-gray-800 relative">

    <div class="bg-decor">
        <div class="shape s1"></div>
        <div class="shape s2"></div>
        <div class="shape s3"></div>
        <div class="shape s4"></div>
    </div>
    <i class="fa-solid fa-book float-icon i1"></i>
    <i class="fa-solid fa-pencil float-icon i2"></i>
    <i class="fa-solid fa-puzzle-piece float-icon i3"></i>
    <i class="fa-solid fa-tree float-icon i4"></i>
    <i class="fa-solid fa-star float-icon i5"></i>

    <!-- NAVBAR -->
    <nav class="bg-white/90 backdrop-blur-md shadow-md fixed w-full z-50 top-0 border-b border-pink-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-20">
                <a href="/" class="flex-shrink-0 flex items-center">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center text-white font-bold text-2xl shadow-lg mr-3"
                         style="background: linear-gradient(145deg, #ffe66d, #ff9ec6);">
                        <i class="fa-solid fa-star"></i>
                    </div>
                    <span class="font-extrabold text-2xl text-brand-blue tracking-tight">
                        BIMBA <span class="text-brand-red">KSR</span>
                    </span>
                </a>
                <div class="hidden md:flex items-center space-x-8">
                    <a href="/" class="text-brand-blue font-bold border-b-2 border-brand-yellow pb-1">Home</a>
                    <a href="/galeri.php" class="nav-pill text-gray-600 font-semibold">Galeri</a>
                    <a href="#pengajar" class="nav-pill text-gray-600 font-semibold">Pengajar</a>
                    <a href="#testimoni" class="nav-pill text-gray-600 font-semibold">Testimoni</a>
                    <a href="/login.php"
                        class="btn-login text-white px-5 py-2 rounded-full font-bold transition">
                        Login
                    </a>
                </div>
                <div class="md:hidden flex items-center">
                    <button id="menuBtn" class="text-gray-600 hover:text-brand-blue text-2xl">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                </div>
            </div>
        </div>
        <div id="mobileMenu" class="hidden md:hidden bg-white border-t px-4 pb-4">
            <a href="/" class="block py-2 font-semibold text-brand-blue">Home</a>
            <a href="/galeri.php" class="block py-2 text-gray-600 font-semibold">Galeri</a>
            <a href="#pengajar" class="block py-2 text-gray-600">Pengajar</a>
            <a href="#testimoni" class="block py-2 text-gray-600">Testimoni</a>
            <a href="/login.php" class="inline-block mt-2 btn-login text-white px-5 py-2 rounded-full font-bold">Login</a>
        </div>
    </nav>

    <!-- HERO SLIDER -->
    <section class="relative h-screen min-h-[560px] mt-20 overflow-hidden z-10">
        <?php foreach ($slides as $i => $s):
            $img = $s['image_url'] ?? '';
            if ($img && !str_starts_with($img, 'http')) {
                $img = '/uploads/' . ltrim($img, '/');
            }
            if (!$img) {
                $img = 'https://images.unsplash.com/photo-1441974231531-c6227db76b6e?w=1600&q=80';
            }
        ?>
        <div class="slide absolute inset-0 <?= $i === 0 ? 'active' : '' ?>" data-slide="<?= $i ?>">
            <div class="absolute inset-0 bg-cover bg-center"
                 style="background-image:url('<?= e($img) ?>')"></div>
            <div class="absolute inset-0 bg-gradient-to-b from-black/50 via-black/45 to-black/60"></div>
            <div class="relative h-full flex items-center justify-center text-center px-4">
                <div class="max-w-3xl text-white">
                    <?php if (!empty($s['badge'])): ?>
                        <span class="inline-block text-white text-xs font-bold tracking-wider px-4 py-1.5 rounded-full mb-5 shadow"
                              style="background: linear-gradient(90deg, #ff9ec6, #ffe66d); color: #4a3a6a;">
                            <?= e($s['badge']) ?>
                        </span>
                    <?php endif; ?>
                    <h1 class="text-4xl md:text-6xl font-extrabold leading-tight mb-4 drop-shadow"><?= $s['title'] ?></h1>
                    <?php if (!empty($s['description'])): ?>
                        <p class="text-lg md:text-xl text-gray-100 mb-8"><?= e($s['description']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($s['cta_text'])): ?>
                        <a href="<?= e($s['cta_link'] ?: '#fasilitas') ?>"
                           class="inline-block font-bold px-8 py-3 rounded-full shadow-lg transition transform hover:scale-105 text-white"
                           style="background: linear-gradient(135deg, #3B82F6, #7ec8f8);">
                            <?= e($s['cta_text']) ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <?php if (count($slides) > 1): ?>
        <button id="prevSlide" class="absolute left-4 top-1/2 -translate-y-1/2 w-12 h-12 rounded-full bg-white/85 hover:bg-white text-gray-800 shadow flex items-center justify-center z-10">
            <i class="fa-solid fa-chevron-left"></i>
        </button>
        <button id="nextSlide" class="absolute right-4 top-1/2 -translate-y-1/2 w-12 h-12 rounded-full bg-white/85 hover:bg-white text-gray-800 shadow flex items-center justify-center z-10">
            <i class="fa-solid fa-chevron-right"></i>
        </button>
        <div class="absolute bottom-8 left-0 right-0 flex justify-center gap-2 z-10">
            <?php for ($i = 0; $i < count($slides); $i++): ?>
                <button class="dot w-3 h-3 rounded-full transition <?= $i === 0 ? 'bg-white' : 'bg-white/50' ?>" data-dot="<?= $i ?>"></button>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </section>

    <!-- FASILITAS -->
    <section id="fasilitas" class="py-20 relative z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-14">
                <p class="text-sm font-bold tracking-widest text-pink-400 mb-2">
                    <i class="fa-solid fa-crayon mr-1"></i> BELAJAR & BERMAIN
                </p>
                <h2 class="text-3xl md:text-4xl font-extrabold text-gray-900 section-title mb-3">Fasilitas &amp; Layanan Kami</h2>
                <p class="text-gray-500 max-w-2xl mx-auto mt-4">
                    Lingkungan belajar penuh warna yang mendukung tumbuh kembang kognitif, motorik, dan sosial emosional anak.
                </p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                <?php foreach ($fasilitas as $f): ?>
                <div class="card-kids p-8 text-center">
                    <div class="icon-bubble w-16 h-16 <?= e($f['color'] ?: 'bg-brand-blue') ?> text-white flex items-center justify-center text-2xl mx-auto mb-5">
                        <i class="fa-solid <?= e($f['icon'] ?: 'fa-star') ?>"></i>
                    </div>
                    <h3 class="font-bold text-xl mb-2 text-gray-900"><?= e($f['title']) ?></h3>
                    <p class="text-gray-500 text-sm leading-relaxed"><?= e($f['description'] ?? '') ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- PENGAJAR -->
    <section id="pengajar" class="py-20 relative z-10" style="background: linear-gradient(180deg, rgba(255,230,109,0.12) 0%, rgba(126,200,248,0.15) 100%);">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-14">
                <p class="text-sm font-bold tracking-widest text-blue-400 mb-2">
                    <i class="fa-solid fa-chalkboard-user mr-1"></i> TIM PENGAJAR
                </p>
                <h2 class="text-3xl md:text-4xl font-extrabold text-gray-900 section-title mb-3">Kenali Pengajar Kami</h2>
                <p class="text-gray-500 mt-4">Tenaga pendidik profesional yang sabar dan berpengalaman.</p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                <?php foreach ($gurus as $g):
                    $foto = $g['foto_url'] ?? '';
                    if ($foto && !str_starts_with($foto, 'http')) {
                        $foto = '/uploads/' . ltrim($foto, '/');
                    }
                    if (!$foto) {
                        $foto = 'https://picsum.photos/seed/guru/400/400';
                    }
                ?>
                <div class="card-kids overflow-hidden text-center">
                    <img src="<?= e($foto) ?>" alt="<?= e($g['nama']) ?>" class="w-full h-48 object-cover">
                    <div class="p-6">
                        <h3 class="font-bold text-xl text-gray-900"><?= e($g['nama']) ?></h3>
                        <p class="<?= e($g['warna'] ?: 'text-brand-blue') ?> font-semibold text-sm mb-2"><?= e($g['jabatan'] ?? '') ?></p>
                        <p class="text-gray-500 text-sm"><?= e($g['deskripsi'] ?? '') ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- TESTIMONI -->
    <section id="testimoni" class="py-20 relative z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-14">
                <p class="text-sm font-bold tracking-widest text-green-500 mb-2">
                    <i class="fa-solid fa-heart mr-1"></i> KATA ORANG TUA
                </p>
                <h2 class="text-3xl md:text-4xl font-extrabold text-gray-900 section-title mb-3">Testimoni</h2>
                <p class="text-gray-500 mt-4">Pengalaman nyata dari orang tua yang mempercayakan putra-putrinya kepada kami.</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <?php foreach ($tests as $t): ?>
                <div class="card-kids p-8">
                    <div class="text-brand-yellow text-xl mb-4">
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                    </div>
                    <p class="text-gray-600 italic mb-6 leading-relaxed">"<?= e($t['isi']) ?>"</p>
                    <div>
                        <p class="font-bold text-gray-900"><?= e($t['nama']) ?></p>
                        <p class="text-sm text-gray-500"><?= e($t['relasi']) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="py-16 cta-band text-white relative z-10">
        <div class="max-w-4xl mx-auto px-4 text-center">
            <div class="text-3xl mb-3 opacity-90">
                <i class="fa-solid fa-book-open mx-1"></i>
                <i class="fa-solid fa-pencil mx-1"></i>
                <i class="fa-solid fa-balloon mx-1"></i>
            </div>
            <h2 class="text-3xl md:text-4xl font-extrabold mb-4 drop-shadow">Siap Bergabung dengan Kami?</h2>
            <p class="text-white/90 mb-8 text-lg">Daftarkan putra-putri Anda dan mulai perjalanan belajar yang ceria.</p>
            <a href="/login.php" class="inline-block bg-white text-brand-blue font-bold px-8 py-3 rounded-full shadow-lg hover:bg-gray-50 transition">
                Hubungi Admin / Login
            </a>
        </div>
    </section>

    <!-- FOOTER -->
    <footer class="kids-footer text-gray-400 py-12 relative z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row justify-between items-center gap-6">
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center text-white mr-3"
                         style="background: linear-gradient(145deg, #ffe66d, #ff9ec6);">
                        <i class="fa-solid fa-star"></i>
                    </div>
                    <span class="font-extrabold text-xl text-white">BIMBA <span class="text-brand-red">KSR</span></span>
                </div>
                <p class="text-sm">&copy; <?= date('Y') ?> <?= e($appName) ?>. Semua hak dilindungi.</p>
                <div class="flex gap-4 text-lg">
                    <a href="#" class="hover:text-white transition"><i class="fa-brands fa-instagram"></i></a>
                    <a href="#" class="hover:text-white transition"><i class="fa-brands fa-facebook"></i></a>
                    <a href="#" class="hover:text-white transition"><i class="fa-brands fa-youtube"></i></a>
                </div>
            </div>
        </div>
    </footer>

    <script>
        document.getElementById('menuBtn')?.addEventListener('click', () => {
            document.getElementById('mobileMenu').classList.toggle('hidden');
        });

        const slides = document.querySelectorAll('.slide');
        const dots = document.querySelectorAll('.dot');
        let current = 0;
        let timer;

        function showSlide(n) {
            slides.forEach((s, i) => s.classList.toggle('active', i === n));
            dots.forEach((d, i) => {
                d.classList.toggle('bg-white', i === n);
                d.classList.toggle('bg-white/50', i !== n);
            });
            current = n;
        }
        function next() { if (slides.length) showSlide((current + 1) % slides.length); }
        function prev() { if (slides.length) showSlide((current - 1 + slides.length) % slides.length); }
        function startAuto() { if (slides.length > 1) timer = setInterval(next, 5000); }
        function resetAuto() { clearInterval(timer); startAuto(); }

        document.getElementById('nextSlide')?.addEventListener('click', () => { next(); resetAuto(); });
        document.getElementById('prevSlide')?.addEventListener('click', () => { prev(); resetAuto(); });
        dots.forEach(d => d.addEventListener('click', () => { showSlide(+d.dataset.dot); resetAuto(); }));
        startAuto();
    </script>
</body>
</html>