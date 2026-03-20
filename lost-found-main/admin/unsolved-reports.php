<?php
require_once __DIR__ . '/../auth/guard_admin.php';

/**
 * Admin: Unsolved Reports.
 * Lists items that remain open/unresolved — no match found yet.
 */
$pageTitle = 'Unsolved Reports';
$activePage = 'unsolved-reports';

require_once __DIR__ . '/../backend/config.php';

// Filter by type
$typeFilter = $_GET['type'] ?? '';
$where = "status='open' AND verification_status='approved'";
$params = [];
$types = '';

if ($typeFilter !== '' && in_array($typeFilter, ['lost', 'found'])) {
    $where .= " AND type = ?";
    $params[] = $typeFilter;
    $types .= 's';
}

$sql = "SELECT * FROM items WHERE $where ORDER BY date_created ASC";
$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Summary counts
$totalOpen = $conn->query("SELECT COUNT(*) as c FROM items WHERE status='open' AND verification_status='approved'")->fetch_assoc()['c'];
$openLost = $conn->query("SELECT COUNT(*) as c FROM items WHERE type='lost' AND status='open' AND verification_status='approved'")->fetch_assoc()['c'];
$openFound = $conn->query("SELECT COUNT(*) as c FROM items WHERE type='found' AND status='open' AND verification_status='approved'")->fetch_assoc()['c'];

include __DIR__ . '/includes/header.php';
?>

<h3 class="mb-1">Unsolved Reports</h3>
<p class="text-muted mb-4">Items that have not been matched or claimed yet</p>

<!-- Summary Cards -->
<div class="row gy-4 mb-4">
    <div class="col-sm-6 col-xl-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar">
                        <div class="avatar-initial bg-warning rounded shadow-xs"><i class="icon-base ri ri-hourglass-2-fill icon-24px"></i></div>
                    </div>
                    <div class="ms-3">
                        <p class="mb-0">Total Unsolved</p>
                        <h5 class="mb-0"><?php echo (int)$totalOpen; ?></h5>
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
                        <div class="avatar-initial bg-danger rounded shadow-xs"><i class="icon-base ri ri-search-line icon-24px"></i></div>
                    </div>
                    <div class="ms-3">
                        <p class="mb-0">Open Lost</p>
                        <h5 class="mb-0"><?php echo (int)$openLost; ?></h5>
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
                        <div class="avatar-initial bg-success rounded shadow-xs"><i class="icon-base ri ri-hand-heart-line icon-24px"></i></div>
                    </div>
                    <div class="ms-3">
                        <p class="mb-0">Open Found</p>
                        <h5 class="mb-0"><?php echo (int)$openFound; ?></h5>
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
                <label class="form-label">Filter by Type</label>
                <select name="type" class="form-select" onchange="this.form.submit()">
                    <option value="">All Types</option>
                    <option value="lost" <?php echo $typeFilter === 'lost' ? 'selected' : ''; ?>>Lost</option>
                    <option value="found" <?php echo $typeFilter === 'found' ? 'selected' : ''; ?>>Found</option>
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
                        <th>Type</th>
                        <th>Item Name</th>
                        <th>Category</th>
                        <th>Color</th>
                        <th>Location</th>
                        <th>Reporter Email</th>
                        <th>Date Submitted</th>
                        <th>Days Open</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <?php
                            $daysOpen = (int)((time() - strtotime($row['date_created'])) / 86400);
                            $typeBadge = $row['type'] === 'lost'
                                ? "<span class='badge bg-label-danger'>Lost</span>"
                                : "<span class='badge bg-label-success'>Found</span>";
                            ?>
                            <tr>
                                <td><?php echo (int)$row['id']; ?></td>
                                <td><?php echo $typeBadge; ?></td>
                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                                <td><?php echo htmlspecialchars($row['category']); ?></td>
                                <td><?php echo htmlspecialchars($row['color'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($row['location'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($row['email'] ?? ''); ?></td>
                                <td><?php echo date('M d, Y', strtotime($row['date_created'])); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $daysOpen > 30 ? 'danger' : ($daysOpen > 7 ? 'warning' : 'secondary'); ?>">
                                        <?php echo $daysOpen; ?> day<?php echo $daysOpen !== 1 ? 's' : ''; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center">No unsolved items.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php $stmt->close(); ?>
<?php include __DIR__ . '/includes/footer.php'; ?>