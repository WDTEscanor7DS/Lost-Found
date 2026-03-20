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

$pageTitle = 'Add Category';
$activePage = 'add-category';
include __DIR__ . '/includes/header.php';
?>

            <h3 class="mb-1">Add Category</h3>
            <div class="row mb-6 gy-6">
              <div class="col-xl">
                <div class="card">
                  <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Category Details</h5>
                  </div>
                  <div class="card-body">
                    <form action="insert-category.php" method="POST">
                      <div class="form-floating form-floating-outline mb-6">
                        <input type="text" name="categories" class="form-control" id="categories" placeholder="e.g. Electronics" required />
                        <label for="categories">Category Name</label>
                      </div>

                      <div class="form-floating form-floating-outline mb-6">
                        <select name="status" id="status" class="form-select" required>
                          <?php
                          foreach ($statusEnum as $value) {
                            $safeValue = htmlspecialchars($value);
                            echo '<option value="' . $safeValue . '">' . ucfirst($safeValue) . '</option>';
                          }
                          ?>
                        </select>
                        <label for="status">Status</label>
                      </div>

                      <button type="submit" class="btn btn-primary">Submit</button>
                    </form>
                  </div>
                </div>
              </div>
            </div>

<?php include __DIR__ . '/includes/footer.php'; ?>
