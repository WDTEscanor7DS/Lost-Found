<?php

/**
 * Verify item (approve or reject) from admin panel.
 * Called via POST with item_id and verification (approved/rejected).
 */
require_once __DIR__ . '/../auth/guard_admin_api.php';
require_once __DIR__ . '/config.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../admin/lost-submit.php");
    exit;
}

$item_id = (int)($_POST['item_id'] ?? 0);
$verification = $_POST['verification'] ?? '';

if ($item_id > 0 && in_array($verification, ['approved', 'rejected'])) {
    $stmt = $conn->prepare("UPDATE items SET verification_status = ? WHERE id = ?");
    $stmt->bind_param("si", $verification, $item_id);
    $stmt->execute();
    $stmt->close();

    // Only redirect to same-origin admin pages
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    $fallback = '../admin/lost-submit.php';
    $redirect = (strpos($referer, SITE_URL) === 0) ? $referer : $fallback;
    header("Location: " . $redirect . "?verified=1&action=" . urlencode($verification));
} else {
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    $fallback = '../admin/lost-submit.php';
    $redirect = (strpos($referer, SITE_URL) === 0) ? $referer : $fallback;
    header("Location: " . $redirect . "?verify_error=1");
}
exit;
