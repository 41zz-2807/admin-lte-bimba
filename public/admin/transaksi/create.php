<?php
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/flash.php';

require_login();

$pageTitle = 'Tambah Transaksi';
$error = '';
global $pdo;

$siswaList = $pdo->query("SELECT id, nama FROM siswa WHERE status='aktif' ORDER BY nama")->fetchAll();

$bulanNama = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
];

// Tahun ajaran: 2 tahun ke belakang s/d 2 tahun ke depan
// Jika bulan >= Juli → TA berjalan = Y/(Y+1), else (Y-1)/Y
$yNow = (int) date('Y');
$start = (date('n') >= 7) ? $yNow : $yNow - 1;
$tahunAjaranList = [];
for ($i = -2; $i <= 2; $i++) {
    $a = $start + $i;
    $tahunAjaranList[] = $a . '/' . ($a + 1);
}
$defaultTA = $start . '/' . ($start + 1);

$jenis          = $_POST['jenis'] ?? 'pemasukan';
$kategori       = trim($_POST['kategori'] ?? 'lain');
$tanggal        = $_POST['tanggal'] ?? date('Y-m-d');
$jumlah         = $_POST['jumlah'] ?? '';
$keterangan     = trim($_POST['keterangan'] ?? '');
$siswa_id       = (int) ($_POST['siswa_id'] ?? 0);
$tahun_ajaran   = trim($_POST['tahun_ajaran'] ?? $defaultTA);
$tarif          = (float) ($_POST['tarif'] ?? 0);
$bulan_dipilih  = array_map('intval', $_POST['bulan'] ?? []);

// Validasi format TA
if ($tahun_ajaran !== '' && !preg_match('/^\d{4}\/\d{4}$/', $tahun_ajaran)) {
    $tahun_ajaran = $defaultTA;
}

