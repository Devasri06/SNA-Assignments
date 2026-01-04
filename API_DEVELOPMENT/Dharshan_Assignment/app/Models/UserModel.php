<?php
class UserModel {
    private $db;
    private $table = "users";

    public $id;
    public $username;
    public $email;
    public $password;
    public $full_name;
    public $phone;
    public $role;
    public $is_active;
    public $created_at;
    public $updated_at;

    public function __construct($dbConnection) {
        $this->db = $dbConnection;
    }

    public function registerUser() {
        $sql = "INSERT INTO " . $this->table . " 
                SET username=:u, email=:e, password=:p, full_name=:f, phone=:ph";
        
        $stmt = $this->db->prepare($sql);

        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->password = htmlspecialchars(strip_tags($this->password));
        $this->full_name = !empty($this->full_name) ? htmlspecialchars(strip_tags($this->full_name)) : null;
        $this->phone = !empty($this->phone) ? htmlspecialchars(strip_tags($this->phone)) : null;

        $stmt->bindParam(":u", $this->username);
        $stmt->bindParam(":e", $this->email);
        $stmt->bindParam(":p", $this->password);
        $stmt->bindParam(":f", $this->full_name);
        $stmt->bindParam(":ph", $this->phone);

        if ($stmt->execute()) {
            $this->id = $this->db->lastInsertId();
            return true;
        }
        return false;
    }

    public function checkExistence($col, $val) {
        $sql = "SELECT id, username, email, password FROM " . $this->table . " WHERE " . $col . " = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(1, $val);
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

    public function fetchAll() {
        $sql = "SELECT id, username, email, full_name, phone, role, is_active, created_at, updated_at FROM " . $this->table . " ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt;
    }

    public function fetchOne() {
        $sql = "SELECT id, username, email, full_name, phone, role, is_active, created_at, updated_at FROM " . $this->table . " WHERE id = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();

        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($data) {
            $this->username = $data['username'];
            $this->email = $data['email'];
            $this->full_name = $data['full_name'];
            $this->phone = $data['phone'];
            $this->role = $data['role'];
            $this->is_active = $data['is_active'];
            $this->created_at = $data['created_at'];
            $this->updated_at = $data['updated_at'];
            return true;
        }
        return false;
    }

    public function modifyUser() {
        $fields = [];
        if (!empty($this->username)) $fields[] = "username = :u";
        if (!empty($this->email)) $fields[] = "email = :e";
        if (!empty($this->password)) $fields[] = "password = :p";
        if (!empty($this->full_name)) $fields[] = "full_name = :f";
        if (!empty($this->phone)) $fields[] = "phone = :ph";
        
        if (empty($fields)) return false;
        
        $sql = "UPDATE " . $this->table . " SET " . implode(", ", $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);

        if (!empty($this->username)) {
            $this->username = htmlspecialchars(strip_tags($this->username));
            $stmt->bindParam(':u', $this->username);
        }
        if (!empty($this->email)) {
            $this->email = htmlspecialchars(strip_tags($this->email));
            $stmt->bindParam(':e', $this->email);
        }
        if (!empty($this->password)) {
            $this->password = htmlspecialchars(strip_tags($this->password));
            $stmt->bindParam(':p', $this->password);
        }
        if (!empty($this->full_name)) {
            $this->full_name = htmlspecialchars(strip_tags($this->full_name));
            $stmt->bindParam(':f', $this->full_name);
        }
        if (!empty($this->phone)) {
            $this->phone = htmlspecialchars(strip_tags($this->phone));
            $stmt->bindParam(':ph', $this->phone);
        }
        
        $stmt->bindParam(':id', $this->id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function removeUser() {
        $sql = "DELETE FROM " . $this->table . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(1, $this->id);
        if ($stmt->execute()) return true;
        return false;
    }
}
?>
