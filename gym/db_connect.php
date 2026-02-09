<<?php
$servername = "localhost";
$username = "root";
$password = "Root1234"; // leave blank unless you set a password
$database = "campus";

$conn = mysqli_connect($servername, $username, $password, $database);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
