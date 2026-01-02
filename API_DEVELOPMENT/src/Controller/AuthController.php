<?php
require_once __DIR__ . '/../SimpleJWT.php';

class AuthController {
    private $db;
    private $user;

    public function __construct($db) {
        $this->db = $db;
        $this->user = new User($db);
    }

    public function signup($data) {
        if (!isset($data['username']) || !isset($data['email']) || !isset($data['password'])) {
            $this->response(false, 400, "Please provide username, email and password.");
            return;
        }

        // Check if email already exists
        if ($this->user->fieldExists('email', $data['email'])) {
            $this->response(false, 400, "Email already exists.");
            return;
        }
        
        // Check if username already exists
        if ($this->user->fieldExists('username', $data['username'])) {
            $this->response(false, 400, "Username already exists.");
            return;
        }

        $this->user->username = $data['username'];
        $this->user->email = $data['email'];
        $this->user->password = password_hash($data['password'], PASSWORD_BCRYPT);

        if ($this->user->create()) {
            $payload = [
                "id" => $this->user->id,
                "username" => $this->user->username,
                "email" => $this->user->email
            ];
            $this->response(true, 201, "User registered successfully.", $payload);
        } else {
            $this->response(false, 500, "Unable to register user.");
        }
    }

    public function login($data) {
        if (!isset($data['password']) || (!isset($data['email']) && !isset($data['username']))) {
            $this->response(false, 400, "Please provide email/username and password.");
            return;
        }

        $loginField = isset($data['email']) ? 'email' : 'username';
        $loginValue = $data[$loginField];

        $userExists = $this->user->fieldExists($loginField, $loginValue);

        if ($userExists && password_verify($data['password'], $this->user->password)) {
            $tokenPayload = [
                "iss" => "localhost",
                "iat" => time(),
                "exp" => time() + (60 * 60), // 1 hour expiration
                "data" => [
                    "id" => $this->user->id,
                    "username" => $this->user->username,
                    "email" => $this->user->email
                ]
            ];
            
            $jwt = SimpleJWT::encode($tokenPayload);

            $this->response(true, 200, "Login successful.", [
                "token" => $jwt,
                "user" => [
                    "id" => $this->user->id,
                    "username" => $this->user->username,
                    "email" => $this->user->email
                ]
            ]);
        } else {
            $this->response(false, 401, "Invalid credentials.");
        }
    }

    private function response($success, $code, $message, $data = null) {
        http_response_code($code);
        $res = [
            "success" => $success,
            "message" => $message
        ];
        if (!$success) {
            $res["error"] = $code; // Just using code as error type for simplicity
        }
        if ($data !== null) {
            $res["data"] = $data;
        }
        echo json_encode($res);
    }
}
?>
