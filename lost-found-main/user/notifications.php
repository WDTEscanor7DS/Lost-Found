<?php
$pageTitle = 'Notifications';
$activePage = 'notifications';
include __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../backend/claim_helpers.php';

$userId = (int) ($_SESSION['id'] ?? 0);

// Mark all as read if requested
if (isset($_POST['mark_all_read'])) {
  $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
  $stmt->bind_param("i", $userId);
  $stmt->execute();
  $stmt->close();
}

// Fetch notifications from notifications table
$stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY date_created DESC LIMIT 50");
$stmt->bind_param("i", $userId);
$stmt->execute();
$notifications = $stmt->get_result();
$stmt->close();

// Also fetch match notifications for items belonging to the user
$stmtNotif = $conn->prepare("
    SELECT m.*, li.name AS lost_name, li.email AS lost_email,
           fi.name AS found_name, fi.email AS found_email
    FROM matches m
    JOIN items li ON m.lost_item_id = li.id
    JOIN items fi ON m.found_item_id = fi.id
    WHERE li.user_id = ? OR fi.user_id = ?
    ORDER BY m.date_matched DESC
    LIMIT 20
");
$stmtNotif->bind_param("ii", $userId, $userId);
$stmtNotif->execute();
$recentMatches = $stmtNotif->get_result();

$unreadCount = getUnreadNotificationCount($conn, $userId);
?>

<div class="d-flex align-items-center justify-content-between mb-4">
  <h3 class="mb-0">Notifications</h3>
  <?php if ($unreadCount > 0): ?>
    <form method="POST" class="d-inline">
      <button type="submit" name="mark_all_read" class="btn btn-sm btn-outline-primary">
        <i class="bi bi-check-all me-1"></i> Mark All as Read (<?php echo $unreadCount; ?>)
      </button>
    </form>
  <?php endif; ?>
</div>

<!-- System Notifications -->
<?php if ($notifications->num_rows > 0): ?>
  <div class="card mb-4">
    <div class="card-header">
      <h5 class="mb-0"><i class="bi bi-bell me-2"></i>System Notifications</h5>
    </div>
    <div class="card-body p-0">
      <div class="list-group list-group-flush">
        <?php while ($n = $notifications->fetch_assoc()):
          $typeIcon = match ($n['type']) {
            'success' => 'bi-check-circle-fill text-success',
            'danger' => 'bi-x-circle-fill text-danger',
            'warning' => 'bi-exclamation-triangle-fill text-warning',
            default => 'bi-info-circle-fill text-info',
          };
          $bgClass = $n['is_read'] ? '' : 'bg-light';
        ?>
          <div class="list-group-item <?php echo $bgClass; ?> py-3">
            <div class="d-flex align-items-start">
              <i class="bi <?php echo $typeIcon; ?> fs-5 me-3 mt-1"></i>
              <div class="flex-grow-1">
                <h6 class="mb-1">
                  <?php echo htmlspecialchars($n['title']); ?>
                  <?php if (!$n['is_read']): ?><span class="badge bg-primary ms-1">New</span><?php endif; ?>
                </h6>
                <p class="mb-1 text-muted"><?php echo htmlspecialchars($n['message']); ?></p>
                <small class="text-muted"><?php echo date('M d, Y g:i A', strtotime($n['date_created'])); ?></small>
                <?php if (!empty($n['link'])): ?>
                  <a href="<?php echo htmlspecialchars($n['link']); ?>" class="ms-2 small">View details &rarr;</a>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endwhile; ?>
      </div>
    </div>
  </div>
<?php endif; ?>

<!-- Match Notifications -->
<div class="card">
  <div class="card-header">
    <h5 class="mb-0"><i class="bi bi-link-45deg me-2"></i>Match Activity</h5>
  </div>
  <div class="card-body">
    <?php if ($recentMatches->num_rows === 0): ?>
      <div class="text-center py-4">
        <p class="text-muted mb-0">No match activity yet.</p>
      </div>
    <?php else: ?>
      <?php while ($row = $recentMatches->fetch_assoc()): ?>
        <div class="d-flex align-items-start border-bottom pb-3 mb-3">
          <div class="flex-grow-1">
            <h6 class="mb-1">
              <?php
              $icon = match ($row['status']) {
                'pending' => '<span class="badge bg-warning me-2">Pending</span>',
                'user_confirmed' => '<span class="badge bg-info me-2">Awaiting Admin</span>',
                'confirmed' => '<span class="badge bg-success me-2">Confirmed</span>',
                'rejected' => '<span class="badge bg-danger me-2">Rejected</span>',
                default => '',
              };
              echo $icon;
              ?>
              <?php echo htmlspecialchars($row['lost_name']); ?> matched with <?php echo htmlspecialchars($row['found_name']); ?>
            </h6>
            <small class="text-muted">Score: <?php echo (int)$row['match_score']; ?>% &bull; <?php echo htmlspecialchars($row['date_matched']); ?></small>
          </div>
        </div>
      <?php endwhile; ?>
    <?php endif; ?>
  </div>
</div>

<?php if ($notifications->num_rows === 0 && $recentMatches->num_rows === 0): ?>
  <div class="card mt-3">
    <div class="card-body text-center py-5">
      <i class="bi bi-bell-slash fs-1 text-muted mb-3"></i>
      <h5 class="text-muted">No notifications yet</h5>
      <p class="text-muted">When your items get matched or claims are processed, notifications will appear here.</p>
    </div>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>