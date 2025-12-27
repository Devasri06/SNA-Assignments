<?php
/**
 * Production Configuration Template
 * 
 * SETUP INSTRUCTIONS:
 * 1. Copy this file to config.php: cp config.example.php config.php
 * 2. Edit config.php with your actual credentials
 * 3. DO NOT commit config.php to git (it's in .gitignore)
 */

// =============================================
// MongoDB Atlas Configuration
// =============================================
putenv('MONGO_URI=mongodb+srv://YOUR_USERNAME:YOUR_PASSWORD@cluster.mongodb.net/?retryWrites=true&w=majority');
putenv('MONGO_DB=taskflow_db');

// Set to 'true' only if you have TLS certificate issues
putenv('MONGO_ALLOW_INVALID_CERTS=false');

// =============================================
// RabbitMQ Configuration (Optional)
// =============================================
putenv('RABBITMQ_HOST=localhost');
putenv('RABBITMQ_PORT=5672');
putenv('RABBITMQ_USER=admin');
putenv('RABBITMQ_PASS=admin123');

// =============================================
// SMTP Email Configuration
// =============================================
// For Gmail: Use App Password from https://myaccount.google.com/apppasswords
putenv('SMTP_HOST=smtp.gmail.com');
putenv('SMTP_PORT=587');
putenv('SMTP_USER=your-email@gmail.com');
putenv('SMTP_PASS=your-app-password');
putenv('SMTP_FROM_EMAIL=your-email@gmail.com');
putenv('SMTP_FROM_NAME=TaskFlow');

// =============================================
// Application Settings
// =============================================
putenv('APP_NAME=TaskFlow');
putenv('APP_URL=https://yourdomain.com');
