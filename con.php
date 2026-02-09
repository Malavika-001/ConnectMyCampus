<?php
// register.php
// ... [DB Connection Setup] ...

$host = "localhost";
$user = "root";   // default for WAMP/XAMPP
$pass = "Root1234";       // default empty password


// Connect without selecting a database first
$conn = new mysqli($host, $user, $pass);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // ... [Existing variable declarations] ...
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // ⭐ NEW: Extract the role from the form
    $role = $_POST['role'] ?? 'user'; 

    // Basic validations
    // ... [Password and Phone validation logic] ...
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Prepare and insert
    // 1. Add 'role' to the column list and a placeholder '?' to the VALUES list.
    $stmt = $conn->prepare("INSERT INTO users 
        (name, college_id, department, year, phone, category, gym_user, gym_id, username, password, role)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    // 2. Add 's' to the bind_param types and $role to the variables.
    // s = string, i = integer, d = double, b = blob
    $stmt->bind_param(
        "sssssssssss", // 11 's' for 11 string/varchar columns
        $name, $college_id, $department, $year, $phone, $category, $gym_user, $gym_id, $username, $hashed_password, $role
    );
    
    if ($stmt->execute()) {
        $message = "✅ Registration successful! You can now login.";
        // Optionally redirect to login.php
        // header("Location: login.php"); exit;
    } else {
        // ... [Error handling] ...
    }
}
?>