<?php
require_once 'Database.php';
class Application {
    private $conn;
    private $table = "applications";
    public function __construct() {
        $db = new Database();
        $this->conn = $db->connect();
    }
    public function apply($job_id, $user_id) {
        $check = "SELECT id FROM " . $this->table . "
                  WHERE job_id = :job_id
                  AND user_id = :user_id";
        $check_stmt = $this->conn->prepare($check);
        $check_stmt->bindParam(':job_id', $job_id);
        $check_stmt->bindParam(':user_id', $user_id);
        $check_stmt->execute();
        if ($check_stmt->rowCount() > 0) {
            return false;
        }
        $query = "INSERT INTO " . $this->table . "
                  (job_id, user_id, status)
                  VALUES (:job_id, :user_id, 'Pending')";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':job_id', $job_id);
        $stmt->bindParam(':user_id', $user_id);
        return $stmt->execute();
    }
    public function getApplicationsByJob($job_id) {
        $query = "SELECT
                    applications.id,
                    applications.status,
                    users.fullname,
                    users.email,
                    applications.application_date
                  FROM applications
                  JOIN users
                  ON applications.user_id = users.id
                  WHERE applications.job_id = :job_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':job_id', $job_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>