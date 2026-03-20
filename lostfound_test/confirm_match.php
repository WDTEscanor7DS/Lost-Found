<?php
include "config.php";

// Get parameters from URL
$lost_id = (int)($_GET['lost_id'] ?? 0);
$found_id = (int)($_GET['found_id'] ?? 0);

if ($lost_id <= 0 || $found_id <= 0) {
    die("Invalid request parameters.");
}

// Find the match
$stmt = $conn->prepare("SELECT id FROM matches WHERE lost_item_id = ? AND found_item_id = ? AND status = 'pending'");
$stmt->bind_param("ii", $lost_id, $found_id);
$stmt->execute();
$matchResult = $stmt->get_result();
$match = $matchResult->fetch_assoc();
$stmt->close();

if (!$match) {
    echo "<h2>Match Not Found</h2>";
    echo "<p>This match may have already been processed or does not exist.</p>";
    echo "<p><a href='index.php'>Return to Home</a></p>";
    exit;
}

$match_id = $match['id'];

// Update match status to user_confirmed (or keep as pending but mark as user_confirmed)
$stmt = $conn->prepare("UPDATE matches SET status = 'user_confirmed' WHERE id = ?");
$stmt->bind_param("i", $match_id);
$stmt->execute();
$stmt->close();

// Get item details for display
$stmt = $conn->prepare("SELECT name FROM items WHERE id = ?");
$stmt->bind_param("i", $found_id);
$stmt->execute();
$foundItem = $stmt->get_result()->fetch_assoc();
$stmt->close();

$foundName = $foundItem['name'];
?>

<!DOCTYPE html>
<html>

<head>
    <title>Match Confirmed - Lost & Found</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 40px;
            background: #f5f5f5;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .success {
            color: #4CAF50;
            text-align: center;
            font-size: 48px;
            margin-bottom: 20px;
        }

        .message {
            text-align: center;
            font-size: 18px;
            line-height: 1.6;
            color: #333;
        }

        .instructions {
            background: #e8f5e8;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #4CAF50;
        }

        .contact {
            background: #fff3cd;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #ffc107;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="success">✓</div>
        <h1 style="text-align: center; color: #4CAF50;">Match Confirmed!</h1>

        <div class="message">
            <p>Thank you for confirming that <strong>"<?php echo htmlspecialchars($foundName); ?>"</strong> is your lost item.</p>
            <p>Your confirmation has been recorded and forwarded to our administration team for final approval.</p>
        </div>

        <div class="instructions">
            <h3>Next Steps:</h3>
            <ol>
                <li>Our administration team will review your confirmation</li>
                <li>Once approved, you'll receive another email with pickup instructions</li>
                <li>Please visit the Lost & Found claim booth during business hours to collect your item</li>
                <li>Bring a valid ID for verification purposes</li>
            </ol>
        </div>

        <div class="contact">
            <p><strong>Need Help?</strong></p>
            <p>If you have any questions or need assistance, please contact our administration office.</p>
        </div>

        <p style="text-align: center; margin-top: 30px;">
            <a href="index.php" style="background: #2196F3; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold;">Return to Lost & Found</a>
        </p>
    </div>
</body>

</html>