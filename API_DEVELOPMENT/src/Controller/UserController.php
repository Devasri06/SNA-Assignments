<?php
require_once __DIR__ . '/../Middleware/AuthMiddleware.php';

class UserController {
    private $db;
    private $user;

    public function __construct($db) {
        $this->db = $db;
        $this->user = new User($db);
    }

    private function checkAuth() {
        $auth = AuthMiddleware::authenticate();
        if (!$auth) {
            $this->response(false, 401, "Unauthorized. Please provide a valid token.");
            exit;
        }
        return $auth;
    }

    public function getAll() {
        $this->checkAuth();
        $stmt = $this->user->read();
        $num = $stmt->rowCount();
        
        $users_arr = [];
        if($num > 0) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                array_push($users_arr, $row);
            }
        }
        $this->response(true, 200, "Users list.", $users_arr);
    }

    public function getOne($id) {
        $this->checkAuth();
        $this->user->id = $id;
        if ($this->user->readOne()) {
            $user_arr = [
                "id" => $this->user->id,
                "username" => $this->user->username,
                "email" => $this->user->email,
                "full_name" => $this->user->full_name,
                "phone" => $this->user->phone,
                "role" => $this->user->role,
                "is_active" => $this->user->is_active,
                "created_at" => $this->user->created_at,
                "updated_at" => $this->user->updated_at
            ];
            $this->response(true, 200, "User details.", $user_arr);
        } else {
            $this->response(false, 404, "User not found.");
        }
    }

    public function create($data) {
        $this->checkAuth();
        
        if (!isset($data['username']) || !isset($data['email']) || !isset($data['password'])) {
            $this->response(false, 400, "Please provide username, email and password.");
            return;
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $this->response(false, 400, "Invalid email format.");
            return;
        }

        if ($this->user->fieldExists('email', $data['email']) || $this->user->fieldExists('username', $data['username'])) {
            $this->response(false, 400, "User already exists (username or email).");
            return;
        }

        $this->user->username = $data['username'];
        $this->user->email = $data['email'];
        $this->user->password = password_hash($data['password'], PASSWORD_BCRYPT);
        
        if (isset($data['full_name'])) $this->user->full_name = $data['full_name'];
        if (isset($data['phone'])) $this->user->phone = $data['phone'];

        if ($this->user->create()) {
            $this->response(true, 201, "User created successfully.", [
                "id" => $this->user->id,
                "username" => $this->user->username,
                "email" => $this->user->email,
                "full_name" => $this->user->full_name,
                "phone" => $this->user->phone
            ]);
        } else {
            $this->response(false, 500, "Unable to create user.");
        }
    }

    public function update($id, $data) {
        $this->checkAuth();
        
        $this->user->id = $id;
        
        // First check if user exists
        if (!$this->user->readOne()) {
            $this->response(false, 404, "User not found.");
            return;
        } else {
            // Need to reset values because readOne populates the model, but for update we might want to change them
            // Actually, we pass new values to specific fields. 
            // My model update() uses object properties.
            // So we need to set the object properties to the NEW values if they exist in $data
            
            // NOTE: The model update() checks if property is !empty. 
            // So we just set what we have.
            // Reset fields first to avoid accidental carry over if the model instance is reused (it is newly created in cons here but good practice)
            $this->user->username = null;
            $this->user->email = null;
            $this->user->password = null;
            $this->user->full_name = null;
            $this->user->phone = null;
        }

        if (isset($data['username'])) $this->user->username = $data['username'];
        if (isset($data['email'])) $this->user->email = $data['email'];
        if (isset($data['password'])) $this->user->password = password_hash($data['password'], PASSWORD_BCRYPT);
        if (isset($data['full_name'])) $this->user->full_name = $data['full_name'];
        if (isset($data['phone'])) $this->user->phone = $data['phone'];

        if ($this->user->update()) {
             // Retrieve updated data to return
             $this->user->readOne(); 
             $res_data = [
                 "id" => $this->user->id,
                 "username" => $this->user->username,
                 "email" => $this->user->email,
                 "full_name" => $this->user->full_name,
                 "phone" => $this->user->phone,
                 "role" => $this->user->role,
                 "is_active" => $this->user->is_active,
                 "updated_at" => $this->user->updated_at
             ];
             $this->response(true, 200, "User updated successfully.", $res_data);
        } else {
             $this->response(false, 500, "Unable to update user.");
        }
    }

    public function delete($id) {
        $this->checkAuth();
        
        $this->user->id = $id;
        // Check existence
        if (!$this->user->readOne()) {
            $this->response(false, 404, "User not found.");
            return;
        }

        if ($this->user->delete()) {
            $this->response(true, 200, "User deleted successfully.");
        } else {
            $this->response(false, 500, "Unable to delete user.");
        }
    }

    private function response($success, $code, $message, $data = null) {
        http_response_code($code);
        $res = [
            "success" => $success,
            "message" => $message
        ];
        if (!$success) {
            $res["error"] = $code;
        }
        if ($data !== null) {
            $res["data"] = $data;
        }
        echo json_encode($res);
    }
}
?>
