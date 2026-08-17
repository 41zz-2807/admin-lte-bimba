<?php
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/flash.php';
require_once __DIR__ . '/../../../includes/helpers.php';

require_login();

$pageTitle = 'Tambah Transaksi';
$error = '';
global $pdo;

$siswaList = $pdo->query(
    "SELECT id, nama, email_ortu, nama_ortu FROM siswa WHERE status='aktif' ORDER BY nama"
)->fetchAll();

$bulanNama = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
];

// ---- Tahun ajaran + tarif dari tabel tahun_ajaran ----
$tahunAjaranList = [];
$tarifMap        = [];
$defaultTA       = '';

try {
    $rowsTA = $pdo->query(
        'SELECT kode, tarif_spp, is_aktif FROM tahun_ajaran ORDER BY kode DESC'
    )->fetchAll();
    foreach ($rowsTA as $r) {
        $tahunAjaranList[] = $r['kode'];
        $tarifMap[$r['kode']] = (float) $r['tarif_spp'];
        if ((int) $r['is_aktif'] === 1) {
            $defaultTA = $r['kode'];
        }
    }
} catch (PDOException $e) {
    $tahunAjaranList = [];
}

if ($defaultTA === '' && $tahunAjaranList) {
    $defaultTA = $tahunAjaranList[0];
}

$jenis          = $_POST['jenis'] ?? 'pemasukan';
$kategori       = trim($_POST['kategori'] ?? 'lain');
$tanggal        = $_POST['tanggal'] ?? date('Y-m-d');
$jumlah         = $_POST['jumlah'] ?? '';
$keterangan     = trim($_POST['keterangan'] ?? '');
$siswa_id       = (int) ($_POST['siswa_id'] ?? 0);
$tahun_ajaran   = trim($_POST['tahun_ajaran'] ?? $defaultTA);
$tarif          = (float) ($_POST['tarif'] ?? 0);
$bulan_dipilih  = array_map('intval', $_POST['bulan'] ?? []);
$kirim_email    = isset($_POST['kirim_email_kwitansi']);

if ($tahun_ajaran !== '' && !preg_match('/^\d{4}\/\d{4}$/', $tahun_ajaran)) {
    $tahun_ajaran = $defaultTA;
}

// Default tarif dari map jika kosong
if ($tarif <= 0 && $tahun_ajaran !== '' && isset($tarifMap[$tahun_ajaran])) {
    $tarif = $tarifMap[$tahun_ajaran];
}

// Bulan lunas
$lunas = [];
if ($siswa_id > 0 && $kategori === 'spp' && $tahun_ajaran !== '') {
    $st = $pdo->prepare(
        'SELECT bulan FROM transaksi_spp_bulan WHERE siswa_id = ? AND tahun_ajaran = ?'
    );
    $st->execute([$siswa_id, $tahun_ajaran]);
    $lunas = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
}

function generate_kwitansi_transaksi(PDO $pdo, int $transaksiId): array
{
    $noKwitansi = 'KW/T/' . date('Ymd') . '/' . str_pad((string) $transaksiId, 4, '0', STR_PAD_LEFT);

    do {
        $kode = strtoupper(bin2hex(random_bytes(8)));
        $cek  = $pdo->prepare('SELECT id FROM transaksi WHERE kode_verifikasi = ? LIMIT 1');
        $cek->execute([$kode]);
    } while ($cek->fetch());

    try {
        $cek2 = $pdo->prepare('SELECT id FROM konfirmasi_bayar WHERE kode_verifikasi = ? LIMIT 1');
        $cek2->execute([$kode]);
        while ($cek2->fetch()) {
            $kode = strtoupper(bin2hex(random_bytes(8)));
            $cek2->execute([$kode]);
        }
    } catch (PDOException $e) {
        // ignore
    }

    $pdo->prepare(
        'UPDATE transaksi SET no_kwitansi = ?, kode_verifikasi = ? WHERE id = ?'
    )->execute([$noKwitansi, $kode, $transaksiId]);

    return ['no_kwitansi' => $noKwitansi, 'kode_verifikasi' => $kode];
}

