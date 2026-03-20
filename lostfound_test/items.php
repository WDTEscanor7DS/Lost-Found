<?php
include "config.php";
include "header.php";
include "sidebar.php";

// Notification system
$notification = '';
$notificationType = '';

// Handle status update notification from update_status.php redirect
if (isset($_GET['status_updated'])) {
    $newStatus = htmlspecialchars($_GET['new_status'] ?? '');
    $notification = 'Item status updated to "' . ucfirst($newStatus) . '" successfully.';
    $notificationType = 'success';
} elseif (isset($_GET['status_error'])) {
    $notification = 'Error: Could not update item status.';
    $notificationType = 'error';
}
?>

<h2>Item Management</h2>

<?php
// VERIFY ITEM (Approve or Reject)
if (isset($_POST['verify_item'])) {
    $item_id = (int)$_POST['item_id'];
    $verification = $_POST['verification']; // 'approved' or 'rejected'

    if ($item_id > 0 && in_array($verification, ['approved', 'rejected'])) {
        $stmt = $conn->prepare("UPDATE items SET verification_status = ? WHERE id = ?");
        $stmt->bind_param("si", $verification, $item_id);
        $stmt->execute();
        $stmt->close();

        $notification = 'Item ' . ucfirst($verification) . ' successfully.';
        $notificationType = 'success';
    }
}

// Display notification if exists
if (!empty($notification)): ?>
    <div style="padding:15px;margin-bottom:20px;border-radius:5px;background:<?php echo $notificationType === 'success' ? '#d4edda' : '#f8d7da'; ?>;color:<?php echo $notificationType === 'success' ? '#155724' : '#721c24'; ?>;border:1px solid <?php echo $notificationType === 'success' ? '#c3e6cb' : '#f5c6cb'; ?>;display:flex;justify-content:space-between;align-items:center;">
        <span><?php echo htmlspecialchars($notification); ?></span>
        <button type="button" onclick="this.parentElement.style.display='none'" style="background:none;border:none;color:inherit;cursor:pointer;font-size:20px;padding:0;margin-left:15px;">&times;</button>
    </div>
<?php endif;

// Display Pending Items for Admin Verification
echo "<h3 style='color:#ff6b6b;'>Pending Verification</h3>";
$pending = $conn->query("SELECT * FROM items WHERE verification_status='pending' ORDER BY date_created DESC");

if ($pending->num_rows == 0) {
    echo "<p style='color:#888;'>All items have been verified.</p>";
} else {
    while ($row = $pending->fetch_assoc()) {
        echo "<div style='margin-bottom:25px;padding:15px;border:3px solid #ff6b6b;border-radius:10px;background:#fff5f5;'>";

        echo "<h4 style='margin-top:0;'>" . htmlspecialchars($row['name']) . " (" . strtoupper($row['type']) . ")</h4>";
        echo "<p><strong>Status:</strong> <span style='color:#ff6b6b;'>PENDING VERIFICATION</span></p>";
        echo "<p><strong>Location:</strong> " . htmlspecialchars($row['location']) . "</p>";
        echo "<p><strong>Category:</strong> " . htmlspecialchars($row['category']) . "</p>";
        echo "<p><strong>Color:</strong> " . htmlspecialchars($row['color']) . "</p>";

        echo "<p><strong>Description:</strong></p>";
        echo "<div style='margin-bottom:10px;background:white;padding:10px;border-radius:5px;'>" . nl2br(htmlspecialchars($row['description'])) . "</div>";

        if (!empty($row['image']) && file_exists("uploads/" . $row['image'])) {
            echo "<img src='uploads/" . htmlspecialchars($row[' image']) . "' 
                  width='200' 
                  style='border-radius:8px;border:2px solid #ddd;margin-bottom:15px;display:block;'><br>";
        }

        echo "<form method='POST' style='display:inline-flex;gap:10px;'>";
        echo "<input type='hidden' name='item_id' value='" . $row['id'] . "'>";
        echo "<button type='submit' name='verify_item' value='approve' style='padding:8px 15px;background:#4CAF50;color:white;border:none;border-radius:5px;cursor:pointer;'>";
        echo "✓ Approve as Real";
        echo "</button>";
        echo "<input type='hidden' name='verification' value='approved'>";
        echo "</form>";

        echo "<form method='POST' style='display:inline;'>";
        echo "<input type='hidden' name='item_id' value='" . $row['id'] . "'>";
        echo "<input type='hidden' name='verification' value='rejected'>";
        echo "<button type='submit' name='verify_item' style='padding:8px 15px;background:#f44336;color:white;border:none;border-radius:5px;cursor:pointer;'>";
        echo "✗ Reject as Fake";
        echo "</button>";
        echo "</form>";

        echo "</div>";
    }
}
?>

