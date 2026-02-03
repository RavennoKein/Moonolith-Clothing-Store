-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Waktu pembuatan: 03 Feb 2026 pada 02.19
-- Versi server: 8.0.30
-- Versi PHP: 8.3.27

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `backend`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `carts`
--

CREATE TABLE `carts` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `tanggal_dibuat` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `cart_items`
--

CREATE TABLE `cart_items` (
  `id` bigint UNSIGNED NOT NULL,
  `cart_id` bigint UNSIGNED NOT NULL,
  `variant_id` bigint UNSIGNED NOT NULL,
  `quantity` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `categories`
--

CREATE TABLE `categories` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `categories`
--

INSERT INTO `categories` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'Celana', '2026-02-03 00:41:27', '2026-02-03 00:41:27'),
(2, 'Kaos', '2026-02-03 00:41:35', '2026-02-03 00:41:35'),
(3, 'Kemeja', '2026-02-03 00:41:45', '2026-02-03 00:41:45'),
(4, 'Blouse', '2026-02-03 00:42:01', '2026-02-03 00:42:01'),
(5, 'Jaket', '2026-02-03 00:42:08', '2026-02-03 00:42:08');

-- --------------------------------------------------------

--
-- Struktur dari tabel `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `favorites`
--

CREATE TABLE `favorites` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `item_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `favorites`
--

INSERT INTO `favorites` (`id`, `user_id`, `item_id`, `created_at`, `updated_at`) VALUES
(1, 3, 12, '2026-02-03 02:11:41', '2026-02-03 02:11:41'),
(2, 3, 3, '2026-02-03 02:12:30', '2026-02-03 02:12:30');

-- --------------------------------------------------------

--
-- Struktur dari tabel `flash_sales`
--

CREATE TABLE `flash_sales` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `status` enum('active','inactive','scheduled') COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `flash_sales`
--

