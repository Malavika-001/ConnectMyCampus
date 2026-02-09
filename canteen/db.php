<?php
/**
 * Database Connection for Canteen Module
 */
$host = 'localhost';
$db   = 'campus';
$user = 'root';
$pass = 'Root1234';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

/**
 * ✅ Fetch user details directly from `users` table in `campus` DB.
 * This replaces the old mock user array.
 * 
 * @param int|string $user_id Logged-in user's ID from session
 * @return array|null Returns user details or null if not found/inactive
 */
function fetchUserDetails($user_id) {
    global $pdo;

    if (!$user_id) return null;

    try {
        $stmt = $pdo->prepare("
            SELECT 
                id AS user_id,
                name,
                college_id,
                department,
                year,
                phone,
                role,
                username,
                status
            FROM users
            WHERE id = ?
              AND status = 'active'
              AND (admin_request_status = 'approved' OR admin_request_status IS NULL)
        ");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            return [
                'id'         => $user['user_id'],
                'name'       => $user['name'],
                'college_id' => $user['college_id'],
                'department' => $user['department'],
                'year'       => $user['year'],
                'phone'      => $user['phone'],
                'role'       => $user['role'],
                'username'   => $user['username'],
                'status'     => $user['status']
            ];
        }

        return null; // user not found or inactive
    } catch (PDOException $e) {
        error_log("DB Error in fetchUserDetails(): " . $e->getMessage());
        return null;
    }
}
?>
