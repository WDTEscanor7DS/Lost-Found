<?php
require_once __DIR__ . '/../auth/guard_admin.php';

/**
 * Admin: Found Item Reports.
 * Lists all found items (approved) with their current status.
 */
$pageTitle = 'Found Item Reports';
$activePage = 'found-item-reports';

require_once __DIR__ . '/../backend/config.php';

// Filter by status
$statusFilter = $_GET['status'] ?? '';
$where = "type='found' AND verification_status='approved'";
$params = [];
$types = '';

if ($statusFilter !== '' && in_array($statusFilter, ['open', 'matched', 'claimed'])) {
    $where .= " AND status = ?";
    $params[] = $statusFilter;
    $types .= 's';
}

$sql = "SELECT * FROM items WHERE $where ORDER BY date_created DESC";
$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Summary counts
$totalFound = $conn->query("SELECT COUNT(*) as c FROM items WHERE type='found' AND verification_status='approved'")->fetch_assoc()['c'];
$openFound = $conn->query("SELECT COUNT(*) as c FROM items WHERE type='found' AND status='open' AND verification_status='approved'")->fetch_assoc()['c'];
$matchedFound = $conn->query("SELECT COUNT(*) as c FROM items WHERE type='found' AND status='matched' AND verification_status='approved'")->fetch_assoc()['c'];
$claimedFound = $conn->query("SELECT COUNT(*) as c FROM items WHERE type='found' AND status='claimed' AND verification_status='approved'")->fetch_assoc()['c'];

include __DIR__ . '/includes/header.php';
?>

<h3 class="mb-1">Found Item Reports</h3>
<p class="text-muted mb-4">Overview of all found items in the system</p>

<!-- Summary Cards -->
<div class="row gy-4 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar">
                        <div class="avatar-initial bg-primary rounded shadow-xs"><i class="icon-base ri ri-file-list-3-line icon-24px"></i></div>
                    </div>
                    <div class="ms-3">
                        <p class="mb-0">Total Found</p>
                        <h5 class="mb-0"><?php echo (int)$totalFound; ?></h5>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar">
                        <div class="avatar-initial bg-info rounded shadow-xs"><i class="icon-base ri ri-search-line icon-24px"></i></div>
                    </div>
                    <div class="ms-3">
                        <p class="mb-0">Open</p>
                        <h5 class="mb-0"><?php echo (int)$openFound; ?></h5>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar">
                        <div class="avatar-initial bg-warning rounded shadow-xs"><i class="icon-base ri ri-links-line icon-24px"></i></div>
                    </div>
                    <div class="ms-3">
                        <p class="mb-0">Matched</p>
                        <h5 class="mb-0"><?php echo (int)$matchedFound; ?></h5>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar">
                        <div class="avatar-initial bg-success rounded shadow-xs"><i class="icon-base ri ri-checkbox-circle-line icon-24px"></i></div>
                    </div>
                    <div class="ms-3">
                        <p class="mb-0">Claimed</p>
                        <h5 class="mb-0"><?php echo (int)$claimedFound; ?></h5>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filter -->
<div class="card mb-4">
    <div class="card-body py-3">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Filter by Status</label>
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="open" <?php echo $statusFilter === 'open' ? 'selected' : ''; ?>>Open</option>
                    <option value="matched" <?php echo $statusFilter === 'matched' ? 'selected' : ''; ?>>Matched</option>
                    <option value="claimed" <?php echo $statusFilter === 'claimed' ? 'selected' : ''; ?>>Claimed</option>
                </select>
            </div>
        </form>
    </div>
</div>

<!-- Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Item Name</th>
                        <th>Category</th>
                        <th>Color</th>
                        <th>Location</th>
                        <th>Reporter Email</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo (int)$row['id']; ?></td>
                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                                <td><?php echo htmlspecialchars($row['category']); ?></td>
                                <td><?php echo htmlspecialchars($row['color'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($row['location'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($row['email'] ?? ''); ?></td>
                                <td>
                                    <?php
                                    $statusBadge = match ($row['status']) {
                                        'matched' => 'bg-label-warning',
                                        'claimed' => 'bg-label-success',
                                        default => 'bg-label-primary',
                                    };
                                    ?>
                                    <span class="badge <?php echo $statusBadge; ?>"><?php echo ucfirst($row['status']); ?></span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($row['date_created'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center">No found items to display.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php $stmt->close(); ?>
<?php include __DIR__ . '/includes/footer.php'; ?>