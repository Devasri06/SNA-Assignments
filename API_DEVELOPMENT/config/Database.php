<?php
class Database {
    private $host = "localhost";
    private $db_name = "user_api_db";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $exception) {
            // In a real API, you might want to log this and return a 500 JSON error
            // For now, we will handle it in the controller or just let it bubble if needed
            // echo "Connection error: " . $exception->getMessage();
        }
        return $this->conn;
    }
}
?>
