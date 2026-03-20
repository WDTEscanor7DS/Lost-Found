-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 16, 2026 at 06:23 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

START TRANSACTION;

SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */
;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */
;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */
;
/*!40101 SET NAMES utf8mb4 */
;

--
-- Database: `db`
--

-- --------------------------------------------------------

--
-- Table structure for table `items`
--

CREATE TABLE `items` (
    `id` int(11) NOT NULL,
    `type` enum('lost', 'found') NOT NULL,
    `name` varchar(100) NOT NULL,
    `email` varchar(100),
    `category` varchar(50) NOT NULL,
    `color` varchar(50) NOT NULL,
    `location` varchar(100) NOT NULL,
    `description` text NOT NULL,
    `image` varchar(255) NOT NULL,
    `status` enum('open', 'matched', 'closed') DEFAULT 'open',
    `verification_status` enum(
        'pending',
        'approved',
        'rejected'
    ) DEFAULT 'pending',
    `id_type` varchar(50) DEFAULT NULL,
    `id_number` varchar(50) DEFAULT NULL,
    `id_issuer` varchar(100) DEFAULT NULL,
    `date_created` timestamp NULL DEFAULT current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

--
-- Dumping data for table `items`
--

INSERT INTO
    `items` (
        `id`,
        `type`,
        `name`,
        `category`,
        `color`,
        `location`,
        `description`,
        `image`,
        `status`,
        `date_created`
    )
VALUES (
        4,
        'lost',
        'Headphone',
        'Electronics',
        'Black',
        'Computer Lab',
        'black wireless headphone',
        '1771219049_pngimg.com - headphones_PNG7643.png',
        'open',
        '2026-02-16 05:17:29'
    ),
    (
        5,
        'found',
        'Headphone',
        'Electronics',
        'Black',
        'Computer Lab',
        'wireless black headphone',
        '1771219068_pngimg.com - headphones_PNG7643.png',
        'open',
        '2026-02-16 05:17:48'
    ),
    (
        6,
        'found',
        'Headphone',
        'Electronics',
        'Red',
        'Cafeteria',
        'red headphone wireless',
        '1771219101_pngimg.com - headphones_PNG7643.png',
        'open',
        '2026-02-16 05:18:21'
    ),
    (
        7,
        'found',
        'Red Headphone',
        'Electronics',
        'Red',
        'Cafeteria',
        'red wireless headphone',
        '1771219148_pngimg.com - headphones_PNG7643.png',
        'open',
        '2026-02-16 05:19:08'
    );

--
-- Indexes for dumped tables
--

--
-- Indexes for table `items`
--
ALTER TABLE `items` ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `items`
--
ALTER TABLE `items`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,
AUTO_INCREMENT = 8;

-- --------------------------------------------------------

--
-- Table structure for table `rejected_matches`
--

CREATE TABLE `rejected_matches` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `lost_item_id` int(11) NOT NULL,
    `found_item_id` int(11) NOT NULL,
    `rejected_date` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `pair_unique` (
        `lost_item_id`,
        `found_item_id`
    )
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */
;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */
;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */
;