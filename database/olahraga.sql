-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 05 Feb 2025 pada 09.37
-- Versi server: 10.4.27-MariaDB
-- Versi PHP: 8.2.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
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
-- Struktur dari tabel `beratbadan`
--

CREATE TABLE `beratbadan` (
  `id_beratbadan` int(11) NOT NULL,
  `berat` decimal(11,0) NOT NULL,
  `tinggi` int(11) NOT NULL,
  `bmi` decimal(11,0) NOT NULL,
  `kategori` varchar(50) NOT NULL,
  `tanggal` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `beratbadan`
--

INSERT INTO `beratbadan` (`id_beratbadan`, `berat`, `tinggi`, `bmi`, `kategori`, `tanggal`) VALUES
(27, '65', 185, '19', 'ideal', '2025-02-05');

-- --------------------------------------------------------

--
-- Struktur dari tabel `hitung_makanan`
--

CREATE TABLE `hitung_makanan` (
  `id_hitung` int(11) NOT NULL,
  `makanan` varchar(50) NOT NULL,
  `berat` decimal(5,2) NOT NULL,
  `kalori` decimal(5,2) NOT NULL,
  `protein` decimal(5,2) NOT NULL,
  `tanggal` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `hitung_makanan`
--

INSERT INTO `hitung_makanan` (`id_hitung`, `makanan`, `berat`, `kalori`, `protein`, `tanggal`) VALUES
(7, 'rendang', '100.00', '166.90', '15.00', '2025-02-05');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `beratbadan`
--
ALTER TABLE `beratbadan`
  ADD PRIMARY KEY (`id_beratbadan`);

--
-- Indeks untuk tabel `hitung_makanan`
--
ALTER TABLE `hitung_makanan`
  ADD PRIMARY KEY (`id_hitung`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `beratbadan`
--
ALTER TABLE `beratbadan`
  MODIFY `id_beratbadan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT untuk tabel `hitung_makanan`
--
ALTER TABLE `hitung_makanan`
  MODIFY `id_hitung` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