// Bulan sudah lunas untuk siswa + tahun ajaran
$lunas = [];
if ($siswa_id > 0 && $kategori === 'spp') {
    $st = $pdo->prepare('SELECT bulan FROM transaksi_spp_bulan WHERE siswa_id = ? AND tahun_ajaran = ?');
    $st->execute([$siswa_id, $tahun_ajaran]);
    $lunas = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan'])) {
    if (!in_array($jenis, ['pemasukan', 'pengeluaran'], true)) {
        $error = 'Jenis transaksi tidak valid.';
    } elseif ($tanggal === '') {
        $error = 'Tanggal wajib diisi.';
    } else {
        $bukti = null;
        if (!empty($_FILES['bukti']['tmp_name'])) {
            $bukti = upload_bukti($_FILES['bukti']);
            if ($bukti === null) {
                $error = 'Upload bukti gagal (jpg/png/pdf, max 5MB).';
            }
        }

        // ===== SPP multi-bulan =====
        if ($error === '' && $jenis === 'pemasukan' && $kategori === 'spp') {
            if ($siswa_id <= 0) {
                $error = 'Pilih siswa untuk pembayaran SPP.';
            } elseif ($tarif <= 0) {
                $error = 'Tarif SPP per bulan harus lebih dari 0.';
            } elseif (empty($bulan_dipilih)) {
                $error = 'Pilih minimal satu bulan.';
            } elseif (!preg_match('/^\d{4}\/\d{4}$/', $tahun_ajaran)) {
                $error = 'Tahun ajaran tidak valid.';
            } else {
                $bulan_ok = array_values(array_filter(
                    $bulan_dipilih,
                    fn($b) => $b >= 1 && $b <= 12 && !in_array($b, $lunas, true)
                ));

                if (empty($bulan_ok)) {
                    $error = 'Semua bulan yang dipilih sudah lunas / tidak valid.';
                } else {
                    $total     = $tarif * count($bulan_ok);
                    $namaBulan = array_map(fn($b) => $bulanNama[$b], $bulan_ok);
                    $ket       = $keterangan ?: ('SPP ' . implode(', ', $namaBulan) . ' TA ' . $tahun_ajaran);
                    $user      = current_user();

                    try {
                        $pdo->beginTransaction();

                        $ins = $pdo->prepare('INSERT INTO transaksi
                            (jenis, kategori, siswa_id, jumlah, tanggal, keterangan, bukti, user_id)
                            VALUES ("pemasukan", "spp", ?, ?, ?, ?, ?, ?)');
                        $ins->execute([
                            $siswa_id,
                            $total,
                            $tanggal,
                            $ket,
                            $bukti,
                            $user['id'] ?? null,
                        ]);
                        $transaksi_id = (int) $pdo->lastInsertId();

                        $det = $pdo->prepare('INSERT INTO transaksi_spp_bulan
                            (transaksi_id, siswa_id, bulan, tahun_ajaran, jumlah)
                            VALUES (?, ?, ?, ?, ?)');
                        foreach ($bulan_ok as $b) {
                            $det->execute([$transaksi_id, $siswa_id, $b, $tahun_ajaran, $tarif]);
                        }

                        $pdo->commit();
                        set_flash(
                            'success',
                            'SPP berhasil dicatat: ' . count($bulan_ok) . ' bulan, total Rp ' . number_format($total, 0, ',', '.')
                        );
                        redirect('admin/transaksi/');
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        $error = 'Gagal menyimpan SPP. Mungkin ada bulan yang sudah lunas.';
                    }
                }
            }
        }
        // ===== Pemasukan / Pengeluaran biasa =====
        elseif ($error === '') {
            $jml = (float) str_replace(['.', ','], ['', '.'], (string) $jumlah);
            if ($jml <= 0) {
                $error = 'Jumlah harus lebih dari 0.';
            } else {
                $user = current_user();
                $kat  = $kategori !== '' ? $kategori : ($jenis === 'pemasukan' ? 'lain' : 'operasional');
                $sid  = ($siswa_id > 0 && $jenis === 'pemasukan') ? $siswa_id : null;

                $ins = $pdo->prepare('INSERT INTO transaksi
                    (jenis, kategori, siswa_id, jumlah, tanggal, keterangan, bukti, user_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                $ins->execute([
                    $jenis,
                    $kat,
                    $sid,
                    $jml,
                    $tanggal,
                    $keterangan ?: null,
                    $bukti,
                    $user['id'] ?? null,
                ]);

                set_flash('success', ucfirst($jenis) . ' berhasil disimpan.');
                redirect('admin/transaksi/');
            }
        }
    }
}

ob_start();
?>
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Tambah Transaksi</h3>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= e($error) ?></div>
                <?php endif; ?>

                <form method="post" enctype="multipart/form-data" id="formTransaksi">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Jenis <span class="text-danger">*</span></label>
                            <select name="jenis" id="jenis" class="form-select" required>
                                <option value="pemasukan" <?= $jenis === 'pemasukan' ? 'selected' : '' ?>>Pemasukan</option>
                                <option value="pengeluaran" <?= $jenis === 'pengeluaran' ? 'selected' : '' ?>>Pengeluaran</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Kategori <span class="text-danger">*</span></label>
                            <select name="kategori" id="kategori" class="form-select" required>
                                <!-- diisi JS -->
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal" class="form-control" required value="<?= e($tanggal) ?>">
                        </div>
                    </div>

                    <div class="mb-3" id="wrapSiswa">
                        <label class="form-label">Siswa</label>
                        <select name="siswa_id" id="siswa_id" class="form-select">
                            <option value="">— Tidak terkait siswa —</option>
                            <?php foreach ($siswaList as $s): ?>
                                <option value="<?= (int) $s['id'] ?>" <?= $siswa_id === (int) $s['id'] ? 'selected' : '' ?>>
                                    <?= e($s['nama']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Jumlah (non-SPP) -->
                    <div id="wrapBiasa">
                        <div class="mb-3">
                            <label class="form-label">Jumlah (Rp) <span class="text-danger">*</span></label>
                            <input type="number" name="jumlah" id="jumlah" class="form-control" min="1" step="1"
                                   value="<?= e((string) $jumlah) ?>">
                        </div>
                    </div>

                    <!-- SPP multi-bulan -->
                    <div id="wrapSpp" style="display:none;">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Tahun Ajaran</label>
                                <select name="tahun_ajaran" id="tahun_ajaran" class="form-select">
                                    <?php foreach ($tahunAjaranList as $ta): ?>
                                        <option value="<?= e($ta) ?>" <?= $tahun_ajaran === $ta ? 'selected' : '' ?>>
                                            <?= e($ta) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-8 mb-3">
                                <label class="form-label">Tarif SPP / bulan (Rp) <span class="text-danger">*</span></label>
                                <input type="number" name="tarif" id="tarif" class="form-control" min="1" step="1"
                                       value="<?= $tarif > 0 ? (int) $tarif : '' ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Pilih Bulan</label>
                            <div class="row" id="listBulan">
                                <?php foreach ($bulanNama as $num => $nama): ?>
                                    <?php $sudah = in_array($num, $lunas, true); ?>
                                    <div class="col-6 col-md-3 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="bulan[]"
                                                   value="<?= $num ?>" id="bln<?= $num ?>"
                                                   <?= $sudah ? 'disabled' : '' ?>
                                                   <?= in_array($num, $bulan_dipilih, true) && !$sudah ? 'checked' : '' ?>>
                                            <label class="form-check-label <?= $sudah ? 'text-muted' : '' ?>" for="bln<?= $num ?>">
                                                <?= $nama ?>
                                                <?php if ($sudah): ?>
                                                    <span class="badge text-bg-success">Lunas</span>
                                                <?php endif; ?>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="form-text">Bulan yang sudah lunas (pada tahun ajaran ini) tidak bisa dipilih lagi.</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Keterangan</label>
                        <textarea name="keterangan" class="form-control" rows="2"><?= e($keterangan) ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Bukti (opsional)</label>
                        <input type="file" name="bukti" class="form-control" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf">
                        <div class="form-text">JPG, PNG, PDF — max 5MB</div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" name="simpan" value="1" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i> Simpan
                        </button>
                        <a href="/admin/transaksi/" class="btn btn-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const jenis = document.getElementById('jenis');
    const kategori = document.getElementById('kategori');
    const wrapBiasa = document.getElementById('wrapBiasa');
    const wrapSpp = document.getElementById('wrapSpp');
    const wrapSiswa = document.getElementById('wrapSiswa');
    const jumlah = document.getElementById('jumlah');
    const tarif = document.getElementById('tarif');

    const katPemasukan = [
        { v: 'spp', t: 'SPP' },
        { v: 'pendaftaran', t: 'Pendaftaran' },
        { v: 'donasi', t: 'Donasi' },
        { v: 'lain', t: 'Lain-lain' },
    ];
    const katPengeluaran = [
        { v: 'operasional', t: 'Operasional' },
        { v: 'gaji', t: 'Gaji' },
        { v: 'ATK', t: 'ATK' },
        { v: 'utilitas', t: 'Utilitas' },
        { v: 'lain', t: 'Lain-lain' },
    ];

    const selectedKat = <?= json_encode($kategori) ?>;

    function isiKategori() {
        const list = jenis.value === 'pemasukan' ? katPemasukan : katPengeluaran;
        kategori.innerHTML = '';
        list.forEach(function (item) {
            const opt = document.createElement('option');
            opt.value = item.v;
            opt.textContent = item.t;
            if (item.v === selectedKat) opt.selected = true;
            kategori.appendChild(opt);
        });
        if (![...kategori.options].some(o => o.selected)) {
            kategori.selectedIndex = 0;
        }
        toggleSpp();
    }

    function toggleSpp() {
        const isSpp = jenis.value === 'pemasukan' && kategori.value === 'spp';
        wrapSpp.style.display = isSpp ? 'block' : 'none';
        wrapBiasa.style.display = isSpp ? 'none' : 'block';
        wrapSiswa.style.display = jenis.value === 'pemasukan' ? 'block' : 'none';

        if (isSpp) {
            jumlah.removeAttribute('required');
            tarif.setAttribute('required', 'required');
        } else {
            tarif.removeAttribute('required');
            jumlah.setAttribute('required', 'required');
        }
    }

    function refreshLunas() {
        if (kategori.value !== 'spp') return;
        const f = document.getElementById('formTransaksi');
        const tmp = document.createElement('input');
        tmp.type = 'hidden';
        tmp.name = '_refresh';
        tmp.value = '1';
        f.appendChild(tmp);
        f.submit();
    }

    jenis.addEventListener('change', isiKategori);
    kategori.addEventListener('change', toggleSpp);
    document.getElementById('siswa_id').addEventListener('change', refreshLunas);
    document.getElementById('tahun_ajaran').addEventListener('change', function () {
        if (document.getElementById('siswa_id').value) refreshLunas();
    });

    isiKategori();
})();
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../../includes/layout_admin.php';