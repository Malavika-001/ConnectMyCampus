<?php
include('db_connect.php');

// Security check: If no user_type is set, redirect to main login
if (!isset($_SESSION['user_type'])) {
    header('Location: /login.php'); // Adjust path to your main login page
    exit;
}

$user_type = $_SESSION['user_type'];
$username = $_SESSION['username'] ?? 'Guest'; // Use username from session

// Determine the target notices based on user_type
if ($user_type == 'student') {
    $target_condition = "target_user = 'student' OR target_user = 'both'";
    $page_title = "Student Notice Board";
} elseif ($user_type == 'staff') {
    $target_condition = "target_user = 'staff' OR target_user = 'both'";
    $page_title = "Staff Notice Board";
} else {
    // Should not happen, but a safeguard
    echo "Access Denied.";
    exit;
}

// Fetch notices based on the target condition
$sql = "SELECT title, description, file, date_time FROM notices WHERE $target_condition ORDER BY date_time DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> | Connect My Campus</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f4f4f9; }
        .container { max-width: 900px; margin: auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h2 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        .notice-item { border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; border-radius: 6px; background-color: #fff; }
        .notice-item h3 { color: #007bff; margin-top: 0; }
        .notice-meta { font-size: 0.9em; color: #666; margin-bottom: 10px; }
        .notice-actions a { margin-right: 10px; text-decoration: none; padding: 5px 10px; border-radius: 4px; display: inline-block; }
        .view-btn { background-color: #28a745; color: white; }
        .download-btn { background-color: #ffc107; color: #333; }
        .logout-btn { float: right; padding: 10px 15px; background-color: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <a href="/logout.php" class="logout-btn">Logout</a>
        <h2>NOTICE BOARD – SSV College Valayanchiragara</h2>
        <p>Welcome, **<?php echo htmlspecialchars(ucfirst($user_type)) . ' ' . htmlspecialchars($username); ?>**.</p>
        <hr>

        <?php if ($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <div class="notice-item">
                    <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                    <div class="notice-meta">
                        Published: <?php echo date("F j, Y, g:i a", strtotime($row['date_time'])); ?>
                    </div>
                    <p><?php echo nl2br(htmlspecialchars($row['description'])); ?></p>
                    
                    <div class="notice-actions">
                        <a href="#" class="view-btn" 
                           onclick="alert('Title: <?php echo htmlspecialchars($row['title']); ?>\n\nDescription:\n<?php echo htmlspecialchars($row['description']); ?>')">
                           View Notice
                        </a>
                        
                        <?php if ($row['file']): ?>
                            <a href="<?php echo BASE_URL . 'uploads/' . urlencode($row['file']); ?>" class="download-btn" download>
                                Download Poster
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No notices currently available for your user type.</p>
        <?php endif; ?>

    </div>
</body>
</html>
<?php
$conn->close();
?>