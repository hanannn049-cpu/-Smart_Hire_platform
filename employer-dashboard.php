<?php
include "auth.php";

checkLogin();
checkRole("employer");

$conn = new mysqli("localhost", "root", "", "smarthire_db");
if ($conn->connect_error) { die("Connection failed"); }

$employer_id = $_SESSION['user_id'];
$company_name = $_SESSION['fullname'];

function calculateMatch($jobSkills, $userSkills) {
    if (count($userSkills) == 0) return [0, "Low"];

    $jobArray = array_map('trim', explode(",", strtolower($jobSkills)));
    $userArray = array_map('strtolower', $userSkills);

    $matched = 0;
    foreach ($jobArray as $skill) {
        if (in_array($skill, $userArray)) {
            $matched++;
        }
    }

    $percent = round(($matched / count($jobArray)) * 100);

    if ($percent >= 66) $level = "High";
    elseif ($percent >= 33) $level = "Medium";
    else $level = "Low";

    return [$percent, $level];
}


if (isset($_POST['add_job'])) {
    $job_title = $_POST['job_title'];
    $required_skills = $_POST['required_skills'];
    $job_description = $_POST['job_description'];
    $location = $_POST['location'];
    $salary = $_POST['salary'];

    $stmt = $conn->prepare(
        "INSERT INTO jobs
        (employer_id, company_name, job_title, job_description, required_skills, location, salary)
        VALUES (?, ?, ?, ?, ?, ?, ?)"
    );

    $stmt->bind_param(
        "issssss",
        $employer_id,
        $company_name,
        $job_title,
        $job_description,
        $required_skills,
        $location,
        $salary
    );

    $stmt->execute();
}


if (isset($_GET['action']) && isset($_GET['app_id'])) {
    $app_id = $_GET['app_id'];
    $status = $_GET['action'] == "accept" ? "Accepted" : "Rejected";

    $stmt = $conn->prepare(
        "UPDATE applications
         JOIN jobs ON applications.job_id = jobs.id
         SET applications.status = ?
         WHERE applications.id = ? AND jobs.employer_id = ?"
    );

    $stmt->bind_param("sii", $status, $app_id, $employer_id);
    $stmt->execute();

    header("Location: employer-dashboard.php#candidates");
    exit();
}


$jobs = $conn->prepare("SELECT * FROM jobs WHERE employer_id = ? ORDER BY created_at DESC");
$jobs->bind_param("i", $employer_id);
$jobs->execute();
$jobs_result = $jobs->get_result();


$applications = $conn->prepare(
    "SELECT applications.id AS app_id,
            applications.status,
            users.id AS user_id,
            users.fullname,
            users.email,
            jobs.job_title,
            jobs.required_skills
     FROM applications
     JOIN users ON applications.user_id = users.id
     JOIN jobs ON applications.job_id = jobs.id
     WHERE jobs.employer_id = ?
     ORDER BY applications.application_date DESC"
);

$applications->bind_param("i", $employer_id);
$applications->execute();
$applications_result = $applications->get_result();

$candidates = [];

while ($app = $applications_result->fetch_assoc()) {
    $skillStmt = $conn->prepare("SELECT skill_name FROM skills WHERE user_id = ?");
    $skillStmt->bind_param("i", $app['user_id']);
    $skillStmt->execute();
    $skillResult = $skillStmt->get_result();

    $userSkills = [];
    while ($s = $skillResult->fetch_assoc()) {
        $userSkills[] = $s['skill_name'];
    }

    list($percent, $level) = calculateMatch($app['required_skills'], $userSkills);

    $app['skills'] = implode(", ", $userSkills);
    $app['match_percent'] = $percent;
    $app['match_level'] = $level;

    $candidates[] = $app;
}

$filter = isset($_GET['filter']) ? $_GET['filter'] : "all";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Employer Dashboard</title>
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
            <li><a href="#addJob">Add Job</a></li>
            <li><a href="#myJobs">My Jobs</a></li>
            <li><a href="#candidates">Candidates</a></li>
        </ul>

        <div class="user-box">
            <span class="welcome-text">Welcome <?php echo $_SESSION['fullname']; ?></span>
            <div class="user-icon" onclick="toggleMenu()">👤</div>

            <div class="dropdown-menu" id="userMenu">
                <a href="logout.php">Logout</a>
            </div>
        </div>
    </nav>
</header>

