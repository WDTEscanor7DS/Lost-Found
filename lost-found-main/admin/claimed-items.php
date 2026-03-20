<?php
require_once __DIR__ . '/../auth/guard_admin.php';

/**
 * Admin: Claimed Items (Confirmation Center).
 * Multi-stage workflow:
 *   1. User confirms via email → status = 'user_confirmed' → admin reviews
 *   2. Admin clicks "Confirm Match" → status = 'confirmed', pickup_deadline set
 *   3. Admin clicks "Confirm Claimed" → items marked 'claimed'
 *   4. Expired pickups auto-move items back to 'open'
 */
$pageTitle = 'Claimed Items';
$activePage = 'claimed-items';

require_once __DIR__ . '/../backend/config.php';

$message = '';
$messageType = '';

// Handle redirect messages
if (isset($_GET['error']) && $_GET['error'] === 'user_already_confirmed') {
    $message = "Cannot reject: the user has already confirmed this match.";
    $messageType = "danger";
}

// Auto-expire: move overdue pickups back to open
$expired = $conn->query("SELECT id, lost_item_id, found_item_id FROM matches WHERE status = 'confirmed' AND pickup_deadline IS NOT NULL AND pickup_deadline < NOW()");
while ($expired && $exp = $expired->fetch_assoc()) {
    $stmtExp = $conn->prepare("UPDATE matches SET status = 'rejected' WHERE id = ?");
    $stmtExp->bind_param("i", $exp['id']);
    $stmtExp->execute();
    $stmtExp->close();

    $stmtItems = $conn->prepare("UPDATE items SET status = 'open' WHERE id IN (?, ?)");
    $stmtItems->bind_param("ii", $exp['lost_item_id'], $exp['found_item_id']);
    $stmtItems->execute();
    $stmtItems->close();
}

// PHPMailer helper for sending emails
function sendClaimedEmail(string $toEmail, string $subject, string $body): bool
{
    $mailerPath = __DIR__ . '/../backend/PHPMailer/';
    if (!file_exists($mailerPath . 'PHPMailer.php')) return false;

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
        $mail->addAddress($toEmail);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->send();
        return true;
    } catch (\Exception $e) {
        return false;
    }
}

// Handle: Confirm Match (user_confirmed → confirmed + deadline)
if (isset($_POST['confirm_match'])) {
    $match_id = (int)$_POST['match_id'];
    if ($match_id > 0) {
        $stmt = $conn->prepare("SELECT lost_item_id, found_item_id FROM matches WHERE id = ? AND status = 'user_confirmed'");
        $stmt->bind_param("i", $match_id);
        $stmt->execute();
        $matchData = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($matchData) {
            $deadline = date('Y-m-d H:i:s', strtotime('+3 days'));
            $stmt = $conn->prepare("UPDATE matches SET status = 'confirmed', pickup_deadline = ? WHERE id = ?");
            $stmt->bind_param("si", $deadline, $match_id);
            $stmt->execute();
            $stmt->close();

            // Send pickup instructions email
            $lostStmt = $conn->prepare("SELECT i.name, i.email, u.fullname FROM items i LEFT JOIN users u ON i.user_id = u.id WHERE i.id = ?");
            $lostStmt->bind_param("i", $matchData['lost_item_id']);
            $lostStmt->execute();
            $owner = $lostStmt->get_result()->fetch_assoc();
            $lostStmt->close();

            if ($owner && !empty($owner['email'])) {
                $deadlineFormatted = date('F j, Y \a\t g:i A', strtotime($deadline));
                $ownerName = !empty($owner['fullname']) ? htmlspecialchars($owner['fullname']) : 'there';
                $itemName  = htmlspecialchars($owner['name']);

                $emailBody = "
                <h2>Your Match Has Been Confirmed!</h2>
                <p>Hi {$ownerName},</p>
                <p>Great news! The administration team has verified the match for your lost item <strong>\"{$itemName}\"</strong>.</p>
                <div style='background:#d4edda;padding:20px;border-radius:8px;margin:20px 0;border-left:4px solid #28a745;'>
                    <h3 style='margin-top:0;color:#155724;'>Pickup Instructions</h3>
                    <ul style='color:#155724;line-height:2;'>
                        <li><strong>Location:</strong> Lost &amp; Found Office</li>
                        <li><strong>Deadline:</strong> {$deadlineFormatted}</li>
                        <li><strong>Bring:</strong> A valid ID for verification</li>
                    </ul>
                </div>
                <div style='background:#fff3cd;padding:15px;border-radius:8px;margin:20px 0;border-left:4px solid #ffc107;'>
                    <p style='margin:0;color:#856404;'><strong>Important:</strong> If you do not claim your item by <strong>{$deadlineFormatted}</strong>, the match will expire and the item will be returned to our tracking system.</p>
                </div>
                <p>Thank you for using the Lost & Found system!</p>
                <p style='color:#666;font-size:13px;'>&copy; " . date('Y') . " Lost & Found System</p>
                ";
                sendClaimedEmail($owner['email'], 'Pickup Instructions - Your Lost Item Match is Confirmed!', $emailBody);
            }

            $message = "Match confirmed! Pickup deadline set. The owner has been emailed with pickup instructions.";
            $messageType = "success";
        } else {
            $message = "Error: Match not found or not in user-confirmed status.";
            $messageType = "danger";
        }
    }
}

