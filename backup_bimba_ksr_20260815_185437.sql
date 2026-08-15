-- MySQL dump 10.13  Distrib 8.0.46, for Linux (x86_64)
--
-- Host: localhost    Database: bimba_ksr
-- ------------------------------------------------------
-- Server version	8.0.46

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `guru_staff`
--

DROP TABLE IF EXISTS `guru_staff`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `guru_staff` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `nip` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nama` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `jenis_kelamin` enum('L','P') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'L',
  `jabatan` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `no_hp` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `alamat` text COLLATE utf8mb4_unicode_ci,
  `status` enum('aktif','nonaktif') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'aktif',
  `catatan` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_guru_nip` (`nip`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `guru_staff`
--

LOCK TABLES `guru_staff` WRITE;
/*!40000 ALTER TABLE `guru_staff` DISABLE KEYS */;
INSERT INTO `guru_staff` VALUES (1,'1234','gema','P','Guru','0819','hgemaputri@gmail.com','pmr','aktif','pmr','2026-08-15 04:24:22','2026-08-15 04:24:22'),(2,'1234566','ardin','L','Kepala Sekolah','0819','ardin@gmail.com','pmr','aktif','pmr','2026-08-15 09:57:47','2026-08-15 09:57:47');
/*!40000 ALTER TABLE `guru_staff` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `landing_fasilitas`
--

DROP TABLE IF EXISTS `landing_fasilitas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `landing_fasilitas` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `icon` varchar(50) NOT NULL DEFAULT 'fa-star',
  `color` varchar(50) NOT NULL DEFAULT 'bg-brand-blue',
  `title` varchar(150) NOT NULL,
  `description` text,
  `sort_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `landing_fasilitas`
--

LOCK TABLES `landing_fasilitas` WRITE;
/*!40000 ALTER TABLE `landing_fasilitas` DISABLE KEYS */;
INSERT INTO `landing_fasilitas` VALUES (1,'fa-puzzle-piece','bg-brand-blue','satu','angka baru',0,1,'2026-08-15 10:50:28','2026-08-15 10:50:28'),(2,'fa-palette','bg-brand-yellow','dua','dua',0,1,'2026-08-15 11:35:14','2026-08-15 11:35:14'),(3,'fa-tree','bg-brand-green','tiga','tiga',0,1,'2026-08-15 11:35:25','2026-08-15 11:35:25'),(4,'fa-shield-halved','bg-brand-red','empat','empat',0,1,'2026-08-15 11:35:34','2026-08-15 11:35:34');
/*!40000 ALTER TABLE `landing_fasilitas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `landing_hero`
--

DROP TABLE IF EXISTS `landing_hero`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `landing_hero` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `badge` varchar(100) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `image_url` varchar(500) DEFAULT NULL,
  `cta_text` varchar(100) DEFAULT NULL,
  `cta_link` varchar(255) DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `landing_hero`
--

LOCK TABLES `landing_hero` WRITE;
/*!40000 ALTER TABLE `landing_hero` DISABLE KEYS */;
INSERT INTO `landing_hero` VALUES (1,'baru test','ettststt','','','','',0,1,'2026-08-15 10:43:19','2026-08-15 10:43:28'),(2,'test baru','maryam ayunidya','','','','',0,1,'2026-08-15 10:44:12','2026-08-15 10:44:12'),(3,'gambar slider upoad','gambar slider upoad',NULL,'landing/20260815_110426_15bc0275.png',NULL,NULL,0,1,'2026-08-15 11:04:26','2026-08-15 11:04:26');
/*!40000 ALTER TABLE `landing_hero` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `landing_pengajar`
--

DROP TABLE IF EXISTS `landing_pengajar`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `landing_pengajar` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `nama` varchar(150) NOT NULL,
  `jabatan` varchar(100) DEFAULT NULL,
  `deskripsi` text,
  `foto_url` varchar(500) DEFAULT NULL,
  `warna` varchar(50) NOT NULL DEFAULT 'text-brand-blue',
  `sort_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `landing_pengajar`
--

LOCK TABLES `landing_pengajar` WRITE;
/*!40000 ALTER TABLE `landing_pengajar` DISABLE KEYS */;
INSERT INTO `landing_pengajar` VALUES (1,'dua','walikelas','satu','','text-brand-blue',0,1,'2026-08-15 10:50:42','2026-08-15 10:50:42'),(2,'pengajar upload','pengajar upload','pengajar upload','landing/20260815_110446_754d275b.png','text-brand-blue',0,1,'2026-08-15 11:04:46','2026-08-15 11:04:46'),(3,'tiga','tiga',NULL,'landing/20260815_113605_9663f6b5.png','text-brand-red',0,1,'2026-08-15 11:36:05','2026-08-15 11:36:05'),(4,'empat','empat','empat','landing/20260815_113624_8211d5f2.png','text-brand-yellow',0,1,'2026-08-15 11:36:24','2026-08-15 11:36:24');
/*!40000 ALTER TABLE `landing_pengajar` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `landing_testimoni`
--

DROP TABLE IF EXISTS `landing_testimoni`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `landing_testimoni` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `nama` varchar(150) NOT NULL,
  `relasi` varchar(150) DEFAULT NULL,
  `isi` text NOT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `landing_testimoni`
--

LOCK TABLES `landing_testimoni` WRITE;
/*!40000 ALTER TABLE `landing_testimoni` DISABLE KEYS */;
/*!40000 ALTER TABLE `landing_testimoni` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES ('smtp_from','aziz@altama.co.id','2026-08-15 10:20:13'),('smtp_from_name','Bimba KSR','2026-08-15 10:18:28'),('smtp_host','mail.altama.co.id','2026-08-15 10:19:55'),('smtp_pass','azizBu85aNo4','2026-08-15 10:19:55'),('smtp_port','587','2026-08-15 10:18:28'),('smtp_secure','tls','2026-08-15 10:18:28'),('smtp_user','aziz@altama.co.id','2026-08-15 10:19:55'),('telegram_bot_token','8987973292:AAEWeJ0UHcEqKhqiwQcfDSB5hxiqy6nBaXM','2026-08-15 10:26:41'),('telegram_chat_id','1032303044','2026-08-15 10:26:41');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `siswa`
--

DROP TABLE IF EXISTS `siswa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `siswa` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `nis` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nama` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `jenis_kelamin` enum('L','P') COLLATE utf8mb4_unicode_ci NOT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `nama_ortu` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `no_hp_ortu` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_ortu` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `alamat` text COLLATE utf8mb4_unicode_ci,
  `status` enum('aktif','nonaktif','lulus') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'aktif',
  `catatan` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_siswa_nis` (`nis`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `siswa`
--

LOCK TABLES `siswa` WRITE;
/*!40000 ALTER TABLE `siswa` DISABLE KEYS */;
INSERT INTO `siswa` VALUES (1,'1234','bilal','L','2014-09-29','gema','0819',NULL,'pmr','aktif','pmr','2026-08-15 04:14:28','2026-08-15 04:14:28'),(2,'1234567890','maryam','L','2018-01-11','gema','0819','maryam@gmail.com','pmr','aktif','pmr','2026-08-15 09:45:33','2026-08-15 09:45:33');
/*!40000 ALTER TABLE `siswa` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transaksi`
--

DROP TABLE IF EXISTS `transaksi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `transaksi` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `jenis` enum('pemasukan','pengeluaran') COLLATE utf8mb4_unicode_ci NOT NULL,
  `kategori` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `siswa_id` int unsigned DEFAULT NULL,
  `jumlah` decimal(15,2) NOT NULL DEFAULT '0.00',
  `tanggal` date NOT NULL,
  `keterangan` text COLLATE utf8mb4_unicode_ci,
  `bukti` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_id` int unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_transaksi_tanggal` (`tanggal`),
  KEY `idx_transaksi_jenis` (`jenis`),
  KEY `idx_transaksi_siswa` (`siswa_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transaksi`
--

LOCK TABLES `transaksi` WRITE;
/*!40000 ALTER TABLE `transaksi` DISABLE KEYS */;
INSERT INTO `transaksi` VALUES (1,'pemasukan','spp',1,1200000.00,'2026-08-15','4 bulan','bukti/20260815_041836_57882e36.jpg',1,'2026-08-15 04:18:36','2026-08-15 04:18:36'),(2,'pengeluaran','ATK',NULL,200000.00,'2026-08-15','beli keranjang','bukti/20260815_042311_f026c212.jpg',1,'2026-08-15 04:23:11','2026-08-15 04:23:11'),(3,'pemasukan','spp',2,60000.00,'2026-08-15','SPP Januari, Maret, Mei TA 2026/2027',NULL,1,'2026-08-15 11:50:08','2026-08-15 11:50:08'),(4,'pengeluaran','gaji',NULL,200000.00,'2026-08-15',NULL,NULL,1,'2026-08-15 11:51:18','2026-08-15 11:51:18');
/*!40000 ALTER TABLE `transaksi` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transaksi_spp_bulan`
--

DROP TABLE IF EXISTS `transaksi_spp_bulan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `transaksi_spp_bulan` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `transaksi_id` int unsigned NOT NULL,
  `siswa_id` int unsigned NOT NULL,
  `bulan` tinyint unsigned NOT NULL,
  `tahun_ajaran` varchar(9) COLLATE utf8mb4_unicode_ci NOT NULL,
  `jumlah` decimal(15,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_siswa_bulan_ta` (`siswa_id`,`bulan`,`tahun_ajaran`),
  KEY `idx_spp_transaksi` (`transaksi_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transaksi_spp_bulan`
--

LOCK TABLES `transaksi_spp_bulan` WRITE;
/*!40000 ALTER TABLE `transaksi_spp_bulan` DISABLE KEYS */;
INSERT INTO `transaksi_spp_bulan` VALUES (1,1,1,1,'2026/2027',300000.00),(2,1,1,2,'2026/2027',300000.00),(3,1,1,5,'2026/2027',300000.00),(4,1,1,9,'2026/2027',300000.00),(5,3,2,1,'2026/2027',20000.00),(6,3,2,3,'2026/2027',20000.00),(7,3,2,5,'2026/2027',20000.00);
/*!40000 ALTER TABLE `transaksi_spp_bulan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('superadmin','admin') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'admin',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `last_login_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_users_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Super Admin','superadmin@bimba-ksr.local','$2y$12$osheB3pypclZ89q.qxrZgOzC1MXcgEnR3acKZHqU0eV4tKbDdqWGS','superadmin',1,'2026-08-15 11:47:59','2026-08-15 02:45:22','2026-08-15 11:47:59');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'bimba_ksr'
--

--
-- Dumping routines for database 'bimba_ksr'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-08-15 11:54:37
