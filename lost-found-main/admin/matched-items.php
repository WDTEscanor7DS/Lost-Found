<?php
require_once __DIR__ . '/../auth/guard_admin.php';

/**
 * Admin: Matched Items overview page.
 * Displays all matched pairs with their scores and statuses.
 */
$pageTitle = 'Matched Items';
$activePage = 'matched-items';

require_once __DIR__ . '/../backend/config.php';

$query = $conn->query("
    SELECT m.*,
           l.name AS lost_name, l.description AS lost_description, l.image AS lost_image,
           l.category AS lost_category, l.location AS lost_location, l.color AS lost_color,
           f.name AS found_name, f.description AS found_description, f.image AS found_image,
           f.category AS found_category, f.location AS found_location, f.color AS found_color
    FROM matches m
    JOIN items l ON m.lost_item_id = l.id
    JOIN items f ON m.found_item_id = f.id
    ORDER BY m.date_matched DESC
");

include __DIR__ . '/includes/header.php';
?>

<h3 class="mb-1">Matched Items</h3>

<?php if ($query->num_rows === 0): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <h5 class="text-muted">No matched items yet.</h5>
        </div>
    </div>
<?php else: ?>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Lost Item</th>
                            <th>Found Item</th>
                            <th>Category</th>
                            <th>Score</th>
                            <th>Status</th>
                            <th>Matched On</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $query->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo (int)$row['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['lost_name']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($row['lost_location']); ?></small>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['found_name']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($row['found_location']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($row['lost_category']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $row['match_score'] >= 80 ? 'success' : 'warning'; ?>">
                                        <?php echo (int)$row['match_score']; ?>%
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $statusClass = match ($row['status']) {
                                        'pending' => 'bg-label-warning',
                                        'user_confirmed' => 'bg-label-info',
                                        'confirmed' => 'bg-label-success',
                                        'rejected' => 'bg-label-danger',
                                        default => 'bg-label-secondary',
                                    };
                                    ?>
                                    <span class="badge <?php echo $statusClass; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $row['status'])); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($row['date_matched']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>