-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 26, 2026 at 02:55 AM
-- Server version: 10.11.16-MariaDB
-- PHP Version: 8.5.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_camprent`
--

-- --------------------------------------------------------

--
-- Table structure for table `captcha_logs`
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
-- Dumping data for table `captcha_logs`
--

INSERT INTO `captcha_logs` (`id_captcha`, `kode_captcha`, `session_token`, `is_verified`, `expires_at`, `created_at`) VALUES
(1, 'CMP84X', 'sess_tok_98a7sd98a7sd', 1, '2026-11-25 14:00:00', '2026-11-25 06:42:02'),
(2, 'RNT29K', 'sess_tok_12b3cd45ef67', 0, '2026-11-25 14:15:00', '2026-11-25 06:42:02');

-- --------------------------------------------------------

--
-- Table structure for table `carts`
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
-- Dumping data for table `carts`
--

INSERT INTO `carts` (`id_cart`, `id_user`, `tanggal_mulai_sewa`, `durasi_hari`, `alamat_pengiriman`, `catatan_kurir`, `catatan_pesanan`, `metode_pembayaran`, `updated_at`) VALUES
(1, 2, '2026-11-28', 3, 'Jl. Babarsari No. 43, Depok, Sleman, Yogyakarta', 'Titip di pos satpam jika belum di tempat', 'Mohon cek resleting tenda dan carrier sebelum dikirim', 'Bank Transfer', '2026-11-25 06:42:02');

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `id_cart_item` int(10) UNSIGNED NOT NULL,
  `id_cart` int(10) UNSIGNED NOT NULL,
  `id_product` int(10) UNSIGNED NOT NULL,
  `id_variant` int(10) UNSIGNED NOT NULL,
  `qty` int(10) UNSIGNED NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cart_items`
--

INSERT INTO `cart_items` (`id_cart_item`, `id_cart`, `id_product`, `id_variant`, `qty`) VALUES
(1, 1, 1, 1, 1),
(2, 1, 4, 5, 1),
(3, 1, 10, 12, 1);

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id_category` int(10) UNSIGNED NOT NULL,
  `nama_kategori` varchar(50) NOT NULL,
  `slug_kategori` varchar(60) NOT NULL,
  `icon_url` varchar(255) NOT NULL,
  `deskripsi` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id_category`, `nama_kategori`, `slug_kategori`, `icon_url`, `deskripsi`) VALUES
(1, 'Carrier Bag', 'carrier-bag', 'assets/icons/cat-carrier.png', 'Tas gunung dan ransel pendakian berbagai kapasitas liter'),
(2, 'Tent', 'tent', 'assets/icons/cat-tent.png', 'Tenda dome kapasitas 2 hingga 6 orang tahan angin dan hujan'),
(3, 'Cooking Gear', 'cooking-gear', 'assets/icons/cat-cooking.png', 'Peralatan masak lapangan, nesting, dan kompor portable'),
(4, 'Tracking Pole', 'tracking-pole', 'assets/icons/cat-pole.png', 'Tongkat bantu pendakian ringan dan kokoh'),
(5, 'Jacket', 'jacket', 'assets/icons/cat-jacket.png', 'Jaket gunung waterproof dan windproof'),
(6, 'Shoes', 'shoes', 'assets/icons/cat-shoes.png', 'Sepatu hiking dengan grip anti-slip dan pelindung pergelangan');

-- --------------------------------------------------------

--
-- Table structure for table `products`
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
-- Dumping data for table `products`
--

