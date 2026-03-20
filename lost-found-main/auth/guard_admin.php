<?php

/**
 * Admin access guard.
 * Include this at the very top of every admin page.
 * Redirects to login if user is not authenticated or not an admin.
 */
session_start();

if (!isset($_SESSION['id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}
