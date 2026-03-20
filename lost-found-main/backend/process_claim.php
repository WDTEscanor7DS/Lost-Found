<?php

/**
 * Backend: Process Claim (Admin action)
 * Handles approve/reject of claims from admin panel.
 */
session_start();
require_once __DIR__ . '/../auth/guard_admin_api.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/claim_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../admin/claim-requests.php");
    exit;
}

$claimDbId = (int) ($_POST['claim_db_id'] ?? 0);
$action = $_POST['action'] ?? '';
$adminNotes = trim($_POST['admin_notes'] ?? '');

if ($claimDbId <= 0 || !in_array($action, ['approve', 'reject', 'under_review'], true)) {
    header("Location: ../admin/claim-requests.php?error=invalid");
    exit;
}

// Fetch the claim
$stmt = $conn->prepare("SELECT c.*, i.name as item_name, i.id as item_id FROM claims c JOIN items i ON c.item_id = i.id WHERE c.id = ?");
$stmt->bind_param("i", $claimDbId);
$stmt->execute();
$claim = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$claim) {
    header("Location: ../admin/claim-requests.php?error=notfound");
    exit;
}

$statusMap = ['approve' => 'approved', 'reject' => 'rejected', 'under_review' => 'under_review'];
$newStatus = $statusMap[$action] ?? 'pending';

// Update claim status
$stmt = $conn->prepare("UPDATE claims SET status = ?, admin_notes = ? WHERE id = ?");
$stmt->bind_param("ssi", $newStatus, $adminNotes, $claimDbId);
$stmt->execute();
$stmt->close();

// If approved, update item status to 'claimed'
if ($action === 'approve') {
    $stmt = $conn->prepare("UPDATE items SET status = 'claimed' WHERE id = ?");
    $stmt->bind_param("i", $claim['item_id']);
    $stmt->execute();
    $stmt->close();

    // Reject all other pending claims for the same item
    $stmt = $conn->prepare("UPDATE claims SET status = 'rejected', admin_notes = 'Auto-rejected: another claim was approved.' WHERE item_id = ? AND id != ? AND status IN ('pending', 'under_review')");
    $stmt->bind_param("ii", $claim['item_id'], $claimDbId);
    $stmt->execute();
    $stmt->close();
}

// Notify the claimant
$notifTitles = ['approve' => 'Claim Approved!', 'reject' => 'Claim Rejected', 'under_review' => 'Claim Under Review'];
$notifTitle = $notifTitles[$action] ?? 'Claim Update';
$notifTypes = ['approve' => 'success', 'reject' => 'danger', 'under_review' => 'warning'];
$notifType = $notifTypes[$action] ?? 'info';
$notifMessages = [
    'approve' => "Your claim ({$claim['claim_id']}) for \"{$claim['item_name']}\" has been approved! Please visit the Lost & Found office to collect your item.",
    'reject' => "Your claim ({$claim['claim_id']}) for \"{$claim['item_name']}\" has been rejected." . ($adminNotes ? " Reason: {$adminNotes}" : ''),
    'under_review' => "Your claim ({$claim['claim_id']}) for \"{$claim['item_name']}\" is now under review by an administrator.",
];
$notifMessage = $notifMessages[$action] ?? 'Your claim status has been updated.';

addNotification($conn, (int) $claim['user_id'], $notifTitle, $notifMessage, $notifType, 'my-claims.php');

// Send email notification if approved or rejected
if (in_array($action, ['approve', 'reject']) && !empty($claim['claimant_email'])) {
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
            $mail->addAddress($claim['claimant_email']);
            $mail->isHTML(true);

            if ($action === 'approve') {
                $mail->Subject = 'Claim Approved - ' . $claim['claim_id'];
                $mail->Body = "
                <h2 style='color:#28a745;'>Your Claim Has Been Approved!</h2>
                <p>Dear " . htmlspecialchars($claim['claimant_name']) . ",</p>
                <p>Your claim <strong>{$claim['claim_id']}</strong> for <strong>\"" . htmlspecialchars($claim['item_name']) . "\"</strong> has been verified and approved.</p>
                <div style='background:#d4edda;padding:20px;border-radius:8px;margin:20px 0;border-left:4px solid #28a745;'>
                    <h3 style='margin-top:0;color:#155724;'>Next Steps</h3>
                    <ul style='color:#155724;line-height:2;'>
                        <li>Visit the <strong>Lost & Found Office</strong> to collect your item</li>
                        <li>Bring a <strong>valid ID</strong> for verification</li>
                        <li>You can download your claim receipt from the system</li>
                    </ul>
                </div>
                <p style='color:#666;font-size:13px;'>&copy; " . date('Y') . " Lost & Found System</p>";
            } else {
                $mail->Subject = 'Claim Rejected - ' . $claim['claim_id'];
                $mail->Body = "
                <h2 style='color:#dc3545;'>Claim Update</h2>
                <p>Dear " . htmlspecialchars($claim['claimant_name']) . ",</p>
                <p>Your claim <strong>{$claim['claim_id']}</strong> for <strong>\"" . htmlspecialchars($claim['item_name']) . "\"</strong> has been reviewed and unfortunately could not be approved.</p>
                " . ($adminNotes ? "<div style='background:#fff3cd;padding:15px;border-radius:8px;margin:20px 0;border-left:4px solid #ffc107;'><p style='margin:0;'><strong>Admin notes:</strong> " . htmlspecialchars($adminNotes) . "</p></div>" : "") . "
                <p>If you believe this was an error, you may submit a new claim with additional proof.</p>
                <p style='color:#666;font-size:13px;'>&copy; " . date('Y') . " Lost & Found System</p>";
            }
            $mail->send();
        } catch (\Exception $e) {
            // Email failed silently; claim still processed
        }
    }
}

$msgMap = ['approve' => 'approved', 'reject' => 'rejected', 'under_review' => 'reviewing'];
$msg = $msgMap[$action] ?? 'done';
header("Location: ../admin/claim-requests.php?msg={$msg}");
exit;
