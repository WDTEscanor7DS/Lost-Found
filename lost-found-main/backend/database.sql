-- ===========================================================================
-- Lost & Found System - Database Migration
-- Run this script to set up the unified database schema.
-- Compatible with MySQL 5.7+ / MariaDB 10.3+
-- ===========================================================================

CREATE DATABASE IF NOT EXISTS `lost_found` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

USE `lost_found`;

-- -------------------------------------------------------------------
-- Users table (from lost-found-main)
-- -------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `username` varchar(100) NOT NULL,
    `fullname` varchar(200) DEFAULT NULL,
    `password` varchar(255) NOT NULL,
    `role` enum('admin', 'user') NOT NULL DEFAULT 'user',
    PRIMARY KEY (`id`),
    UNIQUE KEY `username` (`username`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- -------------------------------------------------------------------
-- Categories table (from lost-found-main)
-- -------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `categories` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `category-name` varchar(100) NOT NULL,
    `status` enum('Active', 'Inactive') DEFAULT 'Active',
    PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- Default categories
INSERT IGNORE INTO
    `categories` (`category-name`, `status`)
VALUES ('Electronics', 'Active'),
    ('Wallet', 'Active'),
    ('ID', 'Active'),
    ('Bag', 'Active'),
    ('Keys', 'Active'),
    ('Clothing', 'Active'),
    ('Books / Notes', 'Active'),
    ('Water Bottle', 'Active'),
    ('Umbrella', 'Active'),
    ('Others', 'Active');

-- -------------------------------------------------------------------
-- Items table (from lostfound_test — the unified items table)
-- -------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `items` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) DEFAULT NULL,
    `type` enum('lost', 'found') NOT NULL,
    `name` varchar(100) NOT NULL,
    `email` varchar(100) DEFAULT NULL,
    `category` varchar(50) NOT NULL,
    `color` varchar(50) NOT NULL,
    `location` varchar(100) NOT NULL,
    `description` text NOT NULL,
    `image` varchar(255) NOT NULL DEFAULT '',
    `status` enum('open', 'matched', 'claimed') DEFAULT 'open',
    `verification_status` enum(
        'pending',
        'approved',
        'rejected'
    ) DEFAULT 'pending',
    `id_type` varchar(50) DEFAULT NULL,
    `id_number` varchar(50) DEFAULT NULL,
    `id_issuer` varchar(100) DEFAULT NULL,
    `date_created` timestamp NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- -------------------------------------------------------------------
-- Matches table
-- -------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `matches` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `lost_item_id` int(11) NOT NULL,
    `found_item_id` int(11) NOT NULL,
    `match_score` int(11) DEFAULT 0,
    `status` enum(
        'pending',
        'user_confirmed',
        'confirmed',
        'rejected'
    ) DEFAULT 'pending',
    `pickup_deadline` datetime DEFAULT NULL,
    `date_matched` timestamp NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_match_pair` (
        `lost_item_id`,
        `found_item_id`
    )
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- -------------------------------------------------------------------
-- Rejected matches table
-- -------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `rejected_matches` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `lost_item_id` int(11) NOT NULL,
    `found_item_id` int(11) NOT NULL,
    `rejected_date` timestamp NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `pair_unique` (
        `lost_item_id`,
        `found_item_id`
    )
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- -------------------------------------------------------------------
-- Performance indexes for matching algorithm
-- -------------------------------------------------------------------
ALTER TABLE `items`
ADD INDEX IF NOT EXISTS `idx_match_candidates` (
    `type`,
    `status`,
    `verification_status`,
    `category`
);

ALTER TABLE `items`
ADD FULLTEXT INDEX IF NOT EXISTS `ft_name_desc` (`name`, `description`);

-- -------------------------------------------------------------------
-- Default admin account
-- Password: admin123 (bcrypt hashed via PHP password_hash)
-- -------------------------------------------------------------------
INSERT INTO
    `users` (
        `username`,
        `fullname`,
        `password`,
        `role`
    )
VALUES (
        'admin',
        'System Administrator',
        '$2y$10$7vuQFfm1fW9.NCyB3h7QJe745nwCo6P2GdWSUsmqqeQ23RCdQ9qfK',
        'admin'
    )
ON DUPLICATE KEY UPDATE
    `id` = `id`;

-- Default user account
-- Password: User123 (bcrypt hashed via PHP password_hash)
INSERT INTO
    `users` (
        `username`,
        `fullname`,
        `password`,
        `role`
    )
VALUES (
        'User',
        'Default User',
        '$2y$10$9YweHa/p3a2/xXWdQSKk/u3nA8CXuBKp5modHaE6Vj5F7bxQOa5Za',
        'user'
    )
ON DUPLICATE KEY UPDATE
    `id` = `id`;

-- -------------------------------------------------------------------
-- Migration for existing installs
-- Run these if upgrading from a previous version.
-- -------------------------------------------------------------------

-- Add user_id column to items (skip if already exists)
ALTER TABLE `items`
ADD COLUMN IF NOT EXISTS `user_id` int(11) DEFAULT NULL AFTER `id`;

ALTER TABLE `items`
ADD INDEX IF NOT EXISTS `idx_user_id` (`user_id`);

-- Change status enum from 'closed' to 'claimed' (if not already done)
ALTER TABLE `items`
MODIFY COLUMN `status` enum('open', 'matched', 'claimed') DEFAULT 'open';

UPDATE `items` SET `status` = 'claimed' WHERE `status` = 'closed';