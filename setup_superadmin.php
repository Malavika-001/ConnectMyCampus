<?php
$host = "localhost";
$user = "root";
$pass = "Root1234";
$db = "campus"; // update if needed

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Super Admin Account Details
$name = "Super Admin";
$college_id = "0000"; // dummy ID
$department = "Administration";
$year = "N/A";
$phone = "0000000000";
$gym_user = "No";
$gym_id = NULL;
$username = "superadmin";
$password = password_hash("admin123", PASSWORD_DEFAULT);
$role = "superadmin";
$admin_request_status = "approved";

// Prepare and insert
$sql = "INSERT INTO users (name, college_id, department, year, phone, gym_user, gym_id, username, password, role, admin_request_status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "sssssssssss",
    $name, $college_id, $department, $year, $phone,
    $gym_user, $gym_id, $username, $password, $role, $admin_request_status
);

if ($stmt->execute()) {
    echo "✅ Super Admin created successfully!<br>";
    echo "👉 Username: <b>superadmin</b><br>";
    echo "👉 Password: <b>admin123</b><br>";
    echo "You can now log in as Super Admin.";
} else {
    echo "❌ Error: " . $conn->error;
}

$stmt->close();
$conn->close();
?>
