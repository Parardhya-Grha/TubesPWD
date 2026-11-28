-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 28 Nov 2026 pada 04.39
-- Versi server: 10.11.16-MariaDB
-- Versi PHP: 8.5.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Basis data: `db_camprent`
--
CREATE DATABASE IF NOT EXISTS `db_camprent` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `db_camprent`;

-- --------------------------------------------------------

--
-- Struktur dari tabel `captcha_logs`
--

CREATE TABLE `captcha_logs` (
  `id_captcha` int(10) UNSIGNED NOT NULL,
  `kode_captcha` varchar(12) NOT NULL,
  `session_token` varchar(64) NOT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `captcha_logs`
--

INSERT INTO `captcha_logs` (`id_captcha`, `kode_captcha`, `session_token`, `is_verified`, `expires_at`, `created_at`) VALUES
(1, 'CMP84X', 'sess_tok_98a7sd98a7sd', 1, '2026-11-25 14:00:00', '2026-11-26 10:40:21'),
(2, 'RNT29K', 'sess_tok_12b3cd45ef67', 0, '2026-11-25 14:15:00', '2026-11-26 10:40:21');

-- --------------------------------------------------------

--
-- Struktur dari tabel `carts`
--

CREATE TABLE `carts` (
  `id_cart` int(10) UNSIGNED NOT NULL,
  `id_user` int(10) UNSIGNED NOT NULL,
  `tanggal_mulai_sewa` date DEFAULT NULL,
  `durasi_hari` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `alamat_pengiriman` text DEFAULT NULL,
  `catatan_kurir` varchar(255) DEFAULT NULL,
  `catatan_pesanan` text DEFAULT NULL,
  `metode_pembayaran` enum('Bank Transfer','E-Wallet','COD') NOT NULL DEFAULT 'Bank Transfer',
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `carts`
--

INSERT INTO `carts` (`id_cart`, `id_user`, `tanggal_mulai_sewa`, `durasi_hari`, `alamat_pengiriman`, `catatan_kurir`, `catatan_pesanan`, `metode_pembayaran`, `updated_at`) VALUES
(1, 2, '2026-11-28', 3, 'Jl. Babarsari No. 43, Depok, Sleman, Yogyakarta', 'Titip di pos satpam jika belum di tempat', 'Mohon cek resleting tenda dan carrier sebelum dikirim', 'Bank Transfer', '2026-11-26 10:40:21'),
(2, 4, NULL, 1, NULL, NULL, NULL, '', '2026-11-28 01:16:45'),
(3, 5, NULL, 1, NULL, NULL, NULL, '', '2026-11-28 03:26:38');

-- --------------------------------------------------------

--
-- Struktur dari tabel `cart_items`
--

CREATE TABLE `cart_items` (
  `id_cart_item` int(10) UNSIGNED NOT NULL,
  `id_cart` int(10) UNSIGNED NOT NULL,
  `id_product` int(10) UNSIGNED NOT NULL,
  `id_variant` int(10) UNSIGNED NOT NULL,
  `qty` int(10) UNSIGNED NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `cart_items`
--

INSERT INTO `cart_items` (`id_cart_item`, `id_cart`, `id_product`, `id_variant`, `qty`) VALUES
(1, 1, 1, 1, 1),
(2, 1, 4, 5, 1),
(3, 1, 10, 12, 1),
(4, 2, 4, 5, 1),
(5, 2, 11, 13, 1),
(6, 2, 10, 12, 1),
(7, 2, 9, 11, 1),
(11, 3, 6, 8, 1),
(12, 3, 5, 6, 1),
(13, 3, 8, 10, 1);

-- --------------------------------------------------------

--
-- Struktur dari tabel `categories`
--

CREATE TABLE `categories` (
  `id_category` int(10) UNSIGNED NOT NULL,
  `nama_kategori` varchar(50) NOT NULL,
  `slug_kategori` varchar(60) NOT NULL,
  `icon_url` varchar(255) NOT NULL,
  `deskripsi` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `categories`
--

INSERT INTO `categories` (`id_category`, `nama_kategori`, `slug_kategori`, `icon_url`, `deskripsi`) VALUES
(1, 'Carrier Bag', 'carrier-bag', 'Images/carrier-NorthF.svg', 'Tas gunung dan ransel pendakian berbagai kapasitas liter'),
(2, 'Tent', 'tent', 'Images/tenda.svg', 'Tenda dome kapasitas 2 hingga 6 orang tahan angin dan hujan'),
(3, 'Cooking Gear', 'cooking-gear', 'Images/alat-masak.svg', 'Peralatan masak lapangan, nesting, dan kompor portable'),
(4, 'Tracking Pole', 'tracking-pole', 'Images/tracking-pool.svg', 'Tongkat bantu pendakian ringan dan kokoh'),
(5, 'Jacket', 'jacket', 'Images/jacket.svg', 'Jaket gunung waterproof dan windproof'),
(6, 'Shoes', 'shoes', 'Images/shoes.svg', 'Sepatu hiking dengan grip anti-slip dan pelindung pergelangan');

-- --------------------------------------------------------

--
-- Struktur dari tabel `products`
--

CREATE TABLE `products` (
  `id_product` int(10) UNSIGNED NOT NULL,
  `id_category` int(10) UNSIGNED NOT NULL,
  `nama_produk` varchar(120) NOT NULL,
  `brand` varchar(60) NOT NULL,
  `kapasitas_ukuran` varchar(50) DEFAULT NULL,
  `harga_per_hari` decimal(12,2) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `gambar_utama` varchar(255) NOT NULL,
  `badge` enum('None','Best Seller','New','Limited Stock') NOT NULL DEFAULT 'None',
  `is_popular` tinyint(1) NOT NULL DEFAULT 0,
  `rating_rata_rata` decimal(2,1) NOT NULL DEFAULT 5.0,
  `total_disewa` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `products`
--

INSERT INTO `products` (`id_product`, `id_category`, `nama_produk`, `brand`, `kapasitas_ukuran`, `harga_per_hari`, `deskripsi`, `gambar_utama`, `badge`, `is_popular`, `rating_rata_rata`, `total_disewa`, `created_at`) VALUES
(1, 1, 'Carrier Eiger 60L Blue', 'Eiger', '60 Liter', 35000.00, 'Tas carrier kapasitas 60L dengan backsystem ergonomis dan raincover.', 'Images/carrier-Eiger.svg', 'Best Seller', 1, 4.9, 142, '2026-11-26 10:40:21'),
(2, 1, 'Consina Tarebbi 60L', 'Consina', '60 Liter', 35000.00, 'Carrier ringan dan tangguh untuk pendakian 2-4 hari.', 'Images/carrier.png', 'None', 1, 4.8, 119, '2026-11-26 10:40:21'),
(3, 2, 'Tendaki Borneo 4 Tent', 'Tendaki', '4 Orang', 45000.00, 'Tenda double layer kapasitas 4 orang dengan frame alloy.', 'Images/tenda.svg', 'New', 0, 4.9, 64, '2026-11-26 10:40:21'),
(4, 2, 'Consina Magnum 4 Tent', 'Consina', '4 Orang', 50000.00, 'Tenda camping keluarga dan pendakian kapasitas 4 orang tahan hujan deras.', 'Images/tendaConsina.svg', 'None', 1, 4.9, 131, '2026-11-26 10:40:21'),
(5, 4, 'Arc’teryx Tracking Pole', 'Naturehike', '135 cm', 15000.00, 'Trekking pole berbahan duralumin ringan dengan sistem pengunci cepat.', 'Images/Tracking-Pole-Elfs.svg', 'None', 1, 4.7, 95, '2026-11-26 10:40:21'),
(6, 5, 'Eiger Waterproof Jacket', 'Eiger', 'Size L', 30000.00, 'Jaket gunung tahan air dan angin dengan sirkulasi udara optimal.', 'Images/Jacket-Arc\'teryx.svg', 'Best Seller', 1, 4.9, 111, '2026-11-26 10:40:21'),
(7, 5, 'Arc\'teryx Alpha SV Jacket', 'Arc\'teryx', 'Size M', 40000.00, 'Jaket hardshell cuaca ekstrem dengan lapisan Gore-Tex Pro.', 'Images/Jacket-Arc\'teryx-White.svg', 'New', 0, 5.0, 41, '2026-11-26 10:40:21'),
(8, 6, 'Salomon X Ultra 4 Gore-Tex', 'Salomon', 'Size 42', 45000.00, 'Sepatu hiking ringan dengan sol Contagrip anti licin.', 'Images/Sepatu-Salomon.svg', 'Limited Stock', 0, 4.9, 77, '2026-11-26 10:40:21'),
(9, 3, 'Nesting Cooking Gear Set', 'Naturehike', '3-4 Orang', 20000.00, 'Set panci dan wajan camping aluminium anodized anti lengket.', 'Images/alat-masak.svg', 'None', 0, 4.8, 88, '2026-11-26 10:40:21'),
(10, 3, 'Portable Stove', 'Kovar', 'Standard', 15000.00, 'Kompor gas mini portable lipat tahan angin untuk kegiatan outdoor.', 'Images/alat-masak.svg', 'None', 0, 4.8, 125, '2026-11-26 10:40:21'),
(11, 1, 'Sleeping Bag Polar', 'Eiger', '200 x 75 cm', 20000.00, 'Kantong tidur lapisan polar hangat untuk suhu dingin pegunungan.', 'Images/carrier-NorthF.svg', 'None', 0, 4.7, 102, '2026-11-26 10:40:21'),
(12, 2, 'Matras Camping', 'Consina', '180 x 60 cm', 10000.00, 'Matras spons gulung isolasi dingin alas tenda.', 'Images/tenda.svg', 'None', 0, 4.6, 150, '2026-11-26 10:40:21');

-- --------------------------------------------------------

--
-- Struktur dari tabel `product_variants`
--

CREATE TABLE `product_variants` (
  `id_variant` int(10) UNSIGNED NOT NULL,
  `id_product` int(10) UNSIGNED NOT NULL,
  `nama_warna` varchar(40) NOT NULL,
  `kode_hex` varchar(7) NOT NULL,
  `gambar_varian` varchar(255) NOT NULL,
  `stok_tersedia` int(10) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `product_variants`
--

INSERT INTO `product_variants` (`id_variant`, `id_product`, `nama_warna`, `kode_hex`, `gambar_varian`, `stok_tersedia`) VALUES
(1, 1, 'Navy Blue', '#1B365D', 'Images/Backpack.jpg', 8),
(2, 1, 'Charcoal Black', '#222222', 'Images/Backpack_Wikipedia.JPG', 5),
(3, 2, 'Terracotta Orange', '#D9532F', 'Images/Backpack_(26645453056).jpg', 5),
(4, 3, 'Deep Navy', '#1D3557', 'Images/Dome_tent.JPG', 7),
(5, 4, 'Blue Grey', '#4A6B82', 'Images/Dome_tent_in_the_BWCA_(CDM11452CT).jpg', 4),
(6, 5, 'Matte Black', '#1C1C1C', 'Images/Trekking_poles.jpg', 14),
(7, 5, 'Silver Grey', '#A8B2BD', 'Images/Retracted_trekking_poles.jpg', 10),
(8, 6, 'Jet Black', '#111111', 'Images/Windbreaker_Jacket,_Hood_Outside.jpg', 8),
(9, 7, 'Ice Grey', '#DCE3E6', 'Images/Windjack_-_Windbreaker.jpg', 4),
(10, 8, 'Earth Khaki', '#8C7A6B', 'Images/Hiking_boots.jpg', 2),
(11, 9, 'Stainless Silver', '#C0C0C0', 'Images/Open_mess_kit.jpg', 10),
(12, 10, 'Standard Black', '#2B2B2B', 'Images/MSR_WindPro_portable_stove.jpg', 12),
(13, 11, 'Midnight Blue', '#1F305E', 'Images/Compactsleepingbag.jpg', 11),
(14, 12, 'Dark Grey', '#3A3A3A', 'Images/Mattress.jpg', 15);

-- --------------------------------------------------------

--
-- Struktur dari tabel `rentals`
--

CREATE TABLE `rentals` (
  `id_rental` int(10) UNSIGNED NOT NULL,
  `kode_transaksi` varchar(30) NOT NULL,
  `id_user` int(10) UNSIGNED NOT NULL,
  `tanggal_mulai_sewa` date NOT NULL,
  `tanggal_selesai_sewa` date NOT NULL,
  `durasi_hari` int(10) UNSIGNED NOT NULL,
  `alamat_pengiriman` text NOT NULL,
  `catatan_kurir` varchar(255) DEFAULT NULL,
  `catatan_pesanan` text DEFAULT NULL,
  `subtotal_sewa` decimal(12,2) NOT NULL,
  `biaya_pengiriman` decimal(12,2) NOT NULL DEFAULT 0.00,
  `biaya_layanan` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_bayar` decimal(12,2) NOT NULL,
  `metode_pembayaran` enum('Bank Transfer','E-Wallet','COD') NOT NULL,
  `status_pembayaran` enum('belum_bayar','lunas','gagal') NOT NULL DEFAULT 'belum_bayar',
  `status_sewa` enum('menunggu_konfirmasi','diproses','sedang_disewa','selesai','dibatalkan') NOT NULL DEFAULT 'menunggu_konfirmasi',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `rentals`
--

INSERT INTO `rentals` (`id_rental`, `kode_transaksi`, `id_user`, `tanggal_mulai_sewa`, `tanggal_selesai_sewa`, `durasi_hari`, `alamat_pengiriman`, `catatan_kurir`, `catatan_pesanan`, `subtotal_sewa`, `biaya_pengiriman`, `biaya_layanan`, `total_bayar`, `metode_pembayaran`, `status_pembayaran`, `status_sewa`, `created_at`) VALUES
(1, 'CR-20261110-001', 2, '2026-11-10', '2026-11-12', 2, 'Jl. Babarsari No. 43, Depok, Sleman, Yogyakarta', 'Hubungi WA saat tiba', 'Sewa untuk camp Merbabu', 130000.00, 15000.00, 5000.00, 150000.00, 'E-Wallet', 'lunas', 'selesai', '2026-11-26 10:40:21'),
(2, 'CR-20261124-002', 3, '2026-11-24', '2026-11-27', 3, 'Jl. Kaliurang KM 5, Sleman, Yogyakarta', 'Antar sore jam 4', 'Tenda pastikan pasak lengkap', 225000.00, 15000.00, 5000.00, 245000.00, 'Bank Transfer', 'lunas', 'sedang_disewa', '2026-11-26 10:40:21'),
(3, 'CR-20261128-5E2D', 5, '2026-11-26', '2026-11-28', 2, 'Self Pickup at CampRent Basecamp Yogyakarta', NULL, 'Penerima: yohanes (083432424356) | Tipe: Self Pickup', 230000.00, 0.00, 25300.00, 255300.00, '', 'lunas', 'menunggu_konfirmasi', '2026-11-28 03:27:40');

-- --------------------------------------------------------

--
-- Struktur dari tabel `rental_items`
--

CREATE TABLE `rental_items` (
  `id_rental_item` int(10) UNSIGNED NOT NULL,
  `id_rental` int(10) UNSIGNED NOT NULL,
  `id_product` int(10) UNSIGNED NOT NULL,
  `id_variant` int(10) UNSIGNED NOT NULL,
  `qty` int(10) UNSIGNED NOT NULL,
  `harga_per_hari` decimal(12,2) NOT NULL,
  `subtotal_item` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `rental_items`
--

INSERT INTO `rental_items` (`id_rental_item`, `id_rental`, `id_product`, `id_variant`, `qty`, `harga_per_hari`, `subtotal_item`) VALUES
(1, 1, 1, 1, 1, 35000.00, 70000.00),
(2, 1, 6, 8, 1, 30000.00, 60000.00),
(3, 2, 4, 5, 1, 50000.00, 150000.00),
(4, 2, 5, 6, 1, 15000.00, 45000.00),
(5, 2, 12, 14, 1, 10000.00, 30000.00),
(6, 3, 4, 5, 1, 50000.00, 100000.00),
(7, 3, 2, 3, 1, 35000.00, 70000.00),
(8, 3, 6, 8, 1, 30000.00, 60000.00);

-- --------------------------------------------------------

--
-- Struktur dari tabel `returns`
--

CREATE TABLE `returns` (
  `id_return` int(10) UNSIGNED NOT NULL,
  `id_rental` int(10) UNSIGNED NOT NULL,
  `tanggal_kembali_aktual` datetime NOT NULL,
  `kondisi_barang` enum('baik','rusak_ringan','rusak_berat','hilang') NOT NULL DEFAULT 'baik',
  `denda` decimal(12,2) NOT NULL DEFAULT 0.00,
  `catatan_pengembalian` text DEFAULT NULL,
  `status_retur` enum('menunggu_pengecekan','selesai','komplain') NOT NULL DEFAULT 'menunggu_pengecekan'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `returns`
--

INSERT INTO `returns` (`id_return`, `id_rental`, `tanggal_kembali_aktual`, `kondisi_barang`, `denda`, `catatan_pengembalian`, `status_retur`) VALUES
(1, 1, '2026-11-12 17:30:00', 'baik', 0.00, 'Carrier dan jaket dikembalikan lengkap, kering, dan bersih.', 'selesai');

-- --------------------------------------------------------

--
-- Struktur dari tabel `reviews`
--

CREATE TABLE `reviews` (
  `id_review` int(10) UNSIGNED NOT NULL,
  `id_user` int(10) UNSIGNED NOT NULL,
  `id_product` int(10) UNSIGNED NOT NULL,
  `id_rental` int(10) UNSIGNED NOT NULL,
  `rating` tinyint(3) UNSIGNED NOT NULL,
  `komentar` text NOT NULL,
  `is_featured` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `reviews`
--

INSERT INTO `reviews` (`id_review`, `id_user`, `id_product`, `id_rental`, `rating`, `komentar`, `is_featured`, `created_at`) VALUES
(1, 2, 1, 1, 5, 'Carrier Eiger 60L-nya bersih banget, wangi, dan nyaman dipakai nanjak ke Merbabu!', 1, '2026-11-26 10:40:21'),
(2, 3, 4, 2, 5, 'Tenda Consina Magnum 4 kokoh dan pengirimannya tepat waktu di area Jogja.', 1, '2026-11-26 10:40:21');

-- --------------------------------------------------------

--
-- Struktur dari tabel `site_menus`
--

CREATE TABLE `site_menus` (
  `id_menu` int(10) UNSIGNED NOT NULL,
  `nama_menu` varchar(50) NOT NULL,
  `url_slug` varchar(100) NOT NULL,
  `urutan` int(11) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `site_menus`
--

INSERT INTO `site_menus` (`id_menu`, `nama_menu`, `url_slug`, `urutan`, `is_active`) VALUES
(1, 'Product', '/products', 1, 1),
(2, 'Support', '/support', 2, 1),
(3, 'Our Store', '/our-store', 3, 1),
(4, 'FAQ', '/faq', 4, 1),
(5, 'About Us', '/about-us', 5, 1);

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id_user` int(10) UNSIGNED NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `no_hp` varchar(20) NOT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','customer') NOT NULL DEFAULT 'customer',
  `alamat_default` text DEFAULT NULL,
  `foto_profil` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id_user`, `nama_lengkap`, `username`, `email`, `no_hp`, `tanggal_lahir`, `password_hash`, `role`, `alamat_default`, `foto_profil`, `created_at`) VALUES
(1, 'Admin CampRent', 'user1', 'admin@camprent.id', '081234567890', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Basecamp CampRent Yogyakarta', NULL, '2026-11-26 10:40:21'),
(2, 'Ardhya Jayawardhana', 'user2', 'ardhya@example.com', '085712345678', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', 'Jl. Babarsari No. 43, Depok, Sleman, Yogyakarta', NULL, '2026-11-26 10:40:21'),
(3, 'Nabila Putri', 'user3', 'nabila@example.com', '082198765432', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', 'Jl. Kaliurang KM 5, Sleman, Yogyakarta', NULL, '2026-11-26 10:40:21'),
(4, 'Dionisius Oktavianus Aldo', 'Dioooajaaa', 'diotes@gmail.com', '081232123456', '2007-10-17', '9dc55c7f356bd3dfe9f3674de184879d818833405e201c9602a8ad117d45d055', 'customer', 'jl. babasari 123', NULL, '2026-11-28 01:13:53'),
(5, 'yohanes', 'yohh', 'yoh@gmail.com', '083432424356', NULL, '9dc55c7f356bd3dfe9f3674de184879d818833405e201c9602a8ad117d45d055', 'customer', '', 'Images/avatars/avatar_5_1795836351_6cb5cd.jpg', '2026-11-28 03:25:13');

--
-- Indeks untuk tabel yang dibuang
--

--
-- Indeks untuk tabel `captcha_logs`
--
ALTER TABLE `captcha_logs`
  ADD PRIMARY KEY (`id_captcha`);

--
-- Indeks untuk tabel `carts`
--
ALTER TABLE `carts`
  ADD PRIMARY KEY (`id_cart`),
  ADD UNIQUE KEY `id_user` (`id_user`);

--
-- Indeks untuk tabel `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id_cart_item`),
  ADD KEY `fk_cart_items_cart` (`id_cart`),
  ADD KEY `fk_cart_items_product` (`id_product`),
  ADD KEY `fk_cart_items_variant` (`id_variant`);

--
-- Indeks untuk tabel `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id_category`),
  ADD UNIQUE KEY `slug_kategori` (`slug_kategori`);

--
-- Indeks untuk tabel `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id_product`),
  ADD KEY `fk_products_category` (`id_category`);

--
-- Indeks untuk tabel `product_variants`
--
ALTER TABLE `product_variants`
  ADD PRIMARY KEY (`id_variant`),
  ADD KEY `fk_variants_product` (`id_product`);

--
-- Indeks untuk tabel `rentals`
--
ALTER TABLE `rentals`
  ADD PRIMARY KEY (`id_rental`),
  ADD UNIQUE KEY `kode_transaksi` (`kode_transaksi`),
  ADD KEY `fk_rentals_user` (`id_user`);

--
-- Indeks untuk tabel `rental_items`
--
ALTER TABLE `rental_items`
  ADD PRIMARY KEY (`id_rental_item`),
  ADD KEY `fk_rental_items_rental` (`id_rental`),
  ADD KEY `fk_rental_items_product` (`id_product`),
  ADD KEY `fk_rental_items_variant` (`id_variant`);

--
-- Indeks untuk tabel `returns`
--
ALTER TABLE `returns`
  ADD PRIMARY KEY (`id_return`),
  ADD UNIQUE KEY `id_rental` (`id_rental`);

--
-- Indeks untuk tabel `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id_review`),
  ADD KEY `fk_reviews_user` (`id_user`),
  ADD KEY `fk_reviews_product` (`id_product`),
  ADD KEY `fk_reviews_rental` (`id_rental`);

--
-- Indeks untuk tabel `site_menus`
--
ALTER TABLE `site_menus`
  ADD PRIMARY KEY (`id_menu`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `captcha_logs`
--
ALTER TABLE `captcha_logs`
  MODIFY `id_captcha` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `carts`
--
ALTER TABLE `carts`
  MODIFY `id_cart` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id_cart_item` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT untuk tabel `categories`
--
ALTER TABLE `categories`
  MODIFY `id_category` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `products`
--
ALTER TABLE `products`
  MODIFY `id_product` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT untuk tabel `product_variants`
--
ALTER TABLE `product_variants`
  MODIFY `id_variant` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT untuk tabel `rentals`
--
ALTER TABLE `rentals`
  MODIFY `id_rental` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `rental_items`
--
ALTER TABLE `rental_items`
  MODIFY `id_rental_item` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT untuk tabel `returns`
--
ALTER TABLE `returns`
  MODIFY `id_return` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id_review` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `site_menus`
--
ALTER TABLE `site_menus`
  MODIFY `id_menu` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id_user` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `carts`
--
ALTER TABLE `carts`
  ADD CONSTRAINT `fk_carts_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `fk_cart_items_cart` FOREIGN KEY (`id_cart`) REFERENCES `carts` (`id_cart`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cart_items_product` FOREIGN KEY (`id_product`) REFERENCES `products` (`id_product`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cart_items_variant` FOREIGN KEY (`id_variant`) REFERENCES `product_variants` (`id_variant`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_products_category` FOREIGN KEY (`id_category`) REFERENCES `categories` (`id_category`) ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `product_variants`
--
ALTER TABLE `product_variants`
  ADD CONSTRAINT `fk_variants_product` FOREIGN KEY (`id_product`) REFERENCES `products` (`id_product`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `rentals`
--
ALTER TABLE `rentals`
  ADD CONSTRAINT `fk_rentals_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `rental_items`
--
ALTER TABLE `rental_items`
  ADD CONSTRAINT `fk_rental_items_product` FOREIGN KEY (`id_product`) REFERENCES `products` (`id_product`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rental_items_rental` FOREIGN KEY (`id_rental`) REFERENCES `rentals` (`id_rental`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rental_items_variant` FOREIGN KEY (`id_variant`) REFERENCES `product_variants` (`id_variant`) ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `returns`
--
ALTER TABLE `returns`
  ADD CONSTRAINT `fk_returns_rental` FOREIGN KEY (`id_rental`) REFERENCES `rentals` (`id_rental`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `fk_reviews_product` FOREIGN KEY (`id_product`) REFERENCES `products` (`id_product`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_reviews_rental` FOREIGN KEY (`id_rental`) REFERENCES `rentals` (`id_rental`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_reviews_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
