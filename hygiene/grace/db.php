<?php
// Database connection for Notice Board module
$host = "localhost";
$user = "root";  // Adjust to your DB user
$pass = "Root1234";
$db   = "campus_main";  // Central project DB name

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
?>
