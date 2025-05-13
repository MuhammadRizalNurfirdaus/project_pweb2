-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 13, 2025 at 08:08 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `cilengkrang_web_wisata`
--

-- --------------------------------------------------------

--
-- Table structure for table `articles`
--

CREATE TABLE `articles` (
  `id` int(11) NOT NULL,
  `judul` varchar(200) NOT NULL,
  `isi` text NOT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `articles`
--

INSERT INTO `articles` (`id`, `judul`, `isi`, `gambar`, `created_at`) VALUES
(2, 'Panduan Lengkap Berkemah Aman dan Nyaman di Area Camping Cilengkrang', 'Ingin merasakan sensasi berkemah di tengah alam Cilengkrang? Artikel ini memberikan panduan lengkap mulai dari persiapan perlengkapan, pemilihan lokasi tenda yang strategis, hingga tips menjaga keamanan dan kebersihan selama berkemah. Nikmati malam berbintang Anda!', 'artikel_6820479462d0a.jpg', '2025-05-09 04:14:42'),
(10, 'Pesona Curug Cilengkrang', 'Curug Cilengkrang menawarkan keindahan air terjun alami yang memukau, terletak di kawasan hijau nan sejuk di Kuningan, Jawa Barat. Dengan air yang jernih dan suasana hutan yang asri, tempat ini menjadi destinasi favorit bagi pecinta alam dan wisatawan yang ingin melepas penat.\r\n\r\nDi sekitar curug, pengunjung dapat menikmati trekking ringan, berfoto dengan latar air terjun yang eksotis, serta bersantai di tepi aliran sungai yang tenang. Udara segar dan suara gemericik air menciptakan suasana damai yang jarang ditemukan di kota.\r\n\r\nFasilitas yang tersedia pun cukup memadai, seperti area parkir, gazebo untuk beristirahat, serta warung yang menyediakan makanan dan minuman. Akses menuju Curug Cilengkrang juga cukup mudah, hanya membutuhkan perjalanan sekitar 30 menit dari pusat kota Kuningan.\r\n\r\nJadikan Curug Cilengkrang destinasi liburan Anda berikutnya, dan rasakan ketenangan serta keindahan alam yang masih terjaga.', 'artikel_68205b3d52656.jpg', '2025-05-11 08:09:33'),
(13, 'Keindahan Alam Puncak Cilengkrang', 'Puncak Cilengkrang adalah salah satu destinasi wisata alam terbaik di Kuningan yang menawarkan pemandangan pegunungan yang memukau, udara sejuk, dan suasana yang tenang. Tempat ini sangat cocok untuk para pecinta alam, pendaki, dan wisatawan yang ingin melepas penat dari hiruk pikuk perkotaan.\r\n\r\nSelain keindahan panoramanya, Puncak Cilengkrang juga memiliki berbagai spot foto menarik dan jalur pendakian yang menantang. Jangan lupa untuk membawa kamera dan perlengkapan hiking yang memadai saat berkunjung ke sini.\r\n\r\nNikmati keindahan alam Cilengkrang yang asri dan segarkan kembali pikiran Anda dengan suasana alam yang menenangkan.', 'artikel_6820c7498665d.jpg', '2025-05-11 15:50:33');

-- --------------------------------------------------------

--
-- Table structure for table `contacts`
--

CREATE TABLE `contacts` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `pesan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contacts`
--

INSERT INTO `contacts` (`id`, `nama`, `email`, `pesan`, `created_at`) VALUES
(1, 'Rina Puspita Dewi', 'rina_dewi@example.com', 'Saya ingin menanyakan informasi mengenai paket gathering untuk perusahaan sekitar 50 orang. Apakah ada penawaran khusus dan fasilitas apa saja yang bisa kami dapatkan? Terima kasih.', '2025-05-10 01:16:50'),
(2, 'Agus Wijaya Kusuma', 'agus_kusuma@example.net', 'Mohon informasi lebih lanjut mengenai aksesibilitas untuk penyandang disabilitas di area pemandian air panas. Apakah tersedia jalur khusus atau bantuan petugas?', '2025-05-10 03:16:50'),
(3, 'Dewi Lestari', 'dewi_lestari@example.org', 'Saya kehilangan dompet di sekitar area gazebo pada tanggal [tanggal kehilangan]. Jika ditemukan, mohon hubungi saya di nomor 083174829123. Terima kasih banyak.', '2025-05-10 04:16:50');

-- --------------------------------------------------------

--
-- Table structure for table `detail_pemesanan_tiket`
--

CREATE TABLE `detail_pemesanan_tiket` (
  `id` int(11) NOT NULL,
  `pemesanan_tiket_id` int(11) NOT NULL,
  `jenis_tiket_id` int(11) NOT NULL,
  `jumlah` int(11) NOT NULL,
  `harga_satuan_saat_pesan` int(11) NOT NULL,
  `subtotal_item` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `artikel_id` int(11) DEFAULT NULL,
  `komentar` text DEFAULT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`id`, `user_id`, `artikel_id`, `komentar`, `rating`, `created_at`) VALUES
(5, 3, NULL, 'Secara keseluruhan, fasilitas di Wisata Cilengkrang sudah baik. Pemandian air panasnya adalah favorit saya! Mungkin bisa ditambahkan lebih banyak pilihan kuliner di area istirahat. Terima kasih!', 4, '2025-05-09 04:31:50'),
(6, 2, 2, 'Tips berkemah di artikel ini sangat detail dan sangat membantu untuk perencanaan kami. Terutama bagian pemilihan lokasi tenda dan apa saja yang perlu dibawa. Mantap!', 5, '2025-05-09 16:31:50'),
(9, 3, NULL, 'Secara keseluruhan, fasilitas di Wisata Cilengkrang sudah baik. Pemandian air panasnya adalah favorit saya! Mungkin bisa ditambahkan lebih banyak pilihan kuliner di area istirahat. Terima kasih!', 4, '2025-05-09 04:32:53'),
(10, 2, 2, 'Tips berkemah di artikel ini sangat detail dan sangat membantu untuk perencanaan kami. Terutama bagian pemilihan lokasi tenda dan apa saja yang perlu dibawa. Mantap!', 5, '2025-05-09 16:32:53');

-- --------------------------------------------------------

--
-- Table structure for table `galeri`
--

CREATE TABLE `galeri` (
  `id` int(11) NOT NULL,
  `nama_file` varchar(255) DEFAULT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `galeri`
--

INSERT INTO `galeri` (`id`, `nama_file`, `keterangan`, `uploaded_at`) VALUES
(8, 'kolam_air_panas.jpg', 'Suasana salah satu kolam di Pemandian Air Panas Cilengkrang.', '2025-05-10 04:15:22'),
(9, 'air_panas_polos.jpg', 'Detail sumber air panas alami yang masih terjaga keasriannya.', '2025-05-10 04:15:22'),
(10, 'curug_cilengkrang.jpg', 'Keindahan Curug Cilengkrang dilihat dari dekat.', '2025-05-10 04:15:22'),
(11, 'lembah_cilengkrang.jpg', 'Pemandangan luas Lembah Cilengkrang yang hijau dan menyejukkan.', '2025-05-10 04:15:22'),
(15, 'galeri_68205a0898c802.84297583.jpg', 'Panorama indah dari Puncak Cilengkrang yang memukau dengan hamparan perbukitan hijau dan udara sejuk, cocok untuk melepas penat dan menikmati keindahan alam.', '2025-05-11 08:04:24');

-- --------------------------------------------------------

--
-- Table structure for table `jadwal_ketersediaan_tiket`
--

CREATE TABLE `jadwal_ketersediaan_tiket` (
  `id` int(11) NOT NULL,
  `jenis_tiket_id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `jumlah_total_tersedia` int(11) NOT NULL,
  `jumlah_saat_ini_tersedia` int(11) NOT NULL,
  `aktif` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Status apakah jadwal ini aktif dan bisa dipesan',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jadwal_ketersediaan_tiket`
--

INSERT INTO `jadwal_ketersediaan_tiket` (`id`, `jenis_tiket_id`, `tanggal`, `jumlah_total_tersedia`, `jumlah_saat_ini_tersedia`, `aktif`, `created_at`, `updated_at`) VALUES
(1, 1, '2025-05-13', 1000, 100, 1, '2025-05-12 08:12:37', '2025-05-12 17:27:52'),
(2, 2, '2025-05-13', 100, 100, 1, '2025-05-12 15:30:18', '2025-05-12 17:27:23');

-- --------------------------------------------------------

--
-- Table structure for table `jenis_tiket`
--

CREATE TABLE `jenis_tiket` (
  `id` int(11) NOT NULL,
  `nama_layanan_display` varchar(100) NOT NULL,
  `tipe_hari` enum('Hari Kerja','Hari Libur','Semua Hari') NOT NULL,
  `harga` int(11) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `aktif` tinyint(1) DEFAULT 1,
  `wisata_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jenis_tiket`
--

INSERT INTO `jenis_tiket` (`id`, `nama_layanan_display`, `tipe_hari`, `harga`, `deskripsi`, `aktif`, `wisata_id`, `created_at`, `updated_at`) VALUES
(1, 'Tiket Wisata', 'Hari Kerja', 20000, 'Akses umum ke Lembah Cilengkrang (Air Terjun, Air Panas dll)', 1, NULL, '2025-05-12 05:50:01', '2025-05-12 05:50:01'),
(2, 'Tiket Wisata', 'Hari Libur', 25000, 'Akses umum ke Lembah Cilengkrang (Air Terjun, Air Panas dll)', 1, NULL, '2025-05-12 05:50:01', '2025-05-12 05:50:01'),
(3, 'Tiket Camp', 'Hari Kerja', 25000, 'Tiket masuk area camping per orang per malam', 1, NULL, '2025-05-12 05:50:01', '2025-05-12 05:50:01'),
(4, 'Tiket Camp', 'Hari Libur', 30000, 'Tiket masuk area camping per orang per malam', 1, NULL, '2025-05-12 05:50:01', '2025-05-12 05:50:01');

-- --------------------------------------------------------

--
-- Table structure for table `pembayaran`
--

CREATE TABLE `pembayaran` (
  `id` int(11) NOT NULL,
  `pemesanan_tiket_id` int(11) NOT NULL,
  `metode_pembayaran` varchar(100) DEFAULT NULL,
  `jumlah_dibayar` decimal(10,2) NOT NULL DEFAULT 0.00,
  `waktu_pembayaran` datetime DEFAULT NULL,
  `status_pembayaran` enum('pending','success','failed','expired','refunded','awaiting_confirmation') NOT NULL DEFAULT 'pending',
  `bukti_pembayaran` varchar(255) DEFAULT NULL,
  `id_transaksi_gateway` varchar(255) DEFAULT NULL,
  `nomor_virtual_account` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pembayaran`
--

INSERT INTO `pembayaran` (`id`, `pemesanan_tiket_id`, `metode_pembayaran`, `jumlah_dibayar`, `waktu_pembayaran`, `status_pembayaran`, `bukti_pembayaran`, `id_transaksi_gateway`, `nomor_virtual_account`, `created_at`, `updated_at`) VALUES
(1, 1, 'Transfer Bank', 10000.00, '2025-05-13 07:24:14', 'success', 'bukti_transfer_budi.jpg', 'TRX1234567890', '123456789012', '2025-05-13 00:06:18', '2025-05-13 01:54:14');

-- --------------------------------------------------------

--
-- Table structure for table `pemesanan_sewa_alat`
--

CREATE TABLE `pemesanan_sewa_alat` (
  `id` int(11) NOT NULL,
  `pemesanan_tiket_id` int(11) NOT NULL,
  `sewa_alat_id` int(11) NOT NULL,
  `jumlah` int(11) NOT NULL DEFAULT 1,
  `harga_satuan_saat_pesan` decimal(10,2) NOT NULL,
  `durasi_satuan_saat_pesan` int(11) NOT NULL,
  `satuan_durasi_saat_pesan` enum('Jam','Hari','Peminjaman') NOT NULL,
  `tanggal_mulai_sewa` datetime NOT NULL,
  `tanggal_akhir_sewa_rencana` datetime NOT NULL,
  `total_harga_item` decimal(10,2) NOT NULL,
  `status_item_sewa` enum('Dipesan','Diambil','Dikembalikan','Hilang','Rusak','Dibatalkan') NOT NULL DEFAULT 'Dipesan',
  `catatan_item_sewa` text DEFAULT NULL,
  `denda` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pemesanan_sewa_alat`
--

INSERT INTO `pemesanan_sewa_alat` (`id`, `pemesanan_tiket_id`, `sewa_alat_id`, `jumlah`, `harga_satuan_saat_pesan`, `durasi_satuan_saat_pesan`, `satuan_durasi_saat_pesan`, `tanggal_mulai_sewa`, `tanggal_akhir_sewa_rencana`, `total_harga_item`, `status_item_sewa`, `catatan_item_sewa`, `denda`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 2, 5000.00, 1, 'Hari', '2025-05-12 08:00:00', '2025-05-13 08:00:00', 10000.00, 'Dipesan', 'Alat untuk camping Budi Santoso', 10.00, '2025-05-12 16:14:04', '2025-05-13 01:59:50');

-- --------------------------------------------------------

--
-- Table structure for table `pemesanan_tiket`
--

CREATE TABLE `pemesanan_tiket` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `nama_pemesan_tamu` varchar(255) DEFAULT NULL,
  `email_pemesan_tamu` varchar(255) DEFAULT NULL,
  `nohp_pemesan_tamu` varchar(20) DEFAULT NULL,
  `kode_pemesanan` varchar(50) NOT NULL,
  `tanggal_kunjungan` date NOT NULL,
  `total_harga_akhir` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('pending','waiting_payment','paid','confirmed','completed','cancelled','expired') NOT NULL DEFAULT 'pending',
  `catatan_umum_pemesanan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pemesanan_tiket`
--

INSERT INTO `pemesanan_tiket` (`id`, `user_id`, `nama_pemesan_tamu`, `email_pemesan_tamu`, `nohp_pemesan_tamu`, `kode_pemesanan`, `tanggal_kunjungan`, `total_harga_akhir`, `status`, `catatan_umum_pemesanan`, `created_at`, `updated_at`) VALUES
(1, 2, 'Budi Santoso', 'budisantoso@gmail.com', '0893718427465', '12', '2025-05-12', 20000.00, 'paid', 'gasss', '2025-05-12 15:09:41', '2025-05-13 01:54:14');

-- --------------------------------------------------------

--
-- Table structure for table `sewa_alat`
--

CREATE TABLE `sewa_alat` (
  `id` int(11) NOT NULL,
  `nama_item` varchar(255) NOT NULL,
  `kategori_alat` varchar(100) DEFAULT NULL,
  `deskripsi` text DEFAULT NULL,
  `harga_sewa` int(11) NOT NULL,
  `durasi_harga_sewa` int(11) NOT NULL DEFAULT 1,
  `satuan_durasi_harga` enum('Jam','Hari','Peminjaman') NOT NULL DEFAULT 'Hari',
  `stok_tersedia` int(11) NOT NULL DEFAULT 0,
  `gambar_alat` varchar(255) DEFAULT NULL,
  `kondisi_alat` enum('Baik','Rusak Ringan','Perlu Perbaikan','Hilang') DEFAULT 'Baik',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sewa_alat`
--

INSERT INTO `sewa_alat` (`id`, `nama_item`, `kategori_alat`, `deskripsi`, `harga_sewa`, `durasi_harga_sewa`, `satuan_durasi_harga`, `stok_tersedia`, `gambar_alat`, `kondisi_alat`, `created_at`, `updated_at`) VALUES
(1, 'Meja Portable Kecil', 'Furnitur Camping', 'Meja kecil untuk piknik dan camping', 20000, 1, 'Hari', 10, NULL, 'Baik', '2025-05-11 16:48:47', '2025-05-13 01:59:46'),
(2, 'Meja Portable Besar', 'Furnitur Camping', 'Meja besar untuk piknik dan camping', 30000, 1, 'Hari', 5, NULL, 'Baik', '2025-05-11 16:48:47', '2025-05-11 16:48:47'),
(3, 'Kursi Portable', 'Furnitur Camping', 'Kursi portabel untuk camping, harga per buah', 15000, 1, 'Hari', 20, NULL, 'Baik', '2025-05-11 16:48:47', '2025-05-11 16:48:47'),
(4, 'Kompor Kecil', 'Peralatan Masak', 'Kompor kecil untuk masak, harga per 3 jam pertama, jam berikutnya @5rb', 10000, 3, 'Jam', 15, NULL, 'Baik', '2025-05-11 16:48:47', '2025-05-11 16:48:47'),
(5, 'Kompor Besar', 'Peralatan Masak', 'Kompor besar untuk masak kelompok, harga flat per peminjaman', 25000, 1, 'Peminjaman', 8, NULL, 'Baik', '2025-05-11 16:48:47', '2025-05-11 16:48:47'),
(6, 'Grill BBQ Kecil', 'Peralatan Masak', 'Grill kecil untuk barbeque', 20000, 1, 'Peminjaman', 7, NULL, 'Baik', '2025-05-11 16:48:47', '2025-05-11 16:48:47'),
(7, 'Gas Portable', 'Peralatan Masak', 'Gas kecil untuk kompor portable (harga per tabung, habis pakai)', 10000, 1, 'Peminjaman', 50, NULL, 'Baik', '2025-05-11 16:48:47', '2025-05-11 16:48:47'),
(8, 'Set Alat Makan (1 Orang)', 'Peralatan Makan', 'Set Piring, Mangkok, Gelas, Sendok, Garpu untuk 1 orang', 15000, 1, 'Peminjaman', 30, NULL, 'Baik', '2025-05-11 16:48:47', '2025-05-11 16:48:47'),
(9, 'Tenda Kap. 3-4 Orang', 'Peralatan Tidur', 'Tenda dome untuk 3-4 orang, harga per malam/hari', 50000, 1, 'Hari', 10, NULL, 'Baik', '2025-05-11 16:48:47', '2025-05-11 16:48:47'),
(10, 'Tenda Kap. 5-6 Orang', 'Peralatan Tidur', 'Tenda dome untuk 5-6 orang, harga per malam/hari', 70000, 1, 'Hari', 5, NULL, 'Baik', '2025-05-11 16:48:47', '2025-05-11 16:48:47'),
(11, 'Matras Kecil', 'Peralatan Tidur', 'Matras kecil untuk alas tidur', 10000, 1, 'Hari', 20, NULL, 'Baik', '2025-05-11 16:48:47', '2025-05-11 16:48:47'),
(12, 'Sleeping Bag', 'Peralatan Tidur', 'Kantong tidur standar', 20000, 1, 'Hari', 15, NULL, 'Baik', '2025-05-11 16:48:47', '2025-05-11 16:48:47'),
(13, 'Lampu Tenda LED', 'Penerangan', 'Lampu LED gantung untuk dalam tenda', 15000, 1, 'Hari', 12, NULL, 'Baik', '2025-05-11 16:48:47', '2025-05-11 16:48:47');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `nama`, `email`, `password`, `no_hp`, `alamat`, `role`, `created_at`) VALUES
(1, 'Admin', 'admin@example.com', '$2y$10$0xz9N1pBA2PudHnBkJ3mYuCx20LQDjlXplXy3.6E7l9obS4MD3aFi', '081200000001', 'Kantor Pengelola Wisata Lembah Cilengkrang, Kuningan', 'admin', '2025-05-10 04:14:23'),
(2, 'Budi Santoso', 'budi_santoso@example.com', '$2y$10$PrjAeon1e8XvdBEVoGIsF.2VlsJBW/qFcKVk5F9fLJ47n8N9CYena', '087654321001', 'Jl. Kenangan Indah No. 12, Bandung', 'user', '2025-05-10 04:14:23'),
(3, 'Susanti', 'susanti@example.com', '$2y$10$3kjWhTELgojTCCmrO0mGl.jj9C.ft2.Ym0I59CZ0qw5bCNG.ovxia', '081122334401', 'Jl. Mawar Melati No. 8, Jakarta Selatan', 'user', '2025-05-10 04:14:23');

-- --------------------------------------------------------

--
-- Table structure for table `wisata`
--

CREATE TABLE `wisata` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) DEFAULT NULL,
  `deskripsi` text DEFAULT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `lokasi` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wisata`
--

INSERT INTO `wisata` (`id`, `nama`, `deskripsi`, `gambar`, `lokasi`, `created_at`) VALUES
(10, 'Pemandian Air Panas Alami Cilengkrang', 'Rasakan khasiat air panas alami yang bersumber langsung dari pegunungan. Cocok untuk relaksasi, meredakan nyeri otot, dan menyehatkan kulit. Tersedia kolam umum dan private.', 'wisata_6820b3e95fe47_1746973673.jpg', 'Area Pemandian Utama', '2025-05-10 04:15:04'),
(11, 'Curug Cilengkrang yang Megah', 'Air terjun utama di kawasan ini dengan ketinggian mencapai puluhan meter. Nikmati kesegaran percikan airnya dan keindahan alam sekitarnya. Akses jalan setapak yang menantang namun sepadan.', 'wisata_6820af508dcbe_1746972496.jpg', 'Area Curug Utama', '2025-05-10 04:15:04'),
(12, 'Camping Ground Lembah Tepus', 'Area berkemah favorit dengan fasilitas lengkap seperti toilet, mushola, dan sumber air bersih. Terletak strategis dekat aliran sungai, menawarkan suasana malam yang tenang.', 'wisata_6820a9f969a33_1746971129.jpg', 'Kawasan Lembah Tepus', '2025-05-10 04:15:04'),
(13, 'Gazebo Keluarga Tepi Sungai', 'Tempat istirahat yang ideal untuk keluarga sambil menikmati pemandangan sungai dan hutan. Gratis digunakan oleh pengunjung, cocok untuk bersantap atau sekadar duduk santai.', 'wisata_6820b28c3f917_1746973324.jpg', 'Sepanjang Aliran Sungai Dekat Area Utama', '2025-05-10 04:15:04'),
(15, 'Kolam Air Panas Anak & Keluarga', 'Dirancang khusus untuk kenyamanan dan keamanan anak-anak, kolam ini memiliki kedalaman yang lebih dangkal dan suhu air yang lebih bersahabat.', 'wisata_6820b6c8a42ff_1746974408.jpg', 'Area Pemandian Keluarga', '2025-05-11 14:39:54'),
(25, 'Pemandian Air Panas Alami Cilengkrang', 'Rasakan khasiat air panas alami yang bersumber langsung dari pegunungan. Cocok untuk relaksasi, meredakan nyeri otot, dan menyegarkan tubuh.', 'wisata_6820b3e95fe47_1746973673.jpg', 'Area Pemandian Utama', '2025-05-12 05:36:22'),
(26, 'Curug Cilengkrang yang Megah', 'Air terjun utama di kawasan ini dengan ketinggian yang menawan. Nikmati kesegaran percikan airnya dan keindahan alam sekitarnya.', 'wisata_6820af508dcbe_1746972496.jpg', 'Area Curug Utama', '2025-05-12 05:36:22'),
(27, 'Camping Ground Lembah Tepus', 'Area berkemah favorit dengan fasilitas lengkap seperti toilet, mushola, dan sumber air bersih. Terletak strategis dekat dengan keindahan alam Lembah Tepus.', 'wisata_6820a9f969a33_1746971129.jpg', 'Kawasan Lembah Tepus', '2025-05-12 05:36:22'),
(28, 'Gazebo Keluarga Tepi Sungai', 'Tempat istirahat yang ideal untuk keluarga sambil menikmati pemandangan sungai dan hutan. Gratis digunakan oleh pengunjung untuk bersantai.', 'wisata_6820b28c3f917_1746973324.jpg', 'Sepanjang Aliran Sungai Dekat Area Utama', '2025-05-12 05:36:22'),
(29, 'Kolam Air Panas Anak & Keluarga', 'Dirancang khusus untuk kenyamanan dan keamanan anak-anak, kolam ini memiliki kedalaman yang lebih dangkal dan suhu air yang lebih bersahabat untuk keluarga.', 'wisata_6820b6c8a42ff_1746974408.jpg', 'Area Pemandian Keluarga', '2025-05-12 05:36:22');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `articles`
--
ALTER TABLE `articles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `contacts`
--
ALTER TABLE `contacts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `detail_pemesanan_tiket`
--
ALTER TABLE `detail_pemesanan_tiket`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pemesanan_tiket_id` (`pemesanan_tiket_id`),
  ADD KEY `jenis_tiket_id` (`jenis_tiket_id`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `artikel_id` (`artikel_id`);

--
-- Indexes for table `galeri`
--
ALTER TABLE `galeri`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `jadwal_ketersediaan_tiket`
--
ALTER TABLE `jadwal_ketersediaan_tiket`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_unik_ketersediaan` (`jenis_tiket_id`,`tanggal`);

--
-- Indexes for table `jenis_tiket`
--
ALTER TABLE `jenis_tiket`
  ADD PRIMARY KEY (`id`),
  ADD KEY `wisata_id` (`wisata_id`);

--
-- Indexes for table `pembayaran`
--
ALTER TABLE `pembayaran`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `pemesanan_tiket_id` (`pemesanan_tiket_id`);

--
-- Indexes for table `pemesanan_sewa_alat`
--
ALTER TABLE `pemesanan_sewa_alat`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pemesanan_tiket_id_sewa` (`pemesanan_tiket_id`),
  ADD KEY `idx_sewa_alat_id_sewa` (`sewa_alat_id`);

--
-- Indexes for table `pemesanan_tiket`
--
ALTER TABLE `pemesanan_tiket`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_pemesanan` (`kode_pemesanan`),
  ADD UNIQUE KEY `kode_pemesanan_2` (`kode_pemesanan`),
  ADD KEY `fk_pemesanan_user` (`user_id`);

--
-- Indexes for table `sewa_alat`
--
ALTER TABLE `sewa_alat`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `wisata`
--
ALTER TABLE `wisata`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `articles`
--
ALTER TABLE `articles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `contacts`
--
ALTER TABLE `contacts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `detail_pemesanan_tiket`
--
ALTER TABLE `detail_pemesanan_tiket`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `galeri`
--
ALTER TABLE `galeri`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `jadwal_ketersediaan_tiket`
--
ALTER TABLE `jadwal_ketersediaan_tiket`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `jenis_tiket`
--
ALTER TABLE `jenis_tiket`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `pembayaran`
--
ALTER TABLE `pembayaran`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `pemesanan_sewa_alat`
--
ALTER TABLE `pemesanan_sewa_alat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `pemesanan_tiket`
--
ALTER TABLE `pemesanan_tiket`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `sewa_alat`
--
ALTER TABLE `sewa_alat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `wisata`
--
ALTER TABLE `wisata`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `detail_pemesanan_tiket`
--
ALTER TABLE `detail_pemesanan_tiket`
  ADD CONSTRAINT `detail_pemesanan_tiket_ibfk_1` FOREIGN KEY (`pemesanan_tiket_id`) REFERENCES `pemesanan_tiket` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `detail_pemesanan_tiket_ibfk_2` FOREIGN KEY (`jenis_tiket_id`) REFERENCES `jenis_tiket` (`id`);

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `feedback_ibfk_2` FOREIGN KEY (`artikel_id`) REFERENCES `articles` (`id`);

--
-- Constraints for table `jadwal_ketersediaan_tiket`
--
ALTER TABLE `jadwal_ketersediaan_tiket`
  ADD CONSTRAINT `jadwal_ketersediaan_tiket_ibfk_1` FOREIGN KEY (`jenis_tiket_id`) REFERENCES `jenis_tiket` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `jenis_tiket`
--
ALTER TABLE `jenis_tiket`
  ADD CONSTRAINT `jenis_tiket_ibfk_1` FOREIGN KEY (`wisata_id`) REFERENCES `wisata` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `pembayaran`
--
ALTER TABLE `pembayaran`
  ADD CONSTRAINT `pembayaran_ibfk_1` FOREIGN KEY (`pemesanan_tiket_id`) REFERENCES `pemesanan_tiket` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pemesanan_sewa_alat`
--
ALTER TABLE `pemesanan_sewa_alat`
  ADD CONSTRAINT `fk_sewa_alat_master` FOREIGN KEY (`sewa_alat_id`) REFERENCES `sewa_alat` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sewa_pemesanan_tiket` FOREIGN KEY (`pemesanan_tiket_id`) REFERENCES `pemesanan_tiket` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `pemesanan_sewa_alat_ibfk_1` FOREIGN KEY (`pemesanan_tiket_id`) REFERENCES `pemesanan_tiket` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pemesanan_sewa_alat_ibfk_2` FOREIGN KEY (`sewa_alat_id`) REFERENCES `sewa_alat` (`id`);

--
-- Constraints for table `pemesanan_tiket`
--
ALTER TABLE `pemesanan_tiket`
  ADD CONSTRAINT `fk_pemesanan_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `pemesanan_tiket_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
