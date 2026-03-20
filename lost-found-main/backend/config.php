<?php

/**
 * Central database configuration for the Lost & Found system.
 * All backend scripts include this file for database access.
 */
require_once __DIR__ . '/../deploy_config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    if (DEBUG_MODE) {
        die("Connection failed: " . htmlspecialchars($conn->connect_error));
    } else {
        die("Database connection error. Please try again later.");
    }
}

$conn->set_charset("utf8mb4");

// Email credentials for PHPMailer (SMTP)
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_USERNAME', 'lostandfoundbot2000@gmail.com');
define('MAIL_PASSWORD', 'zlyc fynv owzc vkur');
define('MAIL_PORT', 587);
define('MAIL_FROM_NAME', 'Lost & Found System');

// Auto-migrate: ensure user_id column exists in items table
$colCheck = $conn->query("SHOW COLUMNS FROM `items` LIKE 'user_id'");
if ($colCheck && $colCheck->num_rows === 0) {
    $conn->query("ALTER TABLE `items` ADD COLUMN `user_id` int(11) DEFAULT NULL AFTER `id`");
    $conn->query("ALTER TABLE `items` ADD INDEX `idx_user_id` (`user_id`)");
}

// Auto-migrate: ensure pickup_deadline column exists in matches table
$colCheck = $conn->query("SHOW COLUMNS FROM `matches` LIKE 'pickup_deadline'");
if ($colCheck && $colCheck->num_rows === 0) {
    $conn->query("ALTER TABLE `matches` ADD COLUMN `pickup_deadline` DATETIME DEFAULT NULL AFTER `status`");
}

// Auto-migrate: create claims table for claim verification system
$conn->query("
    CREATE TABLE IF NOT EXISTS `claims` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `claim_id` varchar(20) NOT NULL,
        `item_id` int(11) NOT NULL,
        `user_id` int(11) NOT NULL,
        `claimant_name` varchar(200) NOT NULL,
        `claimant_email` varchar(100) NOT NULL,
        `claimant_phone` varchar(20) DEFAULT NULL,
        `item_description` text NOT NULL,
        `unique_identifiers` text DEFAULT NULL,
        `proof_image` varchar(255) DEFAULT NULL,
        `id_document` varchar(255) DEFAULT NULL,
        `proof_document` varchar(255) DEFAULT NULL,
        `confidence_score` int(11) DEFAULT 0,
        `status` enum('pending','under_review','approved','rejected') DEFAULT 'pending',
        `admin_notes` text DEFAULT NULL,
        `date_claimed` timestamp NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `claim_id` (`claim_id`),
        KEY `idx_claim_item` (`item_id`),
        KEY `idx_claim_user` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
");

// Auto-migrate: create notifications table
$conn->query("
    CREATE TABLE IF NOT EXISTS `notifications` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `title` varchar(200) NOT NULL,
        `message` text NOT NULL,
        `type` enum('info','success','warning','danger') DEFAULT 'info',
        `is_read` tinyint(1) DEFAULT 0,
        `link` varchar(255) DEFAULT NULL,
        `date_created` timestamp NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `idx_notif_user` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
");

// Auto-migrate: add claim_token column to matches table for secure email links
$colCheck = $conn->query("SHOW COLUMNS FROM `matches` LIKE 'claim_token'");
if ($colCheck && $colCheck->num_rows === 0) {
    $conn->query("ALTER TABLE `matches` ADD COLUMN `claim_token` varchar(64) DEFAULT NULL AFTER `pickup_deadline`");
    $conn->query("ALTER TABLE `matches` ADD UNIQUE KEY `idx_claim_token` (`claim_token`)");
}
