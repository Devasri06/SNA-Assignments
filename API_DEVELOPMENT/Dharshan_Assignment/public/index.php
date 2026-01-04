<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET,POST,PUT,DELETE,OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER["REQUEST_METHOD"] == "OPTIONS") {
    http_response_code(200);
    exit();
}

require_once '../config/DatabaseConnection.php';
require_once '../app/Models/UserModel.php';
require_once '../app/Controllers/AuthHandler.php';
require_once '../app/Controllers/UserHandler.php';

// Init DB
$dbConn = new DatabaseConnection();
$pdo = $dbConn->connect();

// Router
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$pathParts = explode('/', $requestUri);
$segments = array_values(array_filter($pathParts, fn($p) => $p !== ''));

$module = $segments[0] ?? null;
$method = $_SERVER["REQUEST_METHOD"];
$input = json_decode(file_get_contents('php://input'), true);

if ($module === 'auth') {
    $op = $segments[1] ?? null;
    $auth = new AuthHandler($pdo);

    if ($method === 'POST') {
        if ($op === 'signup') {
            $auth->handleSignup($input);
        } elseif ($op === 'login') {
            $auth->handleLogin($input);
        } else {
            sendFourOhFour("Auth action unknown");
        }
    } else {
        sendMethodNotAllowed();
    }
} elseif ($module === 'api') {
    $action = $segments[1] ?? null;
    $param = $segments[2] ?? null;

    $users = new UserHandler($pdo);

    switch ($action) {
        case 'fetch-all-accounts':
            if ($method === 'GET') $users->index();
            else sendMethodNotAllowed();
            break;
        
        case 'retrieve-account':
            if ($method === 'GET') {
                if ($param) $users->show($param);
                else sendBadRequest("ID missing");
            } else {
                sendMethodNotAllowed();
            }
            break;

        case 'register-account':
            if ($method === 'POST') $users->store($input);
            else sendMethodNotAllowed();
            break;

        case 'modify-account':
            if ($method === 'PUT') {
                if ($param) $users->update($param, $input);
                else sendBadRequest("ID missing");
            } else {
                sendMethodNotAllowed();
            }
            break;

        case 'remove-account':
            if ($method === 'DELETE') {
                if ($param) $users->destroy($param);
                else sendBadRequest("ID missing");
            } else {
                sendMethodNotAllowed();
            }
            break;

        default:
            sendFourOhFour("API route not found");
            break;
    }

} else {
    if (!$module) {
        echo json_encode(["success" => true, "message" => "Dharshan's API is running."]);
    } else {
        sendFourOhFour("Endpoint not found.");
    }
}

function sendMethodNotAllowed() {
    header("HTTP/1.1 405 Method Not Allowed");
    echo json_encode(["success" => false, "message" => "Method not permitted"]);
}

function sendBadRequest($m) {
    header("HTTP/1.1 400 Bad Request");
    echo json_encode(["success" => false, "message" => $m]);
}

function sendFourOhFour($m) {
    header("HTTP/1.1 404 Not Found");
    echo json_encode(["success" => false, "message" => $m]);
}
?>
