<?php
$pageTitle = 'Report Lost Item';
$activePage = 'report-lost';
include __DIR__ . '/includes/header.php';
?>

<?php if (isset($_GET['success'])): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    Item submitted successfully! It will be reviewed by an admin before appearing in the system.
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php elseif (isset($_GET['error'])): ?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    There was an error submitting your item. Please try again.
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>

<h3 class="mb-4">Report Lost Item</h3>
<div class="card">
  <div class="card-body">
    <form action="../backend/save.php" method="POST" enctype="multipart/form-data">
      <input type="hidden" name="type" value="lost">

      <div class="mb-3">
        <label class="form-label">Email <span class="text-danger">*</span></label>
        <input type="email" name="email" class="form-control" required>
      </div>

      <div class="mb-3">
        <label class="form-label">Item Name <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control" required>
      </div>

      <div class="mb-3">
        <label class="form-label">Category <span class="text-danger">*</span></label>
        <select name="category" id="categorySelect" class="form-select" onchange="toggleIDFields(); updateDescriptionHint();" required>
          <option value="">-- Select Category --</option>
          <?php
          $catResult = $conn->query("SELECT `category-name` FROM categories ORDER BY `category-name` ASC");
          while ($cat = $catResult->fetch_assoc()): ?>
            <option><?php echo htmlspecialchars($cat['category-name']); ?></option>
          <?php endwhile; ?>
        </select>
      </div>

      <div id="idFields" style="display:none;" class="card card-body bg-light mb-3">
        <h6>ID Card Details</h6>
        <div class="mb-3">
          <label class="form-label">ID Type</label>
          <select name="id_type" class="form-select">
            <option value="">-- Select ID Type --</option>
            <option>Government ID</option>
            <option>School ID</option>
            <option>Employee ID</option>
            <option>Student ID</option>
            <option>Driver's License</option>
            <option>Passport</option>
            <option>Other</option>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">ID Number (if visible)</label>
          <input type="text" name="id_number" class="form-control" placeholder="e.g. 123456789">
        </div>
        <div class="mb-3">
          <label class="form-label">Issuing Authority</label>
          <input type="text" name="id_issuer" class="form-control" placeholder="e.g. Department of Education">
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label">Color</label>
        <input type="text" name="color" class="form-control">
      </div>

      <div class="mb-3">
        <label class="form-label">Location <span class="text-danger">*</span></label>
        <select name="location" id="locationSelect" class="form-select" onchange="toggleRoomInput()" required>
          <option value="">-- Select Location --</option>
          <option>Cafeteria</option>
          <option>Library</option>
          <option>Computer Lab</option>
          <option>Gym</option>
          <option>Admin Office</option>
          <option>Hallway</option>
          <option>Parking Area</option>
          <option value="Room">Classroom Room</option>
        </select>
      </div>

      <div id="roomBox" style="display:none;" class="mb-3">
        <label class="form-label">Room Number</label>
        <input type="text" name="room_number" class="form-control" placeholder="e.g. 304">
      </div>

      <div class="mb-3">
        <label class="form-label">Description</label>
        <textarea name="description" id="descriptionBox" class="form-control" rows="4" placeholder="Describe your lost item in detail..."></textarea>
      </div>

      <div class="mb-3">
        <label class="form-label">Image</label>
        <input type="file" name="image" class="form-control" accept="image/*">
      </div>

      <button type="submit" class="btn btn-primary">Submit Report</button>
    </form>
  </div>
</div>

<script>
  function toggleRoomInput() {
    var loc = document.getElementById("locationSelect").value;
    document.getElementById("roomBox").style.display = (loc === "Room") ? "block" : "none";
  }

  function toggleIDFields() {
    var cat = document.getElementById("categorySelect").value;
    document.getElementById("idFields").style.display = (cat === "ID") ? "block" : "none";
  }

  function updateDescriptionHint() {
    var cat = document.getElementById("categorySelect").value;
    var desc = document.getElementById("descriptionBox");
    var hints = {
      "Electronics": "Describe the brand, model, color, size, and any distinguishing marks.",
      "Wallet": "Describe the material, color, brand, and any visible contents.",
      "ID": "Describe the ID appearance, holder/lanyard details. Fill in the ID details above if visible.",
      "Bag": "Describe the type (backpack, tote, pouch), brand, color, size, and features.",
      "Keys": "Describe the number of keys, keychain design, and any attached accessories.",
      "Clothing": "Describe the type, brand, color, size, and any logos or prints.",
      "Books / Notes": "Describe the title/subject, cover color, any name written on it.",
      "Water Bottle": "Describe the brand, color, size, material, and any stickers.",
      "Umbrella": "Describe the color, pattern, brand, size, and handle type.",
      "Others": "Provide as much detail as possible about the item."
    };
    desc.placeholder = hints[cat] || "Describe your lost item in detail...";
  }
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>