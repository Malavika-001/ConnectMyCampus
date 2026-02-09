<?php
session_start();
require_once __DIR__ . '/../db.php';


$requested_module = $_GET['module'] ?? '';
$error = '';

// ✅ If already logged in → skip login
if (isset($_SESSION['role'])) {
    $role = $_SESSION['role'];
    redirect_to_dashboard($requested_module, $role);
    exit;
}

// ✅ When form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $module = $_POST['module'] ?? '';

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // ✅ Save session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            redirect_to_dashboard($module, $user['role']);
            exit;
        } else {
            $error = "❌ Invalid username or password!";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// ✅ Redirect Function
function redirect_to_dashboard($module, $role) {
    $base = "/campus";

    switch ($module) {
        case 'canteen':
            header("Location: $base/canteen/" . ($role === 'admin' ? 'admin_dashboard.php' : 'user_dashboard.php'));
            break;

        case 'hygiene':
            header("Location: $base/hygiene/" . ($role === 'admin' ? 'admin_dashboard.php' : 'user_dashboard.php'));
            break;

        case 'gym':
            header("Location: $base/gym/" . ($role === 'admin' ? 'gym_admin_dashboard.php' : 'gym_home_dashboard.php'));
            break;

        case 'notice':
            header("Location: $base/notice/" . ($role === 'admin' ? 'notice_admin.php' : 'notice_user.php'));
            break;

        default:
            header("Location: $base/main_dashboard.php");
            break;
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Gym Login | Connect My Campus</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<style>
    body {
        margin: 0;
        font-family: 'Segoe UI', sans-serif;
        background: linear-gradient(120deg, #dff6ff, #ffffff);
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
    }

    .login-box {
        width: 360px;
        background: #ffffff;
        padding: 35px;
        border-radius: 15px;
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        text-align: center;
    }

    .login-box h2 {
        color: #0077b6;
        margin-bottom: 20px;
        font-size: 24px;
    }

    .input-group {
        position: relative;
        margin-bottom: 20px;
    }

    .input-group i {
        position: absolute;
        left: 10px;
        top: 50%;
        transform: translateY(-50%);
        color: #0077b6;
        font-size: 18px;
    }

    .input-group input {
        width: 100%;
        padding: 12px 12px 12px 38px;
        border: 1px solid #ccc;
        border-radius: 8px;
        font-size: 15px;
    }

    .input-group input:focus {
        outline: none;
        border-color: #0077b6;
        box-shadow: 0 0 5px rgba(0,119,182,0.3);
    }

    input[type=submit] {
        width: 100%;
        padding: 12px;
        border: none;
        border-radius: 8px;
        background-color: #0077b6;
        color: #fff;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.3s;
    }

    input[type=submit]:hover {
        background-color: #023e8a;
    }

    .error {
        color: red;
        margin-bottom: 15px;
        font-weight: 500;
    }

    .back-link {
        display: inline-block;
        margin-top: 15px;
        color: #0077b6;
        text-decoration: none;
        font-size: 14px;
    }

    .back-link:hover {
        text-decoration: underline;
    }

    .logo {
        font-size: 40px;
        color: #0077b6;
        margin-bottom: 10px;
    }
</style>
</head>
<body>

<div class="login-box">
    <div class="logo"><i class="fa-solid fa-dumbbell"></i></div>
    <h2>Connect My Campus - Gym</h2>

    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="input-group">
            <i class="fa-solid fa-user"></i>
            <input type="text" name="username" placeholder="Enter Username" required>
        </div>

        <div class="input-group">
            <i class="fa-solid fa-lock"></i>
            <input type="password" name="password" placeholder="Enter Password" required>
        </div>

        <input type="submit" value="Login">
    </form>

    <a class="back-link" href="../main_dashboard.php">
        <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
    </a>
</div>

</body>
</html>