// Handle: Confirm Claimed (confirmed → items marked 'claimed')
if (isset($_POST['confirm_claimed'])) {
    $match_id = (int)$_POST['match_id'];
    if ($match_id > 0) {
        $stmt = $conn->prepare("SELECT lost_item_id, found_item_id FROM matches WHERE id = ? AND status = 'confirmed'");
        $stmt->bind_param("i", $match_id);
        $stmt->execute();
        $matchData = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($matchData) {
            $stmt = $conn->prepare("UPDATE items SET status = 'claimed' WHERE id IN (?, ?)");
            $stmt->bind_param("ii", $matchData['lost_item_id'], $matchData['found_item_id']);
            $stmt->execute();
            $stmt->close();

            // Clear deadline since item is claimed
            $conn->query("UPDATE matches SET pickup_deadline = NULL WHERE id = " . (int)$match_id);

            $message = "Item successfully claimed and returned to owner!";
            $messageType = "success";
        } else {
            $message = "Error: Match not found or not in confirmed status.";
            $messageType = "danger";
        }
    }
}

// Handle: Item Not Claimed (confirmed → items back to 'open')
if (isset($_POST['item_not_claimed'])) {
    $match_id = (int)$_POST['match_id'];
    if ($match_id > 0) {
        $stmt = $conn->prepare("SELECT lost_item_id, found_item_id FROM matches WHERE id = ? AND status = 'confirmed'");
        $stmt->bind_param("i", $match_id);
        $stmt->execute();
        $matchData = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($matchData) {
            $stmt = $conn->prepare("UPDATE matches SET status = 'rejected', pickup_deadline = NULL WHERE id = ?");
            $stmt->bind_param("i", $match_id);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("UPDATE items SET status = 'open' WHERE id IN (?, ?)");
            $stmt->bind_param("ii", $matchData['lost_item_id'], $matchData['found_item_id']);
            $stmt->execute();
            $stmt->close();

            $message = "Item marked as not claimed. Items have been reopened.";
            $messageType = "warning";
        }
    }
}

