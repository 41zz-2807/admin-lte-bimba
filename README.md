# Bimba KSR

Aplikasi manajemen **Keuangan & Data Master** untuk Bimba KSR berbasis web.

Dibangun dengan **PHP 8.4**, **AdminLTE**, **MySQL**, dan **Docker**.

---

## Fitur Utama

### Dashboard
Ringkasan data dan aktivitas aplikasi.

### Data Master
- **Siswa**
  - Tambah / Edit / Hapus data siswa
  - Field: NIS, Nama, Jenis Kelamin, Tanggal Lahir, Status (Aktif / Nonaktif / Lulus), Nama Ortu, No. HP Ortu, Alamat, Catatan
  - Filter & pencarian
  - Import & Export Excel

- **Guru / Staff**
  - Tambah / Edit / Hapus data guru & staff
  - Field: NIP, Nama, Jenis Kelamin, Status, No. HP, Email, Alamat, Catatan
  - Import & Export Excel

### Transaksi Keuangan
- Pemasukan & Pengeluaran
- Kategori transaksi (termasuk **SPP**)
- Pembayaran SPP multi-bulan per siswa + tahun ajaran
- Upload bukti transaksi (jpg/png/pdf, max 5MB)
- Detail transaksi

### Manajemen User
- Role: `superadmin` & `admin`
- Aktif / Nonaktif user
- Ganti password
- Last login tracking

### Lainnya
- Autentikasi berbasis session + password hashing (bcrypt)
- Flash message
- Layout AdminLTE modern (Bootstrap Icons)

---

## Teknologi

| Komponen     | Teknologi                |
|--------------|--------------------------|
| Frontend     | AdminLTE, Bootstrap, Bootstrap Icons |
| Backend      | PHP 8.4 (PDO)            |
| Database     | MySQL 8                  |
| Container    | Docker + Docker Compose  |
| Web Server   | Apache (php:8.4-apache)  |

---

## Struktur Folder

```
admin-lte-bimba/
├── config/
│   ├── app.php              # Konfigurasi aplikasi
│   └── database.php         # Konfigurasi database
├── database/
│   ├── schema.sql           # Schema database
│   └── seed_superadmin.php  # Seed user superadmin
├── includes/
│   ├── auth.php             # Login, logout, role helper
│   ├── db.php               # Koneksi PDO
│   ├── flash.php            # Flash message
│   ├── helpers.php          # Helper functions
│   └── layout_admin.php     # Layout AdminLTE
├── public/
│   ├── admin/
│   │   ├── index.php        # Dashboard
│   │   ├── login.php
│   │   ├── logout.php
│   │   ├── change-password.php
│   │   ├── siswa/           # CRUD Siswa + Import/Export
│   │   ├── guru/            # CRUD Guru/Staff + Import/Export
│   │   ├── transaksi/       # Transaksi Keuangan + SPP
│   │   └── users/           # Manajemen User
│   ├── uploads/             # File upload (bukti transaksi)
│   ├── .htaccess
│   └── index.php            # Redirect ke /admin/
├── .env.example
├── docker-compose.yml
├── Dockerfile
└── README.md
```

---

## Requirement

- Docker & Docker Compose
- MySQL (bisa pakai container yang sudah ada atau lokal)
- (Opsional) phpMyAdmin

---

## Cara Menjalankan

### 1. Clone repository

```bash
git clone <url-repo-anda>
cd admin-lte-bimba
```

### 2. Setup environment

```bash
cp .env.example .env
```

Sesuaikan isi `.env`:

```env
DB_HOST=host.docker.internal   # atau nama container MySQL
DB_PORT=3306
DB_NAME=bimba_ksr
DB_USER=root
DB_PASS=password_anda

APP_NAME="Bimba KSR"
APP_URL=http://localhost:8181
APP_ENV=local
```

### 3. Buat database

Jalankan file `database/schema.sql` di phpMyAdmin / MySQL client:

```sql
-- Membuat database + tabel users
SOURCE database/schema.sql;
```

> **Catatan:** Schema saat ini berisi tabel `users`.  
> Tabel `siswa`, `guru_staff`, `transaksi`, dan `transaksi_spp_bulan` perlu ditambahkan sesuai struktur yang dipakai di kode (atau generate dari migration / SQL lengkap).

### 4. Jalankan aplikasi (Docker)

```bash
docker compose up -d --build
```

Aplikasi akan berjalan di:

```
http://localhost:8181
```

### 5. Seed Superadmin

```bash
docker compose exec app php database/seed_superadmin.php
```

**Login default:**

| Field    | Nilai                        |
|----------|------------------------------|
| Email    | `superadmin@bimba-ksr.local` |
| Password | `Superadmin123!`             |

> **Penting:** Segera ganti password setelah login pertama.

---

## Docker Compose (Ringkas)

Aplikasi ini dirancang untuk **bergabung ke network Docker yang sudah ada** (contoh: network dari project AdminLTE RT sebelumnya).

```yaml
services:
  app:
    build: .
    container_name: bimba-ksr-app
    ports:
      - "8181:80"
    volumes:
      - .:/var/www/html
    environment:
      - DB_HOST=adminlte_db
      - DB_PORT=3306
      - DB_NAME=bimba_ksr
      - DB_USER=bimba
      - DB_PASS=merdeka1945
    networks:
      - admin-lte-web_default
    restart: unless-stopped

networks:
  admin-lte-web_default:
    external: true
```

Sesuaikan `DB_HOST`, `DB_USER`, `DB_PASS`, dan nama network sesuai environment kamu.

---

## Kredensial Database (Contoh)

| Parameter | Nilai default (contoh) |
|-----------|------------------------|
| Host      | `host.docker.internal` / `adminlte_db` |
| Port      | `3306`                 |
| Database  | `bimba_ksr`            |
| Username  | `root` / `bimba`       |
| Password  | (sesuaikan di `.env`)  |

---

## Role & Hak Akses

| Role         | Keterangan                          |
|--------------|-------------------------------------|
| `superadmin` | Akses penuh (termasuk manajemen user) |
| `admin`      | Akses operasional (siswa, guru, transaksi) |

---

## Upload File

- Folder: `public/uploads/bukti/`
- Format yang diizinkan: `jpg`, `jpeg`, `png`, `gif`, `webp`, `pdf`
- Maksimal ukuran: **5 MB**

Folder upload **tidak** di-commit ke Git (disarankan masukkan ke `.gitignore`).

---

## Catatan Pengembangan

1. Schema database masih minimal (hanya `users`). Pastikan tabel berikut sudah dibuat sebelum memakai fitur terkait:
   - `siswa`
   - `guru_staff`
   - `transaksi`
   - `transaksi_spp_bulan`

2. Aplikasi menggunakan **path-based routing** sederhana (`/admin/siswa/`, `/admin/transaksi/`, dll).

3. Helper penting:
   - `base_url()`
   - `e()` → escape HTML
   - `redirect()`
   - `upload_bukti()`
   - `require_login()` / role check

4. Versi aplikasi saat ini: **0.1.0**

---

## Lisensi

Project internal Bimba KSR.  
Silakan digunakan dan dikembangkan sesuai kebutuhan.

---

**Bimba KSR** — Manajemen data & keuangan yang lebih rapi.