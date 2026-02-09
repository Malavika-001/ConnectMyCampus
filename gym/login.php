<?php
declare(strict_types=1);
ob_start();
session_start();

require_once __DIR__ . "/db.php";  // shared database connection (must define $pdo)

// Get requested module (hygiene/canteen/gym/notice) from GET or POST (safe default '')
$requested_module = (string) ($_GET['module'] ?? $_POST['module'] ?? '');

/* ==========================================================
   REDIRECTION FUNCTION (correct for all modules)
========================================================== */
function redirect_after_login(string $module, string $role): void {

    switch ($module) {

        case 'hygiene':
            header("Location: " . ($role === 'admin'
                ? "/campus/hygiene/admin_dashboard.php"
                : "/campus/hygiene/user_dashboard.php"));
            exit;

        case 'canteen':
            header("Location: " . ($role === 'admin'
                ? "/campus/canteen/admin_dashboard.php"
                : "/campus/canteen/user_dashboard.php"));
            exit;

        case 'gym':
            header("Location: " . ($role === 'admin'
                ? "/campus/gym/gym_admin_dashboard.php"
                : "/campus/gym/gym_home_dashboard.php"));
            exit;

        case 'notice':
            header("Location: " . ($role === 'admin'
                ? "/campus/notice/admin_notice_dashboard.php"
                : "/campus/notice/view_notice2.php"));
            exit;

        default:
            header("Location: /campus/main_dashboard.php");
            exit;
    }
}

/* ==========================================================
   IF ALREADY LOGGED IN → direct redirect 
========================================================== */
if (isset($_SESSION['user_id'], $_SESSION['role'])) {

    // Superadmin override
    if ($_SESSION['role'] === 'superadmin') {
        header("Location: /campus/superadmin_dashboard.php");
        exit;
    }

    redirect_after_login($requested_module, $_SESSION['role']);
}

/* ==========================================================
   PROCESS LOGIN FORM
========================================================== */
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // safe retrieval; ensures string type for trim
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    // NOTE: module may be empty string
    $module   = (string) ($_POST['module'] ?? '');

    if ($username === '' || $password === '') {
        $error = "Please enter username and password.";
    } else {
        // Fetch user
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $user = false;
            $error = "Database error.";
        }

        if (!$user) {
            $error = "Invalid username!";
        }
        elseif (!password_verify($password, (string)$user['password'])) {
            $error = "Incorrect password!";
        }
        else {

            // Special GYM rule: only gym_user = yes
            if ($module === "gym" && (($user['gym_user'] ?? '') !== "yes")) {
                $error = "You are not registered for the Gym Module.";
            } else {
                // SUCCESS
                session_regenerate_id(true);
                $_SESSION['user_id']  = (int)$user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role']     = $user['role'];

                // Superadmin override
                if ($user['role'] === "superadmin") {
                    header("Location: /campus/superadmin_dashboard.php");
                    exit;
                }

                redirect_after_login($module, $user['role']);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Login | Connect My Campus</title>
<style>
    body {
        font-family: 'Segoe UI', sans-serif;
        /* update to your actual image path if needed */
        background: url('/campus/assets/login_bg.jpg') no-repeat center center fixed;
        background-size: cover;
        height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
        margin: 0;
    }

    .overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.45); /* dim effect for readability */
        z-index: 1;
    }

    .login-container {
        position: relative;
        z-index: 2;
        background: rgba(255, 255, 255, 0.95);
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        width: 350px;
        text-align: center;
        animation: fadeIn 0.4s ease-in-out;
    }

    @keyframes fadeIn {
        from {opacity: 0; transform: scale(0.95);}
        to {opacity: 1; transform: scale(1);}
    }

    h2 {
        color: #0077b6;
        margin-bottom: 20px;
        text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
    }

    input {
        width: 90%;
        padding: 10px;
        margin: 10px 0;
        border: 1px solid #ccc;
        border-radius: 6px;
        font-size: 15px;
    }

    input[type="submit"] {
        background: #0077b6;
        color: white;
        cursor: pointer;
        border: none;
        transition: 0.3s;
        width: 95%;
        font-weight: 600;
    }

    input[type="submit"]:hover {
        background: #023e8a;
    }

    .error {
        color: red;
        margin-bottom: 10px;
    }

    .register-link {
        margin-top: 15px;
        display: block;
        color: #0077b6;
        text-decoration: none;
    }

    .register-link:hover {
        text-decoration: underline;
    }
</style>
</head>
<body>

<div class="overlay"></div>

<div class="login-container">
    <h2>Login</h2>

    <?php if (!empty($error)): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" action="">
        <input type="text" name="username" placeholder="Enter Username" required><br>
        <input type="password" name="password" placeholder="Enter Password" required><br>

        <!-- ensure module is passed back if clicking from main dashboard -->
        <input type="hidden" name="module" value="<?= htmlspecialchars($requested_module, ENT_QUOTES) ?>">

        <input type="submit" value="Login">
    </form>

    <a class="register-link" href="register.php">Don’t have an account? Register here</a>
    <p style="margin-top:8px"><small>Forgot password? Contact admin</small></p>
</div>

</body>
</html>

<?php ob_end_flush(); ?>
