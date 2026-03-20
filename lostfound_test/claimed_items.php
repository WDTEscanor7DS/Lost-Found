<?php
include "config.php";
include "header.php";
include "sidebar.php";

// Function to confirm a claimed match
function confirmClaimedMatch($conn, $match_id)
{
    if ($match_id <= 0) return false;

    // Get the match details
    $stmt = $conn->prepare("SELECT lost_item_id, found_item_id FROM matches WHERE id = ? AND status IN ('pending', 'user_confirmed')");
    $stmt->bind_param("i", $match_id);
    $stmt->execute();
    $matchResult = $stmt->get_result();
    $matchData = $matchResult->fetch_assoc();
    $stmt->close();

    if (!$matchData) return false;

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

    return true;
}

// Function to reject a claimed match
function rejectClaimedMatch($conn, $match_id)
{
    if ($match_id <= 0) return false;

    // Get the match details
    $stmt = $conn->prepare("SELECT lost_item_id, found_item_id FROM matches WHERE id = ? AND status IN ('pending', 'user_confirmed')");
    $stmt->bind_param("i", $match_id);
    $stmt->execute();
    $matchResult = $stmt->get_result();
    $matchData = $matchResult->fetch_assoc();
    $stmt->close();

    if (!$matchData) return false;

    // Update match status to rejected
    $stmt = $conn->prepare("UPDATE matches SET status = 'rejected' WHERE id = ?");
    $stmt->bind_param("i", $match_id);
    $stmt->execute();
    $stmt->close();

    // Reset both items back to open
    $stmt = $conn->prepare("UPDATE items SET status = 'open' WHERE id IN (?, ?)");
    $stmt->bind_param("ii", $matchData['lost_item_id'], $matchData['found_item_id']);
    $stmt->execute();
    $stmt->close();

    // Add to rejected_matches so this pair won't be suggested again
    $stmt = $conn->prepare("INSERT INTO rejected_matches (lost_item_id, found_item_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $matchData['lost_item_id'], $matchData['found_item_id']);
    $stmt->execute();
    $stmt->close();

    return true;
}

// Handle user confirmation from email link
if (isset($_GET['user_confirm'])) {
    // Parse the parameters from the URL
    $params = $_GET['user_confirm'];
    parse_str($params, $parsed);
    $lost_id = (int)($parsed['lost_id'] ?? 0);
    $found_id = (int)($parsed['found_id'] ?? 0);

    if ($lost_id > 0 && $found_id > 0) {
        // Find the match
        $stmt = $conn->prepare("SELECT id, status FROM matches WHERE lost_item_id = ? AND found_item_id = ?");
        $stmt->bind_param("ii", $lost_id, $found_id);
        $stmt->execute();
        $matchResult = $stmt->get_result();
        $match = $matchResult->fetch_assoc();
        $stmt->close();

        if ($match) {
            $match_id = $match['id'];
            $current_status = $match['status'];

            if ($current_status === 'pending') {
                // Update match status to user_confirmed
                $stmt = $conn->prepare("UPDATE matches SET status = 'user_confirmed' WHERE id = ?");
                $stmt->bind_param("i", $match_id);
                $stmt->execute();
                $stmt->close();

                $notification = 'Thank you for confirming this match. The item is now ready for pickup at the Lost & Found booth.';
                $notificationType = 'success';
            } elseif ($current_status === 'user_confirmed') {
                $notification = 'This match has already been confirmed by you. The item is ready for pickup at the Lost & Found booth.';
                $notificationType = 'info';
            } elseif ($current_status === 'confirmed') {
                $notification = 'This match has already been processed and the item has been claimed.';
                $notificationType = 'info';
            } elseif ($current_status === 'rejected') {
                $notification = 'This match has been rejected. We will continue searching for your lost item.';
                $notificationType = 'warning';
            }
        } else {
            $notification = 'Match not found. This may have already been processed.';
            $notificationType = 'error';
        }
    } else {
        $notification = 'Invalid confirmation parameters.';
        $notificationType = 'error';
    }
}


// Handle form submissions
$message = '';
$messageType = '';

if (isset($_POST['confirm_match'])) {
    $match_id = (int)$_POST['match_id'];
    if (confirmClaimedMatch($conn, $match_id)) {
        $message = "Match confirmed successfully! Items have been closed.";
        $messageType = "success";
    } else {
        $message = "Error: Could not confirm match.";
        $messageType = "error";
    }
}

if (isset($_POST['reject_match'])) {
    $match_id = (int)$_POST['match_id'];
    if (rejectClaimedMatch($conn, $match_id)) {
        $message = "Match rejected. Items have been reopened for new matches.";
        $messageType = "success";
    } else {
        $message = "Error: Could not reject match.";
        $messageType = "error";
    }
}
?>

<h2>Admin Confirmation Center</h2>
<p style="color:#666;margin-bottom:20px;">Review and confirm claimed item matches</p>

<!-- Confirmation Modal -->
<div id="confirmModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;">
    <div style="background:white;padding:30px;border-radius:10px;box-shadow:0 4px 20px rgba(0,0,0,0.3);max-width:400px;text-align:center;">
        <h3 style="color:#f44336;margin-top:0;">Confirm Rejection</h3>
        <p style="color:#666;font-size:16px;line-height:1.6;">Are you sure you want to reject this match?</p>
        <p style="color:#999;font-size:14px;">The items will be reopened for new matches and this pair will be blocked from appearing together again.</p>

        <div style="margin-top:30px;display:flex;gap:10px;justify-content:center;">
            <button type="button" onclick="closeConfirmModal()" style="padding:10px 25px;background:#999;color:white;border:none;border-radius:6px;cursor:pointer;font-weight:bold;">
                Cancel
            </button>
            <form method='POST' style='margin:0;display:inline;'>
                <input type='hidden' id='modalMatchId' name='match_id' value=''>
                <button type='submit' name='reject_match' style='padding:10px 25px;background:#f44336;color:white;border:none;border-radius:6px;cursor:pointer;font-weight:bold;'>
                    Yes, Reject Match
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    function openConfirmModal(matchId) {
        document.getElementById('modalMatchId').value = matchId;
        document.getElementById('confirmModal').style.display = 'flex';
    }

    function closeConfirmModal() {
        document.getElementById('confirmModal').style.display = 'none';
    }

    // Close modal when clicking outside of it
    document.getElementById('confirmModal').addEventListener('click', function(event) {
        if (event.target === this) {
            closeConfirmModal();
        }
    });
</script>

<?php if (!empty($notification)): ?>
    <div style="padding:15px;margin-bottom:20px;border-radius:5px;background:<?php echo $notificationType === 'success' ? '#d4edda' : ($notificationType === 'info' ? '#d1ecf1' : ($notificationType === 'warning' ? '#fff3cd' : '#f8d7da')); ?>;color:<?php echo $notificationType === 'success' ? '#155724' : ($notificationType === 'info' ? '#0c5460' : ($notificationType === 'warning' ? '#856404' : '#721c24')); ?>;border:1px solid <?php echo $notificationType === 'success' ? '#c3e6cb' : ($notificationType === 'info' ? '#bee5eb' : ($notificationType === 'warning' ? '#ffeeba' : '#f5c6cb')); ?>;display:flex;justify-content:space-between;align-items:center;">
        <span><?php echo htmlspecialchars($notification); ?></span>
        <button type="button" onclick="this.parentElement.style.display='none'" style="background:none;border:none;color:inherit;cursor:pointer;font-size:20px;padding:0;margin-left:15px;">&times;</button>
    </div>
<?php endif; ?>

<?php if (!empty($message)): ?>
    <div style="padding:15px;margin-bottom:20px;border-radius:5px;background:<?php echo $messageType === 'success' ? '#d4edda' : '#f8d7da'; ?>;color:<?php echo $messageType === 'success' ? '#155724' : '#721c24'; ?>;border:1px solid <?php echo $messageType === 'success' ? '#c3e6cb' : '#f5c6cb'; ?>;display:flex;justify-content:space-between;align-items:center;">
        <span><?php echo htmlspecialchars($message); ?></span>
        <button type="button" onclick="this.parentElement.style.display='none'" style="background:none;border:none;color:inherit;cursor:pointer;font-size:20px;padding:0;margin-left:15px;">&times;</button>
    </div>
<?php endif; ?>

<?php
// Get pending matches for confirmation

$pendingMatches = $conn->query("SELECT m.id as match_id, m.match_score, m.date_matched, m.status,
                                       li.id as lost_id, li.name as lost_name, li.email as lost_email, li.description as lost_desc,
                                       li.id_type as lost_id_type, li.id_number as lost_id_number, li.id_issuer as lost_id_issuer,
                                       fi.id as found_id, fi.name as found_name, fi.category, fi.color, fi.location, fi.description as found_desc, fi.image as found_image,
                                       fi.id_type as found_id_type, fi.id_number as found_id_number, fi.id_issuer as found_id_issuer
                                FROM matches m
                                JOIN items li ON m.lost_item_id = li.id
                                JOIN items fi ON m.found_item_id = fi.id
                                WHERE m.status = 'user_confirmed'
                                ORDER BY m.date_matched DESC");

if ($pendingMatches->num_rows == 0) {
    echo "<div style='text-align:center;padding:50px;background:#f9f9f9;border-radius:10px;margin:20px 0;'>";
    echo "<h3 style='color:#666;margin-bottom:10px;'>No Items Awaiting Pickup</h3>";
    echo "<p style='color:#888;'>All confirmed matches have been processed.</p>";
    echo "</div>";
} else {
    echo "<h3 style='color:#FF9800;border-bottom:2px solid #FF9800;padding-bottom:5px;'>⏳ Items Ready for Pickup (" . $pendingMatches->num_rows . ")</h3>";

    while ($match = $pendingMatches->fetch_assoc()) {
        $isUserConfirmed = $match['status'] === 'user_confirmed';
        $borderColor = $isUserConfirmed ? '#4CAF50' : '#FF9800';
        $backgroundColor = $isUserConfirmed ? '#f1f8e9' : '#fff8e1';
        $statusText = $isUserConfirmed ? '✅ Ready for Pickup' : '⏳ Awaiting User Confirmation';

        echo "<div style='margin-bottom:25px;padding:20px;border:2px solid {$borderColor};border-radius:10px;background:{$backgroundColor};'>";

        echo "<div style='display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:15px;'>";
        echo "<h4 style='margin:0;color:#e65100;'>Match #" . $match['match_id'] . " - " . htmlspecialchars($match['found_name']) . "</h4>";
        echo "<div style='text-align:right;'>";
        echo "<span style='background:{$borderColor};color:white;padding:3px 8px;border-radius:15px;font-size:0.8em;margin-bottom:5px;display:block;'>Score: " . $match['match_score'] . "%</span>";
        echo "<span style='background:#666;color:white;padding:2px 6px;border-radius:10px;font-size:0.7em;'>{$statusText}</span>";
        echo "<br><span style='color:#666;font-size:0.7em;'>Ready for pickup at Lost & Found booth</span>";
        echo "</div>";
        echo "</div>";

        echo "<div style='display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:15px;'>";

        // Lost Item Details
        echo "<div style='background:#ffebee;padding:15px;border-radius:8px;border-left:4px solid #f44336;'>";
        echo "<h5 style='margin:0 0 10px 0;color:#c62828;'>� Owner Information</h5>";
        echo "<p><strong>Name:</strong> " . htmlspecialchars($match['lost_name']) . "</p>";
        echo "<p><strong>Email:</strong> " . htmlspecialchars($match['lost_email']) . "</p>";
        echo "<p><strong>Description:</strong> " . htmlspecialchars($match['lost_desc']) . "</p>";
        if (!empty($match['lost_id_type'])) {
            echo "<p><strong>ID:</strong> " . htmlspecialchars($match['lost_id_type']);
            if (!empty($match['lost_id_number'])) echo " #" . htmlspecialchars($match['lost_id_number']);
            if (!empty($match['lost_id_issuer'])) echo " (" . htmlspecialchars($match['lost_id_issuer']) . ")";
            echo "</p>";
        }
        echo "</div>";

        // Found Item Details
        echo "<div style='background:#e8f5e8;padding:15px;border-radius:8px;border-left:4px solid #4CAF50;'>";
        echo "<h5 style='margin:0 0 10px 0;color:#2e7d32;'>🔍 Found Item</h5>";
        echo "<p><strong>Name:</strong> " . htmlspecialchars($match['found_name']) . "</p>";
        echo "<p><strong>Category:</strong> " . htmlspecialchars($match['category']) . "</p>";
        echo "<p><strong>Color:</strong> " . htmlspecialchars($match['color']) . "</p>";
        echo "<p><strong>Location:</strong> " . htmlspecialchars($match['location']) . "</p>";
        echo "<p><strong>Description:</strong> " . htmlspecialchars($match['found_desc']) . "</p>";
        if (strtolower($match['category']) === "id" && (!empty($match['found_id_type']) || !empty($match['found_id_number']) || !empty($match['found_id_issuer']))) {
            echo "<p><strong>ID:</strong> " . htmlspecialchars($match['found_id_type'] ?? 'Not specified');
            if (!empty($match['found_id_number'])) echo " #" . htmlspecialchars($match['found_id_number']);
            if (!empty($match['found_id_issuer'])) echo " (" . htmlspecialchars($match['found_id_issuer']) . ")";
            echo "</p>";
        }
        echo "</div>";

        echo "</div>";

        // Image display
        if (!empty($match['found_image']) && file_exists("uploads/" . $match['found_image'])) {
            echo "<div style='text-align:center;margin:15px 0;'>";
            echo "<img src='uploads/" . htmlspecialchars($match['found_image']) . "' style='max-width:300px;max-height:200px;border-radius:8px;border:2px solid #ddd;' alt='Found item image'>";
            echo "</div>";
        }

        // Action buttons
        echo "<div style='text-align:center;margin-top:20px;padding-top:15px;border-top:1px solid #ddd;'>";
        echo "<form method='POST' style='display:inline-block;margin:0 10px;'>";
        echo "<input type='hidden' name='match_id' value='" . $match['match_id'] . "'>";
        echo "<button type='submit' name='confirm_match' style='padding:12px 25px;background:#4CAF50;color:white;border:none;border-radius:6px;cursor:pointer;font-size:16px;font-weight:bold;'>";
        echo "✓ CONFIRM CLAIMED";
        echo "</button>";
        echo "</form>";

        echo "<form method='POST' style='display:inline-block;margin:0 10px;'>";
        echo "<input type='hidden' name='match_id' value='" . $match['match_id'] . "'>";
        echo "<button type='button' onclick='openConfirmModal(" . $match['match_id'] . ")' style='padding:12px 25px;background:#f44336;color:white;border:none;border-radius:6px;cursor:pointer;font-size:16px;font-weight:bold;'>";
        echo "✗ REJECT MATCH";
        echo "</button>";
        echo "</form>";
        echo "</div>";

        echo "</div>";
    }
}

// Get confirmed matches for reference
$confirmedMatches = $conn->query("SELECT COUNT(*) as count FROM matches WHERE status = 'confirmed'");
$confirmedCount = $confirmedMatches->fetch_assoc()['count'];

echo "<div style='margin-top:40px;padding:20px;background:#f0f8ff;border-radius:10px;border:1px solid #4CAF50;'>";
echo "<h4 style='margin:0;color:#2e7d32;'>✓ Successfully Claimed Items: " . $confirmedCount . "</h4>";
echo "<p style='margin:5px 0 0 0;color:#666;'>Items that have been successfully claimed and returned to their owners.</p>";
echo "</div>";
?>

</div>
</body>

</html>