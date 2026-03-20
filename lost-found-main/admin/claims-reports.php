<?php
require_once __DIR__ . '/../auth/guard_admin.php';

/**
 * Admin: Claims Reports.
 * Lists all confirmed matches (claimed/returned items).
 */
$pageTitle = 'Claims Reports';
$activePage = 'claims-reports';

require_once __DIR__ . '/../backend/config.php';

// Get all confirmed matches with item details
$result = $conn->query("
    SELECT m.id as match_id, m.match_score, m.date_matched, m.status as match_status,
           li.id as lost_id, li.name as lost_name, li.email as lost_email,
           li.category, li.location as lost_location,
           fi.id as found_id, fi.name as found_name, fi.email as found_email,
           fi.location as found_location
    FROM matches m
    JOIN items li ON m.lost_item_id = li.id
    JOIN items fi ON m.found_item_id = fi.id
    WHERE m.status = 'confirmed'
    ORDER BY m.date_matched DESC
");

$totalClaims = $result->num_rows;

// Pending claims (user_confirmed waiting for admin)
$pendingCount = $conn->query("SELECT COUNT(*) as c FROM matches WHERE status = 'user_confirmed'")->fetch_assoc()['c'];

include __DIR__ . '/includes/header.php';
?>

<h3 class="mb-1">Claims Reports</h3>
<p class="text-muted mb-4">All successfully claimed and returned items</p>

<!-- Summary -->
<div class="row gy-4 mb-4">
    <div class="col-sm-6 col-xl-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar">
                        <div class="avatar-initial bg-success rounded shadow-xs"><i class="icon-base ri ri-checkbox-circle-line icon-24px"></i></div>
                    </div>
                    <div class="ms-3">
                        <p class="mb-0">Total Confirmed Claims</p>
                        <h5 class="mb-0"><?php echo (int)$totalClaims; ?></h5>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar">
                        <div class="avatar-initial bg-warning rounded shadow-xs"><i class="icon-base ri ri-time-line icon-24px"></i></div>
                    </div>
                    <div class="ms-3">
                        <p class="mb-0">Pending Confirmation</p>
                        <h5 class="mb-0"><?php echo (int)$pendingCount; ?></h5>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Claims Table -->
<div class="card">
    <div class="card-body">
        <?php if ($totalClaims === 0): ?>
            <div class="text-center py-5">
                <h5 class="text-muted">No confirmed claims yet.</h5>
                <p class="text-muted">Claims will appear here once matches are confirmed by admin.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Match #</th>
                            <th>Lost Item</th>
                            <th>Owner Email</th>
                            <th>Found Item</th>
                            <th>Category</th>
                            <th>Score</th>
                            <th>Date Claimed</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo (int)$row['match_id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['lost_name']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($row['lost_location']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($row['lost_email'] ?? ''); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['found_name']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($row['found_location']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($row['category']); ?></td>
                                <td><span class="badge bg-success"><?php echo (int)$row['match_score']; ?>%</span></td>
                                <td><?php echo date('M d, Y', strtotime($row['date_matched'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>