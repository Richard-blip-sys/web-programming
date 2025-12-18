<?php
// config.php - INFINITYFREE
class Database {
    private $host = "sql107.infinityfree.com";
    private $db_name = "if0_40680548_withdrawal_system";
    private $username = "if0_40680548";
    private $password = "lkjE3CaaEi8iR7";  // ← Use the FULL password from the eye icon
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            echo "Connection error: " . $e->getMessage();
        }
        return $this->conn;
    }
}