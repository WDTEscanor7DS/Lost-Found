<?php
$pageTitle = 'Dashboard';
$activePage = 'dashboard';
include __DIR__ . '/includes/header.php';
?>

            <h3 class="mb-4">Dashboard</h3>

            <?php
            $userId = $_SESSION['id'] ?? 0;
            $username = $_SESSION['username'] ?? '';
            $totalItems = $conn->query("SELECT COUNT(*) as c FROM items WHERE verification_status='approved'")->fetch_assoc()['c'];
            $openItems = $conn->query("SELECT COUNT(*) as c FROM items WHERE status='open' AND verification_status='approved'")->fetch_assoc()['c'];
            $matchedItems = $conn->query("SELECT COUNT(*) as c FROM items WHERE status='matched' AND verification_status='approved'")->fetch_assoc()['c'];
            $claimedItems = $conn->query("SELECT COUNT(*) as c FROM items WHERE status='claimed' AND verification_status='approved'")->fetch_assoc()['c'];
            ?>

            <div class="row gy-4">
              <div class="col-sm-6 col-xl-3">
                <div class="card">
                  <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                      <div class="content-left">
                        <span>Total Items</span>
                        <div class="d-flex align-items-center mt-2">
                          <h4 class="mb-0 me-2"><?php echo (int)$totalItems; ?></h4>
                        </div>
                      </div>
                      <span class="badge bg-label-primary rounded p-2">
                        <i class="ri ri-file-list-3-line ri-24px"></i>
                      </span>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-sm-6 col-xl-3">
                <div class="card">
                  <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                      <div class="content-left">
                        <span>Open Items</span>
                        <div class="d-flex align-items-center mt-2">
                          <h4 class="mb-0 me-2"><?php echo (int)$openItems; ?></h4>
                        </div>
                      </div>
                      <span class="badge bg-label-info rounded p-2">
                        <i class="ri ri-search-line ri-24px"></i>
                      </span>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-sm-6 col-xl-3">
                <div class="card">
                  <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                      <div class="content-left">
                        <span>Matched</span>
                        <div class="d-flex align-items-center mt-2">
                          <h4 class="mb-0 me-2"><?php echo (int)$matchedItems; ?></h4>
                        </div>
                      </div>
                      <span class="badge bg-label-warning rounded p-2">
                        <i class="ri ri-links-line ri-24px"></i>
                      </span>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-sm-6 col-xl-3">
                <div class="card">
                  <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                      <div class="content-left">
                        <span>Claimed</span>
                        <div class="d-flex align-items-center mt-2">
                          <h4 class="mb-0 me-2"><?php echo (int)$claimedItems; ?></h4>
                        </div>
                      </div>
                      <span class="badge bg-label-success rounded p-2">
                        <i class="ri ri-checkbox-circle-line ri-24px"></i>
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

<?php include __DIR__ . '/includes/footer.php'; ?>
