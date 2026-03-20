<?php
require_once __DIR__ . '/../auth/guard_admin.php';
include("../misc/connect.php");

$categoryName = trim($_POST['categories'] ?? '');
$status = $_POST['status'] ?? 'Active';

if ($categoryName === '') {
    echo "<script>alert('Category name is required!'); window.history.back();</script>";
    exit;
}

if (!in_array($status, ['Active', 'Inactive'])) {
    echo "<script>alert('Invalid status selected!'); window.history.back();</script>";
    exit;
}

$stmt = $conn->prepare("INSERT INTO categories (`category-name`, `status`) VALUES (?, ?)");
$stmt->bind_param("ss", $categoryName, $status);

if ($stmt->execute()) {
    echo "
    <script>
        alert('Category Added Successfully!');
        window.location.href = 'view.php';
    </script>";
} else {
    echo "<script>alert('Error Adding Category!');</script>";
}

$stmt->close();
$conn->close();
