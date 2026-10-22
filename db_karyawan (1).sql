-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 22, 2026 at 08:50 AM
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
-- Database: `db_karyawan`
--

-- --------------------------------------------------------

--
-- Table structure for table `absensi`
--

CREATE TABLE `absensi` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_karyawan` int(10) UNSIGNED NOT NULL,
  `tanggal` date NOT NULL,
  `jam_masuk` time DEFAULT NULL,
  `jam_pulang` time DEFAULT NULL,
  `status` enum('Hadir','Terlambat','Izin','Sakit') NOT NULL DEFAULT 'Hadir',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `absensi`
--

INSERT INTO `absensi` (`id`, `id_karyawan`, `tanggal`, `jam_masuk`, `jam_pulang`, `status`, `created_at`) VALUES
(1, 5, '2026-10-22', '11:03:05', '11:03:32', 'Hadir', '2026-10-22 04:03:05'),
(2, 6, '2026-10-22', '11:42:12', '11:42:39', 'Hadir', '2026-10-22 04:42:12'),
(30, 6, '2026-10-10', '08:01:00', '17:01:00', 'Terlambat', '2026-10-22 06:37:08'),
(31, 12, '2026-10-10', '08:02:00', '17:02:00', 'Izin', '2026-10-22 06:37:08'),
(32, 5, '2026-10-11', '08:00:00', '17:00:00', 'Sakit', '2026-10-22 06:37:08'),
(33, 12, '2026-10-11', '08:02:00', '17:02:00', 'Terlambat', '2026-10-22 06:37:08'),
(34, 5, '2026-10-12', '08:00:00', '17:00:00', 'Izin', '2026-10-22 06:37:08'),
(35, 6, '2026-10-12', '08:01:00', '17:01:00', 'Sakit', '2026-10-22 06:37:08'),
(36, 6, '2026-10-13', '08:01:00', '17:01:00', 'Izin', '2026-10-22 06:37:08'),
(37, 12, '2026-10-13', '08:02:00', '17:02:00', 'Sakit', '2026-10-22 06:37:08'),
(38, 5, '2026-10-14', '08:00:00', '17:00:00', 'Hadir', '2026-10-22 06:37:08'),
(39, 12, '2026-10-14', '08:02:00', '17:02:00', 'Izin', '2026-10-22 06:37:08'),
(40, 5, '2026-10-15', '08:00:00', '17:00:00', 'Sakit', '2026-10-22 06:37:08'),
(41, 6, '2026-10-15', '08:01:00', '17:01:00', 'Hadir', '2026-10-22 06:37:08'),
(42, 6, '2026-10-16', '08:01:00', '17:01:00', 'Sakit', '2026-10-22 06:37:08'),
(43, 12, '2026-10-16', '08:02:00', '17:02:00', 'Hadir', '2026-10-22 06:37:08'),
(44, 5, '2026-10-17', '08:00:00', '17:00:00', 'Terlambat', '2026-10-22 06:37:08'),
(45, 12, '2026-10-17', '08:02:00', '17:02:00', 'Sakit', '2026-10-22 06:37:08'),
(46, 5, '2026-10-18', '08:00:00', '17:00:00', 'Hadir', '2026-10-22 06:37:08'),
(47, 6, '2026-10-18', '08:01:00', '17:01:00', 'Terlambat', '2026-10-22 06:37:08'),
(48, 6, '2026-10-19', '08:01:00', '17:01:00', 'Hadir', '2026-10-22 06:37:08'),
(49, 12, '2026-10-19', '08:02:00', '17:02:00', 'Terlambat', '2026-10-22 06:37:08'),
(50, 5, '2026-10-20', '08:00:00', '17:00:00', 'Izin', '2026-10-22 06:37:08'),
(51, 12, '2026-10-20', '08:02:00', '17:02:00', 'Hadir', '2026-10-22 06:37:08'),
(52, 5, '2026-10-21', '08:00:00', '17:00:00', 'Terlambat', '2026-10-22 06:37:08'),
(53, 6, '2026-10-21', '08:01:00', '17:01:00', 'Izin', '2026-10-22 06:37:08');

-- --------------------------------------------------------

--
-- Table structure for table `karyawan`
--

CREATE TABLE `karyawan` (
  `id` int(10) UNSIGNED NOT NULL,
  `nik` varchar(30) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `jabatan` varchar(100) NOT NULL,
  `no_hp` varchar(20) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','karyawan') NOT NULL DEFAULT 'karyawan',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `karyawan`
--

INSERT INTO `karyawan` (`id`, `nik`, `nama`, `jabatan`, `no_hp`, `email`, `password`, `role`, `created_at`, `updated_at`) VALUES
(1, '1234567891011', 'Eka Setya Purnama Putra', 'admin', '6287261263', 'test@test.com', '$2y$10$xS9FhCHjx1k65QFlSBY2WOijoXu8aQfzMwVSGM2pqnfSExI5GicsK', 'admin', '2026-10-22 02:41:10', '2026-10-22 02:42:05'),
(4, '23645123123123', 'fifi amelia', 'admin', '08818591746', 'test2@test.com', '$2y$10$2sHWVn0FZkZDKMNtqNSUluQ6rNY04nLNhMpOh5t/L6qMAIuV9z.1C', 'admin', '2026-10-22 03:24:48', '2026-10-22 03:28:46'),
(5, '12735123713', 'eka pur', 'karyawan', '085785677923', 'afifi7678@gmail.com', '$2y$10$a4403Bsq7HTIkJz2nPFpfekhgi8OTgi7NpqArSImy3pueCrwSd/82', 'karyawan', '2026-10-22 03:41:49', '2026-10-22 03:41:49'),
(6, '172534123812', 'Eka Setya Purnama Putra', 'karyawan', '085649565920', 'test3@test.com', '$2y$10$FkA7THRlQwj2UcVmgUN/H.oBSxEDnIIZ39PwJHu3FOTZVFjGyfMEu', 'karyawan', '2026-10-22 04:37:21', '2026-10-22 04:37:21'),
(12, '12312312344', 'eka setya', 'karyawan', '082228158608', 'testing@test.com', '$2y$10$dN3YiDmKgOm/QF5i2dBcteH.qpP.yEjArqUjvqhj6wDBTJW.aHpVi', 'karyawan', '2026-10-22 06:36:13', '2026-10-22 06:36:13');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `absensi`
--
ALTER TABLE `absensi`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_absensi_karyawan_tanggal` (`id_karyawan`,`tanggal`),
  ADD KEY `idx_absensi_tanggal` (`tanggal`),
  ADD KEY `idx_absensi_status_tanggal` (`status`,`tanggal`);

--
-- Indexes for table `karyawan`
--
ALTER TABLE `karyawan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_karyawan_nik` (`nik`),
  ADD UNIQUE KEY `uq_karyawan_email` (`email`),
  ADD KEY `idx_karyawan_role` (`role`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `absensi`
--
ALTER TABLE `absensi`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `karyawan`
--
ALTER TABLE `karyawan`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `absensi`
--
ALTER TABLE `absensi`
  ADD CONSTRAINT `fk_absensi_karyawan` FOREIGN KEY (`id_karyawan`) REFERENCES `karyawan` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
