-- Calorie Tracker / Let's Move Database Schema
-- Compatible with MySQL 5.7+, MariaDB 10+, and SQLite via PDO

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Table structure for table `beratbadan`
--

CREATE TABLE IF NOT EXISTS `beratbadan` (
  `id_beratbadan` int(11) NOT NULL AUTO_INCREMENT,
  `berat` decimal(5,2) NOT NULL,
  `tinggi` int(11) NOT NULL,
  `usia` int(11) DEFAULT 25,
  `gender` varchar(20) DEFAULT 'Laki-laki',
  `aktivitas` varchar(50) DEFAULT 'moderate',
  `bmi` decimal(5,2) NOT NULL,
  `kategori` varchar(50) NOT NULL,
  `kalori_harian` varchar(50) NOT NULL,
  `target_program` varchar(50) DEFAULT 'bulking',
  `tanggal` date NOT NULL,
  PRIMARY KEY (`id_beratbadan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping sample data for table `beratbadan`
--

INSERT INTO `beratbadan` (`id_beratbadan`, `berat`, `tinggi`, `usia`, `gender`, `aktivitas`, `bmi`, `kategori`, `kalori_harian`, `target_program`, `tanggal`) VALUES
(36, 94.00, 182, 28, 'Laki-laki', 'light', 28.00, 'gemuk', '1880', 'diet', '2025-02-05'),
(37, 80.00, 170, 26, 'Laki-laki', 'sedentary', 28.00, 'gemuk', '1600', 'diet', '2025-02-05'),
(38, 70.00, 180, 24, 'Laki-laki', 'moderate', 22.00, 'ideal', '2442', 'bulking', '2025-02-05'),
(41, 70.00, 185, 23, 'Laki-laki', 'active', 20.00, 'ideal', '2773', 'bulking', '2025-02-05'),
(42, 70.00, 165, 25, 'Laki-laki', 'moderate', 26.00, 'gemuk', '2642', 'diet', '2025-02-05'),
(44, 80.00, 182, 25, 'Laki-laki', 'active', 24.00, 'ideal', '2976', 'bulking', '2025-02-09'),
(47, 82.00, 182, 26, 'Laki-laki', 'active', 25.00, 'ideal', '3017', 'bulking', '2025-02-09'),
(58, 80.00, 178, 27, 'Laki-laki', 'very_active', 25.00, 'gemuk', '3318', 'bulking', '2025-02-11'),
(65, 80.00, 180, 25, 'Laki-laki', 'moderate', 25.00, 'ideal', '2650', 'bulking', '2025-02-11');

-- --------------------------------------------------------

--
-- Table structure for table `hitung_makanan`
--

CREATE TABLE IF NOT EXISTS `hitung_makanan` (
  `id_hitung` int(11) NOT NULL AUTO_INCREMENT,
  `makanan` varchar(100) NOT NULL,
  `berat` decimal(6,2) NOT NULL,
  `kalori` decimal(6,2) NOT NULL,
  `protein` decimal(6,2) NOT NULL,
  `karbohidrat` decimal(6,2) DEFAULT 0.00,
  `lemak` decimal(6,2) DEFAULT 0.00,
  `tanggal` date NOT NULL,
  PRIMARY KEY (`id_hitung`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping sample data for table `hitung_makanan`
--

INSERT INTO `hitung_makanan` (`id_hitung`, `makanan`, `berat`, `kalori`, `protein`, `karbohidrat`, `lemak`, `tanggal`) VALUES
(1, 'Dada Ayam Panggang', 150.00, 247.50, 46.50, 0.00, 5.40, '2025-02-11'),
(2, 'Nasi Putih', 200.00, 260.00, 5.40, 56.00, 0.60, '2025-02-11'),
(3, 'Telur Rebus', 100.00, 155.00, 12.60, 1.10, 10.60, '2025-02-11');

-- --------------------------------------------------------

--
-- Table structure for table `olahraga_log`
--

CREATE TABLE IF NOT EXISTS `olahraga_log` (
  `id_olahraga` int(11) NOT NULL AUTO_INCREMENT,
  `nama_olahraga` varchar(100) NOT NULL,
  `durasi_menit` decimal(6,2) DEFAULT 0.00,
  `repetisi` int(11) DEFAULT 0,
  `kalori_terbakar` decimal(6,2) NOT NULL,
  `tanggal` date NOT NULL,
  PRIMARY KEY (`id_olahraga`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping sample data for table `olahraga_log`
--

INSERT INTO `olahraga_log` (`id_olahraga`, `nama_olahraga`, `durasi_menit`, `repetisi`, `kalori_terbakar`, `tanggal`) VALUES
(1, 'Push Up', 0.00, 50, 22.50, '2025-02-11'),
(2, 'Lari (Jogging)', 30.00, 0, 300.00, '2025-02-11');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
