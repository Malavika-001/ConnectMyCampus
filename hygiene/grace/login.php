<?php
ob_start();
session_start();

$host = "localhost";
$user = "root";
$pass = "Root1234";
$db = "campus"; // change if your DB name differs

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Fetch the user
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // Redirect based on role
            if ($user['role'] === 'superadmin') {
                header("Location: superadmin_dashboard.php");
                exit;
            } elseif ($user['role'] === 'admin') {
                header("Location: admin_dashboard.php");
                exit;
            } elseif ($user['role'] === 'user') {
                header("Location: user_dashboard.php");
                exit;
            } else {
                $error = "Invalid role assigned.";
            }
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "User not found.";
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Login | Connect My Campus</title>
<style>
    body {
        font-family: 'Segoe UI', sans-serif;
        background: url('Gemini_Generated_Image_8yligq8yligq8yli') no-repeat center center fixed;
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
        <input type="submit" value="Login">
    </form>

    <a class="register-link" href="register.php">Don’t have an account? Register here</a>
</div>

</body>
</html>

<?php ob_end_flush(); ?>
