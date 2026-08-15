-- ============================================================
-- Bimba KSR - Schema Lengkap
-- Jalankan di phpMyAdmin atau mysql CLI
-- ============================================================

CREATE DATABASE IF NOT EXISTS bimba_ksr
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE bimba_ksr;

-- ------------------------------------------------------------
-- Tabel users (dengan role)
-- Role: superadmin | admin
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(100) NOT NULL,
  email         VARCHAR(150) NOT NULL,
  password      VARCHAR(255) NOT NULL,
  role          ENUM('superadmin', 'admin') NOT NULL DEFAULT 'admin',
  is_active     TINYINT(1) NOT NULL DEFAULT 1,
  last_login_at DATETIME NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  UNIQUE KEY uk_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabel siswa
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS siswa (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nis           VARCHAR(50) NULL,
  nama          VARCHAR(150) NOT NULL,
  jenis_kelamin ENUM('L', 'P') NOT NULL DEFAULT 'L',
  tanggal_lahir DATE NULL,
  nama_ortu     VARCHAR(150) NULL,
  no_hp_ortu    VARCHAR(30) NULL,
  email_ortu    VARCHAR(150) NULL,
  alamat        TEXT NULL,
  status        ENUM('aktif', 'nonaktif', 'lulus') NOT NULL DEFAULT 'aktif',
  catatan       TEXT NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  UNIQUE KEY uk_siswa_nis (nis),
  KEY idx_siswa_status (status),
  KEY idx_siswa_nama (nama)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabel guru_staff
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS guru_staff (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nip           VARCHAR(50) NULL,
  nama          VARCHAR(150) NOT NULL,
  jenis_kelamin ENUM('L', 'P') NOT NULL DEFAULT 'L',
  jabatan       VARCHAR(100) NULL,
  no_hp         VARCHAR(30) NULL,
  email         VARCHAR(150) NULL,
  alamat        TEXT NULL,
  status        ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
  catatan       TEXT NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  UNIQUE KEY uk_guru_nip (nip),
  KEY idx_guru_status (status),
  KEY idx_guru_nama (nama)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabel transaksi (pemasukan / pengeluaran)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS transaksi (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  jenis         ENUM('pemasukan', 'pengeluaran') NOT NULL,
  kategori      VARCHAR(50) NOT NULL DEFAULT 'lain',
  siswa_id      INT UNSIGNED NULL,
  jumlah        DECIMAL(15,2) NOT NULL DEFAULT 0,
  tanggal       DATE NOT NULL,
  keterangan    TEXT NULL,
  bukti         VARCHAR(255) NULL COMMENT 'path relatif dari /uploads',
  user_id       INT UNSIGNED NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  KEY idx_transaksi_jenis (jenis),
  KEY idx_transaksi_kategori (kategori),
  KEY idx_transaksi_tanggal (tanggal),
  KEY idx_transaksi_siswa (siswa_id),
  KEY idx_transaksi_user (user_id),

  CONSTRAINT fk_transaksi_siswa
    FOREIGN KEY (siswa_id) REFERENCES siswa (id)
    ON DELETE SET NULL ON UPDATE CASCADE,

  CONSTRAINT fk_transaksi_user
    FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabel transaksi_spp_bulan (detail pembayaran SPP per bulan)
-- Satu transaksi SPP bisa mencakup beberapa bulan
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS transaksi_spp_bulan (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  transaksi_id  INT UNSIGNED NOT NULL,
  siswa_id      INT UNSIGNED NOT NULL,
  bulan         TINYINT UNSIGNED NOT NULL COMMENT '1=Januari ... 12=Desember',
  tahun_ajaran  VARCHAR(9) NOT NULL COMMENT 'contoh: 2025/2026',
  jumlah        DECIMAL(15,2) NOT NULL DEFAULT 0,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  UNIQUE KEY uk_spp_siswa_bulan_ta (siswa_id, bulan, tahun_ajaran),
  KEY idx_spp_transaksi (transaksi_id),
  KEY idx_spp_tahun_ajaran (tahun_ajaran),

  CONSTRAINT fk_spp_transaksi
    FOREIGN KEY (transaksi_id) REFERENCES transaksi (id)
    ON DELETE CASCADE ON UPDATE CASCADE,

  CONSTRAINT fk_spp_siswa
    FOREIGN KEY (siswa_id) REFERENCES siswa (id)
    ON DELETE CASCADE ON UPDATE CASCADE,

  CONSTRAINT chk_bulan CHECK (bulan BETWEEN 1 AND 12)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;