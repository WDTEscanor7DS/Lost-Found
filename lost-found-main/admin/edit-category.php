<?php
require_once __DIR__ . '/../auth/guard_admin.php';
include __DIR__ . "/../misc/connect.php";

if (isset($_POST['update'])) {
    $id = (int)$_POST['id'];
    $category = trim($_POST['category'] ?? '');
    $status = $_POST['status'] ?? '';

    // Validate required fields
    if ($id <= 0 || $category === '' || !in_array($status, ['Active', 'Inactive'])) {
        echo "<script>alert('Invalid input!'); window.location.href = 'view.php';</script>";
        exit;
    }

    $sql = "UPDATE categories SET `category-name` = ?, status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $category, $status, $id);



    if ($stmt->execute()) {
        echo "
        <script>
            alert('Category Updated Successfully!');
            window.location.href = 'view.php';
        </script>";
    } else {
        echo "<script>alert('Error Updating Category!'); window.location.href = 'view.php';</script>";
    }
} else {
    echo "<script>alert('Invalid Request!');</script>";
}

$conn->close();
