<?php
include "auth.php";

checkLogin();
checkRole("employer");

$conn = new mysqli("localhost", "root", "", "smarthire_db");

if ($conn->connect_error) {
    die("Connection failed");
}

$id = $_GET['id'];
$employer_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM jobs WHERE id = ? AND employer_id = ?");
$stmt->bind_param("ii", $id, $employer_id);
$stmt->execute();
$result = $stmt->get_result();
$job = $result->fetch_assoc();

if (!$job) {
    die("Job not found");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $company_name = $_POST['company_name'];
    $job_title = $_POST['job_title'];
    $required_skills = $_POST['required_skills'];
    $job_description = $_POST['job_description'];
    $location = $_POST['location'];
    $salary = $_POST['salary'];

    $sql = "UPDATE jobs 
            SET company_name=?, job_title=?, required_skills=?, job_description=?, location=?, salary=? 
            WHERE id=? AND employer_id=?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "ssssssii",
        $company_name,
        $job_title,
        $required_skills,
        $job_description,
        $location,
        $salary,
        $id,
        $employer_id
    );

    $stmt->execute();

    header("Location: employer-dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Job</title>
    <link rel="stylesheet" href="CSS/style.css">
</head>

<body>

<header>
    <nav class="navbar">
        <div class="logo">
            <h2>SmartHire</h2>
            <p>Platform</p>
        </div>

        <ul class="nav-links">
            <li><a href="employer-dashboard.php">Dashboard</a></li>
            <li><a href="logout.php" class="signin-btn">Logout</a></li>
        </ul>
    </nav>
</header>

<div class="container">

    <div class="card">
        <h2>Edit Job</h2>

        <form method="POST">

            <input type="text" name="company_name"
                   value="<?php echo $job['company_name']; ?>" required>

            <input type="text" name="job_title"
                   value="<?php echo $job['job_title']; ?>" required>

            <input type="text" name="required_skills"
                   value="<?php echo $job['required_skills']; ?>" required>

            <input type="text" name="location"
                   value="<?php echo $job['location']; ?>" required>

            <input type="text" name="salary"
                   value="<?php echo $job['salary']; ?>" required>

            <textarea name="job_description" required
                style="width:100%;padding:12px;border-radius:10px;margin-bottom:15px;"><?php echo $job['job_description']; ?></textarea>

            <button type="submit">Update Job</button>

        </form>
    </div>

</div>

<footer>
    <p>&copy; 2026 SmartHire Platform</p>
</footer>

</body>
</html>