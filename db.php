<?php
// campus/db.php

$host = "localhost";
$dbname = "campus"; // ← change if your database name is different
$username = "root";
$password = "Root1234";

try {
    // ✅ PDO connection (newer, preferred)
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("❌ PDO Database connection failed: " . $e->getMessage());
}

// ✅ MySQLi connection (for older scripts using $conn)
$conn = @new mysqli($host, $username, $password, $dbname);

if ($conn->connect_errno) {
    die("❌ MySQLi connection failed: " . $conn->connect_error);
}
?>
