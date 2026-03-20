<?php
require_once __DIR__ . '/../auth/guard_admin.php';
include("../misc/connect.php");

if (isset($_GET['id'])) {
  $id = (int)$_GET['id'];

  $sql = "DELETE FROM categories WHERE id=?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $id);

  if ($stmt->execute()) {
    echo "
          <script>
              alert('Category Deleted Successfully!');
              window.location.href = 'view.php';
          </script>";
  } else {
    echo "<script>alert('Error Deleting Category!'); window.location.href = 'view.php';</script>";
  }
} else {
  echo "<script>alert('Error Deleting Category!'); window.location.href = 'view.php';</script>";
}
$conn->close();
