-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               8.0.30 - MySQL Community Server - GPL
-- Server OS:                    Win64
-- HeidiSQL Version:             12.1.0.6537
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Dumping database structure for pmb
CREATE DATABASE IF NOT EXISTS `pmb` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `pmb`;

-- Dumping structure for table pmb.admins
CREATE TABLE IF NOT EXISTS `admins` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `noa` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table pmb.admins: ~1 rows (approximately)
REPLACE INTO `admins` (`id`, `username`, `password`, `noa`, `nama_lengkap`, `email`, `last_login`, `created_at`) VALUES
	(1, 'admin', '$2y$10$CYnQvDrROjYFKYNIycTZvOm9OtKZL3ngHYG4/7JI5so41QBzUNlmW', 'ADM20260001', 'Admin Ganteng', 'admin@gmail.com', '2026-01-24 19:07:48', '2026-01-24 19:07:50');

-- Dumping structure for table pmb.camaba
CREATE TABLE IF NOT EXISTS `camaba` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama_lengkap` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `jenis_kelamin` enum('L','P') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `alamat` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `nama_orang_tua` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `nama_ayah` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `nama_ibu` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `username` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `foto_profil` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `no_hp` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `asal_sekolah` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `prodi_pilihan` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `kode_verifikasi` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `nomor_tes` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `status` enum('belum_verifikasi','baru','aktif','sudah_ujian','lulus','gagal','daftar_ulang','selected','not_selected') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT 'belum_verifikasi',
  `nilai_ujian` int DEFAULT '0',
  `jawaban_benar` int DEFAULT '0',
  `jawaban_salah` int DEFAULT '0',
  `bukti_bayar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `status_bayar` enum('belum','menunggu','lunas') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT 'belum',
  `nim` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `tanggal_daftar` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `tanggal_ujian` timestamp NULL DEFAULT NULL,
  `tanggal_daftar_ulang` timestamp NULL DEFAULT NULL,
  `tanggal_verifikasi` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  `pekerjaan_ayah` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `pekerjaan_ibu` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `penghasilan_ayah` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `penghasilan_ibu` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `alamat_rumah` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `no_kk` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `file_kk` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `file_ktp` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `file_ijazah` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `is_daftar_ulang_complete` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `email` (`email`) USING BTREE,
  UNIQUE KEY `nim` (`nim`) USING BTREE,
  UNIQUE KEY `nomor_tes` (`nomor_tes`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table pmb.camaba: ~2 rows (approximately)
REPLACE INTO `camaba` (`id`, `nama_lengkap`, `tanggal_lahir`, `jenis_kelamin`, `alamat`, `nama_orang_tua`, `nama_ayah`, `nama_ibu`, `username`, `email`, `password`, `foto_profil`, `no_hp`, `asal_sekolah`, `prodi_pilihan`, `kode_verifikasi`, `nomor_tes`, `status`, `nilai_ujian`, `jawaban_benar`, `jawaban_salah`, `bukti_bayar`, `status_bayar`, `nim`, `tanggal_daftar`, `tanggal_ujian`, `tanggal_daftar_ulang`, `tanggal_verifikasi`, `created_at`, `updated_at`, `pekerjaan_ayah`, `pekerjaan_ibu`, `penghasilan_ayah`, `penghasilan_ibu`, `alamat_rumah`, `no_kk`, `file_kk`, `file_ktp`, `file_ijazah`, `is_daftar_ulang_complete`) VALUES
	(15, 'STEVAN RIZKY AL-QARNI', '2007-11-24', 'L', 'Kramat Jati', 'Raiden Shogun', NULL, NULL, 'stevan', 'stevan@email.tes', '$2y$10$Nl5Ma8/52mggQ8qeTm9fdOUOmacOPHisdrPxqE47YnVRR7oK8Lpc2', NULL, '089637100776', 'SMKN 65 Jakartra', 'Sistem Informasi', NULL, 'PMB20260015', 'aktif', 0, 0, 0, NULL, 'belum', NULL, '2026-03-30 01:46:42', NULL, NULL, '2026-03-30 08:47:45', '2026-03-30 01:46:42', NULL, NULL, NULL, NULL, NULL, NULL, '3175847293482942', NULL, 'uploads/ktp/KTP_1774835202_7873.jpg', NULL, 0),
	(16, 'wibu', '1998-02-01', 'L', 'kaligede', 'bawang ', NULL, NULL, 'asucore', 'asucore@gmail.com', '$2y$10$HvDDaYLBRNmOB/oKytIeYOpugls1tICTlOkebBEvLEgZFihiXx2JC', NULL, '0865754324235', 'sma kacung', 'Sistem Informasi', NULL, 'PMB20260016', 'baru', 0, 0, 0, NULL, 'belum', NULL, '2026-03-30 01:58:32', NULL, NULL, '2026-03-30 08:58:55', '2026-03-30 01:58:32', NULL, NULL, NULL, NULL, NULL, NULL, '0987654321234567', NULL, 'uploads/ktp/KTP_1774835912_1878.jpg', NULL, 0);

-- Dumping structure for table pmb.soal
CREATE TABLE IF NOT EXISTS `soal` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kategori` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT 'Umum',
  `pertanyaan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `pertanyaan_gambar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `opsi_a` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `opsi_a_gambar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `opsi_b` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `opsi_b_gambar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `opsi_c` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `opsi_c_gambar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `opsi_d` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `opsi_d_gambar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `jawaban_benar` enum('a','b','c','d') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `dibuat_oleh` int DEFAULT NULL,
  `dibuat_pada` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `diperbarui_pada` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `dibuat_oleh` (`dibuat_oleh`) USING BTREE,
  CONSTRAINT `soal_ibfk_1` FOREIGN KEY (`dibuat_oleh`) REFERENCES `admins` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=142 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table pmb.soal: ~56 rows (approximately)
REPLACE INTO `soal` (`id`, `kategori`, `pertanyaan`, `pertanyaan_gambar`, `opsi_a`, `opsi_a_gambar`, `opsi_b`, `opsi_b_gambar`, `opsi_c`, `opsi_c_gambar`, `opsi_d`, `opsi_d_gambar`, `jawaban_benar`, `dibuat_oleh`, `dibuat_pada`, `diperbarui_pada`) VALUES
	(78, 'Teknik Informatika', 'Apa yang dimaksud dengan algoritma?', '', 'Bahasa pemrograman', '', 'Perangkat keras komputer', '', 'Langkah-langkah logis untuk menyelesaikan masalah', '', 'Sistem operasi komputer', '', 'c', 1, '2026-03-09 02:09:29', '2026-03-09 02:48:17'),
	(79, 'Teknik Informatika', 'Bahasa pemrograman yang sering digunakan untuk pengembangan web adalah?', '', 'HTML', '', 'Photoshop', '', 'Excel', '', 'CorelDraw', '', 'a', 1, '2026-03-09 02:09:29', NULL),
	(80, 'Teknik Informatika', 'Perangkat keras yang berfungsi sebagai otak komputer adalah?', '', 'RAM', '', 'CPU', '', 'Harddisk', '', 'Monitor', '', 'b', 1, '2026-03-09 02:09:29', NULL),
	(81, 'Teknik Informatika', 'Sistem operasi yang populer digunakan pada komputer adalah?', '', 'Windows', '', 'Word', '', 'PowerPoint', '', 'Photoshop', '', 'a', 1, '2026-03-09 02:09:29', NULL),
	(82, 'Teknik Informatika', 'Fungsi utama RAM adalah?', '', 'Menampilkan gambar', '', 'Menyimpan data permanen', '', 'Mengolah grafis', '', 'Menyimpan data sementara', '', 'd', 1, '2026-03-09 02:09:29', '2026-03-09 02:47:43'),
	(83, 'Teknik Informatika', 'Bahasa pemrograman yang terkenal untuk pengembangan aplikasi Android adalah?', '', 'Java', '', 'Pascal', '', 'COBOL', '', 'Basic', '', 'a', 1, '2026-03-09 02:09:29', NULL),
	(84, 'Teknik Informatika', 'HTML digunakan untuk?', '', 'Mengelola database', '', 'Membuat desain grafis', '', 'Membuat struktur halaman web', '', 'Mengolah data', '', 'c', 1, '2026-03-09 02:09:29', '2026-03-09 02:42:14'),
	(85, 'Teknik Informatika', 'Kepanjangan dari CPU adalah?', '', 'Central Process Unit', '', 'Central Processing Unit', '', 'Computer Personal Unit', '', 'Control Processing Unit', '', 'b', 1, '2026-03-09 02:09:29', NULL),
	(86, 'Teknik Informatika', 'Contoh database management system adalah?', '', 'Paint', '', 'Word', '', 'PowerPoint', '', 'MySQL', '', 'd', 1, '2026-03-09 02:09:29', '2026-03-09 02:41:54'),
	(87, 'Teknik Informatika', 'Bahasa pemrograman yang sering digunakan untuk analisis data adalah?', '', 'Python', '', 'HTML', '', 'CSS', '', 'XML', '', 'a', 1, '2026-03-09 02:09:29', NULL),
	(88, 'Sistem Informasi', 'Apa fungsi utama database?', '', 'Menyimpan dan mengelola data', '', 'Membuat program aplikasi', '', 'Merancang antarmuka pengguna', '', 'Mengirim email', '', 'a', 1, '2026-03-09 02:10:59', NULL),
	(89, 'Sistem Informasi', 'Sistem informasi terdiri dari beberapa komponen, kecuali?', '', 'Hardware', '', 'Software', '', 'Data', '', 'Cat tembok', '', 'd', 1, '2026-03-09 02:10:59', NULL),
	(90, 'Sistem Informasi', 'ERP adalah sistem yang digunakan untuk?', '', 'Mengirim pesan', '', 'Mengedit foto', '', 'Bermain game', '', 'Mengelola seluruh proses bisnis perusahaan', '', 'd', 1, '2026-03-09 02:10:59', '2026-03-09 02:40:44'),
	(91, 'Sistem Informasi', 'Tujuan utama sistem informasi dalam organisasi adalah?', '', 'Menghibur pengguna', '', 'Mendukung pengambilan keputusan', '', 'Mengganti karyawan', '', 'Menghapus data', '', 'b', 1, '2026-03-09 02:10:59', NULL),
	(92, 'Sistem Informasi', 'Data yang telah diolah menjadi informasi yang bermakna disebut?', '', 'Informasi', '', 'Database', '', 'Sistem', '', 'File', '', 'a', 1, '2026-03-09 02:10:59', NULL),
	(93, 'Sistem Informasi', 'Contoh perangkat lunak pengolah data adalah?', '', 'Paint', '', 'Microsoft Excel', '', 'VLC', '', 'Winamp', '', 'b', 1, '2026-03-09 02:10:59', '2026-03-09 02:38:09'),
	(94, 'Sistem Informasi', 'Flowchart digunakan untuk?', '', 'Menyimpan data', '', 'Mengedit foto', '', 'Membuat video', '', 'Menggambar alur proses sistem', '', 'd', 1, '2026-03-09 02:10:59', '2026-03-09 02:37:35'),
	(95, 'Sistem Informasi', 'Sistem berbasis web berjalan melalui?', '', 'Browser', '', 'Printer', '', 'Scanner', '', 'Speaker', '', 'a', 1, '2026-03-09 02:10:59', NULL),
	(96, 'Sistem Informasi', 'Contoh browser adalah?', '', 'Word', '', 'Excel', '', 'Chrome', '', 'Photoshop', '', 'c', 1, '2026-03-09 02:10:59', '2026-03-09 02:37:12'),
	(97, 'Sistem Informasi', 'SI dalam perusahaan biasanya digunakan untuk?', '', 'Mengelola informasi bisnis', '', 'Mengganti listrik', '', 'Membuat makanan', '', 'Memperbaiki kendaraan', '', 'a', 1, '2026-03-09 02:10:59', NULL),
	(99, 'Manajemen', 'Fungsi utama manajemen adalah?', '', 'Memasak', '', 'Menggambar', '', 'Perencanaan', '', 'Menulis', '', 'c', 1, '2026-03-09 02:12:34', '2026-03-09 02:36:22'),
	(100, 'Manajemen', 'POAC dalam manajemen berarti?', '', 'Planning, Organizing, Actuating, Controlling', '', 'Planning, Order, Action, Control', '', 'Process, Order, Action, Control', '', 'Plan, Option, Activity, Check', '', 'a', 1, '2026-03-09 02:12:34', NULL),
	(101, 'Manajemen', 'Orang yang memimpin organisasi disebut?', '', 'Programmer', '', 'Manager', '', 'Designer', '', 'Operator', '', 'b', 1, '2026-03-09 02:12:34', '2026-03-09 02:35:50'),
	(102, 'Manajemen', 'Tujuan utama manajemen adalah?', '', 'Membuat laporan saja', '', 'Menghabiskan anggaran', '', 'Mencapai tujuan organisasi secara efektif', '', 'Mengurangi pekerjaan', '', 'c', 1, '2026-03-09 02:12:34', '2026-03-09 02:35:29'),
	(103, 'Manajemen', 'Kegiatan mengatur sumber daya disebut?', '', 'Evaluating', '', 'Planning', '', 'Controlling', '', 'Organizing', '', 'd', 1, '2026-03-09 02:12:34', '2026-03-09 02:35:08'),
	(104, 'Manajemen', 'SWOT digunakan untuk?', '', 'Menyimpan data', '', 'Mengedit gambar', '', 'Menulis kode', '', 'Analisis strategi', '', 'd', 1, '2026-03-09 02:12:34', '2026-03-09 02:34:39'),
	(105, 'Manajemen', 'Huruf S dalam SWOT berarti?', '', 'System', '', 'Strength', '', 'Structure', '', 'Strategy', '', 'b', 1, '2026-03-09 02:12:34', '2026-03-09 02:34:21'),
	(106, 'Manajemen', 'Aktivitas memonitor pekerjaan disebut?', '', 'Organizing', '', 'Planning', '', 'Controlling', '', 'Directing', '', 'c', 1, '2026-03-09 02:12:34', '2026-03-09 02:33:46'),
	(107, 'Manajemen', 'Kegiatan menentukan tujuan disebut?', '', 'Planning', '', 'Organizing', '', 'Directing', '', 'Controlling', '', 'a', 1, '2026-03-09 02:12:34', NULL),
	(108, 'Manajemen', 'Manajemen sumber daya manusia berhubungan dengan?', '', 'Pengelolaan mesin', '', 'Pengelolaan karyawan', '', 'Pengelolaan gedung', '', 'Pengelolaan kendaraan', '', 'b', 1, '2026-03-09 02:12:34', '2026-03-09 02:33:26'),
	(110, 'Akuntansi', 'Akuntansi adalah proses?', '', 'Mengirim email', '', 'Menggambar grafik', '', 'Mengedit video', '', 'Mencatat transaksi keuangan', '', 'd', 1, '2026-03-09 02:14:11', '2026-03-09 02:32:47'),
	(111, 'Akuntansi', 'Laporan keuangan utama adalah?', '', 'Surat', '', 'Jadwal pelajaran', '', 'Neraca', '', 'Proposal', '', 'c', 1, '2026-03-09 02:14:11', '2026-03-09 02:32:21'),
	(112, 'Akuntansi', 'Persamaan dasar akuntansi adalah?', '', 'Ekuitas = Aset', '', 'Aset = Pendapatan', '', 'Aset = Liabilitas + Ekuitas', '', 'Pendapatan = Beban', '', 'c', 1, '2026-03-09 02:14:11', '2026-03-09 02:31:45'),
	(113, 'Akuntansi', 'Contoh aset adalah?', '', 'Kas', '', 'Hutang', '', 'Beban', '', 'Modal', '', 'a', 1, '2026-03-09 02:14:11', NULL),
	(114, 'Akuntansi', 'Hutang disebut juga?', '', 'Ekuitas', '', 'Liabilitas', '', 'Pendapatan', '', 'Beban', '', 'b', 1, '2026-03-09 02:14:11', '2026-03-09 02:31:14'),
	(115, 'Akuntansi', 'Buku besar dalam akuntansi digunakan untuk?', '', 'Menyimpan uang', '', 'Mengelompokkan akun', '', 'Mengedit data', '', 'Menggambar grafik', '', 'b', 1, '2026-03-09 02:14:11', '2026-03-09 02:29:42'),
	(116, 'Akuntansi', 'Laporan laba rugi menunjukkan?', '', 'Jumlah gedung', '', 'Jumlah karyawan', '', 'Jumlah komputer', '', 'Keuntungan atau kerugian perusahaan', '', 'd', 1, '2026-03-09 02:14:11', '2026-03-09 02:29:18'),
	(117, 'Akuntansi', 'Modal pemilik disebut?', '', 'Ekuitas', '', 'Aset', '', 'Hutang', '', 'Beban', '', 'a', 1, '2026-03-09 02:14:11', NULL),
	(118, 'Akuntansi', 'Transaksi dicatat pertama kali dalam?', '', 'Jurnal', '', 'Buku cerita', '', 'Proposal', '', 'Catatan pribadi', '', 'a', 1, '2026-03-09 02:14:11', NULL),
	(119, 'Akuntansi', 'Contoh beban adalah?', '', 'Piutang', '', 'Modal', '', 'Biaya listrik', '', 'Kas', '', 'c', 1, '2026-03-09 02:14:11', '2026-03-09 02:28:55'),
	(121, 'Ilmu Komunikasi', 'Komunikasi adalah proses?', '', 'Penyampaian pesan', '', 'Penyimpanan data', '', 'Pengolahan angka', '', 'Pembuatan program', '', 'a', 1, '2026-03-09 02:15:34', NULL),
	(122, 'Ilmu Komunikasi', 'Orang yang mengirim pesan disebut?', '', 'Manager', '', 'Komunikan', '', 'Operator', '', 'Komunikator', '', 'd', 1, '2026-03-09 02:15:34', '2026-03-09 02:28:21'),
	(123, 'Ilmu Komunikasi', 'Media komunikasi adalah?', '', 'Tempat menyimpan data', '', 'Sarana penyampaian pesan', '', 'Sistem komputer', '', 'Mesin produksi', '', 'b', 1, '2026-03-09 02:15:34', '2026-03-09 02:27:47'),
	(124, 'Ilmu Komunikasi', 'Komunikasi dua arah disebut?', '', 'Interaktif', '', 'Pasif', '', 'Monolog', '', 'Tertutup', '', 'a', 1, '2026-03-09 02:15:34', NULL),
	(125, 'Ilmu Komunikasi', 'Contoh media massa adalah?', '', 'Map', '', 'Buku catatan', '', 'Kalender', '', 'Televisi', '', 'd', 1, '2026-03-09 02:15:34', '2026-03-09 02:27:17'),
	(126, 'Ilmu Komunikasi', 'Hambatan komunikasi disebut?', '', 'Channel', '', 'Signal', '', 'Noise', '', 'Source', '', 'c', 1, '2026-03-09 02:15:34', '2026-03-09 02:26:41'),
	(127, 'Ilmu Komunikasi', 'Komunikasi tanpa kata disebut?', '', 'Formal', '', 'Verbal', '', 'Nonverbal', '', 'Digital', '', 'c', 1, '2026-03-09 02:15:34', '2026-03-09 02:26:14'),
	(128, 'Ilmu Komunikasi', 'Public speaking adalah?', '', 'Membaca buku', '', 'Menulis surat', '', 'Mengedit video', '', 'Berbicara di depan umum', '', 'd', 1, '2026-03-09 02:15:34', '2026-03-09 02:25:49'),
	(129, 'Ilmu Komunikasi', 'Proses menerima pesan dilakukan oleh?', '', 'Komunikan', '', 'Komunikator', '', 'Moderator', '', 'Editor', '', 'a', 1, '2026-03-09 02:15:34', NULL),
	(130, 'Ilmu Komunikasi', 'Komunikasi melalui internet disebut?', '', 'Komunikasi manual', '', 'Komunikasi digital', '', 'Komunikasi langsung', '', 'Komunikasi analog', '', 'b', 1, '2026-03-09 02:15:34', '2026-03-09 02:25:26'),
	(132, 'Desain Komunikasi Visual', 'DKV adalah singkatan dari?', '', 'Desain Komputer Virtual', '', 'Data Komunikasi Visual', '', 'Digital Komputer Visual', '', 'Desain Komunikasi Visual', '', 'd', 1, '2026-03-09 02:17:01', '2026-03-09 02:23:20'),
	(133, 'Desain Komunikasi Visual', 'Tujuan utama desain adalah?', '', 'Membuat program', '', 'Menghitung angka', '', 'Menyampaikan pesan visual', '', 'Menyimpan data', '', 'c', 1, '2026-03-09 02:17:01', '2026-03-09 02:22:18'),
	(134, 'Desain Komunikasi Visual', 'Contoh software desain grafis adalah?', '', 'Adobe Photoshop', '', 'Excel', '', 'Word', '', 'Notepad', '', 'a', 1, '2026-03-09 02:17:01', NULL),
	(135, 'Desain Komunikasi Visual', 'Elemen dasar desain adalah?', '', 'Garis', '', 'Database', '', 'Server', '', 'Kabel', '', 'a', 1, '2026-03-09 02:17:01', NULL),
	(136, 'Desain Komunikasi Visual', 'Warna merah sering melambangkan?', '', 'Ketidakpastian', '', 'Kesedihan', '', 'Ketakutan', '', 'Energi', '', 'd', 1, '2026-03-09 02:17:01', '2026-03-09 02:20:50'),
	(137, 'Desain Komunikasi Visual', 'Tipografi berkaitan dengan?', '', 'Huruf dan teks', '', 'Warna', '', 'Video', '', 'Audio', '', 'a', 1, '2026-03-09 02:17:01', NULL),
	(138, 'Desain Komunikasi Visual', 'Poster digunakan untuk?', '', 'Menghitung data', '', 'Media promosi', '', 'Menyimpan file', '', 'Menulis kode', '', 'b', 1, '2026-03-09 02:17:01', '2026-03-09 02:20:24'),
	(139, 'Desain Komunikasi Visual', 'Layout dalam desain berarti?', '', 'Ukuran komputer', '', 'Tata letak elemen desain', '', 'Jenis kabel', '', 'Sistem operasi', '', 'b', 1, '2026-03-09 02:17:01', '2026-03-09 02:19:49'),
	(140, 'Desain Komunikasi Visual', 'Logo digunakan untuk?', '', 'Identitas merek', '', 'Menyimpan data', '', 'Menulis program', '', 'Mengirim email', '', 'a', 1, '2026-03-09 02:17:01', NULL),
	(141, 'Desain Komunikasi Visual', 'Ilustrasi dalam desain berarti?', '', 'Script Program', '', 'File database', '', 'Gambar untuk menjelaskan ide', '', 'Sistem operasi', '', 'c', 1, '2026-03-09 02:17:01', '2026-03-09 02:19:00');

-- Dumping structure for table pmb.ujian_log
CREATE TABLE IF NOT EXISTS `ujian_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `camaba_id` varchar(50) NOT NULL,
  `user_id` varchar(50) NOT NULL,
  `nama_lengkap` varchar(100) DEFAULT NULL,
  `token_ujian` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `start_time` datetime DEFAULT NULL,
  `waktu_mulai` timestamp NULL DEFAULT NULL,
  `waktu_sisa` int DEFAULT '0',
  `end_time` datetime DEFAULT NULL,
  `status` enum('waiting','active','completed','expired') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT 'active',
  `jawaban_salah` int DEFAULT '0',
  `jawaban_benar` int DEFAULT '0',
  `nilai_ujian` int DEFAULT '0',
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `ujian_id` int NOT NULL,
  `soal_order` text,
  `jawaban_json` text,
  `ditandai_json` text,
  PRIMARY KEY (`id`),
  KEY `idx_ujian_status` (`ujian_id`,`status`),
  KEY `idx_camaba_token` (`camaba_id`,`token_ujian`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table pmb.ujian_log: ~6 rows (approximately)
REPLACE INTO `ujian_log` (`id`, `camaba_id`, `user_id`, `nama_lengkap`, `token_ujian`, `start_time`, `waktu_mulai`, `waktu_sisa`, `end_time`, `status`, `jawaban_salah`, `jawaban_benar`, `nilai_ujian`, `ip_address`, `created_at`, `updated_at`, `ujian_id`, `soal_order`, `jawaban_json`, `ditandai_json`) VALUES
	(22, 'PMB20260002', '2', 'Ibnu Faqih', NULL, NULL, NULL, 0, NULL, 'waiting', 0, 0, 0, NULL, '2026-03-09 03:33:28', '2026-03-11 00:49:02', 3, NULL, NULL, NULL),
	(23, 'PMB20260003', '3', 'MIKu', NULL, NULL, NULL, 0, NULL, 'waiting', 0, 0, 0, NULL, '2026-03-09 03:33:28', '2026-03-11 00:49:02', 3, NULL, NULL, NULL),
	(24, 'PMB20260004', '4', 'Muhammad Raffli Arditya', NULL, NULL, NULL, 0, NULL, 'waiting', 0, 0, 0, NULL, '2026-03-09 03:33:28', '2026-03-11 00:49:02', 3, NULL, NULL, NULL),
	(25, 'PMB20260005', '5', 'adinda', NULL, NULL, NULL, 0, NULL, 'waiting', 0, 0, 0, NULL, '2026-03-09 03:33:28', '2026-03-11 00:49:02', 3, NULL, NULL, NULL),
	(26, 'PMB20260006', '6', 'STEVAN RIZKY AL-QARNI', NULL, NULL, NULL, 0, NULL, 'waiting', 0, 0, 0, NULL, '2026-03-09 03:33:28', '2026-03-12 00:41:43', 3, NULL, NULL, NULL),
	(27, 'PMB20260015', '15', 'STEVAN RIZKY AL-QARNI', NULL, NULL, NULL, 0, NULL, 'waiting', 0, 0, 0, NULL, '2026-03-30 01:51:34', '2026-04-03 22:35:16', 3, NULL, NULL, NULL);

-- Dumping structure for table pmb.ujian_setting
CREATE TABLE IF NOT EXISTS `ujian_setting` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `nama_ujian` varchar(100) DEFAULT NULL,
  `tanggal_ujian` date DEFAULT NULL,
  `jam_mulai` time DEFAULT NULL,
  `jam_selesai` time DEFAULT NULL,
  `durasi_menit` int unsigned DEFAULT '120',
  `token_ujian` varchar(6) DEFAULT NULL,
  `status` enum('pending','active','completed') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table pmb.ujian_setting: ~1 rows (approximately)
REPLACE INTO `ujian_setting` (`id`, `nama_ujian`, `tanggal_ujian`, `jam_mulai`, `jam_selesai`, `durasi_menit`, `token_ujian`, `status`, `created_at`, `updated_at`) VALUES
	(3, 'Ujian Masuk Universitas Kita', '2026-03-30', '07:00:00', '21:00:00', 120, 'KX80RF', 'active', '2026-01-31 18:35:55', '2026-03-30 01:52:29');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
