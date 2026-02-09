<?php
/**
 * db_connect.php
 * Handles connection to noticeboard_db
 */

// Database configuration
$servername = "localhost"; // Change if your DB is elsewhere
$username = "root";        // Replace with your DB username
$password = "Root1234";            // Replace with your DB password
$dbname = "noticeboard_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    // Ideally, log the error and display a friendly message
    die("Connection failed: " . $conn->connect_error);
}

// Ensure the session is started for user type access
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Define the uploads directory path
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('BASE_URL', '/noticeboard/'); // Adjust this if the directory is different

// Check if uploads directory exists, if not, try to create it
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0777, true);
}
?>
