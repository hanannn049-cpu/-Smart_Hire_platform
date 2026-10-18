<?php
class Database {
    private $host = "localhost";
    private $dbname = "smarthire_db";
    private $username = "root";
    private $password = "";
    private $conn;
    public function connect() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->dbname . ";charset=utf8mb4",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(
                PDO::ATTR_ERRMODE,
                PDO::ERRMODE_EXCEPTION
            );
            $this->conn->setAttribute(
                PDO::ATTR_DEFAULT_FETCH_MODE,
                PDO::FETCH_ASSOC
            );
        } catch (PDOException $e) {
            die("Database Connection Error: " . $e->getMessage());

        }
        return $this->conn;
    }
}
?>