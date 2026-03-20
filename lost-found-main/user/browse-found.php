<?php

/**
 * User: Browse Found Items
 * Displays found items that users can claim.
 * Supports search and category filtering.
 */
$pageTitle = 'Browse Found Items';
$activePage = 'browse-found';
include __DIR__ . '/includes/header.php';

// Get categories
$catResult = $conn->query("SELECT `category-name` FROM categories ORDER BY `category-name` ASC");
$categories = [];
while ($r = $catResult->fetch_assoc()) $categories[] = $r['category-name'];

// Build query
$where = ["type = 'found'", "status = 'open'", "verification_status = 'approved'"];
$params = [];
$types = '';

if (!empty($_GET['category'])) {
    $where[] = "category = ?";
    $params[] = $_GET['category'];
    $types .= 's';
}
if (!empty($_GET['search'])) {
    $search = '%' . $_GET['search'] . '%';
    $where[] = "(name LIKE ? OR description LIKE ? OR location LIKE ? OR color LIKE ?)";
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $types .= 'ssss';
}

$sql = "SELECT * FROM items WHERE " . implode(' AND ', $where) . " ORDER BY date_created DESC";
$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$items = $stmt->get_result();
$stmt->close();
?>

<h3 class="mb-1">Browse Found Items</h3>
<p class="text-muted mb-4">Browse items that have been found and submit a claim if one belongs to you</p>

<!-- Search & Filter -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Search by name, description, location..."
                    value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Category</label>
                <select name="category" class="form-select">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo ($_GET['category'] ?? '') === $cat ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-1"></i> Search</button>
            </div>
            <div class="col-md-2">
                <a href="browse-found.php" class="btn btn-outline-secondary w-100">Clear</a>
            </div>
        </form>
    </div>
</div>

<?php if ($items->num_rows === 0): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-search fs-1 text-muted mb-3"></i>
            <h5 class="text-muted">No found items available</h5>
            <p class="text-muted">Check back later or try different search terms.</p>
        </div>
    </div>
<?php else: ?>
    <div class="row g-3">
        <?php while ($item = $items->fetch_assoc()): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100">
                    <?php if (!empty($item['image']) && file_exists(__DIR__ . '/../uploads/' . $item['image'])): ?>
                        <img src="../uploads/<?php echo htmlspecialchars($item['image']); ?>" class="card-img-top" style="height:180px;object-fit:cover;" alt="Item photo">
                    <?php else: ?>
                        <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height:180px;">
                            <i class="bi bi-image text-muted" style="font-size:3rem;"></i>
                        </div>
                    <?php endif; ?>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($item['name']); ?></h5>
                        <p class="card-text">
                            <span class="badge bg-label-success mb-2"><?php echo htmlspecialchars($item['category']); ?></span>
                            <?php if (!empty($item['color'])): ?>
                                <span class="badge bg-label-secondary mb-2"><?php echo htmlspecialchars($item['color']); ?></span>
                            <?php endif; ?>
                        </p>
                        <p class="card-text text-muted small">
                            <i class="bi bi-geo-alt me-1"></i><?php echo htmlspecialchars($item['location']); ?>
                        </p>
                        <p class="card-text small"><?php echo htmlspecialchars(mb_strimwidth($item['description'], 0, 100, '...')); ?></p>
                    </div>
                    <div class="card-footer d-flex justify-content-between align-items-center">
                        <small class="text-muted"><?php echo date('M d, Y', strtotime($item['date_created'])); ?></small>
                        <a href="claim-item.php?id=<?php echo (int)$item['id']; ?>" class="btn btn-sm btn-primary">
                            <i class="bi bi-hand-index me-1"></i> Claim This Item
                        </a>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>