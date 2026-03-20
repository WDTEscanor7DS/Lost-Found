<?php

/**
 * User-facing match confirmation page.
 * Linked from email notification — the "YES" button.
 */
require_once __DIR__ . '/config.php';

$lost_id = 0;
$found_id = 0;
if (isset($_GET['lost_id']) && isset($_GET['found_id'])) {
    $lost_id = (int)$_GET['lost_id'];
    $found_id = (int)$_GET['found_id'];
} elseif (isset($_GET['user_confirm'])) {
    parse_str($_GET['user_confirm'], $parsed);
    $lost_id = (int)($parsed['lost_id'] ?? 0);
    $found_id = (int)($parsed['found_id'] ?? 0);
}

$notification = '';
$notificationType = '';
$foundName = '';
$match_status = '';

if ($lost_id <= 0 || $found_id <= 0) {
    $notification = 'Invalid request parameters.';
    $notificationType = 'error';
} else {
    $stmt = $conn->prepare("SELECT id, status FROM matches WHERE lost_item_id = ? AND found_item_id = ?");
    $stmt->bind_param("ii", $lost_id, $found_id);
    $stmt->execute();
    $match = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$match) {
        $notification = 'This match may have already been processed or does not exist.';
        $notificationType = 'error';
        $match_status = 'not_found';
    } else {
        $match_id = $match['id'];
        $current_status = $match['status'];

        // Get found item name
        $stmt = $conn->prepare("SELECT name FROM items WHERE id = ?");
        $stmt->bind_param("i", $found_id);
        $stmt->execute();
        $foundItem = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $foundName = $foundItem['name'] ?? '';

        if ($current_status === 'user_confirmed') {
            $notification = 'Thank you for confirming that "' . htmlspecialchars($foundName) . '" is your lost item. Your confirmation has been recorded.';
            $notificationType = 'success';
            $match_status = 'confirmed';
        } elseif ($current_status === 'confirmed') {
            $notification = 'This match has already been approved by our administration team. Please check your email for pickup instructions.';
            $notificationType = 'info';
            $match_status = 'admin_confirmed';
        } elseif ($current_status === 'rejected') {
            $notification = 'This match has been rejected. We will continue searching for your lost item.';
            $notificationType = 'warning';
            $match_status = 'rejected';
        } else {
            // Update match status to user_confirmed
            $stmt = $conn->prepare("UPDATE matches SET status = 'user_confirmed' WHERE id = ?");
            $stmt->bind_param("i", $match_id);
            $stmt->execute();
            $stmt->close();

            $notification = 'Thank you for confirming that "' . htmlspecialchars($foundName) . '" is your lost item. Your confirmation has been recorded and forwarded to our administration team for final approval.';
            $notificationType = 'success';
            $match_status = 'confirmed';
        }
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Match Confirmation - Lost & Found</title>
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

        .notification.warning {
            background: #fff3cd;
            border-color: #ffc107;
            color: #856404;
        }

        .notification.info {
            background: #d1ecf1;
            border-color: #0c5460;
            color: #0c5460;
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

        .instructions {
            margin: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }

        .instructions h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 16px;
        }

        .instructions ol {
            margin-left: 20px;
            color: #555;
            line-height: 1.8;
        }

        .instructions li {
            margin-bottom: 10px;
        }

        .contact-info {
            margin: 0 30px 30px;
            padding: 15px;
            background: #e7f3ff;
            border-radius: 8px;
            border-left: 4px solid #0066cc;
            color: #003399;
        }

        .contact-info p:first-child {
            font-weight: bold;
            margin-bottom: 8px;
        }

        .contact-info p:last-child {
            font-size: 14px;
            margin: 0;
        }

        .button-group {
            margin: 30px;
            text-align: center;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: center;
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
            border: none;
            cursor: pointer;
            font-size: 14px;
        }

        .button:hover {
            background: #764ba2;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .button.secondary {
            background: #6c757d;
        }

        .button.secondary:hover {
            background: #5a6268;
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
            <h1>Match Confirmation Status</h1>
        </div>

        <?php if ($notification): ?>
            <div class="notification <?php echo htmlspecialchars($notificationType); ?>">
                <div class="icon">
                    <?php
                    switch ($notificationType) {
                        case 'success':
                            echo '&#10003;';
                            break;
                        case 'error':
                            echo '&#10007;';
                            break;
                        case 'warning':
                            echo '&#9888;';
                            break;
                        case 'info':
                            echo '&#8505;';
                            break;
                    }
                    ?>
                </div>
                <p><?php echo htmlspecialchars($notification); ?></p>
            </div>
        <?php endif; ?>

        <?php if ($match_status === 'confirmed'): ?>
            <div class="instructions">
                <h3>Next Steps:</h3>
                <ol>
                    <li>Our administration team will review your confirmation</li>
                    <li>Once the admin approves the match, you will receive a <strong>pickup instructions email</strong> with the deadline</li>
                    <li>You will have <strong>3 days</strong> from admin approval to claim your item at the Lost &amp; Found office</li>
                    <li>Bring a valid ID for verification purposes</li>
                </ol>
            </div>
            <div class="contact-info">
                <p>Need Help?</p>
                <p>If you have any questions or need assistance, please contact our administration office.</p>
            </div>
        <?php elseif ($match_status === 'not_found'): ?>
            <div class="contact-info">
                <p>What Happened?</p>
                <p>This match has already been processed. If you believe this is an error, please contact our administration team.</p>
            </div>
        <?php elseif (in_array($match_status, ['admin_confirmed', 'rejected'])): ?>
            <div class="contact-info">
                <p>Additional Information</p>
                <p>If you have questions about this match, please contact our administration team or check your email for more details.</p>
            </div>
        <?php endif; ?>

        <div class="button-group">
            <a href="../index.php" class="button">Return to Lost & Found</a>
            <?php if ($match_status === 'confirmed'): ?>
                <a href="javascript:void(0)" class="button secondary" onclick="window.print();">Print Confirmation</a>
            <?php endif; ?>
        </div>

        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> Lost & Found System. All rights reserved.</p>
        </div>
    </div>
</body>

</html>