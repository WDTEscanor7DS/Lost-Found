<?php
require_once __DIR__ . '/../auth/guard_admin.php';
require_once __DIR__ . '/../backend/config.php';

// Auto-sync item statuses based on matches table
$conn->query("
    UPDATE items i
    JOIN matches m ON (i.id = m.lost_item_id OR i.id = m.found_item_id)
    SET i.status = 'claimed'
    WHERE m.status = 'confirmed' AND i.status != 'claimed'
");
$conn->query("
    UPDATE items i
    JOIN matches m ON (i.id = m.lost_item_id OR i.id = m.found_item_id)
    SET i.status = 'matched'
    WHERE m.status IN ('pending', 'user_confirmed') AND i.status = 'open'
");
$conn->query("
    UPDATE items i
    SET i.status = 'open'
    WHERE i.status = 'matched'
    AND i.verification_status = 'approved'
    AND NOT EXISTS (
        SELECT 1 FROM matches m
        WHERE (m.lost_item_id = i.id OR m.found_item_id = i.id)
        AND m.status IN ('pending', 'user_confirmed', 'confirmed')
    )
");

$totalItems = $conn->query("SELECT COUNT(*) as c FROM items WHERE verification_status='approved'")->fetch_assoc()['c'] ?? 0;
$pendingVerification = $conn->query("SELECT COUNT(*) as c FROM items WHERE verification_status='pending'")->fetch_assoc()['c'] ?? 0;
$openLost = $conn->query("SELECT COUNT(*) as c FROM items WHERE type='lost' AND status='open' AND verification_status='approved'")->fetch_assoc()['c'] ?? 0;
$openFound = $conn->query("SELECT COUNT(*) as c FROM items WHERE type='found' AND status='open' AND verification_status='approved'")->fetch_assoc()['c'] ?? 0;
$matchedItems = $conn->query("SELECT COUNT(*) as c FROM items WHERE status='matched'")->fetch_assoc()['c'] ?? 0;
$claimedItems = $conn->query("SELECT COUNT(*) as c FROM items WHERE status='claimed' AND verification_status='approved'")->fetch_assoc()['c'] ?? 0;
$totalMatches = $conn->query("SELECT COUNT(*) as c FROM matches")->fetch_assoc()['c'] ?? 0;

$recentItems = $conn->query("SELECT * FROM items WHERE verification_status='approved' ORDER BY date_created DESC LIMIT 8");

$pageTitle = 'Dashboard';
$activePage = 'dashboard';
include __DIR__ . '/includes/header.php';
?>

            <h3 class="mb-1">Dashboard</h3>
            <p class="mb-6">Lost & Found System Overview</p>

            <div class="row gy-6">
              <div class="col-lg-3 col-sm-6">
                <div class="card h-100">
                  <div class="card-body">
                    <div class="d-flex align-items-center">
                      <div class="avatar">
                        <div class="avatar-initial bg-warning rounded shadow-xs">
                          <i class="icon-base ri ri-time-line icon-24px"></i>
                        </div>
                      </div>
                      <div class="ms-3">
                        <p class="mb-0">Pending Verification</p>
                        <h5 class="mb-0"><?php echo $pendingVerification; ?></h5>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-lg-3 col-sm-6">
                <div class="card h-100">
                  <div class="card-body">
                    <div class="d-flex align-items-center">
                      <div class="avatar">
                        <div class="avatar-initial bg-danger rounded shadow-xs">
                          <i class="icon-base ri ri-search-line icon-24px"></i>
                        </div>
                      </div>
                      <div class="ms-3">
                        <p class="mb-0">Open Lost</p>
                        <h5 class="mb-0"><?php echo $openLost; ?></h5>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-lg-3 col-sm-6">
                <div class="card h-100">
                  <div class="card-body">
                    <div class="d-flex align-items-center">
                      <div class="avatar">
                        <div class="avatar-initial bg-success rounded shadow-xs">
                          <i class="icon-base ri ri-hand-heart-line icon-24px"></i>
                        </div>
                      </div>
                      <div class="ms-3">
                        <p class="mb-0">Open Found</p>
                        <h5 class="mb-0"><?php echo $openFound; ?></h5>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-lg-3 col-sm-6">
                <div class="card h-100">
                  <div class="card-body">
                    <div class="d-flex align-items-center">
                      <div class="avatar">
                        <div class="avatar-initial bg-info rounded shadow-xs">
                          <i class="icon-base ri ri-links-line icon-24px"></i>
                        </div>
                      </div>
                      <div class="ms-3">
                        <p class="mb-0">Matches Found</p>
                        <h5 class="mb-0"><?php echo $totalMatches; ?></h5>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="col-lg-4 col-sm-6">
                <div class="card h-100">
                  <div class="card-body text-center">
                    <div class="avatar avatar-md mx-auto mb-3">
                      <div class="avatar-initial bg-primary rounded shadow-xs">
                        <i class="icon-base ri ri-file-paper-2-line icon-24px"></i>
                      </div>
                    </div>
                    <h4 class="mb-1"><?php echo $totalItems; ?></h4>
                    <p class="mb-0">Total Approved Items</p>
                  </div>
                </div>
              </div>
              <div class="col-lg-4 col-sm-6">
                <div class="card h-100">
                  <div class="card-body text-center">
                    <div class="avatar avatar-md mx-auto mb-3">
                      <div class="avatar-initial bg-warning rounded shadow-xs">
                        <i class="icon-base ri ri-arrow-left-right-line icon-24px"></i>
                      </div>
                    </div>
                    <h4 class="mb-1"><?php echo $matchedItems; ?></h4>
                    <p class="mb-0">Matched Items</p>
                  </div>
                </div>
              </div>
              <div class="col-lg-4 col-sm-6">
                <div class="card h-100">
                  <div class="card-body text-center">
                    <div class="avatar avatar-md mx-auto mb-3">
                      <div class="avatar-initial bg-success rounded shadow-xs">
                        <i class="icon-base ri ri-checkbox-circle-line icon-24px"></i>
                      </div>
                    </div>
                    <h4 class="mb-1"><?php echo $claimedItems; ?></h4>
                    <p class="mb-0">Claimed Items</p>
                  </div>
                </div>
              </div>

              <div class="col-12">
                <div class="card overflow-hidden">
                  <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="card-title m-0">Recent Items</h5>
                    <a href="all-items.php" class="btn btn-sm btn-outline-primary">View All</a>
                  </div>
                  <div class="table-responsive">
                    <table class="table table-sm">
                      <thead>
                        <tr>
                          <th>Type</th>
                          <th>Item Name</th>
                          <th>Category</th>
                          <th>Location</th>
                          <th>Status</th>
                          <th>Date</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php
                        if ($recentItems && $recentItems->num_rows > 0) {
                          while ($row = $recentItems->fetch_assoc()) {
                            $typeBadge = $row['type'] === 'lost'
                              ? "<span class='badge bg-label-danger'>Lost</span>"
                              : "<span class='badge bg-label-success'>Found</span>";
                            $statusBadge = match ($row['status']) {
                              'matched' => "<span class='badge bg-label-warning'>Matched</span>",
                              'claimed' => "<span class='badge bg-label-info'>Claimed</span>",
                              default   => "<span class='badge bg-label-primary'>Open</span>",
                            };
                            $date = date('M d, Y', strtotime($row['date_created']));
                            echo "<tr>
                              <td>{$typeBadge}</td>
                              <td>" . htmlspecialchars($row['name']) . "</td>
                              <td>" . htmlspecialchars($row['category'] ?? '') . "</td>
                              <td>" . htmlspecialchars($row['location'] ?? '') . "</td>
                              <td>{$statusBadge}</td>
                              <td>{$date}</td>
                            </tr>";
                          }
                        } else {
                          echo "<tr><td colspan='6' class='text-center'>No items yet</td></tr>";
                        }
                        ?>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>

<?php include __DIR__ . '/includes/footer.php'; ?>
