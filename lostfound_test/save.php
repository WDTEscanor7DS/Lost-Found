<?php
include "config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $type = $conn->real_escape_string($_POST['type']);
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $category = $conn->real_escape_string($_POST['category']);
    $color = $conn->real_escape_string($_POST['color']);
    $location = $conn->real_escape_string($_POST['location']);
    $description = $conn->real_escape_string($_POST['description']);

    // ID-specific fields
    $id_type = isset($_POST['id_type']) ? $conn->real_escape_string($_POST['id_type']) : '';
    $id_number = isset($_POST['id_number']) ? $conn->real_escape_string($_POST['id_number']) : '';
    $id_issuer = isset($_POST['id_issuer']) ? $conn->real_escape_string($_POST['id_issuer']) : '';

    // Handle room number
    if ($location == "Room" && !empty($_POST['room_number'])) {
        $location = "Room " . $conn->real_escape_string($_POST['room_number']);
    }

    // IMAGE UPLOAD
    $imageName = "";

    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {

        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $imageName = time() . "_" . rand(1000, 9999) . "." . $ext;

        move_uploaded_file($_FILES['image']['tmp_name'], "uploads/" . $imageName);
    }

    $conn->query("
        INSERT INTO items (type,name,email,category,color,location,description,image,id_type,id_number,id_issuer,verification_status)
        VALUES ('$type','$name','$email','$category','$color','$location','$description','$imageName','$id_type','$id_number','$id_issuer','pending')
    ");

    // 🔥 IMPORTANT: redirect to stop double submit
    header("Location: submit.php?success=1");
    exit;
}
