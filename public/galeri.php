<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
$app = require __DIR__ . '/../config/app.php';
$appName = $app['name'] ?? 'Bimba KSR';

$albums = $pdo->query(
    'SELECT a.*, (SELECT COUNT(*) FROM galeri_foto f WHERE f.album_id=a.id) AS jml_foto
     FROM galeri_album a WHERE a.is_active=1 ORDER BY a.sort_order ASC, a.id DESC'
)->fetchAll();
$videos = $pdo->query(
    'SELECT * FROM galeri_video WHERE is_active=1 ORDER BY sort_order ASC, id DESC'
)->fetchAll();

$fotosByAlbum = [];
foreach ($albums as $a) {
    $st = $pdo->prepare('SELECT * FROM galeri_foto WHERE album_id=? ORDER BY sort_order,id LIMIT 50');
    $st->execute([$a['id']]);
    $fotosByAlbum[$a['id']] = $st->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Galeri Kegiatan - <?= e($appName) ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<script>
tailwind.config={theme:{extend:{colors:{brand:{red:'#EF4444',yellow:'#F59E0B',blue:'#3B82F6',green:'#10B981',pink:'#FF9EC6'}}}}}
</script>
<style>
:root {
  --pastel-pink: #ff9ec6;
  --pastel-blue: #7ec8f8;
  --pastel-yellow: #ffe66d;
  --pastel-green: #95e1a3;
  --pastel-purple: #c9a0ff;
}
body {
  background: linear-gradient(180deg, #f0f9ff 0%, #fff7ed 50%, #fdf2f8 100%);
  background-attachment: fixed;
}
.folder-tab{clip-path:polygon(0 0,85% 0,100% 100%,0% 100%)}
#lightbox{backdrop-filter:blur(5px)}

.btn-login {
  background: linear-gradient(135deg, #ff7eb3 0%, #7ec8f8 100%);
  box-shadow: 0 6px 18px rgba(255, 120, 160, 0.35);
}
.album-card {
  transition: transform 0.25s ease, box-shadow 0.25s ease;
}
.album-card:hover {
  transform: translateY(-6px);
  box-shadow: 0 16px 32px rgba(100, 80, 150, 0.15);
}
.hero-galeri {
  background: linear-gradient(135deg, #3B82F6 0%, #7ec8f8 40%, #c9a0ff 100%);
}
</style>
</head>
<body class="font-sans text-gray-800">

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
        <a href="/" class="text-gray-600 font-semibold hover:text-brand-blue">Home</a>
        <a href="/galeri.php" class="text-brand-yellow font-bold border-b-2 border-brand-yellow pb-1">Galeri</a>
        <a href="/#pengajar" class="text-gray-600 font-semibold hover:text-brand-yellow">Pengajar</a>
        <a href="/#testimoni" class="text-gray-600 font-semibold hover:text-brand-yellow">Testimoni</a>
        <a href="/login.php" class="btn-login text-white px-6 py-2 rounded-full font-bold">Login</a>
      </div>
    </div>
  </div>
</nav>

<section class="pt-32 pb-16 hero-galeri relative overflow-hidden">
  <div class="absolute inset-0 opacity-15" style="background-image:radial-gradient(#fff 1.5px,transparent 1.5px);background-size:22px 22px"></div>
  <div class="max-w-7xl mx-auto px-4 text-center relative z-10 text-white">
    <div class="text-3xl mb-3 opacity-90">
      <i class="fa-solid fa-camera mx-1"></i>
      <i class="fa-solid fa-palette mx-1"></i>
      <i class="fa-solid fa-child mx-1"></i>
    </div>
    <h1 class="text-4xl md:text-5xl font-extrabold mb-4 drop-shadow">Galeri Kegiatan</h1>
    <p class="text-blue-50 text-lg max-w-2xl mx-auto">
      Momen keceriaan, kreativitas, dan kebersamaan anak-anak selama belajar dan bermain di Bimba KSR.
    </p>
  </div>
</section>

<section class="max-w-7xl mx-auto px-4 py-12">
  <div class="flex justify-between items-center mb-8">
    <h2 class="text-2xl font-bold flex items-center gap-2 text-gray-800">
      <i class="fa-regular fa-images text-brand-blue"></i> Album Foto
    </h2>
    <span class="bg-yellow-100 text-yellow-800 text-sm px-3 py-1 rounded-full font-semibold">
      <?= count($albums) ?> Folder
    </span>
  </div>
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
  <?php foreach ($albums as $a):
    $fotos = $fotosByAlbum[$a['id']] ?? [];
    $cover1 = $a['cover_url'] ? '/uploads/'.$a['cover_url'] : ($fotos[0]['image_url'] ?? null ? '/uploads/'.$fotos[0]['image_url'] : 'https://picsum.photos/seed/'.$a['id'].'/400/300');
    $cover2 = isset($fotos[1]) ? '/uploads/'.$fotos[1]['image_url'] : $cover1;
  ?>
    <div class="relative group cursor-pointer album-card" onclick="openLightbox(<?= (int)$a['id'] ?>, '<?= e(addslashes($a['judul'])) ?>')">
      <?php if (!empty($a['is_baru'])): ?>
        <span class="absolute -top-2 -right-2 z-20 bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-full shadow">BARU</span>
      <?php endif; ?>
      <div class="bg-yellow-100 rounded-xl p-3 pt-6 shadow-md relative border-2 border-yellow-200">
        <div class="absolute top-0 left-4 w-24 h-5 bg-yellow-200 folder-tab rounded-t"></div>
        <div class="grid grid-cols-2 gap-2 mb-3">
          <img src="<?= e($cover1) ?>" class="w-full h-28 object-cover rounded-lg" alt="">
          <img src="<?= e($cover2) ?>" class="w-full h-28 object-cover rounded-lg" alt="">
        </div>
        <h3 class="font-bold text-gray-800 text-sm"><?= e($a['judul']) ?></h3>
        <p class="text-xs text-gray-500 mt-1">
          <i class="fa-regular fa-calendar mr-1"></i>
          <?= $a['tanggal'] ? date('d M Y', strtotime($a['tanggal'])) : '' ?>
          • <?= (int)$a['jml_foto'] ?> Foto
        </p>
      </div>
    </div>
  <?php endforeach; ?>
  <?php if (!$albums): ?>
    <p class="text-gray-500 col-span-full text-center py-8">Belum ada album foto.</p>
  <?php endif; ?>
  </div>
</section>

<section class="max-w-7xl mx-auto px-4 py-12 border-t border-pink-100">
  <h2 class="text-2xl font-bold mb-8 flex items-center gap-2 text-gray-800">
    <i class="fa-brands fa-youtube text-red-500"></i> Video Dokumentasi
  </h2>
  <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
  <?php foreach ($videos as $v): ?>
    <a href="https://www.youtube.com/watch?v=<?= e($v['youtube_id']) ?>" target="_blank" class="group">
      <div class="relative rounded-xl overflow-hidden shadow-md border-2 border-white">
        <img src="https://img.youtube.com/vi/<?= e($v['youtube_id']) ?>/hqdefault.jpg"
             class="w-full h-48 object-cover group-hover:scale-105 transition" alt="">
        <div class="absolute inset-0 bg-black/30 flex items-center justify-center">
          <div class="w-14 h-14 bg-red-600 rounded-full flex items-center justify-center text-white text-xl shadow-lg">
            <i class="fa-solid fa-play ml-1"></i>
          </div>
        </div>
      </div>
      <h3 class="font-bold mt-3 text-sm group-hover:text-brand-blue"><?= e($v['judul']) ?></h3>
      <p class="text-xs text-gray-500">
        <?= e($v['views_text'] ?? '') ?>
        <?= !empty($v['waktu_text']) ? '• ' . e($v['waktu_text']) : '' ?>
      </p>
    </a>
  <?php endforeach; ?>
  <?php if (!$videos): ?>
    <p class="text-gray-500 col-span-full text-center py-6">Belum ada video.</p>
  <?php endif; ?>
  </div>
</section>

<footer class="bg-gray-900 text-white py-8 border-t-4 border-yellow-300 mt-12 text-center text-sm text-gray-400">
  &copy; <?= date('Y') ?> <?= e($appName) ?>.
</footer>

<!-- Lightbox -->
<div id="lightbox" class="fixed inset-0 bg-black/80 z-[100] hidden opacity-0 transition-opacity duration-300 flex items-center justify-center p-4">
  <div class="bg-white rounded-2xl max-w-4xl w-full max-h-[90vh] overflow-hidden flex flex-col">
    <div class="flex justify-between items-center p-4 border-b">
      <h3 id="lightbox-title" class="font-bold text-lg"></h3>
      <button type="button" onclick="closeLightbox()" class="text-gray-500 hover:text-red-500 text-2xl leading-none">&times;</button>
    </div>
    <div id="lightbox-body" class="p-4 overflow-y-auto grid grid-cols-2 md:grid-cols-3 gap-3"></div>
  </div>
</div>

<div id="viewer" class="fixed inset-0 bg-black/95 z-[110] hidden opacity-0 transition-opacity duration-300 flex items-center justify-center">
  <button type="button" onclick="closeViewer()" class="absolute top-4 right-4 text-white text-3xl hover:text-red-400 z-20">&times;</button>
  <button type="button" onclick="prevFoto()" class="absolute left-3 md:left-6 text-white text-4xl hover:text-yellow-400 z-20 px-2">&lsaquo;</button>
  <button type="button" onclick="nextFoto()" class="absolute right-3 md:right-6 text-white text-4xl hover:text-yellow-400 z-20 px-2">&rsaquo;</button>
  <img id="viewer-img" src="" alt="" class="max-h-[85vh] max-w-[90vw] object-contain rounded shadow-2xl">
  <div id="viewer-counter" class="absolute bottom-4 left-1/2 -translate-x-1/2 text-white text-sm bg-black/50 px-3 py-1 rounded-full"></div>
</div>

<script>
const albumFotos = <?= json_encode(array_map(function ($list) {
    return array_map(fn($f) => '/uploads/' . $f['image_url'], $list);
}, $fotosByAlbum)) ?>;

let currentAlbumId = null;
let currentIndex = 0;
let currentList = [];

function openLightbox(id, title) {
  currentAlbumId = id;
  currentList = albumFotos[id] || [];
  document.getElementById('lightbox-title').innerHTML =
    '<i class="fa-solid fa-folder-open text-yellow-500 mr-2"></i> ' + title;

  const body = document.getElementById('lightbox-body');
  if (!currentList.length) {
    body.innerHTML = '<p class="text-gray-500 col-span-full text-center">Belum ada foto</p>';
  } else {
    body.innerHTML = currentList.map((src, i) =>
      `<img src="${src}" class="w-full h-40 object-cover rounded-lg cursor-pointer hover:opacity-90 transition"
            onclick="openViewer(${i})" alt="">`
    ).join('');
  }

  const lb = document.getElementById('lightbox');
  lb.classList.remove('hidden');
  setTimeout(() => { lb.classList.remove('opacity-0'); lb.classList.add('opacity-100'); }, 10);
  document.body.style.overflow = 'hidden';
}

function closeLightbox() {
  const lb = document.getElementById('lightbox');
  lb.classList.remove('opacity-100');
  lb.classList.add('opacity-0');
  setTimeout(() => { lb.classList.add('hidden'); document.body.style.overflow = 'auto'; }, 300);
}

function openViewer(index) {
  if (!currentList.length) return;
  currentIndex = index;
  showViewerImage();
  const v = document.getElementById('viewer');
  v.classList.remove('hidden');
  setTimeout(() => { v.classList.remove('opacity-0'); v.classList.add('opacity-100'); }, 10);
}

function showViewerImage() {
  document.getElementById('viewer-img').src = currentList[currentIndex];
  document.getElementById('viewer-counter').textContent =
    (currentIndex + 1) + ' / ' + currentList.length;
}

function nextFoto() {
  if (!currentList.length) return;
  currentIndex = (currentIndex + 1) % currentList.length;
  showViewerImage();
}

function prevFoto() {
  if (!currentList.length) return;
  currentIndex = (currentIndex - 1 + currentList.length) % currentList.length;
  showViewerImage();
}

function closeViewer() {
  const v = document.getElementById('viewer');
  v.classList.remove('opacity-100');
  v.classList.add('opacity-0');
  setTimeout(() => { v.classList.add('hidden'); }, 300);
}

document.getElementById('lightbox').addEventListener('click', e => {
  if (e.target.id === 'lightbox') closeLightbox();
});
document.getElementById('viewer').addEventListener('click', e => {
  if (e.target.id === 'viewer') closeViewer();
});

document.addEventListener('keydown', e => {
  const viewerOpen = !document.getElementById('viewer').classList.contains('hidden');
  if (!viewerOpen) return;
  if (e.key === 'ArrowRight') nextFoto();
  if (e.key === 'ArrowLeft') prevFoto();
  if (e.key === 'Escape') closeViewer();
});
</script>
</body>
</html>