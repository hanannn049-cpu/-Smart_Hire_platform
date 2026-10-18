<?php

require_once 'Database.php';

class Job {

    private $conn;
    private $table = "jobs";

    public function __construct() {

        $db = new Database();
        $this->conn = $db->connect();

    }

    // Get all jobs
    public function getAllJobs() {

        $query = "SELECT * FROM " . $this->table . "
                  ORDER BY created_at DESC";

        $stmt = $this->conn->prepare($query);

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get job by ID
    public function getJobById($id) {

        $query = "SELECT * FROM " . $this->table . "
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':id', $id);

        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Add new job
    public function addJob(
        $employer_id,
        $company_name,
        $job_title,
        $job_description,
        $required_skills,
        $location,
        $salary
    ) {

        $query = "INSERT INTO " . $this->table . "
                (
                    employer_id,
                    company_name,
                    job_title,
                    job_description,
                    required_skills,
                    location,
                    salary
                )
                VALUES
                (
                    :employer_id,
                    :company_name,
                    :job_title,
                    :job_description,
                    :required_skills,
                    :location,
                    :salary
                )";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':employer_id', $employer_id);
        $stmt->bindParam(':company_name', $company_name);
        $stmt->bindParam(':job_title', $job_title);
        $stmt->bindParam(':job_description', $job_description);
        $stmt->bindParam(':required_skills', $required_skills);
        $stmt->bindParam(':location', $location);
        $stmt->bindParam(':salary', $salary);

        return $stmt->execute();
    }

    // Delete job
    public function deleteJob($id) {

        $query = "DELETE FROM " . $this->table . "
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':id', $id);

        return $stmt->execute();
    }
}

?>