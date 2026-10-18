<?php
session_start();

$conn = new mysqli("localhost", "root", "", "smarthire_db");
if ($conn->connect_error) { die("Connection failed"); }

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SESSION['usertype'] != "jobseeker") {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $job_id = $_GET['id'];
    $user_id = $_SESSION['user_id'];

    $check = $conn->prepare("SELECT id FROM applications WHERE job_id = ? AND user_id = ?");
    $check->bind_param("ii", $job_id, $user_id);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows == 0) {
        $stmt = $conn->prepare("INSERT INTO applications (job_id, user_id, status) VALUES (?, ?, 'Pending')");
        $stmt->bind_param("ii", $job_id, $user_id);
        $stmt->execute();
    }
}

if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
    echo json_encode(["status" => "success", "message" => "Applied successfully."]);
    exit();
}

header("Location: jobseeker_dashboard.php?applied=success");
exit();
?>