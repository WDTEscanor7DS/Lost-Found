<?php
$conn = new mysqli("localhost","root","","db");

if($conn->connect_error){
    die("Connection failed");
}
?>
