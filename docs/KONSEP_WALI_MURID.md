# Konsep Portal Wali Murid — Bimba KSR

Dokumen ini menjadi acuan jika development dilanjutkan di akun/perangkat lain.

Terakhir diupdate: 2026-08-16

---

## Ringkasan

Portal khusus orang tua/wali murid untuk:
- Melihat profil anak (multi anak, multi tahun ajaran)
- Konfirmasi pembayaran SPP + terima kwitansi
- Kirim pesan ke admin
- Kirim testimoni (perlu verifikasi admin)

**Prinsip utama:** 1 akun login = 1 orang tua, bisa punya banyak anak.

---

## 1. Login & Multi Anak

### Keputusan desain
| Item | Keputusan |
|------|-----------|
| Login | Email + password milik **orang tua** |
| Role di tabel `users` | `wali_murid` (tambahan dari `superadmin` / `admin`) |
| Relasi | Tabel pivot `siswa_wali` (user_id ↔ siswa_id) |
| Multi tahun ajaran | Semua anak (aktif/lulus) tetap terlihat; filter status di UI |

### Alur akun
1. Admin input/edit data siswa (nama ortu, email ortu, HP ortu sudah ada di tabel `siswa`)
2. Admin **buat / tautkan** akun wali murid ke siswa
3. Sistem bisa generate password sementara → orang tua ganti setelah login
4. Setelah login → daftar anak → pilih anak → detail

### Login terpisah
- Admin: `/admin/login.php` → role `admin` / `superadmin` → panel AdminLTE
- Wali murid: `/wali/login.php` → role `wali_murid` → portal wali
- Jangan campur session area (cek role setelah login)

---

## 2. Pesan ke Admin (tahap berikutnya)

- Tabel: `pesan_wali` (user_id, subjek, isi, status, balasan_admin, timestamps)
- Status: `baru` | `dibaca` | `dibalas`
- Admin baca & balas di panel admin
- Opsional: email notifikasi ke admin

---

## 3. Testimoni (tahap berikutnya)

- Wali isi form → status default `pending`
- Admin approve/reject
- Hanya `approved` tampil di frontend publik
- Bisa pakai tabel `landing_testimoni` + kolom `status` + `user_id`, atau tabel terpisah

---

## 4. Konfirmasi Pembayaran + Kwitansi (tahap berikutnya)

1. Wali upload bukti + pilih anak + nominal + bulan/tahun ajaran
2. Status: `pending` → admin **terima** / **tolak**
3. Jika diterima:
   - Insert ke `transaksi` + `transaksi_spp_bulan` (modul keuangan existing)
   - Generate nomor kwitansi
   - Kirim email ke wali
   - Tampil di riwayat portal wali (download)

---

## Struktur Database (tahap 1 + rencana)

```sql
-- Perlu dijalankan:
ALTER TABLE users MODIFY role ENUM('superadmin','admin','wali_murid') NOT NULL DEFAULT 'admin';

CREATE TABLE siswa_wali (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  siswa_id INT UNSIGNED NOT NULL,
  hubungan VARCHAR(50) NULL DEFAULT 'orang tua',  -- ayah/ibu/wali
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_user_siswa (user_id, siswa_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (siswa_id) REFERENCES siswa(id) ON DELETE CASCADE
);