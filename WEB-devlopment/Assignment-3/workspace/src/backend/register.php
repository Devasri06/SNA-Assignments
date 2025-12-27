<?php
header("Content-Type: application/json");
require_once 'Database.php';
require_once 'User.php';
require_once 'QueueManager.php';

// Defensive: Limit request methods
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method Not Allowed"]);
    exit;
}

$input = json_decode(file_get_contents("php://input"), true);

// Defensive: Validate required fields
if (!isset($input['name']) || !isset($input['email']) || !isset($input['password'])) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Missing fields"]);
    exit;
}

// Defensive: Validate email format
if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Invalid email format"]);
    exit;
}

// Defensive: Validate password strength
if (strlen($input['password']) < 6) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Password must be at least 6 characters"]);
    exit;
}

// Defensive: Validate name length
if (strlen($input['name']) < 2 || strlen($input['name']) > 100) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Name must be 2-100 characters"]);
    exit;
}

try {
    $db = new Database();
    $user = new User($db);
    
    $id = $user->register($input['name'], $input['email'], $input['password']);

    // RabbitMQ: Queue welcome email
    $queue = new QueueManager();
    if ($queue->isConnected()) {
        $queue->queueWelcomeEmail((string)$id, $input['email'], $input['name']);
    }

    echo json_encode(["success" => true, "id" => (string)$id]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
