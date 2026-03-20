<?php

/**
 * Update item status (open, matched, claimed).
 * Only accepts POST requests.
 */
require_once __DIR__ . '/../auth/guard_admin_api.php';
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../admin/all-items.php?status_error=1");
    exit;
}

$id = (int)($_POST['id'] ?? 0);
$status = $_POST['status'] ?? '';

$allowed = ['open', 'matched', 'claimed'];

if ($id > 0 && in_array($status, $allowed)) {
    $stmt = $conn->prepare("UPDATE items SET status=? WHERE id=?");
    $stmt->bind_param("si", $status, $id);
    $stmt->execute();
    $stmt->close();
    header("Location: ../admin/all-items.php?status_updated=1&new_status=" . urlencode($status));
} else {
    header("Location: ../admin/all-items.php?status_error=1");
}
exit;
