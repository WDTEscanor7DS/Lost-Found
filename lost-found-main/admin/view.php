<?php
require_once __DIR__ . '/../auth/guard_admin.php';
require_once __DIR__ . '/../backend/config.php';

$sql = "SELECT * FROM categories";
$result = $conn->query($sql);

$pageTitle = 'View Categories';
$activePage = 'view-categories';
include __DIR__ . '/includes/header.php';
?>

<h1 class="mb-1">All Categories Created</h1>
<div class="card">

  <div class="card-body">
    <div class="table-responsive text-nowrap">
      <table class="table table-bordered">


        <thead>
          <tr>
            <th>No.</th>
            <th>Category Name</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php
          if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
              $catId = (int)$row['id'];
              echo "<tr>
                              <td> {$catId}</td>
                              <td>" . htmlspecialchars($row['category-name']) . "</td>
                              <td>" . htmlspecialchars($row['status']) . "</td>
                              <td>
                                <div class='dropdown'>
                                  <button
                                    type='button'
                                    class='btn p-0 dropdown-toggle hide-arrow shadow-none'
                                    data-bs-toggle='dropdown'>
                                    <i class='icon-base ri ri-more-2-line icon-18px'></i>
                                  </button>
                                  <div class='dropdown-menu'>
                                    <a class='dropdown-item' href='update-category.php?id={$catId}'
                                    onclick=\"return confirm('Are you sure you want to edit this record?');\">
                                      <i class='icon-base ri ri-pencil-line icon-18px me-1'></i>
                                      Edit</a
                                    >
                                    <a class='dropdown-item' href='delete-category.php?id={$catId}'
                                    onclick=\"return confirm('Are you sure you want to delete this record?');\">
                                      <i class='icon-base ri ri-delete-bin-6-line icon-18px me-1'></i>
                                      Delete</a
                                    >
                                  </div>
                                </div>
                              </td>
                            </tr>";
            }
          }
          ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>