<hr style="margin:40px 0;border:none;border-top:2px solid #ddd;">

<?php
// Display Approved Lost Items (only open ones)
echo "<h3 style='color:#2196F3;'>Lost Items</h3>";
$lost = $conn->query("SELECT * FROM items 
                      WHERE type='lost' AND verification_status='approved' AND status='open'
                      ORDER BY date_created DESC");

if ($lost->num_rows == 0) {
    echo "<p style='color:#888;'>No lost items at the moment.</p>";
} else {
    while ($row = $lost->fetch_assoc()) {
        echo "<div style='margin-bottom:25px;padding:15px;border:1px solid #2196F3;border-radius:10px;background:#e3f2fd;'>";

        echo "<h4 style='margin-top:0;color:#1565c0;'>" . htmlspecialchars($row['name']) . "</h4>";
        echo "<p><strong>Status:</strong> " . htmlspecialchars($row['status']) . "</p>";
        echo "<p><strong>Location:</strong> " . htmlspecialchars($row['location']) . "</p>";
        echo "<p><strong>Category:</strong> " . htmlspecialchars($row['category']) . "</p>";
        echo "<p><strong>Color:</strong> " . htmlspecialchars($row['color']) . "</p>";

        echo "<p><strong>Description:</strong></p>";
        echo "<div style='margin-bottom:10px;background:white;padding:10px;border-radius:5px;'>" . nl2br(htmlspecialchars($row['description'])) . "</div>";

        if (!empty($row['image']) && file_exists("uploads/" . $row['image'])) {
            echo "<img src='uploads/" . htmlspecialchars($row['image']) . "' 
                  width='200' 
                  style='border-radius:8px;border:1px solid #ddd;margin-bottom:10px;'><br>";
        }

        echo "</div>";
    }
}
?>

<hr style="margin:40px 0;border:none;border-top:2px solid #ddd;">

<?php
// Display Approved Found Items (only open ones)
echo "<h3 style='color:#4CAF50;'>Found Items</h3>";
$found = $conn->query("SELECT * FROM items 
                       WHERE type='found' AND verification_status='approved' AND status='open'
                       ORDER BY date_created DESC");

if ($found->num_rows == 0) {
    echo "<p style='color:#888;'>No found items at the moment.</p>";
} else {
    while ($row = $found->fetch_assoc()) {
        echo "<div style='margin-bottom:25px;padding:15px;border:1px solid #4CAF50;border-radius:10px;background:#f1f8e9;'>";

        echo "<h4 style='margin-top:0;color:#33691e;'>" . htmlspecialchars($row['name']) . "</h4>";
        echo "<p><strong>Status:</strong> " . htmlspecialchars($row['status']) . "</p>";
        echo "<p><strong>Location:</strong> " . htmlspecialchars($row['location']) . "</p>";
        echo "<p><strong>Category:</strong> " . htmlspecialchars($row['category']) . "</p>";
        echo "<p><strong>Color:</strong> " . htmlspecialchars($row['color']) . "</p>";

        echo "<p><strong>Description:</strong></p>";
        echo "<div style='margin-bottom:10px;background:white;padding:10px;border-radius:5px;'>" . nl2br(htmlspecialchars($row['description'])) . "</div>";

        if (!empty($row['image']) && file_exists("uploads/" . $row['image'])) {
            echo "<img src='uploads/" . htmlspecialchars($row['image']) . "' 
                  width='200' 
                  style='border-radius:8px;border:1px solid #ddd;margin-bottom:10px;'><br>";
        }

        echo "</div>";
    }
}
?>

</div>
</body>

</html>