<?php

/**
 * User access guard.
 * Include this at the very top of every user page.
 * Redirects to login if user is not authenticated.
 */
session_start();

if (!isset($_SESSION['id']) || !isset($_SESSION['role'])) {
    header("Location: ../index.php");
    exit();
}