function kirim_email_kwitansi_spp(array $siswa, array $kw, float $total, string $ket, string $tanggal): string
{
    $app   = require __DIR__ . '/../../../config/app.php';
    $email = trim((string) ($siswa['email_ortu'] ?? ''));
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ' Email tidak dikirim (email ortu kosong/tidak valid).';
    }

    $namaOrtu  = trim((string) ($siswa['nama_ortu'] ?? '')) ?: 'Bapak/Ibu';
    $verifyUrl = rtrim($app['url'] ?? '', '/') . '/verifikasi-kwitansi.php?kode=' . urlencode($kw['kode_verifikasi']);

    $body = "Halo {$namaOrtu},\n\n"
        . "Pembayaran SPP {$app['name']} telah dicatat.\n\n"
        . "Nama siswa   : {$siswa['nama']}\n"
        . "Tanggal bayar: {$tanggal}\n"
        . "Jumlah       : Rp " . number_format($total, 0, ',', '.') . "\n"
        . "Keterangan   : {$ket}\n"
        . "No. Kwitansi : {$kw['no_kwitansi']}\n"
        . "Kode verifikasi: {$kw['kode_verifikasi']}\n\n"
        . "Cek keaslian kwitansi:\n{$verifyUrl}\n\n"
        . "Terima kasih.\n{$app['name']}";

    $result = send_smtp_mail(
        $email,
        'Kwitansi SPP ' . $kw['no_kwitansi'] . ' — ' . $app['name'],
        $body,
        $namaOrtu
    );

    return $result === true
        ? ' Email kwitansi terkirim ke ' . $email . '.'
        : ' Gagal kirim email: ' . $result;
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
                $error = 'Tarif SPP per bulan harus lebih dari 0. Atur di Pengaturan Tahun Ajaran.';
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

                        $ins = $pdo->prepare(
                            'INSERT INTO transaksi
                             (jenis, kategori, siswa_id, jumlah, tanggal, keterangan, bukti, user_id)
                             VALUES (\'pemasukan\', \'spp\', ?, ?, ?, ?, ?, ?)'
                        );
                        $ins->execute([
                            $siswa_id,
                            $total,
                            $tanggal,
                            $ket,
                            $bukti,
                            $user['id'] ?? null,
                        ]);
                        $transaksi_id = (int) $pdo->lastInsertId();

                        $det = $pdo->prepare(
                            'INSERT INTO transaksi_spp_bulan
                             (transaksi_id, siswa_id, bulan, tahun_ajaran, jumlah)
                             VALUES (?, ?, ?, ?, ?)'
                        );
                        foreach ($bulan_ok as $b) {
                            $det->execute([$transaksi_id, $siswa_id, $b, $tahun_ajaran, $tarif]);
                        }

                        $kw = generate_kwitansi_transaksi($pdo, $transaksi_id);

                        $pdo->commit();

                        $pesanEmail = '';
                        if ($kirim_email) {
                            $stSiswa = $pdo->prepare(
                                'SELECT id, nama, email_ortu, nama_ortu FROM siswa WHERE id = ? LIMIT 1'
                            );
                            $stSiswa->execute([$siswa_id]);
                            $siswaRow = $stSiswa->fetch() ?: [
                                'nama' => '-', 'email_ortu' => '', 'nama_ortu' => '',
                            ];
                            $pesanEmail = kirim_email_kwitansi_spp(
                                $siswaRow, $kw, $total, $ket, $tanggal
                            );
                        }

                        set_flash(
                            'success',
                            'SPP berhasil dicatat: ' . count($bulan_ok) . ' bulan, total Rp ' .
                            number_format($total, 0, ',', '.') .
                            '. Kwitansi: ' . $kw['no_kwitansi'] . '.' . $pesanEmail
                        );
                        redirect('admin/transaksi/kwitansi.php?id=' . $transaksi_id);
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        $error = 'Gagal menyimpan SPP: ' . $e->getMessage();
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

                $ins = $pdo->prepare(
                    'INSERT INTO transaksi
                     (jenis, kategori, siswa_id, jumlah, tanggal, keterangan, bukti, user_id)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
                );
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

                <?php if (!$tahunAjaranList): ?>
                    <div class="alert alert-warning">
                        Belum ada tahun ajaran. Isi dulu di
                        <a href="/admin/settings.php">Pengaturan → Tahun Ajaran & Tarif SPP</a>.
                    </div>
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

                    <div id="wrapBiasa">
                        <div class="mb-3">
                            <label class="form-label">Jumlah (Rp) <span class="text-danger">*</span></label>
                            <input type="number" name="jumlah" id="jumlah" class="form-control" min="1" step="1"
                                   value="<?= e((string) $jumlah) ?>">
                        </div>
                    </div>

                    <div id="wrapSpp" style="display:none;">
                        <div class="row">
                            <div class="col-md-5 mb-3">
                                <label class="form-label">Tahun Ajaran</label>
                                <select name="tahun_ajaran" id="tahun_ajaran" class="form-select">
                                    <?php foreach ($tahunAjaranList as $ta): ?>
                                        <option value="<?= e($ta) ?>"
                                                data-tarif="<?= (float) ($tarifMap[$ta] ?? 0) ?>"
                                                <?= $tahun_ajaran === $ta ? 'selected' : '' ?>>
                                            <?= e($ta) ?>
                                            <?php if (($tarifMap[$ta] ?? 0) > 0): ?>
                                                — Rp <?= number_format($tarifMap[$ta], 0, ',', '.') ?>
                                            <?php else: ?>
                                                — (tarif belum diatur)
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-7 mb-3">
                                <label class="form-label">Tarif SPP / bulan (Rp) <span class="text-danger">*</span></label>
                                <input type="number" name="tarif" id="tarif" class="form-control" min="1" step="1"
                                       value="<?= $tarif > 0 ? (int) $tarif : '' ?>">
                                <div class="form-text">Otomatis dari pengaturan TA; bisa diubah manual jika perlu.</div>
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
                        <div class="form-check mb-3">
                            <input type="checkbox" class="form-check-input" name="kirim_email_kwitansi"
                                   id="kirim_email_kwitansi" value="1" checked>
                            <label class="form-check-label" for="kirim_email_kwitansi">
                                Kirim kwitansi ke email orang tua setelah simpan
                            </label>
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
    const taSelect = document.getElementById('tahun_ajaran');

    const tarifMap = <?= json_encode($tarifMap, JSON_UNESCAPED_UNICODE) ?>;

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
            isiTarifDariTA();
        } else {
            tarif.removeAttribute('required');
            jumlah.setAttribute('required', 'required');
        }
    }

    function isiTarifDariTA() {
        if (!taSelect || !tarif) return;
        const ta = taSelect.value;
        const t = parseFloat(tarifMap[ta] || 0);
        if (t > 0) {
            tarif.value = Math.round(t);
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

    if (taSelect) {
        taSelect.addEventListener('change', function () {
            isiTarifDariTA();
            if (document.getElementById('siswa_id').value) refreshLunas();
        });
    }

    isiKategori();
})();
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../../includes/layout_admin.php';