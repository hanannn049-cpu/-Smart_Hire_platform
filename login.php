<?php
session_start();
$conn = new mysqli("localhost", "root", "", "smarthire_db");
if ($conn->connect_error) { die("Connection failed"); }
$message = "";
$class = "";
if (isset($_GET['registered'])) {
    $message = "Account created successfully. Please login.";
    $class = "success";
}
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $sql = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $sql->bind_param("s", $email);
    $sql->execute();
    $result = $sql->get_result();
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['fullname'] = $user['fullname'];
            $_SESSION['usertype'] = $user['usertype'];
            if ($user['usertype'] == "employer") {
                header("Location: employer-dashboard.php");
            } else {
                header("Location: jobseeker_dashboard.php");
            }
            exit();
        } else {
            $message = "Wrong email or password";
            $class = "error";
        }
    } else {
        $message = "Wrong email or password";
        $class = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - SmartHire</title>
    <link rel="stylesheet" href="CSS/style.css">
    <link rel="stylesheet" href="CSS/auth.css">
</head>
<body>
<header>
    <nav class="navbar">
        <div class="logo">
            <h2>SmartHire</h2>
            <p>Platform</p>
        </div>
        <ul class="nav-links">
            <li><a href="home.html">Home</a></li>
            <li><a href="register.php" class="signin-btn">Sign Up</a></li>
        </ul>
    </nav>
</header>
<section class="auth-section">
    <div class="auth-card">
        <h1>Login</h1>
        <p>Welcome back to SmartHire</p>
        <form method="POST">
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
        <p class="<?php echo $class; ?>"><?php echo $message; ?></p>
        <div class="auth-link">
            Don't have an account? <a href="register.php">Create Account</a>
        </div>
    </div>
</section>
<footer>
    <p>&copy; 2026 SmartHire Platform</p>
</footer>
</body>
</html>