<?php
class User {
    private $conn;
    private $table_name = "users";

    public $id;
    public $username;
    public $email;
    public $password;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create new user
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET username=:username, email=:email, password=:password";
        
        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->password = htmlspecialchars(strip_tags($this->password));

        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password", $this->password);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    // Checking if email or username already exists
    public function fieldExists($field, $value) {
        $query = "SELECT id, username, email, password FROM " . $this->table_name . " WHERE " . $field . " = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $value);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->username = $row['username'];
            $this->email = $row['email'];
            $this->password = $row['password'];
            return true;
        }
        return false;
    }

    // Get all users
    public function read() {
        $query = "SELECT id, username, email, created_at, updated_at FROM " . $this->table_name . " ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Get single user
    public function readOne() {
        $query = "SELECT id, username, email, created_at, updated_at FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $this->username = $row['username'];
            $this->email = $row['email'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            return true;
        }
        return false;
    }

    // Update user
    public function update() {
        $query = "UPDATE " . $this->table_name . " SET ";
        $params = [];
        
        $setParts = [];
        
        // Build dynamic query
        if (!empty($this->username)) {
            $setParts[] = "username = :username";
        }
        if (!empty($this->email)) {
            $setParts[] = "email = :email";
        }
        if (!empty($this->password)) {
            $setParts[] = "password = :password";
        }
        
        if (empty($setParts)) {
            return false;
        }
        
        $query = "UPDATE " . $this->table_name . " SET " . implode(", ", $setParts) . " WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);

        if (!empty($this->username)) {
            $this->username = htmlspecialchars(strip_tags($this->username));
            $stmt->bindParam(':username', $this->username);
        }
        if (!empty($this->email)) {
            $this->email = htmlspecialchars(strip_tags($this->email));
            $stmt->bindParam(':email', $this->email);
        }
        if (!empty($this->password)) {
            $this->password = htmlspecialchars(strip_tags($this->password));
            $stmt->bindParam(':password', $this->password);
        }
        
        $stmt->bindParam(':id', $this->id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Delete user
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }
}
?>
