<?php
class JwtHandler {
    private static $secret = 'SecretKeyForDharshanProject_ChangeMe';

    public static function generateToken($data) {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload = json_encode($data);
        
        $base64UrlHeader = self::urlSafeB64Encode($header);
        $base64UrlPayload = self::urlSafeB64Encode($payload);
        
        $sign = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, self::$secret, true);
        $base64UrlSign = self::urlSafeB64Encode($sign);
        
        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSign;
    }

    public static function validateToken($token) {
        $parts = explode('.', $token);
        if (count($parts) != 3) {
            return null;
        }
        
        $head = $parts[0];
        $body = $parts[1];
        $crypto = $parts[2];

        $validSig = hash_hmac('sha256', $head . "." . $body, self::$secret, true);
        $base64UrlSign = self::urlSafeB64Encode($validSig);

        if ($base64UrlSign === $crypto) {
            $payload = json_decode(self::urlSafeB64Decode($body), true);
            
            // Check expiration
            if (isset($payload['exp']) && $payload['exp'] < time()) {
                return null;
            }
            
            return $payload;
        }
        
        return null;
    }

    private static function urlSafeB64Encode($input) {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($input));
    }

    private static function urlSafeB64Decode($input) {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $padlen = 4 - $remainder;
            $input .= str_repeat('=', $padlen);
        }
        return base64_decode(str_replace(['-', '_'], ['+', '/'], $input));
    }
}
?>
