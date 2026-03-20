<?php
$pageTitle = 'Track Status';
$activePage = 'track-status';
include __DIR__ . '/includes/header.php';
?>

<h3 class="mb-4">Track Item Status</h3>

<?php
// Fetch categories for filter dropdown
$catResult = $conn->query("SELECT `category-name` FROM categories ORDER BY `category-name` ASC");
$categories = [];
while ($cat = $catResult->fetch_assoc()) {
  $categories[] = $cat['category-name'];
}

// Build query for the logged-in user
$userId = (int) $_SESSION['id'];
$where = ["user_id = ?"];
$params = [$userId];
$types = 'i';

if (!empty($_GET['category'])) {
  $where[] = "category = ?";
  $params[] = $_GET['category'];
  $types .= 's';
}
if (!empty($_GET['status'])) {
  $where[] = "status = ?";
  $params[] = $_GET['status'];
  $types .= 's';
}
if (!empty($_GET['verification'])) {
  $where[] = "verification_status = ?";
  $params[] = $_GET['verification'];
  $types .= 's';
}

$sortOrder = ($_GET['sort'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
$sql = "SELECT * FROM items WHERE " . implode(' AND ', $where) . " ORDER BY date_created $sortOrder";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$items = $stmt->get_result();
$stmt->close();
?>

<!-- Filter Controls -->
<div class="card mb-4">
  <div class="card-body">
    <form method="GET" class="row g-3 align-items-end">
      <div class="col-md-3">
        <label class="form-label">Category</label>
        <select name="category" class="form-select">
          <option value="">All Categories</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo ($_GET['category'] ?? '') === $cat ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Status</label>
        <select name="status" class="form-select">
          <option value="">All Statuses</option>
          <option value="open" <?php echo ($_GET['status'] ?? '') === 'open' ? 'selected' : ''; ?>>Open</option>
          <option value="matched" <?php echo ($_GET['status'] ?? '') === 'matched' ? 'selected' : ''; ?>>Matched</option>
          <option value="claimed" <?php echo ($_GET['status'] ?? '') === 'claimed' ? 'selected' : ''; ?>>Claimed</option>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Verification</label>
        <select name="verification" class="form-select">
          <option value="">All</option>
          <option value="pending" <?php echo ($_GET['verification'] ?? '') === 'pending' ? 'selected' : ''; ?>>Pending</option>
          <option value="approved" <?php echo ($_GET['verification'] ?? '') === 'approved' ? 'selected' : ''; ?>>Approved</option>
          <option value="rejected" <?php echo ($_GET['verification'] ?? '') === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">Sort by Date</label>
        <select name="sort" class="form-select">
          <option value="desc" <?php echo ($_GET['sort'] ?? 'desc') === 'desc' ? 'selected' : ''; ?>>Newest First</option>
          <option value="asc" <?php echo ($_GET['sort'] ?? '') === 'asc' ? 'selected' : ''; ?>>Oldest First</option>
        </select>
      </div>
      <div class="col-md-1">
        <button type="submit" class="btn btn-primary w-100"><i class="ri ri-filter-3-line"></i></button>
      </div>
    </form>
  </div>
</div>

<?php if ($items->num_rows === 0): ?>
  <div class="alert alert-info">No items found. Try adjusting your filters or submit a report first.</div>
<?php else: ?>
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
              <th>Status</th>
              <th>Verification</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($row = $items->fetch_assoc()): ?>
              <tr>
                <td><?php echo (int)$row['id']; ?></td>
                <td><span class="badge bg-<?php echo $row['type'] === 'lost' ? 'danger' : 'success'; ?>"><?php echo ucfirst(htmlspecialchars($row['type'])); ?></span></td>
                <td><?php echo htmlspecialchars($row['name']); ?></td>
                <td><?php echo htmlspecialchars($row['category']); ?></td>
                <td>
                  <?php
                  $statusClasses = [
                    'open' => 'bg-label-primary',
                    'matched' => 'bg-label-warning',
                    'claimed' => 'bg-label-success',
                  ];
                  $statusBadge = $statusClasses[$row['status']] ?? 'bg-label-secondary';
                  ?>
                  <span class="badge <?php echo $statusBadge; ?>"><?php echo ucfirst(htmlspecialchars($row['status'])); ?></span>
                </td>
                <td>
                  <?php
                  $verClasses = [
                    'approved' => 'bg-label-success',
                    'rejected' => 'bg-label-danger',
                    'pending' => 'bg-label-warning',
                  ];
                  $verBadge = $verClasses[$row['verification_status']] ?? 'bg-label-secondary';
                  ?>
                  <span class="badge <?php echo $verBadge; ?>"><?php echo ucfirst(htmlspecialchars($row['verification_status'])); ?></span>
                </td>
                <td><?php echo htmlspecialchars($row['date_created']); ?></td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>