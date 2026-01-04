<?php
class DatabaseConnection {
    private $db_host = "localhost";
    private $db_name = "user_api_db";
    private $db_user = "root";
    private $db_pass = "";
    public $connection;

    public function connect() {
        $this->connection = null;
        try {
            $dsn = "mysql:host=" . $this->db_host . ";dbname=" . $this->db_name;
            $this->connection = new PDO($dsn, $this->db_user, $this->db_pass);
            $this->connection->exec("set names utf8");
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            // Return 500 error if connection fails
            http_response_code(500);
            echo json_encode([
                "status" => false,
                "msg" => "Connection Error: " . $e->getMessage()
            ]);
            exit();
        }
        return $this->connection;
    }
}
?>
