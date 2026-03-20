<?php
$server = "localhost";
$user = "root";
$pass = "";
$dbname = "lost_found";

$conn = new mysqli($server, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("<script>alert('Connection Failed: " . $conn->connect_error . "');</script>");
}

$conn->set_charset("utf8mb4");
