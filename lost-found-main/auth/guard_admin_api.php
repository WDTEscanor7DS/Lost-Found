<?php

/**
 * Admin API guard for backend endpoints.
 * Include this at the top of admin-only backend scripts.
 * Returns 403 JSON if not admin; also usable with redirect-based flows.
 */
session_start();

if (!isset($_SESSION['id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized']);
        exit();
    }
    header("Location: ../index.php");
    exit();
}
