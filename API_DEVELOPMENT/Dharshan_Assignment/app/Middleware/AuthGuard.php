<?php
require_once __DIR__ . '/../Helpers/JwtHandler.php';

class AuthGuard {
    public static function verifyRequests() {
        $token = null;
        if (isset($_SERVER['Authorization'])) {
            $token = trim($_SERVER["Authorization"]);
        } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $token = trim($_SERVER["HTTP_AUTHORIZATION"]);
        } elseif (function_exists('apache_request_headers')) {
            $hdrs = apache_request_headers();
            $hdrs = array_change_key_case($hdrs, CASE_LOWER);
            if (isset($hdrs['authorization'])) {
                $token = trim($hdrs['authorization']);
            }
        }
        
        if (!empty($token)) {
            if (preg_match('/Bearer\s(\S+)/', $token, $m)) {
                $jwt = $m[1];
                $data = JwtHandler::validateToken($jwt);
                if ($data) {
                    return $data;
                }
            }
        }
        return false;
    }
}
?>
