<?php
require_once __DIR__ . '/../auth/guard_admin.php';
require_once __DIR__ . '/../backend/config.php';

// Auto-sync item statuses based on matches table
$conn->query("
    UPDATE items i
    JOIN matches m ON (i.id = m.lost_item_id OR i.id = m.found_item_id)
    SET i.status = 'claimed'
    WHERE m.status = 'confirmed' AND i.status != 'claimed'
");
$conn->query("
    UPDATE items i
    JOIN matches m ON (i.id = m.lost_item_id OR i.id = m.found_item_id)
    SET i.status = 'matched'
    WHERE m.status IN ('pending', 'user_confirmed') AND i.status = 'open'
");
$conn->query("
    UPDATE items i
    SET i.status = 'open'
    WHERE i.status = 'matched'
    AND i.verification_status = 'approved'
    AND NOT EXISTS (
        SELECT 1 FROM matches m
        WHERE (m.lost_item_id = i.id OR m.found_item_id = i.id)
        AND m.status IN ('pending', 'user_confirmed', 'confirmed')
    )
");

// Filter support
$where = ["verification_status = 'approved'"];
$params = [];
$types = '';

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
if (!empty($_GET['type'])) {
  $where[] = "type = ?";
  $params[] = $_GET['type'];
  $types .= 's';
}
if (!empty($_GET['search'])) {
  $search = '%' . $_GET['search'] . '%';
  $where[] = "(name LIKE ? OR description LIKE ? OR location LIKE ? OR category LIKE ?)";
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
$result = $stmt->get_result();

// Get categories from categories table
$catResult = $conn->query("SELECT `category-name` FROM categories ORDER BY `category-name` ASC");
$categories = [];
while ($r = $catResult->fetch_assoc()) $categories[] = $r['category-name'];

$pageTitle = 'All Items';
$activePage = 'all-items';
include __DIR__ . '/includes/header.php';
?>

<h3 class="mb-1">View Information</h3>
<div class="card">
  <div class="filter row align-items-start justify-content-around">

    <form method="GET" class="d-contents">
      <div class="search col nav-item d-inline-flex align-items-center">
        <select name="type" class="form-select" onchange="this.form.submit()">
          <option value="">All Types</option>
          <option value="lost" <?php echo (isset($_GET['type']) && $_GET['type'] === 'lost') ? ' selected' : ''; ?>>Lost</option>
          <option value="found" <?php echo (isset($_GET['type']) && $_GET['type'] === 'found') ? ' selected' : ''; ?>>Found</option>
        </select>
      </div>

      <div class="search col nav-item d-inline-flex align-items-center">
        <select name="category" class="form-select" onchange="this.form.submit()">
          <option value="">All Categories</option>
          <?php
          foreach ($categories as $cat) {
            $safe = htmlspecialchars($cat);
            $sel = (isset($_GET['category']) && $_GET['category'] === $cat) ? ' selected' : '';
            echo '<option value="' . $safe . '"' . $sel . '>' . ucfirst($safe) . '</option>';
          }
          ?>
        </select>
      </div>

      <div class="search col nav-item d-inline-flex align-items-center">
        <select name="status" class="form-select" onchange="this.form.submit()">
          <option value="">All Statuses</option>
          <option value="open" <?php echo (isset($_GET['status']) && $_GET['status'] === 'open') ? ' selected' : ''; ?>>Open</option>
          <option value="matched" <?php echo (isset($_GET['status']) && $_GET['status'] === 'matched') ? ' selected' : ''; ?>>Matched</option>
          <option value="claimed" <?php echo (isset($_GET['status']) && $_GET['status'] === 'claimed') ? ' selected' : ''; ?>>Claimed</option>
        </select>
      </div>
    </form>

    <div class="search col nav-item d-inline-flex align-items-center">
      <i class="icon-base ri ri-search-line icon-lg lh-0"></i>
      <input
        type="text"
        id="itemSearchInput"
        class="form-control border-0 shadow-none"
        placeholder="Search by name, description, location..."
        aria-label="Search..."
        value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" />
    </div>
  </div>

  <!-- AJAX search result count -->
  <div class="px-4 pt-2" id="searchResultInfo" style="display:none;">
    <small class="text-muted"><span id="resultCount">0</span> items found</small>
  </div>


  <div class="card-body">
    <div class="table-responsive text-nowrap">
      <table class="table table-bordered">


        <thead>
          <tr>
            <th>No.</th>
            <th>Type</th>
            <th>Item Name</th>
            <th>Category</th>
            <th>Color</th>
            <th>Location</th>
            <th>Status</th>
            <th>Description</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php
          if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
              $id = (int)$row['id'];
              $typeBadge = $row['type'] === 'lost'
                ? "<span class='badge bg-label-danger'>Lost</span>"
                : "<span class='badge bg-label-success'>Found</span>";
              $statusBadge = match ($row['status']) {
                'matched' => "<span class='badge bg-label-warning'>Matched</span>",
                'claimed' => "<span class='badge bg-label-info'>Claimed</span>",
                default   => "<span class='badge bg-label-primary'>Open</span>",
              };
              echo "<tr>
                              <td>{$id}</td>
                              <td>{$typeBadge}</td>
                              <td>" . htmlspecialchars($row['name']) . "</td>
                              <td>" . htmlspecialchars($row['category']) . "</td>
                              <td>" . htmlspecialchars($row['color'] ?? '') . "</td>
                              <td>" . htmlspecialchars($row['location'] ?? '') . "</td>
                              <td>{$statusBadge}</td>
                              <td>" . htmlspecialchars(mb_strimwidth($row['description'] ?? '', 0, 50, '...')) . "</td>
                              <td>
                                <div class='dropdown'>
                                  <button
                                    type='button'
                                    class='btn p-0 dropdown-toggle hide-arrow shadow-none'
                                    data-bs-toggle='dropdown'>
                                    <i class='icon-base ri ri-more-2-line icon-18px'></i>
                                  </button>
                                  <div class='dropdown-menu'>
                                    <a class='dropdown-item' href='edit.php?id={$id}'>
                                      <i class='icon-base ri ri-pencil-line icon-18px me-1'></i> View / Edit
                                    </a>
                                    <form method='POST' action='../backend/update_status.php' style='display:inline;'>
                                      <input type='hidden' name='id' value='{$id}'>
                                      <input type='hidden' name='status' value='open'>
                                      <button type='submit' class='dropdown-item' onclick=\"return confirm('Reset status to Open?');\">
                                        <i class='icon-base ri ri-refresh-line icon-18px me-1'></i> Reset to Open
                                      </button>
                                    </form>
                                  </div>
                                </div>
                              </td>
                            </tr>";
            }
          }
          ?>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
  (function() {
    var searchInput = document.getElementById('itemSearchInput');
    var typeFilter = document.querySelector('select[name="type"]');
    var categoryFilter = document.querySelector('select[name="category"]');
    var statusFilter = document.querySelector('select[name="status"]');
    var tbody = document.querySelector('.table-bordered tbody');
    var resultInfo = document.getElementById('searchResultInfo');
    var resultCount = document.getElementById('resultCount');
    var debounceTimer = null;

    // Override the form submit for filters to use AJAX
    if (typeFilter) typeFilter.removeAttribute('onchange');
    if (categoryFilter) categoryFilter.removeAttribute('onchange');
    if (statusFilter) statusFilter.removeAttribute('onchange');

    function performSearch() {
      var params = new URLSearchParams();
      if (typeFilter && typeFilter.value) params.set('type', typeFilter.value);
      if (categoryFilter && categoryFilter.value) params.set('category', categoryFilter.value);
      if (statusFilter && statusFilter.value) params.set('status', statusFilter.value);
      if (searchInput && searchInput.value.trim()) params.set('search', searchInput.value.trim());

      fetch('../backend/search_items.php?' + params.toString())
        .then(function(r) {
          return r.json();
        })
        .then(function(data) {
          if (!data.success) return;
          resultInfo.style.display = 'block';
          resultCount.textContent = data.count;
          renderItems(data.items);
        })
        .catch(function() {
          // Fallback: submit the form normally
        });
    }

    function renderItems(items) {
      if (items.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted py-4">No items found</td></tr>';
        return;
      }
      var html = '';
      items.forEach(function(row) {
        var typeBadge = row.type === 'lost' ?
          "<span class='badge bg-label-danger'>Lost</span>" :
          "<span class='badge bg-label-success'>Found</span>";
        var statusMap = {
          matched: 'bg-label-warning',
          claimed: 'bg-label-info',
          open: 'bg-label-primary'
        };
        var statusBadge = "<span class='badge " + (statusMap[row.status] || 'bg-label-primary') + "'>" +
          row.status.charAt(0).toUpperCase() + row.status.slice(1) + "</span>";
        var desc = row.description || '';
        var name = escapeHtml(row.name || '');
        var cat = escapeHtml(row.category || '');
        var color = escapeHtml(row.color || '');
        var loc = escapeHtml(row.location || '');

        html += '<tr>' +
          '<td>' + row.id + '</td>' +
          '<td>' + typeBadge + '</td>' +
          '<td>' + name + '</td>' +
          '<td>' + cat + '</td>' +
          '<td>' + color + '</td>' +
          '<td>' + loc + '</td>' +
          '<td>' + statusBadge + '</td>' +
          '<td>' + escapeHtml(desc) + '</td>' +
          '<td><div class="dropdown">' +
          '<button type="button" class="btn p-0 dropdown-toggle hide-arrow shadow-none" data-bs-toggle="dropdown">' +
          '<i class="icon-base ri ri-more-2-line icon-18px"></i></button>' +
          '<div class="dropdown-menu">' +
          '<a class="dropdown-item" href="edit.php?id=' + row.id + '">' +
          '<i class="icon-base ri ri-pencil-line icon-18px me-1"></i> View / Edit</a>' +
          '<form method="POST" action="../backend/update_status.php" style="display:inline;">' +
          '<input type="hidden" name="id" value="' + row.id + '">' +
          '<input type="hidden" name="status" value="open">' +
          '<button type="submit" class="dropdown-item" onclick="return confirm(\'Reset status to Open?\');">' +
          '<i class="icon-base ri ri-refresh-line icon-18px me-1"></i> Reset to Open</button></form>' +
          '</div></div></td></tr>';
      });
      tbody.innerHTML = html;
    }

    function escapeHtml(str) {
      var div = document.createElement('div');
      div.textContent = str;
      return div.innerHTML;
    }

    // Debounced search on text input
    if (searchInput) {
      searchInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(performSearch, 300);
      });
    }

    // Filter change triggers AJAX search
    [typeFilter, categoryFilter, statusFilter].forEach(function(el) {
      if (el) {
        el.addEventListener('change', function() {
          performSearch();
        });
      }
    });
  })();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>