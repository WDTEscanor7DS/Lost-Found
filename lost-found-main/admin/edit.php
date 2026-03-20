<?php
require_once __DIR__ . '/../auth/guard_admin.php';
include __DIR__ . "/../misc/connect.php";

$message = '';
$messageType = '';

// Handle update
if (isset($_POST['update'])) {
    $id = (int)$_POST['id'];
    $name = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $color = trim($_POST['color'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'open';

    if ($id > 0 && $name !== '' && $category !== '') {
        $stmt = $conn->prepare("UPDATE items SET name=?, category=?, color=?, location=?, description=?, status=? WHERE id=?");
        $stmt->bind_param("ssssssi", $name, $category, $color, $location, $description, $status, $id);
        if ($stmt->execute()) {
            $message = 'Item updated successfully!';
            $messageType = 'success';
        } else {
            $message = 'Error updating item.';
            $messageType = 'danger';
        }
        $stmt->close();
    }
}

// Get item
$item = null;
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM items WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $item = $stmt->get_result()->fetch_assoc();
    $stmt->close();
} elseif (isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    $stmt = $conn->prepare("SELECT * FROM items WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $item = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

if (!$item) {
    echo "<script>alert('Item not found.'); window.location.href='all-items.php';</script>";
    exit;
}

$pageTitle = 'View / Edit Item';
$activePage = 'all-items';
include __DIR__ . '/includes/header.php';
?>

<?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<h3 class="mb-4">View / Edit Item #<?php echo (int)$item['id']; ?></h3>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Item Details</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="id" value="<?php echo (int)$item['id']; ?>">

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Type</label>
                            <input type="text" class="form-control" value="<?php echo ucfirst(htmlspecialchars($item['type'])); ?>" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($item['email'] ?? ''); ?>" disabled>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Item Name</label>
                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($item['name']); ?>" required>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Category</label>
                            <select name="category" class="form-select" required>
                                <option value="">-- Select Category --</option>
                                <?php
                                $catEdit = $conn->query("SELECT `category-name` FROM categories ORDER BY `category-name` ASC");
                                while ($catRow = $catEdit->fetch_assoc()):
                                    $catName = $catRow['category-name'];
                                    $sel = ($item['category'] === $catName) ? ' selected' : '';
                                ?>
                                    <option value="<?php echo htmlspecialchars($catName); ?>" <?php echo $sel; ?>><?php echo htmlspecialchars($catName); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Color</label>
                            <input type="text" name="color" class="form-control" value="<?php echo htmlspecialchars($item['color'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Location</label>
                        <input type="text" name="location" class="form-control" value="<?php echo htmlspecialchars($item['location'] ?? ''); ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="4"><?php echo htmlspecialchars($item['description'] ?? ''); ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <?php foreach (['open', 'matched', 'claimed'] as $s): ?>
                                <option value="<?php echo $s; ?>" <?php echo $item['status'] === $s ? 'selected' : ''; ?>><?php echo ucfirst($s); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Verification Status</label>
                        <input type="text" class="form-control" value="<?php echo ucfirst(htmlspecialchars($item['verification_status'])); ?>" disabled>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Date Submitted</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($item['date_created']); ?>" disabled>
                    </div>

                    <button type="submit" name="update" class="btn btn-primary me-2">Update Item</button>
                    <a href="all-items.php" class="btn btn-secondary">Back to All Items</a>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <?php if (!empty($item['image']) && file_exists(__DIR__ . '/../uploads/' . $item['image'])): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Image</h5>
                </div>
                <div class="card-body text-center">
                    <img src="../uploads/<?php echo htmlspecialchars($item['image']); ?>" class="img-fluid rounded" alt="Item image">
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($item['id_type'])): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">ID Details</h5>
                </div>
                <div class="card-body">
                    <p><strong>ID Type:</strong> <?php echo htmlspecialchars($item['id_type']); ?></p>
                    <?php if (!empty($item['id_number'])): ?>
                        <p><strong>ID Number:</strong> <?php echo htmlspecialchars($item['id_number']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($item['id_issuer'])): ?>
                        <p><strong>Issuer:</strong> <?php echo htmlspecialchars($item['id_issuer']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>