INSERT INTO `products` (`id_product`, `id_category`, `nama_produk`, `brand`, `kapasitas_ukuran`, `harga_per_hari`, `deskripsi`, `gambar_utama`, `badge`, `is_popular`, `rating_rata_rata`, `total_disewa`, `created_at`) VALUES
(1, 1, 'Carrier Eiger 60L Blue', 'Eiger', '60 Liter', 35000.00, 'Tas carrier kapasitas 60L dengan backsystem ergonomis dan raincover.', 'assets/products/carrier-eiger-60l-blue.jpg', 'Best Seller', 1, 4.9, 142, '2026-11-25 06:42:02'),
(2, 1, 'Consina Tarebbi 60L', 'Consina', '60 Liter', 35000.00, 'Carrier ringan dan tangguh untuk pendakian 2-4 hari.', 'assets/products/consina-tarebbi-60l.jpg', 'None', 1, 4.8, 118, '2026-11-25 06:42:02'),
(3, 2, 'Tendaki Borneo 4 Tent', 'Tendaki', '4 Orang', 45000.00, 'Tenda double layer kapasitas 4 orang dengan frame alloy.', 'assets/products/tendaki-borneo-4.jpg', 'New', 0, 4.9, 64, '2026-11-25 06:42:02'),
(4, 2, 'Consina Magnum 4 Tent', 'Consina', '4 Orang', 50000.00, 'Tenda camping keluarga dan pendakian kapasitas 4 orang tahan hujan deras.', 'assets/products/consina-magnum-4.jpg', 'None', 1, 4.9, 130, '2026-11-25 06:42:02'),
(5, 4, 'Ultra-light Trekking Pole', 'Naturehike', '135 cm', 15000.00, 'Trekking pole berbahan duralumin ringan dengan sistem pengunci cepat.', 'assets/products/ultralight-trekking-pole.jpg', 'None', 1, 4.7, 95, '2026-11-25 06:42:02'),
(6, 5, 'Eiger Waterproof Jacket', 'Eiger', 'Size L', 30000.00, 'Jaket gunung tahan air dan angin dengan sirkulasi udara optimal.', 'assets/products/eiger-waterproof-jacket.jpg', 'Best Seller', 1, 4.9, 110, '2026-11-25 06:42:02'),
(7, 5, 'Arc\'teryx Alpha SV Jacket', 'Arc\'teryx', 'Size M', 40000.00, 'Jaket hardshell cuaca ekstrem dengan lapisan Gore-Tex Pro.', 'assets/products/arcteryx-alpha-sv.jpg', 'New', 0, 5.0, 41, '2026-11-25 06:42:02'),
(8, 6, 'Salomon X Ultra 4 Gore-Tex', 'Salomon', 'Size 42', 45000.00, 'Sepatu hiking ringan dengan sol Contagrip anti licin.', 'assets/products/salomon-x-ultra-4.jpg', 'Limited Stock', 0, 4.9, 77, '2026-11-25 06:42:02'),
(9, 3, 'Nesting Cooking Gear Set', 'Naturehike', '3-4 Orang', 20000.00, 'Set panci dan wajan camping aluminium anodized anti lengket.', 'assets/products/nesting-cooking-set.jpg', 'None', 0, 4.8, 88, '2026-11-25 06:42:02'),
(10, 3, 'Portable Stove', 'Kovar', 'Standard', 15000.00, 'Kompor gas mini portable lipat tahan angin untuk kegiatan outdoor.', 'assets/products/portable-stove.jpg', 'None', 0, 4.8, 125, '2026-11-25 06:42:02'),
(11, 1, 'Sleeping Bag Polar', 'Eiger', '200 x 75 cm', 20000.00, 'Kantong tidur lapisan polar hangat untuk suhu dingin pegunungan.', 'assets/products/sleeping-bag-polar.jpg', 'None', 0, 4.7, 102, '2026-11-25 06:42:02'),
(12, 2, 'Matras Camping', 'Consina', '180 x 60 cm', 10000.00, 'Matras spons gulung isolasi dingin alas tenda.', 'assets/products/matras-camping.jpg', 'None', 0, 4.6, 150, '2026-11-25 06:42:02');

-- --------------------------------------------------------

--
-- Table structure for table `product_variants`
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
-- Dumping data for table `product_variants`
--

INSERT INTO `product_variants` (`id_variant`, `id_product`, `nama_warna`, `kode_hex`, `gambar_varian`, `stok_tersedia`) VALUES
(1, 1, 'Navy Blue', '#1B365D', 'assets/products/carrier-eiger-60l-blue.jpg', 8),
(2, 1, 'Charcoal Black', '#222222', 'assets/products/carrier-eiger-60l-black.jpg', 5),
(3, 2, 'Terracotta Orange', '#D9532F', 'assets/products/consina-tarebbi-60l.jpg', 6),
(4, 3, 'Deep Navy', '#1D3557', 'assets/products/tendaki-borneo-4.jpg', 7),
(5, 4, 'Blue Grey', '#4A6B82', 'assets/products/consina-magnum-4.jpg', 5),
(6, 5, 'Matte Black', '#1C1C1C', 'assets/products/ultralight-trekking-pole.jpg', 14),
(7, 5, 'Silver Grey', '#A8B2BD', 'assets/products/ultralight-trekking-pole-silver.jpg', 10),
(8, 6, 'Jet Black', '#111111', 'assets/products/eiger-waterproof-jacket.jpg', 9),
(9, 7, 'Ice Grey', '#DCE3E6', 'assets/products/arcteryx-alpha-sv.jpg', 4),
(10, 8, 'Earth Khaki', '#8C7A6B', 'assets/products/salomon-x-ultra-4.jpg', 2),
(11, 9, 'Stainless Silver', '#C0C0C0', 'assets/products/nesting-cooking-set.jpg', 10),
(12, 10, 'Standard Black', '#2B2B2B', 'assets/products/portable-stove.jpg', 12),
(13, 11, 'Midnight Blue', '#1F305E', 'assets/products/sleeping-bag-polar.jpg', 11),
(14, 12, 'Dark Grey', '#3A3A3A', 'assets/products/matras-camping.jpg', 15);

