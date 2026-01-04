<?php
require_once __DIR__ . '/../Middleware/AuthGuard.php';

class UserHandler {
    private $db;
    private $user;

    public function __construct($db) {
        $this->db = $db;
        $this->user = new UserModel($db);
    }

    private function validateAuth() {
        $payload = AuthGuard::verifyRequests();
        if (!$payload) {
            http_response_code(401);
            echo json_encode(["success" => false, "message" => "Unauthorized access."]);
            exit;
        }
        return $payload;
    }

    public function index() {
        $this->validateAuth();
        $stmt = $this->user->fetchAll();
        $count = $stmt->rowCount();
        
        $list = [];
        if($count > 0) {
            while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $list[] = $r;
            }
        }
        $this->sendResponse(true, 200, "All users fetched.", $list);
    }

    public function show($id) {
        $this->validateAuth();
        $this->user->id = $id;
        if ($this->user->fetchOne()) {
            $details = [
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
            $this->sendResponse(true, 200, "User found.", $details);
        } else {
            $this->sendResponse(false, 404, "Not found.");
        }
    }

    public function store($input) {
        $this->validateAuth();
        
        if (empty($input['username']) || empty($input['email']) || empty($input['password'])) {
            $this->sendResponse(false, 400, "Required fields missing.");
            return;
        }

        if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            $this->sendResponse(false, 400, "Invalid email.");
            return;
        }

        if ($this->user->checkExistence('email', $input['email']) || $this->user->checkExistence('username', $input['username'])) {
            $this->sendResponse(false, 400, "Duplicate record.");
            return;
        }

        $this->user->username = $input['username'];
        $this->user->email = $input['email'];
        $this->user->password = password_hash($input['password'], PASSWORD_BCRYPT);
        
        if (isset($input['full_name'])) $this->user->full_name = $input['full_name'];
        if (isset($input['phone'])) $this->user->phone = $input['phone'];

        if ($this->user->registerUser()) {
            $this->sendResponse(true, 201, "Created successfully.", [
                "id" => $this->user->id,
                "username" => $this->user->username,
                "email" => $this->user->email
            ]);
        } else {
            $this->sendResponse(false, 500, "Creation error.");
        }
    }

    public function update($id, $input) {
        $this->validateAuth();
        
        $this->user->id = $id;
        
        if (!$this->user->fetchOne()) {
            $this->sendResponse(false, 404, "User missing.");
            return;
        } else {
            // clear Props
            $this->user->username = null;
            $this->user->email = null;
            $this->user->password = null;
            $this->user->full_name = null;
            $this->user->phone = null;
        }

        if (isset($input['username'])) $this->user->username = $input['username'];
        if (isset($input['email'])) $this->user->email = $input['email'];
        if (isset($input['password'])) $this->user->password = password_hash($input['password'], PASSWORD_BCRYPT);
        if (isset($input['full_name'])) $this->user->full_name = $input['full_name'];
        if (isset($input['phone'])) $this->user->phone = $input['phone'];

        if ($this->user->modifyUser()) {
             $this->user->fetchOne(); 
             $res = [
                 "id" => $this->user->id,
                 "username" => $this->user->username,
                 "email" => $this->user->email,
                 "full_name" => $this->user->full_name,
                 "phone" => $this->user->phone,
                 "role" => $this->user->role,
                 "updated_at" => $this->user->updated_at
             ];
             $this->sendResponse(true, 200, "Updated.", $res);
        } else {
             $this->sendResponse(false, 500, "Update failed.");
        }
    }

    public function destroy($id) {
        $this->validateAuth();
        
        $this->user->id = $id;
        if (!$this->user->fetchOne()) {
            $this->sendResponse(false, 404, "User not found.");
            return;
        }

        if ($this->user->removeUser()) {
            $this->sendResponse(true, 200, "Deleted.");
        } else {
            $this->sendResponse(false, 500, "Deletion failed.");
        }
    }

    private function sendResponse($status, $code, $msg, $data = null) {
        http_response_code($code);
        $res = [
            "success" => $status,
            "message" => $msg
        ];
        if ($data) {
            $res["data"] = $data;
        }
        echo json_encode($res);
    }
}
?>
