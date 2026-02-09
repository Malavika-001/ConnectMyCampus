<?php
ob_start();
session_start();

$host = "localhost";
$user = "root";
$pass = "Root1234";
$db = "campus"; // adjust if your DB name differs

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Which module requested login? (from main_dashboard links like login.php?module=hygiene)
$requested_module = trim($_GET['module'] ?? $_POST['module'] ?? '');

// If already logged in, redirect immediately to the appropriate dashboard
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    $role = $_SESSION['role'];
    // superadmin
    if ($role === 'superadmin') {
        header("Location: superadmin_dashboard.php");
        exit;
    }
    // module-aware redirect
    if ($requested_module === 'hygiene') {
        header("Location: " . ($role === 'admin' ? "hygiene/admin_dashboard.php" : "hygiene/user_dashboard.php"));
        exit;
    } elseif ($requested_module === 'canteen') {
        header("Location: " . ($role === 'admin' ? "canteen/admin_dashboard.php" : "canteen/user_dashboard.php"));
        exit;
    } elseif ($requested_module === 'gym') {
        header("Location: " . ($role === 'admin' ? "gym/gym_admin_dashboard.php" : "gym/gym_home_dashboard.php"));
        exit;
    } elseif ($requested_module === 'notice') {
        header("Location: " . ($role === 'admin' ? "notice/admin_notice_dashboard.php" : "notice/view_notice2.php"));
        exit;
    } else {
        header("Location: main_dashboard.php");
        exit;
    }
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $module   = trim($_POST['module'] ?? '');

    if ($username === '' || $password === '') {
        $error = "Please enter username and password.";
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                // Gym-specific restriction
                if ($module === "gym" && strtolower(($user['gym_user'] ?? 'no')) !== "yes") {
                    $error = "You are not registered for the Gym Module.";
                } else {
                    // success
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = (int)$user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];

                    // superadmin
                    if ($user['role'] === 'superadmin') {
                        header("Location: superadmin_dashboard.php");
                        exit;
                    }

                    // Module-aware redirect after login
                    if ($module === 'hygiene') {
                        header("Location: " . ($user['role'] === 'admin' ? "hygiene/admin_dashboard.php" : "hygiene/user_dashboard.php"));
                        exit;
                    } elseif ($module === 'canteen') {
                        header("Location: " . ($user['role'] === 'admin' ? "canteen/admin_dashboard.php" : "canteen/user_dashboard.php"));
                        exit;
                    } elseif ($module === 'gym') {
                        header("Location: " . ($user['role'] === 'admin' ? "gym/gym_admin_dashboard.php" : "gym/gym_home_dashboard.php"));
                        exit;
                    } elseif ($module === 'notice') {
                        header("Location: " . ($user['role'] === 'admin' ? "notice/admin_notice_dashboard.php" : "notice/view_notice2.php"));
                        exit;
                    } else {
                        // default dashboards
                        if ($user['role'] === 'admin') header("Location: admin_dashboard.php");
                        else header("Location: user_dashboard.php");
                        exit;
                    }
                }
            } else {
                $error = "Invalid password.";
            }
        } else {
            $error = "User not found.";
        }
        $stmt->close();
    }
}

$conn->close();

// Background image check (file should be at /campus/assets/login_bg.jpg)
$bg_web_path = '/campus/assets/login_bg.jpg';
$bg_disk_path = __DIR__ . '/assets/login_bg.jpg';
$bg_exists = file_exists($bg_disk_path);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Login | Connect My Campus</title>
<style>
    body {
        font-family: 'Segoe UI', sans-serif;
        background: <?= $bg_exists ? "url('{$bg_web_path}') no-repeat center center fixed" : "linear-gradient(120deg,#e0f7ff,#ffffff)" ?>;
        background-size: cover;
        height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
        margin: 0;
    }
    .overlay {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0,0,0,0.45); z-index: 1; <?= $bg_exists ? '' : 'display:none;' ?>
    }
    .login-container {
        position: relative; z-index: 2;
        background: rgba(255,255,255,0.95);
        padding: 30px; border-radius: 12px;
        width: 360px; text-align: center; box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    }
    input { width: 90%; padding:10px; margin:10px 0; border-radius:6px; border:1px solid #ccc; }
    button, input[type="submit"] { width:95%; padding:12px; background:#0077b6; color:#fff; border:none; border-radius:8px; cursor:pointer; }
    .error { color: red; margin-bottom: 10px; }
    .debug-warning { background:#fff3cd; border:1px solid #ffeeba; color:#856404; padding:8px; margin-top:10px; border-radius:6px; font-size:13px; }
    .register-link { margin-top: 12px; display:block; color:#0077b6; text-decoration:none; }
</style>
</head>
<body>
<?php if ($bg_exists): ?><div class="overlay"></div><?php endif; ?>

<div class="login-container">
    <h2>Login</h2>

    <?php if (!empty($error)): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" action="">
        <input type="hidden" name="module" value="<?= htmlspecialchars($requested_module) ?>">
        <input type="text" name="username" placeholder="Enter Username" required><br>
        <input type="password" name="password" placeholder="Enter Password" required><br>
        <input type="submit" value="Login">
    </form>

    <a class="register-link" href="register.php">Don’t have an account? Register here</a>
    <p style="margin-top:8px"><small>Forgot password? Contact admin</small></p>

    <?php if (!$bg_exists): ?>
        <div class="debug-warning">
            Background image missing at: <code><?= htmlspecialchars($bg_disk_path) ?></code><br>
            Put your file at <strong>/campus/assets/login_bg.jpg</strong> (server path: <?= htmlspecialchars($bg_disk_path) ?>)
        </div>
    <?php endif; ?>
</div>
</body>
</html>

<?php ob_end_flush(); ?>
