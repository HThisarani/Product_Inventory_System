-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 07, 2025 at 08:15 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `inventory_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `created_at`) VALUES
(1, 'Electronics', '2025-12-06 01:19:54'),
(2, 'Computers & Accessories', '2025-12-06 01:19:54'),
(3, 'Mobile Phones & Tablets', '2025-12-06 01:19:54'),
(4, 'Clothing & Apparel', '2025-12-06 01:19:54'),
(5, 'Footwear', '2025-12-06 01:19:54'),
(6, 'Food & Beverages', '2025-12-06 01:19:54'),
(7, 'Health & Beauty', '2025-12-06 01:19:54'),
(8, 'Home & Kitchen', '2025-12-06 01:19:54'),
(9, 'Furniture', '2025-12-06 01:19:54'),
(10, 'Books & Stationery', '2025-12-06 01:19:54'),
(11, 'Sports & Fitness', '2025-12-06 01:19:54'),
(12, 'Toys & Games', '2025-12-06 01:19:54'),
(13, 'Automotive', '2025-12-06 01:19:54'),
(14, 'Tools & Hardware', '2025-12-06 01:19:54'),
(15, 'Office Supplies', '2025-12-06 01:19:54'),
(16, 'Pet Supplies', '2025-12-06 01:19:54'),
(17, 'Jewelry & Accessories', '2025-12-06 01:19:54'),
(18, 'Baby Products', '2025-12-06 01:19:54'),
(19, 'Garden & Outdoor', '2025-12-06 01:19:54'),
(20, 'Musical Instruments', '2025-12-06 01:19:54'),
(21, 'Bags & Accessories', '2025-12-06 23:35:28');

-- --------------------------------------------------------

--
-- Table structure for table `items`
--

CREATE TABLE `items` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `stock` int(11) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `items`
--

INSERT INTO `items` (`id`, `name`, `price`, `stock`, `image`, `category`, `created_by`, `created_at`) VALUES
(14, 'Phaoullzon Kawaii Backpack', 3000.00, 15, 'img_69349177714b56.60869576.jfif', 'Bags & Accessories', 1, '2025-12-07 01:56:31'),
(15, 'ALO Recovery Mode Sneaker', 10000.00, 8, 'img_69349250066505.98575887.jfif,img_6934925006cc36.85323313.jfif', 'Footwear', 1, '2025-12-07 02:00:08'),
(16, 'AROMA® Digital Rice Cooker', 12000.00, 5, 'img_693492bec5c341.88253288.jfif', 'Home & Kitchen', 1, '2025-12-07 02:01:58'),
(17, 'FRED Paris', 20000.00, 2, 'img_6934938d4cf2a8.88356352.jfif', 'Jewelry & Accessories', 1, '2025-12-07 02:05:25'),
(18, 'Xiaoyu Fashion Purses', 3500.00, 5, 'img_69349426914cf1.90231485.jfif', 'Bags & Accessories', 1, '2025-12-07 02:07:58'),
(19, 'iPhone 13 pro max 256GB', 30000.00, 5, 'img_6934975ce6dbf1.44481660.jfif,img_6934975ce724d5.49019928.jfif,img_6934975ce76499.89014621.jfif,img_6934977150bc85.47839591.jfif,img_69349bacc5b7c4.14768324.jfif', 'Mobile Phones & Tablets', 1, '2025-12-07 02:21:40');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `role` enum('admin','user') NOT NULL DEFAULT 'user',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `profile_pic`, `role`, `created_at`) VALUES
(1, 'Admin', '$2y$10$hKl66O99lcN7iJvQfbm4qen8t5s/i5Cm3JW4Rg6Rz3K5eMpDKmIEK', 'admin@example.com', 'profile_1_1765054130.jfif', 'user', '2025-12-06 00:07:30');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `items`
--
ALTER TABLE `items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_created_by` (`created_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `items`
--
ALTER TABLE `items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