-- --------------------------------------------------------

--
-- Table structure for table `rentals`
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
-- Dumping data for table `rentals`
--

INSERT INTO `rentals` (`id_rental`, `kode_transaksi`, `id_user`, `tanggal_mulai_sewa`, `tanggal_selesai_sewa`, `durasi_hari`, `alamat_pengiriman`, `catatan_kurir`, `catatan_pesanan`, `subtotal_sewa`, `biaya_pengiriman`, `biaya_layanan`, `total_bayar`, `metode_pembayaran`, `status_pembayaran`, `status_sewa`, `created_at`) VALUES
(1, 'CR-20261110-001', 2, '2026-11-10', '2026-11-12', 2, 'Jl. Babarsari No. 43, Depok, Sleman, Yogyakarta', 'Hubungi WA saat tiba', 'Sewa untuk camp Merbabu', 130000.00, 15000.00, 5000.00, 150000.00, 'E-Wallet', 'lunas', 'selesai', '2026-11-25 06:42:02'),
(2, 'CR-20261124-002', 3, '2026-11-24', '2026-11-27', 3, 'Jl. Kaliurang KM 5, Sleman, Yogyakarta', 'Antar sore jam 4', 'Tenda pastikan pasak lengkap', 225000.00, 15000.00, 5000.00, 245000.00, 'Bank Transfer', 'lunas', 'sedang_disewa', '2026-11-25 06:42:02');

-- --------------------------------------------------------

--
-- Table structure for table `rental_items`
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
-- Dumping data for table `rental_items`
--

INSERT INTO `rental_items` (`id_rental_item`, `id_rental`, `id_product`, `id_variant`, `qty`, `harga_per_hari`, `subtotal_item`) VALUES
(1, 1, 1, 1, 1, 35000.00, 70000.00),
(2, 1, 6, 8, 1, 30000.00, 60000.00),
(3, 2, 4, 5, 1, 50000.00, 150000.00),
(4, 2, 5, 6, 1, 15000.00, 45000.00),
(5, 2, 12, 14, 1, 10000.00, 30000.00);

-- --------------------------------------------------------

--
-- Table structure for table `returns`
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
-- Dumping data for table `returns`
--

INSERT INTO `returns` (`id_return`, `id_rental`, `tanggal_kembali_aktual`, `kondisi_barang`, `denda`, `catatan_pengembalian`, `status_retur`) VALUES
(1, 1, '2026-11-12 17:30:00', 'baik', 0.00, 'Carrier dan jaket dikembalikan lengkap, kering, dan bersih.', 'selesai');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
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
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id_review`, `id_user`, `id_product`, `id_rental`, `rating`, `komentar`, `is_featured`, `created_at`) VALUES
(1, 2, 1, 1, 5, 'Carrier Eiger 60L-nya bersih banget, wangi, dan nyaman dipakai nanjak ke Merbabu!', 1, '2026-11-25 06:42:02'),
(2, 3, 4, 2, 5, 'Tenda Consina Magnum 4 kokoh dan pengirimannya tepat waktu di area Jogja.', 1, '2026-11-25 06:42:02');

-- --------------------------------------------------------

--
-- Table structure for table `site_menus`
--

CREATE TABLE `site_menus` (
  `id_menu` int(10) UNSIGNED NOT NULL,
  `nama_menu` varchar(50) NOT NULL,
  `url_slug` varchar(100) NOT NULL,
  `urutan` int(11) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `site_menus`
--

INSERT INTO `site_menus` (`id_menu`, `nama_menu`, `url_slug`, `urutan`, `is_active`) VALUES
(1, 'Product', '/products', 1, 1),
(2, 'Support', '/support', 2, 1),
(3, 'Our Store', '/our-store', 3, 1),
(4, 'FAQ', '/faq', 4, 1),
(5, 'About Us', '/about-us', 5, 1);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id_user` int(10) UNSIGNED NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `no_hp` varchar(20) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','customer') NOT NULL DEFAULT 'customer',
  `alamat_default` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id_user`, `nama_lengkap`, `email`, `no_hp`, `password_hash`, `role`, `alamat_default`, `created_at`) VALUES
(1, 'Admin CampRent', 'admin@camprent.id', '081234567890', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Basecamp CampRent Yogyakarta', '2026-11-25 06:42:02'),
(2, 'Ardhya Jayawardhana', 'ardhya@example.com', '085712345678', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', 'Jl. Babarsari No. 43, Depok, Sleman, Yogyakarta', '2026-11-25 06:42:02'),
(3, 'Nabila Putri', 'nabila@example.com', '082198765432', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', 'Jl. Kaliurang KM 5, Sleman, Yogyakarta', '2026-11-25 06:42:02');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `captcha_logs`
--
ALTER TABLE `captcha_logs`
  ADD PRIMARY KEY (`id_captcha`);

--
-- Indexes for table `carts`
--
ALTER TABLE `carts`
  ADD PRIMARY KEY (`id_cart`),
  ADD UNIQUE KEY `id_user` (`id_user`);

--
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id_cart_item`),
  ADD KEY `fk_cart_items_cart` (`id_cart`),
  ADD KEY `fk_cart_items_product` (`id_product`),
  ADD KEY `fk_cart_items_variant` (`id_variant`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id_category`),
  ADD UNIQUE KEY `slug_kategori` (`slug_kategori`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id_product`),
  ADD KEY `fk_products_category` (`id_category`);

