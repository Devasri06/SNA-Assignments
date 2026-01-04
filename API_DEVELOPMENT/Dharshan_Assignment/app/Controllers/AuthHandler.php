<?php
require_once __DIR__ . '/../Helpers/JwtHandler.php';

class AuthHandler {
    private $db;
    private $userModel;

    public function __construct($db) {
        $this->db = $db;
        $this->userModel = new UserModel($db);
    }

    public function handleSignup($input) {
        if (empty($input['username']) || empty($input['email']) || empty($input['password'])) {
            $this->sendResponse(false, 400, "Incomplete data.");
            return;
        }

        if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            $this->sendResponse(false, 400, "Bad email format.");
            return;
        }

        if ($this->userModel->checkExistence('email', $input['email'])) {
            $this->sendResponse(false, 400, "Email taken.");
            return;
        }
        
        if ($this->userModel->checkExistence('username', $input['username'])) {
            $this->sendResponse(false, 400, "Username taken.");
            return;
        }

        $this->userModel->username = $input['username'];
        $this->userModel->email = $input['email'];
        $this->userModel->password = password_hash($input['password'], PASSWORD_BCRYPT);
        
        if (isset($input['full_name'])) $this->userModel->full_name = $input['full_name'];
        if (isset($input['phone'])) $this->userModel->phone = $input['phone'];

        if ($this->userModel->registerUser()) {
            $res = [
                "id" => $this->userModel->id,
                "username" => $this->userModel->username,
                "email" => $this->userModel->email,
                "full_name" => $this->userModel->full_name,
                "phone" => $this->userModel->phone
            ];
            $this->sendResponse(true, 201, "Registration successful.", $res);
        } else {
            $this->sendResponse(false, 500, "Server error during registration.");
        }
    }

    public function handleLogin($input) {
        if (empty($input['password']) || (empty($input['email']) && empty($input['username']))) {
            $this->sendResponse(false, 400, "Credentials missing.");
            return;
        }

        $col = isset($input['email']) ? 'email' : 'username';
        $val = $input[$col];

        $found = $this->userModel->checkExistence($col, $val);

        if ($found && password_verify($input['password'], $this->userModel->password)) {
            $tokenData = [
                "iss" => "localhost",
                "iat" => time(),
                "exp" => time() + 3600,
                "data" => [
                    "id" => $this->userModel->id,
                    "username" => $this->userModel->username,
                    "email" => $this->userModel->email
                ]
            ];
            
            $token = JwtHandler::generateToken($tokenData);

            $this->sendResponse(true, 200, "Logged in.", [
                "token" => $token,
                "user" => [
                    "id" => $this->userModel->id,
                    "username" => $this->userModel->username,
                    "email" => $this->userModel->email
                ]
            ]);
        } else {
            $this->sendResponse(false, 401, "Authentication failed.");
        }
    }

    private function sendResponse($status, $code, $msg, $data = null) {
        http_response_code($code);
        $response = [
            "success" => $status,
            "message" => $msg
        ];
        if ($data) {
            $response["data"] = $data;
        }
        echo json_encode($response);
    }
}
?>
