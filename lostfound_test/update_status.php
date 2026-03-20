<?php
include "config.php";

$id = (int)($_GET['id'] ?? 0);
$status = $_GET['status'] ?? '';

// Only allow valid statuses
$allowed = ['open', 'matched', 'claimed'];

if ($id > 0 && in_array($status, $allowed)) {
    $stmt = $conn->prepare("UPDATE items SET status=? WHERE id=?");
    $stmt->bind_param("si", $status, $id);
    $stmt->execute();
    $stmt->close();
    header("Location: items.php?status_updated=1&new_status=" . urlencode($status));
} else {
    header("Location: items.php?status_error=1");
}
exit;
