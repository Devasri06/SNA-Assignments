<?php
header("Content-Type: application/json");
session_start();
require_once 'Database.php';
require_once 'Task.php';

// Defensive: Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

// Defensive: Validate session fingerprint
if (isset($_SESSION['ip_agent'])) {
    $currentFingerprint = md5($_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']);
    if ($_SESSION['ip_agent'] !== $currentFingerprint) {
        session_destroy();
        http_response_code(401);
        echo json_encode(["success" => false, "message" => "Session hijacking detected"]);
        exit;
    }
}

// Auto-lock: Check session timeout (30 minutes)
$sessionTimeout = 30 * 60; // 30 minutes
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $sessionTimeout)) {
    session_destroy();
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Session expired. Please login again."]);
    exit;
}
$_SESSION['last_activity'] = time();

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents("php://input"), true);

try {
    $db = new Database();
    $task = new Task($db, $_SESSION['user_id']);

    switch ($method) {
        case 'GET':
            $action = $_GET['action'] ?? 'list';
            
            switch ($action) {
                case 'stats':
                    echo json_encode(["success" => true, "data" => $task->getStats()]);
                    break;
                    
                case 'activity':
                    echo json_encode(["success" => true, "data" => $task->getActivity()]);
                    break;
                    
                case 'export':
                    echo json_encode(["success" => true, "data" => $task->exportTasks()]);
                    break;
                    
                case 'single':
                    $taskId = $_GET['id'] ?? null;
                    if (!$taskId) throw new Exception("Task ID required");
                    echo json_encode(["success" => true, "data" => $task->getById($taskId)]);
                    break;

                case 'toggle':
                    $taskId = $_GET['id'] ?? null;
                    if (!$taskId) throw new Exception("Task ID required");
                    $newStatus = $task->toggleStatus($taskId);
                    echo json_encode(["success" => true, "status" => $newStatus]);
                    break;
                    
                default:
                    // List tasks with optional search and filters
                    $search = $_GET['search'] ?? null;
                    $priority = $_GET['priority'] ?? null;
                    $status = $_GET['status'] ?? null;
                    echo json_encode(["success" => true, "data" => $task->getAll($search, $priority, $status)]);
            }
            break;

        case 'POST':
            if (empty($input['title'])) {
                throw new Exception("Title is required");
            }
            $id = $task->create(
                $input['title'], 
                $input['description'] ?? '', 
                $input['priority'] ?? 'medium',
                $input['due_date'] ?? null
            );
            echo json_encode(["success" => true, "id" => (string)$id]);
            break;

        case 'PUT':
            if (empty($input['id']) || empty($input['title'])) {
                throw new Exception("ID and Title are required");
            }
            $task->update(
                $input['id'], 
                $input['title'], 
                $input['description'] ?? '',
                $input['priority'] ?? null,
                $input['status'] ?? null,
                $input['due_date'] ?? null
            );
            echo json_encode(["success" => true, "message" => "Task updated"]);
            break;

        case 'DELETE':
            $taskId = $_GET['id'] ?? ($input['id'] ?? null);
            if (empty($taskId)) {
                throw new Exception("Task ID is required");
            }
            $task->delete($taskId);
            echo json_encode(["success" => true, "message" => "Task deleted"]);
            break;

        default:
            http_response_code(405);
            echo json_encode(["success" => false, "message" => "Method not allowed"]);
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
