<?php
/**
 * TaskFlow - Configuration File
 * This file loads environment variables for local development
 */

// =============================================
// MongoDB Atlas Configuration
// =============================================
putenv('MONGO_URI=mongodb+srv://dharshankumarj:dharshankumarj@cluster0.j0wgb.mongodb.net/?retryWrites=true&w=majority&appName=Cluster0');
putenv('MONGO_DB=Devasri_Assign_3');
putenv('MONGO_ALLOW_INVALID_CERTS=false');

// =============================================
// RabbitMQ Configuration
// =============================================
putenv('RABBITMQ_HOST=rabbitmq');
putenv('RABBITMQ_PORT=5672');
putenv('RABBITMQ_USER=admin');
putenv('RABBITMQ_PASS=admin123');

// =============================================
// SMTP Email Configuration
// =============================================
putenv('SMTP_HOST=smtp.gmail.com');
putenv('SMTP_PORT=587');
putenv('SMTP_USER=dharshankumarj2006@gmail.com');
putenv('SMTP_PASS=edrv tslh ysun pfsv');
putenv('SMTP_FROM_EMAIL=dharshankumarj2006@gmail.com');
putenv('SMTP_FROM_NAME=TaskFlow');

// =============================================
// Application Settings
// =============================================
putenv('APP_NAME=TaskFlow');
putenv('APP_URL=http://localhost:8080');
