<?php

/**
 * User-facing match confirmation page.
 * Linked from email notification — the "YES" button.
 * Now uses a secure claim_token instead of raw IDs.
 */
require_once __DIR__ . '/config.php';

/**
 * Generate the PDF in-memory and email it to the claimant.
 */
function sendConfirmationPdfEmail(mysqli $conn, array $match, array $lostItem, array $foundItem, string $recipientEmail): bool
{
    $autoloadPath = __DIR__ . '/../vendor/autoload.php';
    $mailerPath = __DIR__ . '/PHPMailer/';

    if (!file_exists($autoloadPath) || !file_exists($mailerPath . 'PHPMailer.php')) {
        return false;
    }

    require_once $autoloadPath;
    require_once $mailerPath . 'PHPMailer.php';
    require_once $mailerPath . 'SMTP.php';
    require_once $mailerPath . 'Exception.php';

    $matchId     = (int) $match['id'];
    $matchScore  = (int) $match['match_score'];
    $claimRef    = 'MCH-' . str_pad($matchId, 6, '0', STR_PAD_LEFT);
    $matchStatus = 'User Confirmed (Pending Admin)';
    $currentDate = date('F j, Y');
    $currentTime = date('g:i A');
    $claimantName  = htmlspecialchars($lostItem['fullname'] ?? $lostItem['name'] ?? 'N/A');
    $claimantEmail = htmlspecialchars($lostItem['email'] ?? 'N/A');

    // Build the same PDF HTML as generate_match_pdf.php
    $pdfHtml = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>
        body{font-family:"Helvetica","Arial",sans-serif;font-size:12px;color:#333;margin:0;padding:25px;line-height:1.5}
        .header{text-align:center;border-bottom:3px solid #2c3e50;padding-bottom:15px;margin-bottom:25px}
        .header h1{margin:0;font-size:22px;color:#2c3e50;letter-spacing:1px}
        .header h2{margin:5px 0 0;font-size:15px;color:#555;font-weight:normal}
        .header p{margin:8px 0 0;color:#888;font-size:10px}
        .claim-ref-box{background:#f0f4f8;border:2px solid #2c3e50;border-radius:8px;padding:12px;text-align:center;margin:15px 0 25px}	
        .claim-ref-box .label{font-size:10px;color:#666;text-transform:uppercase;letter-spacing:1px}
        .claim-ref-box .value{font-size:20px;font-weight:bold;color:#2c3e50;margin-top:4px}
        .claim-ref-box .status{margin-top:6px;font-size:11px}
        .status-badge{display:inline-block;padding:3px 12px;border-radius:4px;font-weight:bold;font-size:10px;text-transform:uppercase;background:#d4edda;color:#155724}
        .section{margin:20px 0}.section-title{background:#2c3e50;color:white;padding:8px 12px;font-size:12px;font-weight:bold;margin-bottom:0}
        .section-body{border:1px solid #ddd;border-top:none;padding:12px 15px}
        table{width:100%;border-collapse:collapse}table td{padding:5px 8px;vertical-align:top;font-size:11px}
        table td.lbl{font-weight:bold;width:35%;color:#555}
        .instructions-section{margin:25px 0;border:1px solid #ddd;border-radius:6px;overflow:hidden}
        .inst-title{background:#e8f5e9;color:#2e7d32;padding:10px 15px;font-weight:bold;font-size:13px;border-bottom:1px solid #c8e6c9}
        .inst-body{padding:15px}.step{margin-bottom:10px;padding-left:10px}
        .step-num{display:inline-block;width:22px;height:22px;background:#2c3e50;color:white;border-radius:50%;text-align:center;line-height:22px;font-size:10px;font-weight:bold;margin-right:8px}
        .step-text{font-size:11px;color:#444}
        .sig-section{margin-top:40px;page-break-inside:avoid}.sig-row{overflow:hidden;margin-bottom:15px}
        .sig-col{float:left;width:48%}.sig-col:last-child{float:right}
        .sig-line{border-top:1px solid #333;width:100%;margin-top:45px;padding-top:5px;font-size:10px;color:#666}
        .footer{margin-top:30px;padding-top:12px;border-top:2px solid #2c3e50;text-align:center;font-size:9px;color:#888}.footer p{margin:2px 0}
    </style></head><body>
    <div class="header"><h1>LOST &amp; FOUND SYSTEM</h1><h2>Claim Confirmation Document</h2>
    <p>Official Document &mdash; Generated on ' . $currentDate . ' at ' . $currentTime . '</p></div>
    <div class="claim-ref-box"><div class="label">Claim Reference Number</div><div class="value">' . $claimRef . '</div>
    <div class="status"><span class="status-badge">' . htmlspecialchars($matchStatus) . '</span></div></div>
    <div class="section"><div class="section-title">CLAIMANT INFORMATION</div><div class="section-body"><table>
    <tr><td class="lbl">Full Name:</td><td>' . $claimantName . '</td></tr>
    <tr><td class="lbl">Email:</td><td>' . $claimantEmail . '</td></tr>
    <tr><td class="lbl">Date of Claim:</td><td>' . $currentDate . '</td></tr>
    <tr><td class="lbl">Time:</td><td>' . $currentTime . '</td></tr></table></div></div>
    <div class="section"><div class="section-title">ITEM INFORMATION</div><div class="section-body"><table>
    <tr><td class="lbl">Item Name:</td><td>' . htmlspecialchars($foundItem['name']) . '</td></tr>
    <tr><td class="lbl">Category:</td><td>' . htmlspecialchars($foundItem['category']) . '</td></tr>
    <tr><td class="lbl">Color:</td><td>' . htmlspecialchars($foundItem['color']) . '</td></tr>
    <tr><td class="lbl">Location Found:</td><td>' . htmlspecialchars($foundItem['location']) . '</td></tr>
    <tr><td class="lbl">Description:</td><td>' . htmlspecialchars($foundItem['description']) . '</td></tr></table></div></div>
    <div class="section"><div class="section-title">LOST ITEM REPORTED</div><div class="section-body"><table>
    <tr><td class="lbl">Reported Item:</td><td>' . htmlspecialchars($lostItem['name']) . '</td></tr>
    <tr><td class="lbl">Description:</td><td>' . htmlspecialchars($lostItem['description']) . '</td></tr>
    <tr><td class="lbl">Location Lost:</td><td>' . htmlspecialchars($lostItem['location']) . '</td></tr>
    <tr><td class="lbl">Match Score:</td><td>' . $matchScore . '%</td></tr>
    <tr><td class="lbl">Status:</td><td>' . htmlspecialchars($matchStatus) . '</td></tr></table></div></div>
    <div class="instructions-section"><div class="inst-title">CLAIM PROCESS INSTRUCTIONS</div><div class="inst-body">
    <div class="step"><span class="step-num">1</span><span class="step-text">Go to the <strong>Lost &amp; Found Office</strong> during office hours</span></div>
    <div class="step"><span class="step-num">2</span><span class="step-text">Present a <strong>valid government-issued ID</strong></span></div>
    <div class="step"><span class="step-num">3</span><span class="step-text">Show this <strong>Claim Confirmation Document</strong></span></div>
    <div class="step"><span class="step-num">4</span><span class="step-text"><strong>Verify the item details</strong> with staff</span></div>
    <div class="step"><span class="step-num">5</span><span class="step-text"><strong>Sign the claim log</strong> and collect your item</span></div>
    </div></div>
    <div class="sig-section"><div class="sig-row"><div class="sig-col"><div class="sig-line">Claimant Signature</div></div>
    <div class="sig-col"><div class="sig-line">Authorized Staff Signature</div></div></div>
    <div class="sig-row"><div class="sig-col"><div class="sig-line">Date</div></div>
    <div class="sig-col"><div class="sig-line">Date</div></div></div></div>
    <div class="footer"><p><strong>Lost &amp; Found Management System</strong></p>
    <p>Claim Ref: ' . $claimRef . ' &bull; Generated: ' . $currentDate . ' ' . $currentTime . '</p>
    <p><em>Present this document when collecting claimed items.</em></p></div></body></html>';

    // Generate PDF in memory
    $options = new \Dompdf\Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isPhpEnabled', false);
    $options->set('isRemoteEnabled', false);
    $options->set('defaultFont', 'Helvetica');

    $dompdf = new \Dompdf\Dompdf($options);
    $dompdf->loadHtml($pdfHtml);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $pdfContent = $dompdf->output();

    // Send email with PDF attachment
    try {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = 'tls';
        $mail->Port       = MAIL_PORT;

        $mail->setFrom(MAIL_USERNAME, MAIL_FROM_NAME);
        $mail->addAddress($recipientEmail);
        $mail->isHTML(true);
        $mail->Subject = 'Your Claim Confirmation - ' . $claimRef;

        $mail->Body = '
        <div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;">
            <h2 style="color:#2c3e50;">Claim Confirmation Received</h2>
            <p>Thank you for confirming your lost item match. Your claim reference is <strong>' . $claimRef . '</strong>.</p>
            <p>Please find your <strong>Claim Confirmation Document</strong> attached to this email as a PDF.</p>
            <div style="background:#e8f5e9;padding:15px;border-radius:8px;margin:15px 0;border-left:4px solid #4CAF50;">
                <p style="margin:0;"><strong>Next Steps:</strong></p>
                <ol style="margin:10px 0 0;">
                    <li>Our admin team will review your confirmation</li>
                    <li>You will receive pickup instructions once approved</li>
                    <li>Bring a valid ID and this PDF when collecting your item</li>
                </ol>
            </div>
            <p style="color:#888;font-size:12px;">This is an automated message from the Lost &amp; Found System.</p>
        </div>';

        $filename = 'Claim_Confirmation_' . $claimRef . '.pdf';
        $mail->addStringAttachment($pdfContent, $filename, 'base64', 'application/pdf');
        $mail->send();
        return true;
    } catch (\Exception $e) {
        return false;
    }
}

$token = trim($_GET['token'] ?? '');

// Backward compatibility: support old-style links with lost_id & found_id
$lost_id = 0;
$found_id = 0;
if (empty($token)) {
    if (isset($_GET['lost_id']) && isset($_GET['found_id'])) {
        $lost_id = (int)$_GET['lost_id'];
        $found_id = (int)$_GET['found_id'];
    } elseif (isset($_GET['user_confirm'])) {
        parse_str($_GET['user_confirm'], $parsed);
        $lost_id = (int)($parsed['lost_id'] ?? 0);
        $found_id = (int)($parsed['found_id'] ?? 0);
    }
}

$notification = '';
$notificationType = '';
$match_status = '';
$matchData = null;
$lostItemData = null;
$foundItemData = null;
$claimantData = null;
$matchId = 0;

if (empty($token) && ($lost_id <= 0 || $found_id <= 0)) {
    $notification = 'Invalid or missing claim token. Please use the link from your email.';
    $notificationType = 'error';
} else {
    // Look up match by token or by IDs (backward compat)
    if (!empty($token)) {
        $stmt = $conn->prepare("SELECT id, lost_item_id, found_item_id, status, match_score, claim_token FROM matches WHERE claim_token = ?");
        $stmt->bind_param("s", $token);
    } else {
        $stmt = $conn->prepare("SELECT id, lost_item_id, found_item_id, status, match_score, claim_token FROM matches WHERE lost_item_id = ? AND found_item_id = ?");
        $stmt->bind_param("ii", $lost_id, $found_id);
    }
    $stmt->execute();
    $matchData = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$matchData) {
        $notification = 'This claim link is invalid or has already been processed.';
        $notificationType = 'error';
        $match_status = 'not_found';
    } else {
        $matchId = $matchData['id'];
        $lost_id = (int)$matchData['lost_item_id'];
        $found_id = (int)$matchData['found_item_id'];
        $current_status = $matchData['status'];

        // Get lost item details (the claimant's item)
        $stmt = $conn->prepare("SELECT i.*, u.fullname FROM items i LEFT JOIN users u ON i.user_id = u.id WHERE i.id = ?");
        $stmt->bind_param("i", $lost_id);
        $stmt->execute();
        $lostItemData = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // Get found item details
        $stmt = $conn->prepare("SELECT * FROM items WHERE id = ?");
        $stmt->bind_param("i", $found_id);
        $stmt->execute();
        $foundItemData = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $foundName = $foundItemData['name'] ?? 'Unknown Item';

        // Build claimant info from the lost item reporter
        $claimantData = [
            'name' => $lostItemData['fullname'] ?? ($lostItemData['name'] ?? 'N/A'),
            'email' => $lostItemData['email'] ?? 'N/A',
        ];

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
            // First-time confirmation: update to user_confirmed
            $stmt = $conn->prepare("UPDATE matches SET status = 'user_confirmed' WHERE id = ?");
            $stmt->bind_param("i", $matchId);
            $stmt->execute();
            $stmt->close();

            $notification = 'Thank you for confirming that "' . htmlspecialchars($foundName) . '" is your lost item. Your confirmation has been recorded and forwarded to our administration team for final approval.';
            $notificationType = 'success';
            $match_status = 'confirmed';

            // Auto-email the confirmation PDF to the user
            $pdfEmailSent = false;
            $recipientEmail = $claimantData['email'] ?? ($lostItemData['email'] ?? '');
            if (!empty($recipientEmail) && $recipientEmail !== 'N/A' && !empty($matchData['claim_token'])) {
                $pdfEmailSent = sendConfirmationPdfEmail($conn, $matchData, $lostItemData, $foundItemData, $recipientEmail);
            }
        }
    }
}

// Build a safe token for the PDF link
$pdfToken = $matchData['claim_token'] ?? $token;
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

        .details-section {
            margin: 0 30px 20px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
        }

        .details-section h3 {
            background: #f8f9fa;
            margin: 0;
            padding: 12px 15px;
            font-size: 15px;
            color: #333;
            border-bottom: 1px solid #e0e0e0;
        }

        .details-section table {
            width: 100%;
            border-collapse: collapse;
        }

        .details-section table td {
            padding: 8px 15px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
            vertical-align: top;
        }

        .details-section table tr:last-child td {
            border-bottom: none;
        }

        .label-cell {
            font-weight: 600;
            color: #555;
            width: 35%;
        }

        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-badge.confirmed {
            background: #d4edda;
            color: #155724;
        }

        .status-badge.rejected {
            background: #f8d7da;
            color: #721c24;
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

        <?php if ($foundItemData && $claimantData && in_array($match_status, ['confirmed', 'admin_confirmed'])): ?>
            <!-- Claimant Information -->
            <div class="details-section">
                <h3>&#128100; Claimant Information</h3>
                <table>
                    <tr>
                        <td class="label-cell">Full Name:</td>
                        <td><?php echo htmlspecialchars($claimantData['name']); ?></td>
                    </tr>
                    <tr>
                        <td class="label-cell">Email:</td>
                        <td><?php echo htmlspecialchars($claimantData['email']); ?></td>
                    </tr>
                </table>
            </div>

            <!-- Found Item Details -->
            <div class="details-section">
                <h3>&#128270; Item Details</h3>
                <table>
                    <tr>
                        <td class="label-cell">Item Name:</td>
                        <td><?php echo htmlspecialchars($foundItemData['name']); ?></td>
                    </tr>
                    <tr>
                        <td class="label-cell">Category:</td>
                        <td><?php echo htmlspecialchars($foundItemData['category']); ?></td>
                    </tr>
                    <tr>
                        <td class="label-cell">Color:</td>
                        <td><?php echo htmlspecialchars($foundItemData['color']); ?></td>
                    </tr>
                    <tr>
                        <td class="label-cell">Location:</td>
                        <td><?php echo htmlspecialchars($foundItemData['location']); ?></td>
                    </tr>
                    <tr>
                        <td class="label-cell">Description:</td>
                        <td><?php echo nl2br(htmlspecialchars($foundItemData['description'])); ?></td>
                    </tr>
                    <?php if (!empty($foundItemData['image'])): ?>
                        <tr>
                            <td class="label-cell">Image:</td>
                            <td><img src="../uploads/<?php echo htmlspecialchars($foundItemData['image']); ?>" alt="Item" style="max-width:200px;border-radius:6px;"></td>
                        </tr>
                    <?php endif; ?>
                </table>
            </div>

            <!-- Match Info -->
            <div class="details-section">
                <h3>&#128279; Match Details</h3>
                <table>
                    <tr>
                        <td class="label-cell">Match ID:</td>
                        <td>#<?php echo (int)$matchId; ?></td>
                    </tr>
                    <tr>
                        <td class="label-cell">Match Score:</td>
                        <td><?php echo (int)($matchData['match_score'] ?? 0); ?>%</td>
                    </tr>
                    <tr>
                        <td class="label-cell">Status:</td>
                        <td>
                            <span class="status-badge <?php echo $match_status === 'admin_confirmed' ? 'confirmed' : htmlspecialchars($match_status); ?>">
                                <?php echo $match_status === 'admin_confirmed' ? 'Admin Approved' : ucfirst($match_status); ?>
                            </span>
                        </td>
                    </tr>
                </table>
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
                    <li>Present the <strong>Claim Confirmation PDF</strong> at the office</li>
                </ol>
            </div>
            <div class="contact-info">
                <p>Need Help?</p>
                <p>If you have any questions or need assistance, please contact our administration office.</p>
            </div>
        <?php elseif ($match_status === 'not_found'): ?>
            <div class="contact-info">
                <p>What Happened?</p>
                <p>This claim link is invalid or has already been processed. If you believe this is an error, please contact our administration team.</p>
            </div>
        <?php elseif (in_array($match_status, ['admin_confirmed', 'rejected'])): ?>
            <div class="contact-info">
                <p>Additional Information</p>
                <p>If you have questions about this match, please contact our administration team or check your email for more details.</p>
            </div>
        <?php endif; ?>

        <div class="button-group">
            <a href="../index.php" class="button">Return to Lost & Found</a>
            <?php if (in_array($match_status, ['confirmed', 'admin_confirmed']) && !empty($pdfToken)): ?>
                <a href="generate_match_pdf.php?token=<?php echo urlencode($pdfToken); ?>" class="button secondary" target="_blank">&#128196; Download Confirmation PDF</a>
            <?php endif; ?>
        </div>

        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> Lost & Found System. All rights reserved.</p>
        </div>
    </div>
</body>

</html>