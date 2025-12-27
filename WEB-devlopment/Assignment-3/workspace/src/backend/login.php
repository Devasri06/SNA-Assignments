<?php
header("Content-Type: application/json");
session_start();
require_once 'Database.php';
require_once 'User.php';

$input = json_decode(file_get_contents("php://input"), true);

try {
    $db = new Database();
    $user = new User($db);
    
    $userData = $user->login($input['email'], $input['password']);

    if ($userData) {
        // Security: Avoid Session Hijacking using basic fingerprinting (IP/UserAgent)
        $_SESSION['user_id'] = (string)$userData['_id'];
        $_SESSION['ip_agent'] = md5($_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']);
        
        echo json_encode(["success" => true, "user" => ["name" => $userData['name']]]);
    } else {
        echo json_encode(["success" => false, "message" => "Invalid credentials"]);
    }

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
