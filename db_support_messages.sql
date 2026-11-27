-- --------------------------------------------------------
--
-- Run this ONCE on the database `db_camprent` (phpMyAdmin > Import)
-- after importing db_camprent.sql. It adds the table used by support.php.
--
-- Table structure for table `support_messages`
--

CREATE TABLE IF NOT EXISTS `support_messages` (
  `id_message` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_user` int(10) UNSIGNED DEFAULT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `kontak` varchar(100) NOT NULL,
  `topik` varchar(50) NOT NULL,
  `kode_booking` varchar(30) DEFAULT NULL,
  `pesan` text NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_message`),
  KEY `id_user` (`id_user`),
  CONSTRAINT `support_messages_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
