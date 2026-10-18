<?php
include "auth.php";

checkLogin();
checkRole("jobseeker");

$conn = new mysqli("localhost", "root", "", "smarthire_db");
if ($conn->connect_error) { die("Connection failed"); }

$user_id = $_SESSION['user_id'];

/* Add skill_level safely if the old database does not have it */
$checkLevel = $conn->query("SHOW COLUMNS FROM skills LIKE 'skill_level'");
if ($checkLevel && $checkLevel->num_rows == 0) {
    $conn->query("ALTER TABLE skills ADD skill_level VARCHAR(50) DEFAULT 'Beginner'");
}

if (isset($_POST['add_skill'])) {
    $skill = trim($_POST['skill']);
    $level = $_POST['skill_level'];

    // Check if skill already exists for this user
     $check = $conn->prepare("SELECT id FROM skills WHERE user_id = ? AND skill_name = ?");
     $check->bind_param("is", $user_id, $skill);
     $check->execute();
     $result = $check->get_result();

if ($result->num_rows > 0) {
    // Skill already exists → do NOT insert again
    echo "<script>alert('Skill already exists!');</script>";
} else {
    // Insert new skill
    $stmt = $conn->prepare("INSERT INTO skills (user_id, skill_name, skill_level) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $skill, $level);
    $stmt->execute();
}

}

if (isset($_GET['delete_skill'])) {
    $skill_id = $_GET['delete_skill'];

    $stmt = $conn->prepare("DELETE FROM skills WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $skill_id, $user_id);
    $stmt->execute();
}

$skills_result = $conn->prepare("SELECT * FROM skills WHERE user_id = ?");
$skills_result->bind_param("i", $user_id);
$skills_result->execute();
$skills_data = $skills_result->get_result();

$skills_for_display = [];
while ($skill = $skills_data->fetch_assoc()) {
    $skills_for_display[] = $skill;
}

$applications = $conn->prepare(
    "SELECT jobs.job_title, jobs.company_name, applications.status, applications.application_date
     FROM applications
     JOIN jobs ON applications.job_id = jobs.id
     WHERE applications.user_id = ?
     ORDER BY applications.application_date DESC"
);
$applications->bind_param("i", $user_id);
$applications->execute();
$applications_result = $applications->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Job Seeker Dashboard</title>
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
            <li><a href="#skills">My Skills</a></li>
            <li><a href="jobs_page.php">Find Jobs</a></li>
            <li><a href="#applications">My Applications</a></li>
        </ul>

        <div class="user-box">
            <span class="welcome-text">Welcome <?php echo htmlspecialchars($_SESSION['fullname']); ?></span>
            <div class="user-icon" onclick="toggleMenu()">👤</div>

            <div class="dropdown-menu" id="userMenu">
                <a href="logout.php">Logout</a>
            </div>
        </div>
    </nav>
</header>

<div class="container">

    <div class="page-title">
        <h1>Job Seeker Dashboard</h1>
        <p>Add your skills and search for suitable jobs.</p>
    </div>

    <div class="card" id="skills">
        <h3>Add Skill</h3>

        <form method="POST">
            <input type="text" name="skill" placeholder="Skill Name" required>

            <select name="skill_level" required
                    style="width:100%;padding:12px;border-radius:10px;margin-bottom:15px;background:#1e293b;color:#e2e8f0;border:1px solid rgba(255,255,255,0.1);">
                <option value="Beginner">Beginner</option>
                <option value="Intermediate">Intermediate</option>
                <option value="Advanced">Advanced</option>
            </select>

            <button type="submit" name="add_skill">Add Skill</button>
        </form>
    </div>

    <div class="card">
        <h3>My Skills</h3>

        <ul id="skillsList">
            <?php foreach ($skills_for_display as $skill): ?>
                <li>
                    <span class="skill-name">
                        <?php echo htmlspecialchars($skill['skill_name']); ?> -
                        <?php echo htmlspecialchars($skill['skill_level'] ?? 'Beginner'); ?>
                    </span>
                    <a href="jobseeker_dashboard.php?delete_skill=<?php echo $skill['id']; ?>" class="filter-btn">Delete</a>
                </li>
            <?php endforeach; ?>
        </ul>

        <br>
        <a href="jobs_page.php" class="main-btn">Find Jobs</a>
    </div>

    <h2 id="applications">My Applications</h2>

    <?php while ($app = $applications_result->fetch_assoc()): ?>
        <div class="card">
            <p><strong>Job:</strong> <?php echo htmlspecialchars($app['job_title']); ?></p>
            <p><strong>Company:</strong> <?php echo htmlspecialchars($app['company_name']); ?></p>
            <p><strong>Status:</strong> <?php echo htmlspecialchars($app['status']); ?></p>
        </div>
    <?php endwhile; ?>

</div>

<footer>
    <p>&copy; 2026 SmartHire Platform</p>
</footer>

<script>
function toggleMenu() {
    var menu = document.getElementById("userMenu");
    menu.style.display = menu.style.display === "block" ? "none" : "block";
}
</script>

</body>
</html>
