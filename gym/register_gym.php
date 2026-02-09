 <?php
session_start();
$host = "localhost";
$user = "root";
$pass = "Root1234";
$db = "smartcampus";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$message = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST['name']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'];

    if ($password !== $confirm_password) {
        $message = "❌ Passwords do not match!";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username, password, role, name) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $username, $hashed_password, $role, $name);
        if ($stmt->execute()) {
            $message = "✅ Registered successfully! You can now login.";
        } else {
            $message = ($conn->errno == 1062)
                ? "⚠️ Username already exists."
                : "❌ Error: " . $conn->error;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Register | SSV College Gym</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
<style>
body {
  margin:0; padding:0;
  font-family:'Poppins',sans-serif;
  background:url('login.jpeg') no-repeat center center fixed;
  background-size:cover;
  display:flex;align-items:center;justify-content:center;
  height:100vh; backdrop-filter:brightness(60%);
}
.register-box {
  background:rgba(0,0,0,0.75);
  padding:40px; border-radius:15px;
  width:380px; color:white;
  text-align:center; box-shadow:0 4px 12px rgba(255,255,255,0.2);
}
h2 { color:#00bfff; margin-bottom:20px; }
input, select {
  width:90%; padding:10px; margin:10px 0;
  border:none; border-radius:8px;
}
.password-wrapper { position:relative; width:90%; margin:auto; }
.password-wrapper input { width:100%; padding-right:35px; }
.password-wrapper i {
  position:absolute; right:10px; top:10px;
  cursor:pointer; color:#00bfff;
}
input[type="submit"] {
  background:#00bfff; border:none;
  color:white; font-weight:600;
  border-radius:8px; width:95%; padding:10px;
  cursor:pointer; transition:0.3s;
}
input[type="submit"]:hover { background:#0090cc; }
.message { margin:10px 0; font-weight:600; }
.success { color:#00ff88; }
.error { color:#ff6b6b; }
a { color:#00bfff; text-decoration:none; }
a:hover { text-decoration:underline; }
</style>
</head>
<body>
<div class="register-box">
  <h2>🏋️‍♂️ Register for Gym</h2>
  <?php if($message): ?>
  <div class="message <?= strpos($message,'✅')!==false?'success':'error' ?>">
    <?= htmlspecialchars($message) ?>
  </div>
  <?php endif; ?>
  <form method="POST">
    <input type="text" name="name" placeholder="Full Name" required><br>
    <input type="text" name="username" placeholder="Set Username" required><br>

    <div class="password-wrapper">
      <input type="password" name="password" id="password" placeholder="Set Password" required>
      <i class="fas fa-eye" id="togglePassword"></i>
    </div><br>

    <div class="password-wrapper">
      <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm Password" required>
      <i class="fas fa-eye" id="toggleConfirm"></i>
    </div><br>

    <select name="role" required>
      <option value="">-- Select Role --</option>
      <option value="user">User</option>
      <option value="admin">Admin</option>
    </select><br>

    <input type="submit" value="Register">
  </form>
  <p style="margin-top:15px;">Already registered? <a href="login_gym.php">Login</a></p>
</div>

<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
<script>
function toggleVisibility(toggleId, inputId){
  const toggle=document.getElementById(toggleId);
  const input=document.getElementById(inputId);
  toggle.addEventListener("click",()=>{
    const type=input.getAttribute("type")==="password"?"text":"password";
    input.setAttribute("type",type);
    toggle.classList.toggle("fa-eye-slash");
  });
}
toggleVisibility("togglePassword","password");
toggleVisibility("toggleConfirm","confirm_password");
</script>
</body>
</html>
