-- ============================================================
-- Bimba KSR - Schema Awal
-- Jalankan di phpMyAdmin (pilih database bimba_ksr dulu)
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