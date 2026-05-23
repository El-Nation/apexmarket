-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 23, 2026 at 08:30 AM
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
-- Database: `stock_broker`
--

-- --------------------------------------------------------

--
-- Table structure for table `balances`
--

CREATE TABLE `balances` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `asset` varchar(10) NOT NULL,
  `amount` decimal(30,8) DEFAULT 0.00000000,
  `locked` decimal(30,8) DEFAULT 0.00000000
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `balances`
--

INSERT INTO `balances` (`id`, `user_id`, `asset`, `amount`, `locked`) VALUES
(1, 1, 'USDT', 12450.80000000, 0.00000000),
(2, 1, 'BTC', 0.52410000, 0.00000000),
(3, 1, 'ETH', 2.41020000, 0.00000000),
(4, 2, 'USDT', 50000.00000000, 0.00000000),
(5, 2, 'BTC', 1.50000000, 0.00000000),
(6, 2, 'ETH', 10.00000000, 0.00000000);

-- --------------------------------------------------------

--
-- Table structure for table `market_orders`
--

CREATE TABLE `market_orders` (
  `id` int(11) NOT NULL,
  `symbol` varchar(20) NOT NULL,
  `side` enum('buy','sell') NOT NULL,
  `price` decimal(30,8) NOT NULL,
  `amount` decimal(30,8) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `symbol` varchar(20) NOT NULL DEFAULT 'BTC/USDT',
  `side` enum('buy','sell') NOT NULL,
  `type` enum('limit','market','stop') NOT NULL DEFAULT 'limit',
  `price` decimal(30,8) DEFAULT 0.00000000,
  `stop_price` decimal(30,8) DEFAULT 0.00000000,
  `amount` decimal(30,8) NOT NULL,
  `filled` decimal(30,8) DEFAULT 0.00000000,
  `status` enum('open','filled','partial','cancelled','active') DEFAULT 'open',
  `fee` decimal(30,8) DEFAULT 0.00000000,
  `fee_asset` varchar(10) DEFAULT 'USDT',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `trades`
--

CREATE TABLE `trades` (
  `id` int(11) NOT NULL,
  `buy_order_id` int(11) DEFAULT NULL,
  `sell_order_id` int(11) DEFAULT NULL,
  `buyer_id` int(11) DEFAULT NULL,
  `seller_id` int(11) DEFAULT NULL,
  `symbol` varchar(20) NOT NULL,
  `price` decimal(30,8) NOT NULL,
  `amount` decimal(30,8) NOT NULL,
  `buyer_fee` decimal(30,8) DEFAULT 0.00000000,
  `seller_fee` decimal(30,8) DEFAULT 0.00000000,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('deposit','withdrawal') NOT NULL,
  `asset` varchar(10) NOT NULL,
  `amount` decimal(30,8) NOT NULL,
  `address` varchar(255) DEFAULT '',
  `tx_hash` varchar(255) DEFAULT '',
  `status` enum('pending','completed','failed') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) DEFAULT '',
  `is_verified` tinyint(1) DEFAULT 0,
  `two_fa_enabled` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `full_name`, `is_verified`, `two_fa_enabled`, `created_at`, `updated_at`) VALUES
(1, 'demo', 'demo@apexmarkets.com', '$2y$12$zUYLs5qI9TPEh0amfVeHPOFSyBUOkvEcGBafZmR98wtu9KNYL0NfG', 'Demo Trader', 1, 0, '2026-04-22 19:58:30', '2026-04-22 20:15:08'),
(2, 'alice', 'alice@apexmarkets.com', '$2y$12$sXanrjHwMjAZ.JP4L27S6eIlRaMl8E3TcactxeIdcWC5GoXjLkxAS', 'Alice Johnson', 1, 0, '2026-04-22 19:58:30', '2026-04-22 20:15:09');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `balances`
--
ALTER TABLE `balances`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_asset` (`user_id`,`asset`);

--
-- Indexes for table `market_orders`
--
ALTER TABLE `market_orders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_status` (`user_id`,`status`),
  ADD KEY `idx_symbol_side_status` (`symbol`,`side`,`status`);

--
-- Indexes for table `trades`
--
ALTER TABLE `trades`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_symbol` (`symbol`),
  ADD KEY `idx_buyer` (`buyer_id`),
  ADD KEY `idx_seller` (`seller_id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_type` (`user_id`,`type`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `balances`
--
ALTER TABLE `balances`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `market_orders`
--
ALTER TABLE `market_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `trades`
--
ALTER TABLE `trades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `balances`
--
ALTER TABLE `balances`
  ADD CONSTRAINT `balances_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
