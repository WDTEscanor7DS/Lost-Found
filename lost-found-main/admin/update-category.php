<?php
require_once __DIR__ . '/../auth/guard_admin.php';
require_once __DIR__ . '/../backend/config.php';

function getEnumValues($conn, $table, $column)
{
  $sql = "SHOW COLUMNS FROM `$table` LIKE '$column'";
  $result = $conn->query($sql);
  $row = $result->fetch_assoc();

  if ($row && preg_match("/^enum\((.*)\)$/i", $row['Type'], $matches)) {
    return str_getcsv($matches[1], ',', "'");
  }

  return [];
}

$statusEnum = getEnumValues($conn, 'categories', 'status');

$row = null;
$error = null;

if (isset($_GET['id'])) {
  $id = (int)$_GET['id'];
  $stmt = $conn->prepare("SELECT * FROM categories WHERE id = ? LIMIT 1");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $selectedStatus = $row['status'];
  } else {
    $error = 'not_found';
  }
} else {
  $error = 'no_id';
}

$pageTitle = 'Update Category';
$activePage = 'update-category';
include __DIR__ . '/includes/header.php';
?>

<?php if ($error === 'no_id'): ?>
  <script>
    alert('ID Missing from URL');
    window.location.href = 'view.php';
  </script>
<?php elseif ($error === 'not_found'): ?>
  <h4>No Such Id Found</h4>
<?php else: ?>
  <h3 class="mb-1">Update Category</h3>

  <div class="col-xxl">
    <div class="card">
      <div class="card-body">
        <form action="edit-category.php" method="POST">
          <input type="hidden" name="id" value="<?= htmlspecialchars($row['id']); ?>">
          <div class="form-floating form-floating-outline mb-6">
            <input type="text" name="category" class="form-control" id="category" value="<?= htmlspecialchars($row['category-name']) ?>" placeholder="Category of the item" />
            <label for="category">Category</label>
          </div>

          <div class="form-floating form-floating-outline mb-6">
            <select name="status" id="status" class="form-select" required>
              <?php
              foreach ($statusEnum as $value) {
                $safeValue = htmlspecialchars($value);
                $isSelected = ($selectedStatus === $value) ? ' selected' : '';
                echo '<option value="' . $safeValue . '"' . $isSelected . '>' . ucfirst($safeValue) . '</option>';
              }
              ?>
            </select>
            <label for="status">Status</label>
          </div>

          <button type="submit" name="update" class="btn btn-primary">Submit</button>
        </form>
      </div>
    </div>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>