// Handle: Reject Match (user_confirmed → rejected)
if (isset($_POST['reject_match'])) {
    $match_id = (int)$_POST['match_id'];
    if ($match_id > 0) {
        $stmt = $conn->prepare("SELECT lost_item_id, found_item_id, status FROM matches WHERE id = ? AND status = 'pending'");
        $stmt->bind_param("i", $match_id);
        $stmt->execute();
        $matchData = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($matchData) {
            $stmt = $conn->prepare("UPDATE matches SET status = 'rejected' WHERE id = ?");
            $stmt->bind_param("i", $match_id);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("UPDATE items SET status = 'open' WHERE id IN (?, ?)");
            $stmt->bind_param("ii", $matchData['lost_item_id'], $matchData['found_item_id']);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("INSERT IGNORE INTO rejected_matches (lost_item_id, found_item_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $matchData['lost_item_id'], $matchData['found_item_id']);
            $stmt->execute();
            $stmt->close();

            // Send rejection email
            $lostStmt = $conn->prepare("SELECT name, email FROM items WHERE id = ?");
            $lostStmt->bind_param("i", $matchData['lost_item_id']);
            $lostStmt->execute();
            $lostItem = $lostStmt->get_result()->fetch_assoc();
            $lostStmt->close();

            if ($lostItem && !empty($lostItem['email'])) {
                $emailBody = "
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
                sendClaimedEmail($lostItem['email'], 'Match Rejected - Lost & Found Update', $emailBody);
            }

            $message = "Match rejected. Items have been reopened for new matches.";
            $messageType = "success";
        } else {
            $message = "Cannot reject: the user has already confirmed this match or match not found.";
            $messageType = "danger";
        }
    }
}

// Fetch data for all display sections

// Section 1: User-confirmed matches awaiting admin review
$userConfirmed = $conn->query("
    SELECT m.id as match_id, m.match_score, m.date_matched, m.status,
           li.id as lost_id, li.name as lost_name, li.email as lost_email,
           li.category as lost_category, li.color as lost_color, li.location as lost_location,
           li.description as lost_desc, li.image as lost_image,
           li.id_type as lost_id_type, li.id_number as lost_id_number, li.id_issuer as lost_id_issuer,
           COALESCE(lu.fullname, li.name) as owner_name, COALESCE(lu.username, '') as owner_username,
           fi.id as found_id, fi.name as found_name, fi.email as found_email,
           fi.category as found_category, fi.color as found_color, fi.location as found_location,
           fi.description as found_desc, fi.image as found_image,
           fi.id_type as found_id_type, fi.id_number as found_id_number, fi.id_issuer as found_id_issuer,
           COALESCE(fu.fullname, fi.name) as finder_name, COALESCE(fu.username, '') as finder_username
    FROM matches m
    JOIN items li ON m.lost_item_id = li.id
    LEFT JOIN users lu ON li.user_id = lu.id
    JOIN items fi ON m.found_item_id = fi.id
    LEFT JOIN users fu ON fi.user_id = fu.id
    WHERE m.status = 'user_confirmed'
    ORDER BY m.date_matched DESC
");

// Section 2: Confirmed matches awaiting pickup (with deadline)
$readyForPickup = $conn->query("
    SELECT m.id as match_id, m.match_score, m.date_matched, m.status, m.pickup_deadline,
           li.id as lost_id, li.name as lost_name, li.email as lost_email,
           li.category as lost_category, li.color as lost_color, li.location as lost_location,
           li.description as lost_desc, li.image as lost_image,
           li.id_type as lost_id_type, li.id_number as lost_id_number, li.id_issuer as lost_id_issuer,
           COALESCE(lu.fullname, li.name) as owner_name, COALESCE(lu.username, '') as owner_username,
           fi.id as found_id, fi.name as found_name, fi.email as found_email,
           fi.category as found_category, fi.color as found_color, fi.location as found_location,
           fi.description as found_desc, fi.image as found_image,
           fi.id_type as found_id_type, fi.id_number as found_id_number, fi.id_issuer as found_id_issuer,
           COALESCE(fu.fullname, fi.name) as finder_name, COALESCE(fu.username, '') as finder_username
    FROM matches m
    JOIN items li ON m.lost_item_id = li.id
    LEFT JOIN users lu ON li.user_id = lu.id
    JOIN items fi ON m.found_item_id = fi.id
    LEFT JOIN users fu ON fi.user_id = fu.id
    WHERE m.status = 'confirmed' AND (m.pickup_deadline IS NULL OR m.pickup_deadline >= NOW())
    AND li.status != 'claimed'
    ORDER BY m.pickup_deadline ASC
");

// Section 3: Count of successfully claimed
$claimedCount = $conn->query("SELECT COUNT(*) as count FROM matches WHERE status = 'confirmed' AND pickup_deadline IS NULL AND id IN (SELECT m2.id FROM matches m2 JOIN items i ON m2.lost_item_id = i.id WHERE i.status = 'claimed')")->fetch_assoc()['count'];
// Simpler: count items with status='claimed'
$claimedItemCount = $conn->query("SELECT COUNT(DISTINCT m.id) as count FROM matches m JOIN items i ON m.lost_item_id = i.id WHERE i.status = 'claimed'")->fetch_assoc()['count'];

include __DIR__ . '/includes/header.php';
?>

<h3 class="mb-1">Admin Confirmation Center</h3>
<p class="text-muted mb-4">Review matches, confirm pickups, and manage claimed items</p>

<?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo htmlspecialchars($messageType); ?> alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Modals -->

<!-- Reject Match Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger">Confirm Rejection</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to reject this match?</p>
                <p class="text-muted small">The items will be reopened for new matches and this pair will be blocked from appearing together again.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" id="rejectForm" class="d-inline">
                    <input type="hidden" name="match_id" id="rejectMatchId" value="">
                    <button type="submit" name="reject_match" class="btn btn-danger">Yes, Reject Match</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Not Claimed Modal -->
<div class="modal fade" id="notClaimedModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-warning">Item Not Claimed</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Mark this item as not claimed?</p>
                <p class="text-muted small">The items will be moved back to the open pool for new matches.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" id="notClaimedForm" class="d-inline">
                    <input type="hidden" name="match_id" id="notClaimedMatchId" value="">
                    <button type="submit" name="item_not_claimed" class="btn btn-warning">Yes, Not Claimed</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Section 1: User-Confirmed Matches (Pending Admin) -->

<h5 class="mb-3">
    <i class="ri ri-mail-check-line me-1"></i> User Confirmed Matches
    <span class="badge bg-primary ms-1"><?php echo $userConfirmed->num_rows; ?></span>
</h5>

<?php if ($userConfirmed->num_rows === 0): ?>
    <div class="card mb-4">
        <div class="card-body text-center py-4">
            <p class="text-muted mb-0">No matches awaiting admin review.</p>
        </div>
    </div>
<?php else: ?>
    <?php while ($match = $userConfirmed->fetch_assoc()): ?>
        <div class="card mb-4 border-primary">
            <div class="card-header d-flex justify-content-between align-items-center bg-primary bg-opacity-10">
                <h5 class="mb-0">Match #<?php echo (int)$match['match_id']; ?></h5>
                <div>
                    <span class="badge bg-success">Score: <?php echo (int)$match['match_score']; ?>%</span>
                    <span class="badge bg-primary ms-1">User Confirmed</span>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <!-- Owner (Lost Item) -->
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded border-start border-danger border-3 h-100">
                            <h6 class="text-danger mb-3"><i class="ri ri-user-line me-1"></i> Owner Information</h6>
                            <p><strong>Owner Name:</strong> <?php echo htmlspecialchars($match['owner_name']); ?></p>
                            <p><strong>Owner Email:</strong> <?php echo htmlspecialchars($match['lost_email']); ?></p>
                            <hr class="my-2">
                            <p><strong>Item Name:</strong> <?php echo htmlspecialchars($match['lost_name']); ?></p>
                            <?php if (!empty($match['lost_id_type'])): ?>
                                <p><strong>ID:</strong> <?php echo htmlspecialchars($match['lost_id_type']);
                                                        if (!empty($match['lost_id_number'])) echo ' #' . htmlspecialchars($match['lost_id_number']);
                                                        if (!empty($match['lost_id_issuer'])) echo ' (' . htmlspecialchars($match['lost_id_issuer']) . ')';
                                                        ?></p>
                            <?php endif; ?>
                            <p><strong>Category:</strong> <?php echo htmlspecialchars($match['lost_category']); ?></p>
                            <p><strong>Color:</strong> <?php echo htmlspecialchars($match['lost_color']); ?></p>
                            <p><strong>Location:</strong> <?php echo htmlspecialchars($match['lost_location']); ?></p>
                            <p><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($match['lost_desc'])); ?></p>
                            <?php if (!empty($match['lost_image']) && file_exists(__DIR__ . '/../uploads/' . $match['lost_image'])): ?>
                                <img src="../uploads/<?php echo htmlspecialchars($match['lost_image']); ?>" class="img-fluid rounded mt-2" style="max-height:200px;" alt="Lost item">
                            <?php endif; ?>
                        </div>
                    </div>
                    <!-- Finder (Found Item) -->
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded border-start border-success border-3 h-100">
                            <h6 class="text-success mb-3"><i class="ri ri-search-eye-line me-1"></i> Found Item</h6>
                            <p><strong>Finder Name:</strong> <?php echo htmlspecialchars($match['finder_name']); ?></p>
                            <p><strong>Finder Email:</strong> <?php echo htmlspecialchars($match['found_email']); ?></p>
                            <hr class="my-2">
                            <p><strong>Item Name:</strong> <?php echo htmlspecialchars($match['found_name']); ?></p>
                            <?php if (!empty($match['found_id_type'])): ?>
                                <p><strong>ID:</strong> <?php echo htmlspecialchars($match['found_id_type']);
                                                        if (!empty($match['found_id_number'])) echo ' #' . htmlspecialchars($match['found_id_number']);
                                                        if (!empty($match['found_id_issuer'])) echo ' (' . htmlspecialchars($match['found_id_issuer']) . ')';
                                                        ?></p>
                            <?php endif; ?>
                            <p><strong>Category:</strong> <?php echo htmlspecialchars($match['found_category']); ?></p>
                            <p><strong>Color:</strong> <?php echo htmlspecialchars($match['found_color']); ?></p>
                            <p><strong>Location:</strong> <?php echo htmlspecialchars($match['found_location']); ?></p>
                            <p><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($match['found_desc'])); ?></p>
                            <?php if (!empty($match['found_image']) && file_exists(__DIR__ . '/../uploads/' . $match['found_image'])): ?>
                                <img src="../uploads/<?php echo htmlspecialchars($match['found_image']); ?>" class="img-fluid rounded mt-2" style="max-height:200px;" alt="Found item">
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-3 pt-3 border-top">
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="match_id" value="<?php echo (int)$match['match_id']; ?>">
                        <button type="submit" name="confirm_match" class="btn btn-success btn-lg me-2">
                            <i class="ri ri-check-double-line me-1"></i> CONFIRM MATCH
                        </button>
                    </form>
                    <button type="button" class="btn btn-outline-danger btn-lg" onclick="openRejectModal(<?php echo (int)$match['match_id']; ?>)">
                        <i class="ri ri-close-line me-1"></i> REJECT MATCH
                    </button>
                </div>
            </div>
        </div>
    <?php endwhile; ?>
<?php endif; ?>

<!-- Section 2: Items Ready for Pickup -->

<h5 class="mt-4 mb-3">
    <i class="ri ri-hand-coin-line me-1"></i> Items Ready for Pickup
    <span class="badge bg-warning text-dark ms-1"><?php echo $readyForPickup->num_rows; ?></span>
</h5>

<?php if ($readyForPickup->num_rows === 0): ?>
    <div class="card mb-4">
        <div class="card-body text-center py-4">
            <p class="text-muted mb-0">No items are currently awaiting pickup.</p>
        </div>
    </div>
<?php else: ?>
    <?php while ($match = $readyForPickup->fetch_assoc()):
        $deadlineStr = $match['pickup_deadline'] ? date('M j, Y g:i A', strtotime($match['pickup_deadline'])) : 'No deadline';
        $isUrgent = $match['pickup_deadline'] && strtotime($match['pickup_deadline']) < strtotime('+1 day');
    ?>
        <div class="card mb-4 border-warning">
            <div class="card-header d-flex justify-content-between align-items-center bg-warning bg-opacity-10">
                <h5 class="mb-0">Match #<?php echo (int)$match['match_id']; ?></h5>
                <div>
                    <span class="badge bg-success">Score: <?php echo (int)$match['match_score']; ?>%</span>
                    <span class="badge <?php echo $isUrgent ? 'bg-danger' : 'bg-warning text-dark'; ?> ms-1">
                        <i class="ri ri-time-line me-1"></i> Deadline: <?php echo $deadlineStr; ?>
                    </span>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <!-- Owner (Lost Item) -->
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded border-start border-danger border-3 h-100">
                            <h6 class="text-danger mb-3"><i class="ri ri-user-line me-1"></i> Owner Information</h6>
                            <p><strong>Owner Name:</strong> <?php echo htmlspecialchars($match['owner_name']); ?></p>
                            <p><strong>Owner Email:</strong> <?php echo htmlspecialchars($match['lost_email']); ?></p>
                            <hr class="my-2">
                            <p><strong>Item Name:</strong> <?php echo htmlspecialchars($match['lost_name']); ?></p>
                            <?php if (!empty($match['lost_id_type'])): ?>
                                <p><strong>ID:</strong> <?php echo htmlspecialchars($match['lost_id_type']);
                                                        if (!empty($match['lost_id_number'])) echo ' #' . htmlspecialchars($match['lost_id_number']);
                                                        if (!empty($match['lost_id_issuer'])) echo ' (' . htmlspecialchars($match['lost_id_issuer']) . ')';
                                                        ?></p>
                            <?php endif; ?>
                            <p><strong>Category:</strong> <?php echo htmlspecialchars($match['lost_category']); ?></p>
                            <p><strong>Color:</strong> <?php echo htmlspecialchars($match['lost_color']); ?></p>
                            <p><strong>Location:</strong> <?php echo htmlspecialchars($match['lost_location']); ?></p>
                            <p><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($match['lost_desc'])); ?></p>
                            <?php if (!empty($match['lost_image']) && file_exists(__DIR__ . '/../uploads/' . $match['lost_image'])): ?>
                                <img src="../uploads/<?php echo htmlspecialchars($match['lost_image']); ?>" class="img-fluid rounded mt-2" style="max-height:200px;" alt="Lost item">
                            <?php endif; ?>
                        </div>
                    </div>
                    <!-- Finder (Found Item) -->
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded border-start border-success border-3 h-100">
                            <h6 class="text-success mb-3"><i class="ri ri-search-eye-line me-1"></i> Found Item</h6>
                            <p><strong>Finder Name:</strong> <?php echo htmlspecialchars($match['finder_name']); ?></p>
                            <p><strong>Finder Email:</strong> <?php echo htmlspecialchars($match['found_email']); ?></p>
                            <hr class="my-2">
                            <p><strong>Item Name:</strong> <?php echo htmlspecialchars($match['found_name']); ?></p>
                            <?php if (!empty($match['found_id_type'])): ?>
                                <p><strong>ID:</strong> <?php echo htmlspecialchars($match['found_id_type']);
                                                        if (!empty($match['found_id_number'])) echo ' #' . htmlspecialchars($match['found_id_number']);
                                                        if (!empty($match['found_id_issuer'])) echo ' (' . htmlspecialchars($match['found_id_issuer']) . ')';
                                                        ?></p>
                            <?php endif; ?>
                            <p><strong>Category:</strong> <?php echo htmlspecialchars($match['found_category']); ?></p>
                            <p><strong>Color:</strong> <?php echo htmlspecialchars($match['found_color']); ?></p>
                            <p><strong>Location:</strong> <?php echo htmlspecialchars($match['found_location']); ?></p>
                            <p><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($match['found_desc'])); ?></p>
                            <?php if (!empty($match['found_image']) && file_exists(__DIR__ . '/../uploads/' . $match['found_image'])): ?>
                                <img src="../uploads/<?php echo htmlspecialchars($match['found_image']); ?>" class="img-fluid rounded mt-2" style="max-height:200px;" alt="Found item">
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-3 pt-3 border-top">
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="match_id" value="<?php echo (int)$match['match_id']; ?>">
                        <button type="submit" name="confirm_claimed" class="btn btn-success btn-lg me-2">
                            <i class="ri ri-check-line me-1"></i> CONFIRM CLAIMED
                        </button>
                    </form>
                    <button type="button" class="btn btn-outline-warning btn-lg" onclick="openNotClaimedModal(<?php echo (int)$match['match_id']; ?>)">
                        <i class="ri ri-time-line me-1"></i> ITEM NOT CLAIMED
                    </button>
                </div>
            </div>
        </div>
    <?php endwhile; ?>
<?php endif; ?>

<!-- Section 3: Summary -->

<div class="card mt-4 border-success">
    <div class="card-body d-flex align-items-center">
        <i class="ri ri-checkbox-circle-line text-success me-3" style="font-size:2rem;"></i>
        <div>
            <h5 class="text-success mb-0">Successfully Claimed Items: <?php echo (int)$claimedItemCount; ?></h5>
            <p class="text-muted mb-0">Items that have been successfully claimed and returned to their owners.</p>
        </div>
    </div>
</div>

<script>
    function openRejectModal(matchId) {
        document.getElementById('rejectMatchId').value = matchId;
        new bootstrap.Modal(document.getElementById('rejectModal')).show();
    }

    function openNotClaimedModal(matchId) {
        document.getElementById('notClaimedMatchId').value = matchId;
        new bootstrap.Modal(document.getElementById('notClaimedModal')).show();
    }
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>