<?php
$conn = new mysqli("localhost", "root", "", "smarthire_db");
if ($conn->connect_error) {
    die("Connection failed");
}
$message = "";
$class = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $usertype = $_POST['usertype'];
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $result = $check->get_result();
    if ($result->num_rows > 0) {
        $message = "Email already exists.";
        $class = "error";
    } else {
        $sql = $conn->prepare(
            "INSERT INTO users (fullname, email, password, usertype)
             VALUES (?, ?, ?, ?)"
        );
        $sql->bind_param("ssss", $fullname, $email, $hashedPassword, $usertype);
        if ($sql->execute()) {
            header("Location: login.php?registered=success");
            exit();
        } else {
            $message = "Something went wrong. Please try again.";
            $class = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - SmartHire</title>
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
            <li><a href="home.html#home">Home</a></li>
            <li><a href="home.html#about">About</a></li>
            <li><a href="home.html#contact">Contact</a></li>
            <li><a href="login.php" class="signin-btn">Sign In</a></li>
        </ul>
    </nav>
</header>
<section class="auth-section">
    <div class="auth-card">
        <h1>Create Account</h1>
        <p>Join SmartHire Platform</p>
        <form method="POST">
            <select name="usertype" required>
                <option value="">Select Account Type</option>
                <option value="jobseeker">Job Seeker</option>
                <option value="employer">Employer</option>
            </select>
            <input type="text" name="fullname" placeholder="Full Name" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Register</button>
        </form>
        <p class="<?php echo $class; ?>">
            <?php echo $message; ?>
        </p>
        <div class="auth-link">
            Already have an account? <a href="login.php">Login</a>
        </div>
    </div>
</section>
<footer>
    <p>&copy; 2026 SmartHire Platform</p>
</footer>
</body>
</html>