<?php
include "auth.php";

checkLogin(); //
checkRole("jobseeker");

$conn = new mysqli("localhost", "root", "", "smarthire_db");
if ($conn->connect_error) { die("Connection failed"); }

$user_id = $_SESSION['user_id'];

function calculateMatch($jobSkills, $userSkills) {
    if (count($userSkills) == 0) return [0, "Low"];

    $jobArray = array_map('trim', explode(",", strtolower($jobSkills)));  //["php", "java","mysql"]
    $userArray = array_map('strtolower', $userSkills);

    $matched = 0;
    foreach ($jobArray as $skill) {
        if (in_array($skill, $userArray)) {
            $matched++;
        }
    }

    $percent = round(($matched / count($jobArray)) * 100); 

    if ($percent >= 66) $level = "High";  //determine the level of the match
    elseif ($percent >= 33) $level = "Medium";
    else $level = "Low";

    return [$percent, $level]; //array 
}

$skills_result = $conn->prepare("SELECT skill_name FROM skills WHERE user_id = ?"); 
$skills_result->bind_param("i", $user_id);
$skills_result->execute();
$skills_data = $skills_result->get_result();

$userSkills = [];
while ($skill = $skills_data->fetch_assoc()) { //fetch the skills row by row
    $userSkills[] = $skill['skill_name'];
}

$jobs = $conn->query("SELECT * FROM jobs ORDER BY created_at DESC");
$filter = isset($_GET['filter']) ? $_GET['filter'] : "all";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Jobs - SmartHire</title>
    <link rel="stylesheet" href="CSS/style.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body>

<header>
<nav class="navbar">
    <div class="logo">
        <h2>SmartHire</h2>
        <p>Platform</p>
    </div>

    <ul class="nav-links">
        <li><a href="jobseeker_dashboard.php">Dashboard</a></li>
        <li><a href="jobs_page.php" class="active-link">Jobs</a></li>
        <li><a href="jobseeker_dashboard.php#applications">My Applications</a></li>
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
        <h1>Available Jobs</h1>
        <p>Jobs matched based on your skills. Higher match = better fit.</p>
    </div>

    <div class="card">
        <h3>Filter by Match</h3>
        <div class="filter-group">
            <a href="jobs_page.php?filter=all" class="filter-btn">All Jobs</a>
            <a href="jobs_page.php?filter=High" class="filter-btn">High Match</a>
            <a href="jobs_page.php?filter=Medium" class="filter-btn">Medium Match</a>
            <a href="jobs_page.php?filter=Low" class="filter-btn">Low Match</a>
        </div>
    </div>

    <div id="applyMessage"></div>

    <?php while ($job = $jobs->fetch_assoc()): ?>
        <?php
            list($matchPercent, $matchLevel) = calculateMatch($job['required_skills'], $userSkills);
            if ($filter != "all" && $matchLevel != $filter) continue;
        ?>

        <div class="job-card">
            <div class="job-header">
                <h3 class="job-title"><?php echo htmlspecialchars($job['job_title']); ?></h3>

                <div class="match-badge match-<?php echo strtolower($matchLevel); ?>">
                    <div class="match-pct"><?php echo $matchPercent; ?>%</div>
                    <div class="match-label"><?php echo $matchLevel; ?> Match</div>
                </div>
            </div>

            <div class="match-bar-track">
                <div class="match-bar-fill match-fill-<?php echo strtolower($matchLevel); ?>"
                     style="width:<?php echo $matchPercent; ?>%"></div>
            </div>

            <p><strong>Company:</strong> <?php echo htmlspecialchars($job['company_name']); ?></p>
            <p><strong>Location:</strong> <?php echo htmlspecialchars($job['location']); ?></p>
            <p><strong>Salary:</strong> <?php echo htmlspecialchars($job['salary']); ?></p>
            <p><strong>Required Skills:</strong> <?php echo htmlspecialchars($job['required_skills']); ?></p>
            <p><?php echo htmlspecialchars($job['job_description']); ?></p>

            <button class="apply-btn" data-job-id="<?php echo $job['id']; ?>">Apply</button>
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
$(document).on("click", ".apply-btn", function () {
    var btn = $(this);
    btn.prop("disabled", true).text("Applying...");

    $.ajax({
        url: "apply_job.php?id=" + btn.data("job-id"),
        type: "GET",
        success: function () {
            btn.text("✔ Applied");
            $("#applyMessage")
                .removeClass("msg-error")
                .addClass("msg-success")
                .text("Applied successfully.")
                .fadeIn();
        },
        error: function () {
            btn.prop("disabled", false).text("Apply");
            $("#applyMessage")
                .removeClass("msg-success")
                .addClass("msg-error")
                .text("Could not apply. Try again.")
                .fadeIn();
        }
    });
});
</script>

</body>
</html>