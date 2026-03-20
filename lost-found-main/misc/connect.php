<?php
require_once __DIR__ . '/../deploy_config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    if (DEBUG_MODE) {
        die("Connection Failed: " . htmlspecialchars($conn->connect_error));
    } else {
        die("Database connection error. Please try again later.");
    }
}

$conn->set_charset("utf8mb4");
