<?php

/**
 * Confirm match from admin side.
 * Updates match to 'confirmed' and closes both items.
 * Called via POST with match_id.
 */
require_once __DIR__ . '/../auth/guard_admin_api.php';
require_once __DIR__ . '/config.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST['confirm_match'])) {
    header("Location: ../admin/claimed-items.php");
    exit;
}

$match_id = (int)($_POST['match_id'] ?? 0);

if ($match_id <= 0) {
    header("Location: ../admin/claimed-items.php?error=invalid");
    exit;
}

// Get match details
$stmt = $conn->prepare("SELECT lost_item_id, found_item_id FROM matches WHERE id = ? AND status IN ('pending', 'user_confirmed')");
$stmt->bind_param("i", $match_id);
$stmt->execute();
$matchData = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$matchData) {
    header("Location: ../admin/claimed-items.php?error=notfound");
    exit;
}

// Update match status to confirmed
$stmt = $conn->prepare("UPDATE matches SET status = 'confirmed' WHERE id = ?");
$stmt->bind_param("i", $match_id);
$stmt->execute();
$stmt->close();

// Close both items
$stmt = $conn->prepare("UPDATE items SET status = 'closed' WHERE id IN (?, ?)");
$stmt->bind_param("ii", $matchData['lost_item_id'], $matchData['found_item_id']);
$stmt->execute();
$stmt->close();

header("Location: ../admin/claimed-items.php?confirmed=1");
exit;