INSERT INTO `flash_sales` (`id`, `name`, `description`, `start_time`, `end_time`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Promo Hari Valentine', 'Promo di awal februari yang dibalut dengan nuansa menjelang hari valentine. Nikmati Diskon besar-besaran saat ini juga', '2026-02-03 09:14:00', '2026-02-15 09:14:00', 'active', '2026-02-03 02:17:06', '2026-02-03 02:18:00');

-- --------------------------------------------------------

--
-- Struktur dari tabel `flash_sale_items`
--

CREATE TABLE `flash_sale_items` (
  `id` bigint UNSIGNED NOT NULL,
  `flash_sale_id` bigint UNSIGNED NOT NULL,
  `item_id` bigint UNSIGNED NOT NULL,
  `discount_price` decimal(15,2) NOT NULL,
  `flash_sale_stock` int NOT NULL,
  `sold_count` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `flash_sale_items`
--

INSERT INTO `flash_sale_items` (`id`, `flash_sale_id`, `item_id`, `discount_price`, `flash_sale_stock`, `sold_count`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 50000.00, 20, 0, '2026-02-03 02:17:06', '2026-02-03 02:17:06'),
(2, 1, 6, 30000.00, 5, 0, '2026-02-03 02:17:06', '2026-02-03 02:17:06'),
(3, 1, 5, 100000.00, 10, 0, '2026-02-03 02:17:06', '2026-02-03 02:17:06');

-- --------------------------------------------------------

--
-- Struktur dari tabel `items`
--

CREATE TABLE `items` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `price` decimal(15,2) NOT NULL,
  `bahan` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tingkat_ketebalan` enum('tipis','sedang','tebal') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status_produk` enum('aktif','non_aktif','terbatas') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'aktif',
  `gender` enum('men','women','kids','unisex') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unisex',
  `image_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `items`
--

INSERT INTO `items` (`id`, `name`, `description`, `price`, `bahan`, `tingkat_ketebalan`, `status_produk`, `gender`, `image_url`, `category_id`, `created_at`, `updated_at`) VALUES
(1, 'Celana Jeans Premium', 'Celana jeans dengan bahan premium yang pasti akan muat di segala ukuran tanpa membuat sesak pengguna', 120000.00, 'Tekstil Katun', 'sedang', 'aktif', 'men', 'items/tMw7NjBqqpE7oYSvHblCAhPYLhqCuWimcliemlMZ.jpg', 1, '2026-02-03 00:44:46', '2026-02-03 00:44:46'),
(2, 'Kaos Thrasher', 'Tampil edgy dan autentik dengan Kaos Thrasher. Pilihan wajib bagi pecinta kultur skate dan streetwear. Terbuat dari bahan Cotton Combed yang tebal namun tetap adem, kaos ini menampilkan logo ikonik Thrasher yang bold. Potongan regular fit-nya pas di badan, memberikan kenyamanan maksimal untuk aktivitas harian atau sesi skating', 50000.00, 'Katun Combed', 'tebal', 'aktif', 'men', 'items/W6X7wltbWV2qHrQn5x5WFk3nb0BZbDr3is61luif.jpg', 2, '2026-02-03 01:13:59', '2026-02-03 01:13:59'),
(3, 'Kaos Tree Spirit', 'Bawa ketenangan alam ke dalam gaya harianmu dengan Kaos Tree Spirit. Desain grafis bertema natural/pohon yang filosofis memberikan kesan santai namun tetap berkarakter. Sangat cocok dipadukan dengan celana jeans atau kargo untuk tampilan casual artsy.', 70000.00, 'Katun Carded', 'sedang', 'terbatas', 'men', 'items/DED64lQPT28FSCArYKTi03R1KW3D36315xh8Lnw6.jpg', 2, '2026-02-03 01:15:51', '2026-02-03 01:15:51'),
(4, 'Kaos Behosmo', 'Kaos Behosmo hadir untuk Anda yang ingin tampil beda dengan vibe urban yang kental. Dengan desain tipografi/grafis yang fresh dan trendi, kaos ini menjadi statement piece untuk OOTD santai Anda. Bahannya menyerap keringat dengan baik, cocok untuk iklim tropis.', 100000.00, 'Cotton Bamboo', 'tipis', 'non_aktif', 'men', 'items/ZFcwkkh4bhyIc5psCNfQgwGgwcvP49RTnwzzNU78.jpg', 2, '2026-02-03 01:17:46', '2026-02-03 01:17:46'),
(5, 'Celana Kulot Wanita', 'Tampil chic tanpa mengorbankan kenyamanan dengan Celana Kulot andalan kami. Potongan high-waist dan lebar ke bawah memberikan ilusi kaki yang lebih jenjang dan ramping. Menggunakan bahan [sebutkan bahan, misal: Scuba/Linen] yang jatuh (flowy) dan tidak menerawang. Cocok untuk outfit ke kantor maupun hangout.', 150000.00, 'Polyester', 'tebal', 'aktif', 'women', 'items/lW2obkGQ8xUWnhxR6s1rVwqAnJ9L9tZbqsWww8Xq.jpg', 1, '2026-02-03 01:19:49', '2026-02-03 01:19:49'),
(6, 'Kemeja Wanita', 'Definisi effortless beauty. Kemeja Wanita ini didesain dengan potongan modern yang rapi namun tetap rileks. Bisa dipakai sebagai atasan formal untuk meeting, atau dijadikan outer santai di akhir pekan. Bahannya ringan, mudah disetrika, dan tidak panas.', 80000.00, 'Linen', 'sedang', 'aktif', 'women', 'items/jXHJt8i12tnOE7WbwHGUJ3SaXD3v36GRtutx7PZK.jpg', 3, '2026-02-03 01:23:53', '2026-02-03 01:23:53'),
(7, 'Blouse Wanita', 'Tingkatkan rasa percaya diri Anda dengan Blouse Wanita yang manis ini. Aksen cerah memberikan sentuhan feminin yang elegan. Terbuat dari bahan premium yang lembut dan jatuh cantik di badan. Pasangan sempurna untuk rok midi atau celana bahan favorit Anda.', 200000.00, 'Rayon', 'sedang', 'terbatas', 'women', 'items/aKryEM9qasHXBqcNUURAY47W7fpmSAugGMOP5SHI.jpg', 4, '2026-02-03 01:26:39', '2026-02-03 01:26:39'),
(8, 'Kemeja Pria', 'Buat impresi terbaik di setiap kesempatan dengan Kemeja Pria premium ini. Potongan Modern Fit membuat postur tubuh terlihat lebih tegap dan rapi. Cocok untuk outfit kerja, kondangan, atau kencan malam minggu. Bahannya halus dan memberikan sirkulasi udara yang baik, sehingga Anda tetap nyaman seharian.', 230000.00, 'Katun', 'tebal', 'terbatas', 'men', 'items/a6vL5KEMy9nuKP1lKCbmBcy3IXoeY7CeQqZddJXU.jpg', 3, '2026-02-03 01:28:16', '2026-02-03 01:28:16'),
(9, 'Kaos Anak Space', 'Dukung imajinasi si Kecil dengan Kaos Anak Space. Gambar roket, astronaut, dan planet yang colorful pasti jadi favorit anak-anak! Menggunakan bahan katun 100% yang sangat lembut dan menyerap keringat, aman untuk kulit anak yang sensitif dan aktif bergerak.', 40000.00, 'Katun', 'sedang', 'aktif', 'kids', 'items/zLL3nqNddRtQ1B8jONFuEMXTXH74rnPNJvmFxu2J.jpg', 2, '2026-02-03 01:30:07', '2026-02-03 01:30:07'),
(10, 'Kemeja Anak', 'Ubah si Kecil jadi little gentleman dengan Kemeja Anak yang stylish. Desainnya simpel tapi keren, cocok untuk acara ulang tahun, jalan-jalan ke mall, atau acara keluarga. Bahannya ringan dan tidak kaku, sehingga anak tetap bebas bermain tanpa merasa gerah.', 80000.00, 'Linen', 'tebal', 'aktif', 'kids', 'items/zu5d0OvrloC1vvM91jcWupl4G6JYB0qB5sFmVAX5.jpg', 3, '2026-02-03 01:32:34', '2026-02-03 01:32:34'),
(11, 'Jaket Winter Wanita', 'Siap hadapi cuaca dingin atau liburan ke luar negeri dengan Jaket Winter Wanita. Dilengkapi lapisan padding tebal yang mampu menahan suhu rendah dan angin (windproof). Meski tebal, potongannya tetap didesain fashionable agar tidak terlihat menggembung berlebihan.', 250000.00, 'Wol', 'tebal', 'terbatas', 'women', 'items/s9yS8R2CWiWi7k3iujP7wSoHOrYT2s3ykuqNX0ZE.jpg', 5, '2026-02-03 01:34:34', '2026-02-03 01:34:34'),
(12, 'Jaket Bomber Pria', 'Lengkapi gaya street style Anda dengan Jaket Bomber Pria. Model klasik yang tak lekang oleh waktu, cocok untuk pengendara motor atau sekadar pelengkap gaya kasual. Bahan luar parasut tebal melindungi dari angin, sementara lapisan dalam (furing) menjaga suhu tubuh tetap stabil.', 400000.00, 'Taslan', 'tebal', 'terbatas', 'men', 'items/YzErwDqM143dHotNCn1S67inhKq7gLJWyZcWa9QB.jpg', 5, '2026-02-03 01:37:26', '2026-02-03 01:37:26'),
(13, 'Blouse Batik', 'Percantik gaya ke kantor Anda dengan Blouse Batik berpotongan modern ini. Memadukan motif batik [sebutkan motif: Parang/Floral/Abstrak] yang klasik dengan desain kerah dan lengan yang rapi, memberikan kesan wibawa namun tetap modis. Terbuat dari bahan Katun Prima yang menyerap keringat, menjamin Anda tetap nyaman meski sibuk seharian dari meeting pagi hingga lembur sore.', 120000.00, 'Rayon', 'sedang', 'terbatas', 'women', 'items/Dfb9LFGmRc2NwA2U2V0DoGqkpWjy2oV6B6w7ppoY.jpg', 4, '2026-02-03 01:53:13', '2026-02-03 01:53:13');

-- --------------------------------------------------------

--
-- Struktur dari tabel `item_variants`
--

CREATE TABLE `item_variants` (
  `id` bigint UNSIGNED NOT NULL,
  `item_id` bigint UNSIGNED NOT NULL,
  `color` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `size` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `stock` int UNSIGNED NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `item_variants`
--

INSERT INTO `item_variants` (`id`, `item_id`, `color`, `size`, `stock`, `created_at`, `updated_at`) VALUES
(1, 1, 'white', 'S', 20, '2026-02-03 00:44:46', '2026-02-03 00:44:46'),
(2, 1, 'pink', 'S', 5, '2026-02-03 00:44:46', '2026-02-03 00:44:46'),
(3, 1, 'red', 'S', 15, '2026-02-03 00:44:46', '2026-02-03 00:44:46'),
(4, 1, 'black', 'M', 10, '2026-02-03 00:44:46', '2026-02-03 00:44:46'),
(5, 1, 'red', 'M', 20, '2026-02-03 00:44:46', '2026-02-03 00:44:46'),
(6, 1, 'blue', 'L', 5, '2026-02-03 00:44:46', '2026-02-03 00:44:46'),
(7, 1, 'maroon', 'L', 5, '2026-02-03 00:44:46', '2026-02-03 00:44:46'),
(8, 2, 'navy', 'S', 20, '2026-02-03 01:13:59', '2026-02-03 01:13:59'),
(9, 2, 'khaki', 'S', 20, '2026-02-03 01:13:59', '2026-02-03 01:13:59'),
(10, 2, 'blue', 'S', 20, '2026-02-03 01:13:59', '2026-02-03 01:13:59'),
(11, 2, 'black', 'M', 15, '2026-02-03 01:13:59', '2026-02-03 01:13:59'),
(12, 2, 'maroon', 'M', 5, '2026-02-03 01:13:59', '2026-02-03 01:13:59'),
(13, 2, 'brown', 'M', 12, '2026-02-03 01:13:59', '2026-02-03 01:13:59'),
(14, 2, 'khaki', 'L', 4, '2026-02-03 01:13:59', '2026-02-03 01:13:59'),
(15, 2, 'army', 'L', 0, '2026-02-03 01:13:59', '2026-02-03 01:13:59'),
(16, 2, 'purple', 'XL', 10, '2026-02-03 01:13:59', '2026-02-03 01:13:59'),
(17, 3, 'white', 'S', 20, '2026-02-03 01:15:51', '2026-02-03 01:15:51'),
(18, 3, 'black', 'S', 10, '2026-02-03 01:15:51', '2026-02-03 01:15:51'),
(19, 3, 'navy', 'M', 12, '2026-02-03 01:15:51', '2026-02-03 01:15:51'),
(20, 3, 'green', 'M', 3, '2026-02-03 01:15:51', '2026-02-03 01:15:51'),
(21, 4, 'white', 'S', 20, '2026-02-03 01:17:46', '2026-02-03 01:17:46'),
(22, 4, 'cream', 'S', 100, '2026-02-03 01:17:46', '2026-02-03 01:17:46'),
(23, 4, 'blue', 'S', 60, '2026-02-03 01:17:46', '2026-02-03 01:17:46'),
(24, 4, 'maroon', 'XL', 20, '2026-02-03 01:17:46', '2026-02-03 01:17:46'),
(25, 4, 'brown', 'XL', 20, '2026-02-03 01:17:46', '2026-02-03 01:17:46'),
(26, 5, 'blue', 'S', 10, '2026-02-03 01:19:49', '2026-02-03 01:19:49'),
(27, 5, 'white', 'L', 10, '2026-02-03 01:19:49', '2026-02-03 01:19:49'),
(28, 5, 'blue', 'L', 20, '2026-02-03 01:19:49', '2026-02-03 01:19:49'),
(29, 5, 'green', 'XL', 20, '2026-02-03 01:19:49', '2026-02-03 01:19:49'),
(30, 6, 'orange', 'S', 40, '2026-02-03 01:23:53', '2026-02-03 01:23:53'),
(31, 6, 'khaki', 'S', 10, '2026-02-03 01:23:53', '2026-02-03 01:23:53'),
(32, 6, 'blue', 'S', 20, '2026-02-03 01:23:53', '2026-02-03 01:23:53'),
(33, 6, 'blue', 'M', 10, '2026-02-03 01:23:53', '2026-02-03 01:23:53'),
(34, 6, 'brown', 'M', 20, '2026-02-03 01:23:53', '2026-02-03 01:23:53'),
(35, 6, 'brown', 'XL', 5, '2026-02-03 01:23:53', '2026-02-03 01:23:53'),
(36, 6, 'maroon', 'XL', 5, '2026-02-03 01:23:53', '2026-02-03 01:23:53'),
(37, 6, 'blue', 'XL', 10, '2026-02-03 01:23:53', '2026-02-03 01:23:53'),
(38, 6, 'white', 'L', 5, '2026-02-03 01:23:53', '2026-02-03 01:23:53'),
(39, 7, 'white', 'S', 20, '2026-02-03 01:26:39', '2026-02-03 01:26:39'),
(40, 7, 'blue', 'S', 10, '2026-02-03 01:26:39', '2026-02-03 01:26:39'),
(41, 8, 'khaki', 'S', 20, '2026-02-03 01:28:16', '2026-02-03 01:28:16'),
(42, 8, 'blue', 'S', 10, '2026-02-03 01:28:16', '2026-02-03 01:28:16'),
(43, 8, 'khaki', 'M', 10, '2026-02-03 01:28:16', '2026-02-03 01:28:16'),
(44, 8, 'black', 'M', 20, '2026-02-03 01:28:16', '2026-02-03 01:28:16'),
(45, 8, 'khaki', 'L', 20, '2026-02-03 01:28:16', '2026-02-03 01:28:16'),
(46, 8, 'khaki', 'XL', 5, '2026-02-03 01:28:16', '2026-02-03 01:28:16'),
(47, 8, 'maroon', 'XL', 15, '2026-02-03 01:28:16', '2026-02-03 01:28:16'),
(48, 9, 'white', 'S', 10, '2026-02-03 01:30:07', '2026-02-03 01:30:07'),
(49, 9, 'green', 'S', 20, '2026-02-03 01:30:07', '2026-02-03 01:30:07'),
(50, 9, 'black', 'M', 10, '2026-02-03 01:30:07', '2026-02-03 01:30:07'),
(51, 9, 'blue', 'M', 20, '2026-02-03 01:30:07', '2026-02-03 01:30:07'),
(52, 9, 'maroon', 'L', 10, '2026-02-03 01:30:07', '2026-02-03 01:30:07'),
(53, 9, 'black', 'XL', 10, '2026-02-03 01:30:07', '2026-02-03 01:30:07'),
(54, 10, 'white', 'S', 20, '2026-02-03 01:32:34', '2026-02-03 01:32:34'),
(55, 10, 'blue', 'S', 10, '2026-02-03 01:32:34', '2026-02-03 01:32:34'),
(56, 10, 'navy', 'S', 20, '2026-02-03 01:32:34', '2026-02-03 01:32:34'),
(57, 10, 'white', 'M', 20, '2026-02-03 01:32:34', '2026-02-03 01:32:34'),
(58, 10, 'blue', 'M', 20, '2026-02-03 01:32:34', '2026-02-03 01:32:34'),
(59, 10, 'black', 'L', 20, '2026-02-03 01:32:34', '2026-02-03 01:32:34'),
(60, 10, 'maroon', 'L', 20, '2026-02-03 01:32:34', '2026-02-03 01:32:34'),
(61, 10, 'blue', 'XL', 10, '2026-02-03 01:32:34', '2026-02-03 01:32:34'),
(62, 11, 'khaki', 'S', 10, '2026-02-03 01:34:34', '2026-02-03 01:34:34'),
(63, 11, 'blue', 'S', 20, '2026-02-03 01:34:34', '2026-02-03 01:34:34'),
(64, 11, 'blue', 'M', 20, '2026-02-03 01:34:34', '2026-02-03 01:34:34'),
(65, 11, 'khaki', 'M', 10, '2026-02-03 01:34:34', '2026-02-03 01:34:34'),
(66, 11, 'khaki', 'L', 10, '2026-02-03 01:34:34', '2026-02-03 01:34:34'),
(67, 11, 'pink', 'L', 20, '2026-02-03 01:34:34', '2026-02-03 01:34:34'),
(68, 11, 'pink', 'XL', 10, '2026-02-03 01:34:34', '2026-02-03 01:34:34'),
(69, 12, 'black', 'S', 20, '2026-02-03 01:37:26', '2026-02-03 01:37:26'),
(70, 12, 'black', 'L', 10, '2026-02-03 01:37:26', '2026-02-03 01:37:26'),
(71, 12, 'black', 'M', 80, '2026-02-03 01:37:26', '2026-02-03 01:37:26'),
(72, 12, 'red', 'M', 20, '2026-02-03 01:37:26', '2026-02-03 01:37:26'),
(73, 12, 'maroon', 'XL', 10, '2026-02-03 01:37:26', '2026-02-03 01:37:26'),
(74, 12, 'blue', 'XL', 20, '2026-02-03 01:37:26', '2026-02-03 01:37:26'),
(75, 13, 'blue', 'S', 0, '2026-02-03 01:53:13', '2026-02-03 01:53:13');

-- --------------------------------------------------------

--
-- Struktur dari tabel `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint UNSIGNED NOT NULL,
  `reserved_at` int UNSIGNED DEFAULT NULL,
  `available_at` int UNSIGNED NOT NULL,
  `created_at` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `migrations`
--

CREATE TABLE `migrations` (
  `id` int UNSIGNED NOT NULL,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2025_12_07_031209_create_personal_access_tokens_table', 1),
(5, '2025_12_08_172122_create_orders_table', 1),
(6, '2025_12_08_172318_create_categories_table', 1),
(7, '2025_12_08_172337_create_items_table', 1),
(8, '2025_12_08_172354_create_item_variants_table', 1),
(9, '2025_12_08_173304_create_payments_table', 1),
(10, '2025_12_09_172243_create_order_items_table', 1),
(11, '2026_01_05_023559_create_flash_sales_table', 1),
(12, '2026_01_05_023608_create_flash_sale_items_table', 1),
(13, '2026_01_05_023628_create_carts_table', 1),
(14, '2026_01_05_023634_create_cart_items_table', 1),
(15, '2026_01_17_142211_create_favorites_table', 1),
(16, '2026_01_18_140101_create_shipping_rates', 1);

-- --------------------------------------------------------

--
-- Struktur dari tabel `orders`
--

CREATE TABLE `orders` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `invoice` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_amount` decimal(15,2) NOT NULL,
  `status` enum('pending','processing','delivered','done','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `receiver_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `receiver_phone` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `city` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `province` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `postal_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `shipping_cost` int NOT NULL,
  `snap_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `order_items`
--

CREATE TABLE `order_items` (
  `id` bigint UNSIGNED NOT NULL,
  `order_id` bigint UNSIGNED NOT NULL,
  `item_id` bigint UNSIGNED NOT NULL,
  `variant_id` bigint UNSIGNED NOT NULL,
  `quantity` int NOT NULL,
  `price` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `payments`
--

CREATE TABLE `payments` (
  `id` bigint UNSIGNED NOT NULL,
  `order_id` bigint UNSIGNED NOT NULL,
  `payment_gateway` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `transaction_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_method` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `payment_status` enum('pending','paid','expired','cancelled','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `payment_url` text COLLATE utf8mb4_unicode_ci,
  `raw_response` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint UNSIGNED NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `personal_access_tokens`
--

INSERT INTO `personal_access_tokens` (`id`, `tokenable_type`, `tokenable_id`, `name`, `token`, `abilities`, `last_used_at`, `expires_at`, `created_at`, `updated_at`) VALUES
(5, 'App\\Models\\User', 3, 'auth_token', '22030c5576ff136929efdba52f27956fa74c34417c96cd242bb51a546a4135ba', '[\"*\"]', '2026-02-03 02:18:44', NULL, '2026-02-03 02:18:42', '2026-02-03 02:18:44');

-- --------------------------------------------------------

--
-- Struktur dari tabel `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `shipping_rates`
--

CREATE TABLE `shipping_rates` (
  `id` bigint UNSIGNED NOT NULL,
  `origin_city` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `destination_city` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cost` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `shipping_rates`
--

INSERT INTO `shipping_rates` (`id`, `origin_city`, `destination_city`, `cost`) VALUES
(1, 'Mojokerto', 'Surabaya', 20000),
(2, 'Mojokerto', 'Malang', 18000),
(3, 'Mojokerto', 'Kediri', 18000),
(4, 'Mojokerto', 'Blitar', 18000),
(5, 'Mojokerto', 'Mojokerto', 10000),
(6, 'Mojokerto', 'Madiun', 20000),
(7, 'Mojokerto', 'Pasuruan', 15000),
(8, 'Mojokerto', 'Probolinggo', 20000),
(9, 'Mojokerto', 'Batu', 18000),
(10, 'Mojokerto', 'Kabupaten Malang', 18000),
(11, 'Mojokerto', 'Kabupaten Kediri', 18000),
(12, 'Mojokerto', 'Kabupaten Blitar', 18000),
(13, 'Mojokerto', 'Kabupaten Mojokerto', 12000),
(14, 'Mojokerto', 'Kabupaten Sidoarjo', 15000),
(15, 'Mojokerto', 'Kabupaten Gresik', 15000),
(16, 'Mojokerto', 'Kabupaten Jombang', 17000),
(17, 'Mojokerto', 'Kabupaten Nganjuk', 18000),
(18, 'Mojokerto', 'Kabupaten Lamongan', 17000),
(19, 'Mojokerto', 'Kabupaten Tuban', 20000),
(20, 'Mojokerto', 'Kabupaten Bojonegoro', 20000),
(21, 'Mojokerto', 'Kabupaten Madiun', 20000),
(22, 'Mojokerto', 'Kabupaten Ponorogo', 22000),
(23, 'Mojokerto', 'Kabupaten Trenggalek', 22000),
(24, 'Mojokerto', 'Kabupaten Tulungagung', 20000),
(25, 'Mojokerto', 'Kabupaten Banyuwangi', 30000),
(26, 'Mojokerto', 'Kabupaten Situbondo', 28000),
(27, 'Mojokerto', 'Kabupaten Bondowoso', 26000),
(28, 'Mojokerto', 'Kabupaten Jember', 26000),
(29, 'Mojokerto', 'Kabupaten Lumajang', 24000),
(30, 'Mojokerto', 'Kabupaten Probolinggo', 24000),
(31, 'Mojokerto', 'Kabupaten Pasuruan', 17000),
(32, 'Mojokerto', 'Kabupaten Bangkalan', 25000),
(33, 'Mojokerto', 'Kabupaten Sampang', 28000),
(34, 'Mojokerto', 'Kabupaten Pamekasan', 28000),
(35, 'Mojokerto', 'Kabupaten Sumenep', 30000);

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` enum('admin','user','super') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'user',
  `address` text COLLATE utf8mb4_unicode_ci,
  `city` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `province` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `postal_code` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `phone_number`, `role`, `address`, `city`, `province`, `postal_code`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Super Admin', 'superadmin@moonolith.com', NULL, '$2y$12$I7ZYaEVei2mjoxzE5YeMB.ezZLFcye6z0/7mv/FRMOGosIuaMbCou', '080000000000', 'super', 'Moonolith HQ', NULL, NULL, NULL, NULL, '2026-02-03 00:39:43', '2026-02-03 00:39:43'),
(2, 'Ivan', 'ivan@gmail.com', NULL, '$2y$12$mysrKxnzvciHrvbUoztdW.8pyjj63UCse2PcO41PW4MVb1UxG4oZm', '0895808008100', 'admin', 'Jl. Pulorejo VI/11', NULL, NULL, NULL, NULL, '2026-02-03 01:56:38', '2026-02-03 01:58:39'),
(3, 'Ramanda Ivan Hendiyatno', 'ramanda@gmail.com', NULL, '$2y$12$7tF/YhA/IyUeMsnEN5hcp.OtQYnghLAJ1OQV5updroNY8YoMw9yJS', '0895803299415', 'user', 'Jl. Pulorejo VI/11', NULL, NULL, NULL, NULL, '2026-02-03 02:00:13', '2026-02-03 02:00:13');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`);

--
-- Indeks untuk tabel `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`);

--
-- Indeks untuk tabel `carts`
--
ALTER TABLE `carts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `carts_user_id_foreign` (`user_id`);

--
-- Indeks untuk tabel `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cart_items_cart_id_foreign` (`cart_id`),
  ADD KEY `cart_items_variant_id_foreign` (`variant_id`);

--
-- Indeks untuk tabel `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `categories_name_unique` (`name`);

--
-- Indeks untuk tabel `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indeks untuk tabel `favorites`
--
ALTER TABLE `favorites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `favorites_user_id_item_id_unique` (`user_id`,`item_id`),
  ADD KEY `favorites_item_id_foreign` (`item_id`);

--
-- Indeks untuk tabel `flash_sales`
--
ALTER TABLE `flash_sales`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `flash_sale_items`
--
ALTER TABLE `flash_sale_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `flash_sale_items_flash_sale_id_foreign` (`flash_sale_id`),
  ADD KEY `flash_sale_items_item_id_foreign` (`item_id`);

--
-- Indeks untuk tabel `items`
--
ALTER TABLE `items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `items_category_id_foreign` (`category_id`);

--
-- Indeks untuk tabel `item_variants`
--
ALTER TABLE `item_variants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `item_variants_item_id_color_size_unique` (`item_id`,`color`,`size`);

--
-- Indeks untuk tabel `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indeks untuk tabel `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `orders_invoice_unique` (`invoice`),
  ADD KEY `orders_user_id_foreign` (`user_id`);

--
-- Indeks untuk tabel `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_items_order_id_foreign` (`order_id`),
  ADD KEY `order_items_item_id_foreign` (`item_id`),
  ADD KEY `order_items_variant_id_foreign` (`variant_id`);

--
-- Indeks untuk tabel `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indeks untuk tabel `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payments_transaction_id_unique` (`transaction_id`),
  ADD KEY `payments_order_id_foreign` (`order_id`);

--
-- Indeks untuk tabel `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  ADD KEY `personal_access_tokens_expires_at_index` (`expires_at`);

--
-- Indeks untuk tabel `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indeks untuk tabel `shipping_rates`
--
ALTER TABLE `shipping_rates`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `carts`
--
ALTER TABLE `carts`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `categories`
--
ALTER TABLE `categories`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `favorites`
--
ALTER TABLE `favorites`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `flash_sales`
--
ALTER TABLE `flash_sales`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `flash_sale_items`
--
ALTER TABLE `flash_sale_items`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `items`
--
ALTER TABLE `items`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT untuk tabel `item_variants`
--
ALTER TABLE `item_variants`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;

--
-- AUTO_INCREMENT untuk tabel `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT untuk tabel `orders`
--
ALTER TABLE `orders`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `payments`
--
ALTER TABLE `payments`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `shipping_rates`
--
ALTER TABLE `shipping_rates`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `carts`
--
ALTER TABLE `carts`
  ADD CONSTRAINT `carts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `cart_items_cart_id_foreign` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_items_variant_id_foreign` FOREIGN KEY (`variant_id`) REFERENCES `item_variants` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `favorites`
--
ALTER TABLE `favorites`
  ADD CONSTRAINT `favorites_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `favorites_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `flash_sale_items`
--
ALTER TABLE `flash_sale_items`
  ADD CONSTRAINT `flash_sale_items_flash_sale_id_foreign` FOREIGN KEY (`flash_sale_id`) REFERENCES `flash_sales` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `flash_sale_items_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `items`
--
ALTER TABLE `items`
  ADD CONSTRAINT `items_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `item_variants`
--
ALTER TABLE `item_variants`
  ADD CONSTRAINT `item_variants_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`),
  ADD CONSTRAINT `order_items_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_variant_id_foreign` FOREIGN KEY (`variant_id`) REFERENCES `item_variants` (`id`);

--
-- Ketidakleluasaan untuk tabel `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