--
-- Indexes for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD PRIMARY KEY (`id_variant`),
  ADD KEY `fk_variants_product` (`id_product`);

--
-- Indexes for table `rentals`
--
ALTER TABLE `rentals`
  ADD PRIMARY KEY (`id_rental`),
  ADD UNIQUE KEY `kode_transaksi` (`kode_transaksi`),
  ADD KEY `fk_rentals_user` (`id_user`);

--
-- Indexes for table `rental_items`
--
ALTER TABLE `rental_items`
  ADD PRIMARY KEY (`id_rental_item`),
  ADD KEY `fk_rental_items_rental` (`id_rental`),
  ADD KEY `fk_rental_items_product` (`id_product`),
  ADD KEY `fk_rental_items_variant` (`id_variant`);

--
-- Indexes for table `returns`
--
ALTER TABLE `returns`
  ADD PRIMARY KEY (`id_return`),
  ADD UNIQUE KEY `id_rental` (`id_rental`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id_review`),
  ADD KEY `fk_reviews_user` (`id_user`),
  ADD KEY `fk_reviews_product` (`id_product`),
  ADD KEY `fk_reviews_rental` (`id_rental`);

--
-- Indexes for table `site_menus`
--
ALTER TABLE `site_menus`
  ADD PRIMARY KEY (`id_menu`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `captcha_logs`
--
ALTER TABLE `captcha_logs`
  MODIFY `id_captcha` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `carts`
--
ALTER TABLE `carts`
  MODIFY `id_cart` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id_cart_item` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id_category` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id_product` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `product_variants`
--
ALTER TABLE `product_variants`
  MODIFY `id_variant` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `rentals`
--
ALTER TABLE `rentals`
  MODIFY `id_rental` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `rental_items`
--
ALTER TABLE `rental_items`
  MODIFY `id_rental_item` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `returns`
--
ALTER TABLE `returns`
  MODIFY `id_return` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id_review` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `site_menus`
--
ALTER TABLE `site_menus`
  MODIFY `id_menu` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id_user` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `carts`
--
ALTER TABLE `carts`
  ADD CONSTRAINT `fk_carts_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `fk_cart_items_cart` FOREIGN KEY (`id_cart`) REFERENCES `carts` (`id_cart`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cart_items_product` FOREIGN KEY (`id_product`) REFERENCES `products` (`id_product`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cart_items_variant` FOREIGN KEY (`id_variant`) REFERENCES `product_variants` (`id_variant`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_products_category` FOREIGN KEY (`id_category`) REFERENCES `categories` (`id_category`) ON UPDATE CASCADE;

--
-- Constraints for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD CONSTRAINT `fk_variants_product` FOREIGN KEY (`id_product`) REFERENCES `products` (`id_product`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `rentals`
--
ALTER TABLE `rentals`
  ADD CONSTRAINT `fk_rentals_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON UPDATE CASCADE;

--
-- Constraints for table `rental_items`
--
ALTER TABLE `rental_items`
  ADD CONSTRAINT `fk_rental_items_product` FOREIGN KEY (`id_product`) REFERENCES `products` (`id_product`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rental_items_rental` FOREIGN KEY (`id_rental`) REFERENCES `rentals` (`id_rental`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rental_items_variant` FOREIGN KEY (`id_variant`) REFERENCES `product_variants` (`id_variant`) ON UPDATE CASCADE;

--
-- Constraints for table `returns`
--
ALTER TABLE `returns`
  ADD CONSTRAINT `fk_returns_rental` FOREIGN KEY (`id_rental`) REFERENCES `rentals` (`id_rental`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `fk_reviews_product` FOREIGN KEY (`id_product`) REFERENCES `products` (`id_product`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_reviews_rental` FOREIGN KEY (`id_rental`) REFERENCES `rentals` (`id_rental`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_reviews_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
