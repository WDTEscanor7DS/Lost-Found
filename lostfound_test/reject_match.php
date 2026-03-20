<?php
include "config.php";

$notification = '';
$notificationType = '';
$result_status = '';

if (isset($_GET['lost_id']) && isset($_GET['found_id'])) {
    $lost_id = (int)$_GET['lost_id'];
    $found_id = (int)$_GET['found_id'];

    if ($lost_id > 0 && $found_id > 0) {
        // Check current match status before allowing rejection
        $stmt = $conn->prepare("SELECT status FROM matches WHERE lost_item_id = ? AND found_item_id = ?");
        $stmt->bind_param("ii", $lost_id, $found_id);
        $stmt->execute();
        $matchResult = $stmt->get_result();
        $match = $matchResult->fetch_assoc();
        $stmt->close();

        if ($match && in_array($match['status'], ['user_confirmed', 'confirmed'])) {
            // Item has already been confirmed — block rejection
            $notification = 'This item has already been confirmed as claimed. It can no longer be rejected.';
            $notificationType = 'error';
            $result_status = 'already_claimed';
        } else {
            // Remove from matches table
            $stmt = $conn->prepare("DELETE FROM matches WHERE lost_item_id = ? AND found_item_id = ?");
            $stmt->bind_param("ii", $lost_id, $found_id);
            $stmt->execute();
            $stmt->close();

            // Remove from rejected_matches if it was added
            $stmt = $conn->prepare("DELETE FROM rejected_matches WHERE lost_item_id = ? AND found_item_id = ?");
            $stmt->bind_param("ii", $lost_id, $found_id);
            $stmt->execute();
            $stmt->close();

            // Reset item statuses to open
            $stmt = $conn->prepare("UPDATE items SET status='open' WHERE id IN (?, ?)");
            $stmt->bind_param("ii", $lost_id, $found_id);
            $stmt->execute();
            $stmt->close();

            $notification = 'This match has been rejected. The items have been put back into the system and we will continue searching for your lost item.';
            $notificationType = 'success';
            $result_status = 'rejected';
        }
    } else {
        $notification = 'Invalid request parameters.';
        $notificationType = 'error';
        $result_status = 'error';
    }
} else {
    $notification = 'Missing request parameters.';
    $notificationType = 'error';
    $result_status = 'error';
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Match Rejected - Lost & Found</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            width: 100%;
            max-width: 600px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .notification {
            margin: 30px;
            padding: 20px;
            border-radius: 8px;
            border-left: 5px solid;
            animation: slideIn 0.4s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .notification.success {
            background: #d4edda;
            border-color: #28a745;
            color: #155724;
        }

        .notification.error {
            background: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
        }

        .notification p {
            line-height: 1.6;
            margin-bottom: 10px;
        }

        .notification p:last-child {
            margin-bottom: 0;
        }

        .icon {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .info-box {
            margin: 0 30px 30px 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }

        .info-box h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 16px;
        }

        .info-box p {
            color: #555;
            line-height: 1.6;
            margin-bottom: 8px;
        }

        .info-box p:last-child {
            margin-bottom: 0;
        }

        .button-group {
            margin: 30px;
            text-align: center;
        }

        .button {
            display: inline-block;
            padding: 12px 28px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        .button:hover {
            background: #764ba2;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .footer {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            color: #666;
            font-size: 13px;
            border-top: 1px solid #dee2e6;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>Match Rejection Status</h1>
        </div>

        <?php if ($notification): ?>
            <div class="notification <?php echo htmlspecialchars($notificationType); ?>">
                <div class="icon">
                    <?php echo $notificationType === 'success' ? '✓' : '✗'; ?>
                </div>
                <p><?php echo htmlspecialchars($notification); ?></p>
            </div>
        <?php endif; ?>

        <?php if ($result_status === 'rejected'): ?>
            <div class="info-box">
                <h3>What happens next?</h3>
                <p>Both items have been reopened and will be available for new potential matches.</p>
                <p>If your item is found in the future, you will receive another email notification.</p>
                <p>Thank you for helping us keep our records accurate.</p>
            </div>
        <?php elseif ($result_status === 'already_claimed'): ?>
            <div class="info-box">
                <h3>Why can't I reject this?</h3>
                <p>You have already confirmed this item as yours. Once confirmed, the match is locked and cannot be reversed from here.</p>
                <p>If you believe there was a mistake, please contact our administration team directly for assistance.</p>
            </div>
        <?php elseif ($result_status === 'error'): ?>
            <div class="info-box">
                <h3>Need Help?</h3>
                <p>If you believe this is an error, please contact our administration team for assistance.</p>
            </div>
        <?php endif; ?>

        <div class="button-group">
            <a href="index.php" class="button">Return to Lost & Found</a>
        </div>

        <div class="footer">
            <p>&copy; 2026 Lost & Found System. All rights reserved.</p>
        </div>
    </div>
</body>

</html>