<?php
/**
 * Database Connection File
 * Establishes a connection to the MySQL database.
 */

$host = 'localhost'; // Your database host
$db   = 'campus'; // Your database name
$user = 'root'; // Your database user
$pass = 'Root1234'; // Your database password (use a strong one in production!)
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

/**
 * Helper function to simulate fetching user details from a centralized table.
 * Replace this with actual database query to 'registration' table in a real application.
 * @param string $user_id The ID of the user.
 * @return array User details or null.
 */
function fetchUserDetails($user_id) {
    // Dummy data for demonstration. In a real app, query the 'registration' table.
    $mock_users = [
        'S1001' => ['name' => 'Alice Johnson', 'department' => 'CS', 'year' => '3rd', 'phone' => '9876543210', 'role' => 'student'],
        'S1002' => ['name' => 'Bob Williams', 'department' => 'EE', 'year' => '2nd', 'phone' => '9988776655', 'role' => 'student'],
        'A0001' => ['name' => 'Mr. Admin', 'department' => 'Canteen', 'year' => 'N/A', 'phone' => '9000011111', 'role' => 'admin'],
    ];

    return $mock_users[$user_id] ?? null;
}