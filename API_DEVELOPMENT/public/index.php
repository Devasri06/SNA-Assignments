<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: OPTIONS,GET,POST,PUT,DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER["REQUEST_METHOD"] == "OPTIONS") {
    http_response_code(200);
    exit();
}

require_once '../config/Database.php';
require_once '../src/Model/User.php';
require_once '../src/Controller/AuthController.php';
require_once '../src/Controller/UserController.php';

// Database Connection
$database = new Database();
$db = $database->getConnection();

// Parse URI
$uri_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri_segments = explode('/', $uri_path);

// Filter out empty segments to handle leading/trailing slashes reliably
// e.g. /api/users/1 -> ['', 'api', 'users', '1'] -> ['api', 'users', '1']
$uri = array_values(array_filter($uri_segments, function($v) { return $v !== ''; }));

// Basic Routing Logic
// Expected URLs:
// /auth/signup
// /auth/login
// /api/users
// /api/users/{id}

// Note: If running in a subdirectory (e.g. localhost/project/public/api/users), this might break.
// We assume logic based on the LAST segments matching our pattern or we assume root deployment.
// To be safe for "php -S localhost:8000 -t public", the root is /.

$resource = isset($uri[0]) ? $uri[0] : null;
$requestMethod = $_SERVER["REQUEST_METHOD"];

// Get input data
$inputJSON = file_get_contents('php://input');
$data = json_decode($inputJSON, true);

if ($resource === 'auth') {
    $action = isset($uri[1]) ? $uri[1] : null;
    $controller = new AuthController($db);
    
    if ($requestMethod == 'POST' && $action == 'signup') {
        $controller->signup($data);
    } elseif ($requestMethod == 'POST' && $action == 'login') {
        $controller->login($data);
    } else {
        header("HTTP/1.1 404 Not Found");
        echo json_encode(["success" => false, "message" => "Auth endpoint not found"]);
    }
} elseif ($resource === 'api') {
    $entity = isset($uri[1]) ? $uri[1] : null;
    
    if ($entity === 'users') {
        $controller = new UserController($db);
        $id = isset($uri[2]) ? $uri[2] : null;

        if ($requestMethod == 'GET') {
            if ($id) {
                $controller->getOne($id);
            } else {
                $controller->getAll();
            }
        } elseif ($requestMethod == 'POST') {
            $controller->create($data);
        } elseif ($requestMethod == 'PUT') {
            if ($id) {
                $controller->update($id, $data);
            } else {
                 header("HTTP/1.1 400 Bad Request");
                 echo json_encode(["success" => false, "message" => "ID is required for update"]);
            }
        } elseif ($requestMethod == 'DELETE') {
            if ($id) {
                $controller->delete($id);
            } else {
                 header("HTTP/1.1 400 Bad Request");
                 echo json_encode(["success" => false, "message" => "ID is required for delete"]);
            }
        } else {
            header("HTTP/1.1 405 Method Not Allowed");
            echo json_encode(["success" => false, "message" => "Method not allowed"]);
        }
    } else {
        header("HTTP/1.1 404 Not Found");
        echo json_encode(["success" => false, "message" => "Entity not found"]);
    }
} else {
    // Welcome or 404
    if (empty($resource)) {
        echo json_encode(["success" => true, "message" => "Welcome to User Management API"]);
    } else {
        header("HTTP/1.1 404 Not Found");
        echo json_encode(["success" => false, "message" => "Endpoint not found"]);
    }
}
?>
