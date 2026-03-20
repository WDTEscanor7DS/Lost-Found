<?php
require_once __DIR__ . '/../auth/guard_admin.php';
require_once __DIR__ . '/../backend/config.php';

$sql = "SELECT * FROM items WHERE type='lost' AND verification_status='pending' ORDER BY date_created DESC";
$result = $conn->query($sql);

$pageTitle = 'Submitted Lost Items';
$activePage = 'lost-submit';
include __DIR__ . '/includes/header.php';
?>

            <h3 class="mb-1">Submitted Lost Items</h3>
            <div class="card">
              <h5 class="card-header">Pending Verification</h5>
              <div class="card-body">
                <div class="table-responsive text-nowrap">
                  <table class="table table-bordered">
                    <thead>
                      <tr>
                        <th>No.</th>
                        <th>Item Name</th>
                        <th>Category</th>
                        <th>Color</th>
                        <th>Location Lost</th>
                        <th>Description</th>
                        <th>Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                          $id = (int)$row['id'];
                          echo "<tr>
                              <td>{$id}</td>
                              <td>" . htmlspecialchars($row['name']) . "</td>
                              <td>" . htmlspecialchars($row['category']) . "</td>
                              <td>" . htmlspecialchars($row['color']) . "</td>
                              <td>" . htmlspecialchars($row['location']) . "</td>
                              <td>" . htmlspecialchars($row['description']) . "</td>
                              <td>
                                <div class='dropdown'>
                                  <button
                                    type='button'
                                    class='btn p-0 dropdown-toggle hide-arrow shadow-none'
                                    data-bs-toggle='dropdown'>
                                    <i class='icon-base ri ri-more-2-line icon-18px'></i>
                                  </button>
                                  <div class='dropdown-menu'>
                                    <form method='POST' action='../backend/verify_item.php' style='display:inline;'>
                                      <input type='hidden' name='item_id' value='{$id}'>
                                      <input type='hidden' name='verification' value='approved'>
                                      <button type='submit' name='verify_item' class='dropdown-item' onclick=\"return confirm('Are you sure you want to approve this record?');\">
                                        <i class='icon-base ri ri-pencil-line icon-18px me-1'></i> Approve
                                      </button>
                                    </form>
                                    <form method='POST' action='../backend/verify_item.php' style='display:inline;'>
                                      <input type='hidden' name='item_id' value='{$id}'>
                                      <input type='hidden' name='verification' value='rejected'>
                                      <button type='submit' name='verify_item' class='dropdown-item' onclick=\"return confirm('Are you sure you want to reject this record?');\">
                                        <i class='icon-base ri ri-delete-bin-6-line icon-18px me-1'></i> Reject
                                      </button>
                                    </form>
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
