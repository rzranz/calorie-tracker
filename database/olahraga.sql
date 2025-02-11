-- phpMyAdmin SQL Dump
-- version 4.5.1
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Generation Time: Feb 11, 2025 at 02:26 PM
-- Server version: 10.1.9-MariaDB
-- PHP Version: 7.0.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `olahraga`
--

-- --------------------------------------------------------

--
-- Table structure for table `beratbadan`
--

CREATE TABLE `beratbadan` (
  `id_beratbadan` int(11) NOT NULL,
  `berat` decimal(11,0) NOT NULL,
  `tinggi` int(11) NOT NULL,
  `bmi` decimal(11,0) NOT NULL,
  `kategori` varchar(50) NOT NULL,
  `kalori_harian` varchar(50) NOT NULL,
  `tanggal` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `beratbadan`
--

INSERT INTO `beratbadan` (`id_beratbadan`, `berat`, `tinggi`, `bmi`, `kategori`, `kalori_harian`, `tanggal`) VALUES
(36, '94', 182, '28', 'gemuk', '1880', '2025-02-05'),
(37, '80', 170, '28', 'gemuk', '1600', '2025-02-05'),
(38, '70', 180, '22', 'ideal', '2442', '2025-02-05'),
(40, '70', 180, '22', 'ideal', '2442', '2025-02-05'),
(41, '70', 185, '20', 'ideal', '2773', '2025-02-05'),
(42, '70', 165, '26', 'gemuk', '2642', '2025-02-05'),
(44, '80', 182, '24', 'ideal', '2976', '2025-02-09'),
(46, '80', 182, '24', 'ideal', '2632', '2025-02-09'),
(47, '82', 182, '25', 'ideal', '3017', '2025-02-09'),
(48, '70', 165, '26', 'gemuk', '2343', '2025-02-10'),
(49, '80', 170, '28', 'gemuk', '', '2025-02-10'),
(50, '80', 182, '24', 'ideal', '2624', '2025-02-10'),
(51, '90', 182, '27', 'gemuk', '3184', '2025-02-10'),
(52, '90', 182, '27', 'gemuk', '3184', '2025-02-10'),
(53, '80', 170, '28', 'gemuk', '2878', '2025-02-10'),
(54, '60', 175, '20', 'ideal', '2508', '2025-02-10'),
(55, '70', 178, '22', 'ideal', '2756', '2025-02-10'),
(56, '60', 170, '21', 'ideal', '2507', '2025-02-10'),
(57, '80', 170, '28', 'gemuk', '2234', '2025-02-11'),
(58, '80', 178, '25', 'gemuk', '3318', '2025-02-11'),
(59, '80', 182, '24', 'ideal', '3302', '2025-02-11'),
(60, '80', 182, '24', 'ideal', '3302', '2025-02-11'),
(61, '80', 182, '24', 'ideal', '3302', '2025-02-11'),
(62, '80', 182, '24', 'ideal', '3302', '2025-02-11'),
(63, '80', 182, '24', 'ideal', '3302', '2025-02-11'),
(64, '70', 180, '22', 'ideal', '2753', '2025-02-11'),
(65, '80', 180, '25', 'ideal', '2650', '2025-02-11');

-- --------------------------------------------------------

--
-- Table structure for table `hitung_makanan`
--

CREATE TABLE `hitung_makanan` (
  `id_hitung` int(11) NOT NULL,
  `makanan` varchar(50) NOT NULL,
  `berat` decimal(5,2) NOT NULL,
  `kalori` decimal(5,2) NOT NULL,
  `protein` decimal(5,2) NOT NULL,
  `tanggal` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `beratbadan`
--
ALTER TABLE `beratbadan`
  ADD PRIMARY KEY (`id_beratbadan`);

--
-- Indexes for table `hitung_makanan`
--
ALTER TABLE `hitung_makanan`
  ADD PRIMARY KEY (`id_hitung`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `beratbadan`
--
ALTER TABLE `beratbadan`
  MODIFY `id_beratbadan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;
--
-- AUTO_INCREMENT for table `hitung_makanan`
--
ALTER TABLE `hitung_makanan`
  MODIFY `id_hitung` int(11) NOT NULL AUTO_INCREMENT;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