<div class="container">

    <div class="page-title">
        <h1>Employer Dashboard</h1>
        <p>Manage your jobs and candidates.</p>
    </div>

    
    <div class="card" id="addJob">
        <h3>Add New Job</h3>

        <form method="POST">
            <input type="text" name="job_title" placeholder="Job Title" required>
            <input type="text" name="required_skills" placeholder="Required Skills" required>
            <input type="text" name="location" placeholder="Location" required>
            <input type="text" name="salary" placeholder="Salary" required>

            <textarea name="job_description"
                      placeholder="Job Description"
                      required
                      style="width:100%;padding:12px;border-radius:10px;margin-bottom:15px;"></textarea>

            <button type="submit" name="add_job">Add Job</button>
        </form>
    </div>

    <h2 id="myJobs">My Jobs</h2>

    <?php while ($job = $jobs_result->fetch_assoc()): ?>
        <div class="job-card">
            <h3 class="job-title"><?php echo $job['job_title']; ?></h3>

            <p><strong>Company:</strong> <?php echo $job['company_name']; ?></p>
            <p><strong>Skills:</strong> <?php echo $job['required_skills']; ?></p>
            <p><strong>Location:</strong> <?php echo $job['location']; ?></p>
            <p><strong>Salary:</strong> <?php echo $job['salary']; ?></p>
            <p><?php echo $job['job_description']; ?></p>

            <a href="edit_jop.php?id=<?php echo $job['id']; ?>" class="filter-btn">Edit</a>
        </div>
    <?php endwhile; ?>
    

    <h2 id="candidates">Candidates</h2>

    <div class="filter-group">
        <a href="employer-dashboard.php?filter=all#candidates" class="filter-btn">All</a>
        <a href="employer-dashboard.php?filter=High#candidates" class="filter-btn">High</a>
        <a href="employer-dashboard.php?filter=Medium#candidates" class="filter-btn">Medium</a>
        <a href="employer-dashboard.php?filter=Low#candidates" class="filter-btn">Low</a>
    </div>

    <?php foreach ($candidates as $app): ?>
        <?php if ($filter != "all" && $app['match_level'] != $filter) continue; ?>

        <div class="card">
            <div class="job-header">
                <div>
                    <p><strong>Name:</strong> <?php echo $app['fullname']; ?></p>
                    <p><strong>Email:</strong> <?php echo $app['email']; ?></p>
                    <p><strong>Applied For:</strong> <?php echo $app['job_title']; ?></p>
                    <p><strong>Status:</strong> <?php echo $app['status']; ?></p>
                </div>

                <div class="match-badge match-<?php echo strtolower($app['match_level']); ?>">
                    <div class="match-pct"><?php echo $app['match_percent']; ?>%</div>
                    <div class="match-label"><?php echo $app['match_level']; ?> Match</div>
                </div>
            </div>

            <p><strong>Skills:</strong> <?php echo $app['skills']; ?></p>

            <div class="filter-group">
                <button onclick='viewProfile(<?php echo json_encode($app["fullname"]); ?>, <?php echo json_encode($app["email"]); ?>, <?php echo json_encode($app["skills"]); ?>, <?php echo json_encode($app["match_percent"] . "%"); ?>, <?php echo $app["app_id"]; ?>)'>
                    View Profile
                </button>
            </div>
        </div>

    <?php endforeach; ?>

</div>

<div id="profileModal" class="modal-box">
    <div class="modal-content">
        <h2 id="mName"></h2>
        <p id="mEmail"></p>
        <p id="mSkills"></p>
        <p id="mMatch"></p>
        <div class="filter-group">
            <a id="mAccept" class="filter-btn">Accept</a>
            <a id="mReject" class="filter-btn">Reject</a>
            <button onclick="closeModal()">Close</button>
        </div>
    </div>
</div>

<footer>
    <p>&copy; 2026 SmartHire Platform</p>
</footer>

<script>
function toggleMenu() {
    var menu = document.getElementById("userMenu");
    menu.style.display = menu.style.display === "block" ? "none" : "block";
}

function viewProfile(name, email, skills, match, appId) {
    document.getElementById("mName").innerHTML = name;
    document.getElementById("mEmail").innerHTML = "<strong>Email:</strong> " + email;
    document.getElementById("mSkills").innerHTML = "<strong>Skills:</strong> " + skills;
    document.getElementById("mMatch").innerHTML = "<strong>Match:</strong> " + match;
    document.getElementById("mAccept").href = "employer-dashboard.php?action=accept&app_id=" + appId;
    document.getElementById("mReject").href = "employer-dashboard.php?action=reject&app_id=" + appId;
    document.getElementById("profileModal").style.display = "flex";
}

function closeModal() {
    document.getElementById("profileModal").style.display = "none";
}
</script>

</body>
</html>