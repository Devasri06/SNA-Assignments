<?php
/**
 * TASKFLOW - RabbitMQ Worker
 * 
 * This script runs continuously and processes queued tasks.
 * It sends real emails using PHPMailer with SMTP credentials from .env
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

echo "========================================\n";
echo "  TASKFLOW - Message Queue Worker\n";
echo "========================================\n\n";

// RabbitMQ Configuration
$rabbitHost = getenv('RABBITMQ_HOST') ?: 'rabbitmq';
$rabbitPort = (int)(getenv('RABBITMQ_PORT') ?: 5672);
$rabbitUser = getenv('RABBITMQ_USER') ?: 'admin';
$rabbitPass = getenv('RABBITMQ_PASS') ?: 'admin123';

// SMTP Configuration
$smtpHost = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
$smtpPort = (int)(getenv('SMTP_PORT') ?: 587);
$smtpUser = getenv('SMTP_USER') ?: '';
$smtpPass = getenv('SMTP_PASS') ?: '';
$smtpFromEmail = getenv('SMTP_FROM_EMAIL') ?: $smtpUser;
$smtpFromName = getenv('SMTP_FROM_NAME') ?: 'TaskFlow';

echo "📧 SMTP Configuration:\n";
echo "   Host: $smtpHost:$smtpPort\n";
echo "   User: " . ($smtpUser ? substr($smtpUser, 0, 5) . '***' : 'NOT SET') . "\n";
echo "   From: $smtpFromName <$smtpFromEmail>\n\n";

// Check if SMTP is configured
$smtpEnabled = !empty($smtpUser) && !empty($smtpPass);
if (!$smtpEnabled) {
    echo "⚠️  SMTP not configured - emails will be simulated only\n\n";
}

// Retry connection with backoff
$maxRetries = 10;
$retryDelay = 5;
$connection = null;

for ($i = 1; $i <= $maxRetries; $i++) {
    try {
        echo "[Attempt $i/$maxRetries] Connecting to RabbitMQ at $rabbitHost:$rabbitPort...\n";
        $connection = new AMQPStreamConnection($rabbitHost, $rabbitPort, $rabbitUser, $rabbitPass);
        echo "✅ Connected to RabbitMQ!\n\n";
        break;
    } catch (Exception $e) {
        echo "❌ Connection failed: " . $e->getMessage() . "\n";
        if ($i < $maxRetries) {
            echo "⏳ Retrying in $retryDelay seconds...\n\n";
            sleep($retryDelay);
        } else {
            echo "🛑 Max retries reached. Exiting.\n";
            exit(1);
        }
    }
}

$channel = $connection->channel();

// Declare queues
$channel->queue_declare('welcome_emails', false, true, false, false);
$channel->queue_declare('notifications', false, true, false, false);

echo "📬 Listening for messages on queues: welcome_emails, notifications\n";
echo "   Press CTRL+C to exit\n\n";
echo "----------------------------------------\n";

/**
 * Send email using PHPMailer
 */
function sendEmail($to, $name, $subject, $htmlBody) {
    global $smtpHost, $smtpPort, $smtpUser, $smtpPass, $smtpFromEmail, $smtpFromName, $smtpEnabled;
    
    if (!$smtpEnabled) {
        echo "   📝 [SIMULATED] Email would be sent to: $to\n";
        return true;
    }
    
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = $smtpHost;
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtpUser;
        $mail->Password   = $smtpPass;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $smtpPort;
        
        // Recipients
        $mail->setFrom($smtpFromEmail, $smtpFromName);
        $mail->addAddress($to, $name);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = strip_tags($htmlBody);
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email send failed: " . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Generate Welcome Email HTML
 */
function getWelcomeEmailHtml($name) {
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; }
            .container { max-width: 600px; margin: 0 auto; background: #fff; border-radius: 10px; padding: 30px; }
            .header { text-align: center; padding-bottom: 20px; border-bottom: 2px solid #10b981; }
            .header h1 { color: #10b981; margin: 0; }
            .content { padding: 30px 0; }
            .footer { text-align: center; color: #888; font-size: 12px; padding-top: 20px; border-top: 1px solid #eee; }
            .btn { display: inline-block; background: linear-gradient(135deg, #10b981, #3b82f6); color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>📋 TaskFlow</h1>
            </div>
            <div class="content">
                <h2>Welcome, ' . htmlspecialchars($name) . '!</h2>
                <p>Your TaskFlow account has been created successfully.</p>
                <p>With TaskFlow, you can:</p>
                <ul>
                    <li>📋 Create and organize tasks with priorities</li>
                    <li>🏷️ Set due dates and track deadlines</li>
                    <li>✅ Mark tasks complete with progress tracking</li>
                    <li>📜 View activity logs for accountability</li>
                    <li>📤 Export your data anytime</li>
                </ul>
                <p>Your security is our priority. Your session is protected with FingerprintJS technology.</p>
                <center><a href="' . (getenv('APP_URL') ?: 'http://localhost:8080') . '" class="btn">Open TaskFlow</a></center>
            </div>
            <div class="footer">
                <p>&copy; ' . date('Y') . ' TaskFlow. All rights reserved.</p>
                <p>This email was sent because you registered for TaskFlow.</p>
            </div>
        </div>
    </body>
    </html>';
}

// Callback for welcome emails
$welcomeCallback = function (AMQPMessage $msg) {
    $data = json_decode($msg->body, true);
    
    echo "\n📧 [WELCOME EMAIL] Processing...\n";
    echo "   User ID: " . ($data['user_id'] ?? 'N/A') . "\n";
    echo "   Email: " . ($data['email'] ?? 'N/A') . "\n";
    echo "   Name: " . ($data['name'] ?? 'N/A') . "\n";
    echo "   Queued At: " . ($data['queued_at'] ?? 'N/A') . "\n";
    
    $email = $data['email'] ?? '';
    $name = $data['name'] ?? 'User';
    
    if (!$email) {
        echo "   ❌ No email address provided!\n";
        $msg->ack();
        return;
    }
    
    $subject = "Welcome to TaskFlow, $name!";
    $html = getWelcomeEmailHtml($name);
    
    if (sendEmail($email, $name, $subject, $html)) {
        echo "   ✅ Welcome email sent successfully!\n";
        
        // Log to file
        $logMessage = date('Y-m-d H:i:s') . " - Welcome email sent to: $email\n";
        file_put_contents('/var/log/worker.log', $logMessage, FILE_APPEND);
    } else {
        echo "   ❌ Failed to send email\n";
    }
    
    echo "----------------------------------------\n";
    
    // Acknowledge message
    $msg->ack();
};

// Callback for notifications
$notificationCallback = function (AMQPMessage $msg) {
    $data = json_decode($msg->body, true);
    
    echo "\n🔔 [NOTIFICATION] Processing...\n";
    echo "   User ID: " . ($data['user_id'] ?? 'N/A') . "\n";
    echo "   Type: " . ($data['type'] ?? 'info') . "\n";
    echo "   Message: " . ($data['message'] ?? 'N/A') . "\n";
    
    // In a real app, you could send push notifications, SMS, etc.
    sleep(1);
    
    echo "   ✅ Notification processed!\n";
    echo "----------------------------------------\n";
    
    $msg->ack();
};

// Start consuming
$channel->basic_qos(null, 1, null);
$channel->basic_consume('welcome_emails', '', false, false, false, false, $welcomeCallback);
$channel->basic_consume('notifications', '', false, false, false, false, $notificationCallback);

// Keep listening
while ($channel->is_consuming()) {
    $channel->wait();
}

$channel->close();
$connection->close();
?>
