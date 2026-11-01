-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Oct 31, 2026 at 05:41 PM
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
-- Database: `toko_grosir`
--

-- --------------------------------------------------------

--
-- Table structure for table `barang`
--

CREATE TABLE `barang` (
  `id_barang` int(11) NOT NULL,
  `nama_barang` varchar(100) NOT NULL,
  `kategori` varchar(50) DEFAULT NULL,
  `satuan` varchar(20) NOT NULL DEFAULT 'pcs',
  `harga_beli` decimal(12,2) NOT NULL DEFAULT 0.00,
  `harga_jual` decimal(12,2) NOT NULL DEFAULT 0.00,
  `stok` int(11) NOT NULL DEFAULT 0,
  `stok_minimum` int(11) NOT NULL DEFAULT 5
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `barang`
--

INSERT INTO `barang` (`id_barang`, `nama_barang`, `kategori`, `satuan`, `harga_beli`, `harga_jual`, `stok`, `stok_minimum`) VALUES
(1, 'Beras', 'Sembako', 'KG', 11000.00, 12000.00, 100, 25),
(2, 'Beras Ramos 5 Kg', 'Sembako', 'karung', 65000.00, 72000.00, 50, 10),
(3, 'Minyak Goreng 1 Liter', 'Sembako', 'botol', 15000.00, 18000.00, 100, 20),
(4, 'Gula Pasir 1 Kg', 'Sembako', 'kg', 14000.00, 17000.00, 80, 15),
(5, 'Tepung Terigu 1 Kg', 'Sembako', 'kg', 10000.00, 13000.00, 60, 10),
(6, 'Mi Instan', 'Makanan', 'pcs', 2500.00, 3000.00, 200, 30),
(7, 'Kecap Manis 600 ml', 'Bumbu', 'botol', 12000.00, 15000.00, 29, 10),
(8, 'Susu UHT 1 Liter', 'Minuman', 'kotak', 17000.00, 20000.00, 50, 10),
(9, 'Air Mineral 600 ml', 'Minuman', 'botol', 2500.00, 3500.00, 150, 30),
(10, 'Sabun Mandi', 'Perawatan', 'pcs', 3500.00, 5000.00, 100, 20),
(11, 'Deterjen 1 Kg', 'Kebutuhan Rumah Tangga', 'bungkus', 18000.00, 22000.00, 40, 10),
(12, 'Kopi Bubuk 200 Gram', 'Minuman', 'bungkus', 12000.00, 15000.00, 35, 10),
(13, 'Telur Ayam', 'Sembako', 'kg', 27000.00, 32000.00, 25, 10),
(14, 'Sarden Kaleng', 'Makanan', 'kaleng', 11000.00, 14000.00, 45, 10),
(15, 'Kecap Asin 300 ml', 'Bumbu', 'botol', 8000.00, 11000.00, 19, 5),
(16, 'Tisu Wajah', 'Kebutuhan Rumah Tangga', 'bungkus', 7000.00, 10000.00, 60, 10);

-- --------------------------------------------------------

--
-- Table structure for table `barang_keluar`
--

CREATE TABLE `barang_keluar` (
  `id_keluar` int(11) NOT NULL,
  `id_barang` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `jumlah` int(11) NOT NULL,
  `tanggal` datetime DEFAULT current_timestamp(),
  `keterangan` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `barang_keluar`
--

INSERT INTO `barang_keluar` (`id_keluar`, `id_barang`, `id_user`, `jumlah`, `tanggal`, `keterangan`) VALUES
(1, 15, 2, 11, '2026-10-31 22:13:12', 'Kirim ke toko');

-- --------------------------------------------------------

--
-- Table structure for table `barang_masuk`
--

CREATE TABLE `barang_masuk` (
  `id_masuk` int(11) NOT NULL,
  `id_barang` int(11) NOT NULL,
  `id_supplier` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `jumlah` int(11) NOT NULL,
  `tanggal` datetime DEFAULT current_timestamp(),
  `keterangan` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `barang_masuk`
--

INSERT INTO `barang_masuk` (`id_masuk`, `id_barang`, `id_supplier`, `id_user`, `jumlah`, `tanggal`, `keterangan`) VALUES
(1, 1, 1, 2, 50, '2026-10-31 22:09:09', 'Stok awal dari supplier'),
(2, 2, 2, 2, 100, '2026-10-31 22:09:09', 'Pengadaan minyak goreng'),
(3, 3, 3, 2, 80, '2026-10-31 22:09:09', 'Pengadaan gula pasir');

-- --------------------------------------------------------

--
-- Table structure for table `penjualan`
--

CREATE TABLE `penjualan` (
  `id_penjualan` int(11) NOT NULL,
  `id_barang` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `jumlah` int(11) NOT NULL,
  `harga_satuan` decimal(12,2) NOT NULL,
  `total` decimal(12,2) NOT NULL,
  `tanggal` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `penjualan`
--

INSERT INTO `penjualan` (`id_penjualan`, `id_barang`, `id_user`, `jumlah`, `harga_satuan`, `total`, `tanggal`) VALUES
(1, 7, 3, 11, 15000.00, 165000.00, '2026-10-31 22:16:54');

-- --------------------------------------------------------

--
-- Table structure for table `supplier`
--

CREATE TABLE `supplier` (
  `id_supplier` int(11) NOT NULL,
  `nama_supplier` varchar(100) NOT NULL,
  `telepon` varchar(20) DEFAULT NULL,
  `alamat` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `supplier`
--

INSERT INTO `supplier` (`id_supplier`, `nama_supplier`, `telepon`, `alamat`) VALUES
(1, 'Supplier Utama', '081234567890', 'Bandung'),
(2, 'Supplier Cadangan', '081298765432', 'Bandung'),
(3, 'PT Sumber Pangan', '081234567890', 'Bandung'),
(4, 'CV Makmur Jaya', '081298765432', 'Cimahi'),
(5, 'UD Berkah Abadi', '082112223333', 'Sumedang'),
(6, 'PT Distribusi Sejahtera', '085678901234', 'Bandung'),
(7, 'CV Mitra Grosir', '081377788899', 'Garut'),
(8, 'PT Jaya Jaya', '098754321234', 'Cimahi');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id_user` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('owner','gudang','toko') NOT NULL,
  `bahasa` enum('id','en') DEFAULT 'id'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id_user`, `nama`, `username`, `password`, `role`, `bahasa`) VALUES
(1, 'Owner', 'owner', '$2y$10$y.Yi/T2LEmRotQ2LlZGNGeXF18XKKdZXWeSGpvjyuBJaUCvY4uB4O', 'owner', 'en'),
(2, 'Admin Gudang', 'gudang', '$2y$10$FgfarnJYmcCr1DGpVQDWq.KcY67dDDMGEErQhYcI5ehBWSqy1qok2', 'gudang', 'id'),
(3, 'Admin Toko', 'toko', '$2y$10$cEI82CXbPKa1hrbWmCaTGeIdSFr2CWohspRME8WcuQ4wC..g7/U8O', 'toko', 'id');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `barang`
--
ALTER TABLE `barang`
  ADD PRIMARY KEY (`id_barang`);

--
-- Indexes for table `barang_keluar`
--
ALTER TABLE `barang_keluar`
  ADD PRIMARY KEY (`id_keluar`),
  ADD KEY `id_barang` (`id_barang`),
  ADD KEY `id_user` (`id_user`);

--
-- Indexes for table `barang_masuk`
--
ALTER TABLE `barang_masuk`
  ADD PRIMARY KEY (`id_masuk`),
  ADD KEY `id_barang` (`id_barang`),
  ADD KEY `id_supplier` (`id_supplier`),
  ADD KEY `id_user` (`id_user`);

--
-- Indexes for table `penjualan`
--
ALTER TABLE `penjualan`
  ADD PRIMARY KEY (`id_penjualan`),
  ADD KEY `id_barang` (`id_barang`),
  ADD KEY `id_user` (`id_user`);

--
-- Indexes for table `supplier`
--
ALTER TABLE `supplier`
  ADD PRIMARY KEY (`id_supplier`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `barang`
--
ALTER TABLE `barang`
  MODIFY `id_barang` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `barang_keluar`
--
ALTER TABLE `barang_keluar`
  MODIFY `id_keluar` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `barang_masuk`
--
ALTER TABLE `barang_masuk`
  MODIFY `id_masuk` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `penjualan`
--
ALTER TABLE `penjualan`
  MODIFY `id_penjualan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `supplier`
--
ALTER TABLE `supplier`
  MODIFY `id_supplier` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `barang_keluar`
--
ALTER TABLE `barang_keluar`
  ADD CONSTRAINT `barang_keluar_ibfk_1` FOREIGN KEY (`id_barang`) REFERENCES `barang` (`id_barang`),
  ADD CONSTRAINT `barang_keluar_ibfk_2` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`);

--
-- Constraints for table `barang_masuk`
--
ALTER TABLE `barang_masuk`
  ADD CONSTRAINT `barang_masuk_ibfk_1` FOREIGN KEY (`id_barang`) REFERENCES `barang` (`id_barang`),
  ADD CONSTRAINT `barang_masuk_ibfk_2` FOREIGN KEY (`id_supplier`) REFERENCES `supplier` (`id_supplier`),
  ADD CONSTRAINT `barang_masuk_ibfk_3` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`);

--
-- Constraints for table `penjualan`
--
ALTER TABLE `penjualan`
  ADD CONSTRAINT `penjualan_ibfk_1` FOREIGN KEY (`id_barang`) REFERENCES `barang` (`id_barang`),
  ADD CONSTRAINT `penjualan_ibfk_2` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
