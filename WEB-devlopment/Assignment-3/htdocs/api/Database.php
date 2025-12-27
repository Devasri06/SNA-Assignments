<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/config.php';  // Load environment variables

use MongoDB\Client;

class Database {
    private $client;
    private $db;

    public function __construct() {
        // Support for MongoDB Atlas SRV connection string
        $mongo_uri = getenv('MONGO_URI');
        $db_name = getenv('MONGO_DB') ?: 'Devasri_Assign_3';

        if (!$mongo_uri) {
            // Fallback to legacy config
            $host = getenv('MONGO_HOST') ?: 'mongo';
            $port = getenv('MONGO_PORT') ?: '27017';
            $user = getenv('MONGO_USER') ?: 'root';
            $pass = getenv('MONGO_PASS') ?: 'rootpassword';
            $auth_source = getenv('MONGO_AUTH_SOURCE') ?: 'admin';

            if ($user && $pass) {
                $mongo_uri = "mongodb://{$user}:{$pass}@{$host}:{$port}/?authSource={$auth_source}";
            } else {
                $mongo_uri = "mongodb://{$host}:{$port}";
            }
        }
        
        try {
            // MongoDB Atlas connection options for proper TLS handling
            $uriOptions = [];
            $driverOptions = [
                'serverApi' => new \MongoDB\Driver\ServerApi((string) \MongoDB\Driver\ServerApi::V1),
            ];
            
            // For MongoDB Atlas (SRV connections), ensure TLS is properly configured
            if (strpos($mongo_uri, 'mongodb+srv://') !== false) {
                // Determine CA certificate path based on OS
                $caCertPath = null;
                $possibleCaPaths = [
                    '/etc/ssl/certs/ca-certificates.crt',  // Debian/Ubuntu
                    '/etc/pki/tls/certs/ca-bundle.crt',    // CentOS/RHEL
                    '/etc/ssl/ca-bundle.pem',              // OpenSUSE
                    '/etc/ssl/cert.pem',                   // Alpine/macOS
                ];
                
                foreach ($possibleCaPaths as $path) {
                    if (file_exists($path)) {
                        $caCertPath = $path;
                        break;
                    }
                }
                
                $uriOptions = [
                    'tls' => true,
                    'retryWrites' => true,
                    'w' => 'majority',
                    'serverSelectionTimeoutMS' => 30000,
                    'connectTimeoutMS' => 30000,
                ];
                
                // Add CA certificate if found
                if ($caCertPath) {
                    $uriOptions['tlsCAFile'] = $caCertPath;
                }
                
                // Allow invalid certificates only if explicitly set (for debugging)
                if (getenv('MONGO_ALLOW_INVALID_CERTS') === 'true') {
                    $uriOptions['tlsAllowInvalidHostnames'] = true;
                    $uriOptions['tlsAllowInvalidCertificates'] = true;
                }
            }
            
            $this->client = new Client($mongo_uri, $uriOptions, $driverOptions);
            $this->db = $this->client->selectDatabase($db_name);
            
            // Test Connection (Ping)
            $this->client->selectDatabase($db_name)->command(['ping' => 1]);
            
        } catch (Exception $e) {
            error_log("MongoDB Connection Error: " . $e->getMessage());
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode([
                "success" => false, 
                "message" => "Database Connection Failed: " . $e->getMessage()
            ]);
            exit;
        }
    }

    public function getCollection($collectionName) {
        return $this->db->selectCollection($collectionName);
    }
}
?>
