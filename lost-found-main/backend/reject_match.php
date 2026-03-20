<?php

/**
 * Reject match from admin side.
 * Resets items to 'open' status and adds pair to rejected_matches.
 * Called via POST with match_id.
 */
require_once __DIR__ . '/../auth/guard_admin_api.php';
require_once __DIR__ . '/config.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST['reject_match'])) {
    header("Location: ../admin/claimed-items.php");
    exit;
}

$match_id = (int)($_POST['match_id'] ?? 0);

if ($match_id <= 0) {
    header("Location: ../admin/claimed-items.php?error=invalid");
    exit;
}

// Get match details
$stmt = $conn->prepare("SELECT lost_item_id, found_item_id, status FROM matches WHERE id = ? AND status IN ('pending', 'user_confirmed')");
$stmt->bind_param("i", $match_id);
$stmt->execute();
$matchData = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$matchData) {
    header("Location: ../admin/claimed-items.php?error=notfound");
    exit;
}

// Block rejection if user has already confirmed the match
if ($matchData['status'] === 'user_confirmed') {
    header("Location: ../admin/claimed-items.php?error=user_already_confirmed");
    exit;
}

// Update match status to rejected
$stmt = $conn->prepare("UPDATE matches SET status = 'rejected' WHERE id = ?");
$stmt->bind_param("i", $match_id);
$stmt->execute();
$stmt->close();

// Reset items to open
$stmt = $conn->prepare("UPDATE items SET status = 'open' WHERE id IN (?, ?)");
$stmt->bind_param("ii", $matchData['lost_item_id'], $matchData['found_item_id']);
$stmt->execute();
$stmt->close();

// Add to rejected_matches
$stmt = $conn->prepare("INSERT IGNORE INTO rejected_matches (lost_item_id, found_item_id) VALUES (?, ?)");
$stmt->bind_param("ii", $matchData['lost_item_id'], $matchData['found_item_id']);
$stmt->execute();
$stmt->close();

// Send rejection email to the lost item owner
$lostStmt = $conn->prepare("SELECT name, email FROM items WHERE id = ?");
$lostStmt->bind_param("i", $matchData['lost_item_id']);
$lostStmt->execute();
$lostItem = $lostStmt->get_result()->fetch_assoc();
$lostStmt->close();

if ($lostItem && !empty($lostItem['email'])) {
    $mailerPath = __DIR__ . '/PHPMailer/';
    if (file_exists($mailerPath . 'PHPMailer.php')) {
        require_once $mailerPath . 'PHPMailer.php';
        require_once $mailerPath . 'SMTP.php';
        require_once $mailerPath . 'Exception.php';

        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = MAIL_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = MAIL_USERNAME;
            $mail->Password   = MAIL_PASSWORD;
            $mail->SMTPSecure = 'tls';
            $mail->Port       = MAIL_PORT;

            $mail->setFrom(MAIL_USERNAME, MAIL_FROM_NAME);
            $mail->addAddress($lostItem['email']);

            $mail->isHTML(true);
            $mail->Subject = 'Match Rejected - Lost & Found Update';
            $mail->Body = "
            <h2>Match Update</h2>
            <p>We're sorry to inform you that the potential match for your lost item <strong>\"" . htmlspecialchars($lostItem['name']) . "\"</strong> has been rejected after review.</p>
            <div style='background:#fff3cd;padding:20px;border-radius:8px;margin:20px 0;border-left:4px solid #ffc107;'>
                <h3 style='margin-top:0;color:#856404;'>What happens next?</h3>
                <p>Your item has been put back into our system and we will continue searching for potential matches.</p>
                <p>If a new match is found, you will receive another email notification.</p>
            </div>
            <p>Thank you for your patience.</p>
            <p style='color:#666;font-size:13px;'>&copy; " . date('Y') . " Lost & Found System</p>
            ";

            $mail->send();
        } catch (\Exception $e) {
            // Email failed silently — rejection still processed
        }
    }
}

header("Location: ../admin/claimed-items.php?rejected=1");
exit;
