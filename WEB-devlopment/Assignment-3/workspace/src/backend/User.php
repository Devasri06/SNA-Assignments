<?php
class User {
    private $collection;

    public function __construct($db) {
        $this->collection = $db->getCollection('users');
    }

    public function register($name, $email, $password) {
        // Defensive: Basic validation
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new Exception("Invalid email format");
        }

        // Check if user exists
        $existing = $this->collection->findOne(['email' => $email]);
        if ($existing) {
            throw new Exception("User already exists");
        }

        // Secure password hashing
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        $result = $this->collection->insertOne([
            'name' => htmlspecialchars($name), // Defensive: XSS prevention
            'email' => $email,
            'password' => $hashedPassword,
            'created_at' => new MongoDB\BSON\UTCDateTime()
        ]);

        return $result->getInsertedId();
    }

    public function login($email, $password) {
        $user = $this->collection->findOne(['email' => $email]);

        if (!$user || !password_verify($password, $user['password'])) {
            return false;
        }

        return $user;
    }
}
?>
