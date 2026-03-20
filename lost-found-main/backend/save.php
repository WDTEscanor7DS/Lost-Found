<?php

/**
 * Save a submitted item (lost or found).
 * Called via POST from user report forms.
 * Uses prepared statements for security.
 */
session_start();
if (!isset($_SESSION['id'])) {
    header("Location: ../index.php");
    exit();
}
require_once __DIR__ . '/config.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../user/index.php");
    exit;
}

$type = $_POST['type'] ?? '';
$name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$category = $_POST['category'] ?? '';
$color = $_POST['color'] ?? '';
$location = $_POST['location'] ?? '';
$description = $_POST['description'] ?? '';

// ID-specific fields
$id_type = $_POST['id_type'] ?? '';
$id_number = $_POST['id_number'] ?? '';
$id_issuer = $_POST['id_issuer'] ?? '';

// Validate required fields
if (!in_array($type, ['lost', 'found']) || $name === '' || $email === '' || $category === '' || $location === '') {
    header("Location: ../user/report-" . ($type === 'found' ? 'found' : 'lost') . "-item.php?error=1");
    exit;
}

// Handle room number
if ($location === "Room" && !empty($_POST['room_number'])) {
    $location = "Room " . $_POST['room_number'];
}

// Image upload
$imageName = "";
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (in_array($ext, $allowed)) {
        $imageName = time() . "_" . random_int(1000, 9999) . "." . $ext;
        $uploadDir = __DIR__ . '/../uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $imageName);
    }
}

// Insert with prepared statement
$userId = $_SESSION['id'];
$stmt = $conn->prepare("INSERT INTO items (user_id, type, name, email, category, color, location, description, image, id_type, id_number, id_issuer, verification_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
$stmt->bind_param("isssssssssss", $userId, $type, $name, $email, $category, $color, $location, $description, $imageName, $id_type, $id_number, $id_issuer);
$stmt->execute();
$stmt->close();

// Redirect back to the appropriate report page
$redirectPage = ($type === 'found') ? 'report-found-item.php' : 'report-lost-item.php';
header("Location: ../user/{$redirectPage}?success=1");
exit;
