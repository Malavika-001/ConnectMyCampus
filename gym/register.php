<?php
$host = "localhost";
$user = "root";
$pass = "Root1234";
$db = "campus";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $college_id = trim($_POST['college_id']);
    $department = $_POST['department'];
    $year = $_POST['year'];
    $phone = trim($_POST['phone']);
    $category = $_POST['category'];
    $role = $_POST['role'];
    $gym_user = $_POST['gym_user'];
    $gym_id = ($gym_user === "Yes") ? trim($_POST['gym_id']) : NULL;
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate
    if ($password !== $confirm_password) {
        $message = "✗ Passwords do not match!";
    } elseif (!preg_match('/^[0-9]{10}$/', $phone)) {
        $message = "✗ Phone number must be exactly 10 digits!";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Admin requests go to Super Admin
        if ($role === 'admin') {
            $role = 'admin_request';
            $admin_request_status = 'pending';
        } else {
            $admin_request_status = NULL;
        }

        $sql = "INSERT INTO users (name, college_id, department, year, phone,category, gym_user, gym_id, username, password, role, admin_request_status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param(
                "ssssssssssss",
                $name, $college_id, $department, $year, $phone, $category,
                $gym_user, $gym_id, $username, $hashed_password, $role, $admin_request_status
            );

            if ($stmt->execute()) {
                if ($role === 'admin_request') {
                    echo "<script>alert('✅ Registration successful! Your admin request is pending Super Admin approval.'); window.location.href='login.php';</script>";
                } else {
                    echo "<script>alert('✅ Registration successful! Please login.'); window.location.href='login.php';</script>";
                }
                exit;
            } else {
                $message = "✗ Error: Could not register user. (" . $stmt->error . ")";
            }
            $stmt->close();
        } else {
            $message = "✗ Database error: " . $conn->error;
        }
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Register | Connect My Campus</title>
<style>
body {
    font-family: 'Segoe UI', sans-serif;

    /* ⭐ Background Image */
    background: url('/campus/assets/register_bg.jpg') no-repeat center center fixed;
    background-size: cover;

    margin: 0;
    padding: 0;
    height: 100vh;

    display: flex;
    justify-content: center;
    align-items: center;
}

.overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.45);
    z-index: 1;
}

form {
    position: relative;
    z-index: 2;

    background: rgba(255, 255, 255, 0.95);
    border-radius: 12px;
    padding: 30px;
    width: 400px;

    box-shadow: 0 4px 15px rgba(0,0,0,0.3);
}

h2 {
    color: #0077b6;
    text-align: center;
    margin-bottom: 20px;
}

input, select {
    width: 100%;
    padding: 10px;
    margin: 6px 0;
    border: 1px solid #ccc;
    border-radius: 5px;
}

input[type=submit] {
    background-color: #0077b6;
    color: white;
    cursor: pointer;
    border: none;
    transition: 0.3s;
}

input[type=submit]:hover {
    background-color: #023e8a;
}

.radio-group {
    display: flex;
    justify-content: space-between;
    margin: 10px 0;
}

.error {
    color: red;
    text-align: center;
}
</style>
</head>

<body>

<div class="overlay"></div>

<form method="POST">
    <h2>Registration</h2>

    <input type="text" name="name" placeholder="Full Name" required>
    <input type="text" name="college_id" placeholder="College ID" required>

    <select name="department" required>
        <option value="">-- Select Department --</option>
        <option>PG Department Of Computer Science</option>
        <option>Department Of Physics</option>
        <option>Department Of Economics</option>
        <option>Malayalam, Sanskrit, Political Science</option>
        <option>Department Of Physical Education</option>
        <option>PG Department Of Commerce</option>
        <option>PG Department Of History</option>
        <option>Department Of Hindi</option>
        <option>Department Of English</option>
        <option>PG Department Of Chemistry</option>
        <option>Department Of Mathematics</option>
        <option>Department Of Commerce</option>
        <option>Department Of Tourism Studies</option>
    </select>

    <select name="year" required>
        <option value="">-- Select Year --</option>
        <option>1st Year UG</option>
        <option>2nd Year UG</option>
        <option>3rd Year UG</option>
        <option>1st Year PG</option>
        <option>2nd Year PG</option>
    </select>

    <input type="text" name="phone" placeholder="Phone Number" required>

    <div class="radio-group">
        Category
        <label><input type="radio" name="category" value="student" checked>Student</label>
        <label><input type="radio" name="category" value="staff">Staff</label>
    </div>


    <div class="radio-group">
        Role
        <label><input type="radio" name="role" value="user" checked>User</label>
        <label><input type="radio" name="role" value="admin">Admin</label>
    </div>

    <div class="radio-group">
        Gym User?
        <label><input type="radio" name="gym_user" value="No" checked> No</label>
        <label><input type="radio" name="gym_user" value="Yes"> Yes</label>
    </div>

    <div id="gymIdField" style="display:none;">
        <input type="text" name="gym_id" placeholder="Enter Gym ID">
    </div>

    <input type="text" name="username" placeholder="Create Username" required>
    <input type="password" name="password" placeholder="Create Password" required>
    <input type="password" name="confirm_password" placeholder="Confirm Password" required>

    <input type="submit" value="Register">

    <p>Already have an account? <a href="login.php">Login here</a></p>

    <?php if (!empty($message)): ?>
        <p class="error"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>
</form>

<script>
document.querySelectorAll('input[name="gym_user"]').forEach(radio => {
    radio.addEventListener('change', function() {
        document.getElementById('gymIdField').style.display =
            (this.value === 'Yes') ? 'block' : 'none';
    });
});
</script>

</body>
</html>
