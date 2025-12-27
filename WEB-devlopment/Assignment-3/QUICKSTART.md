# Quick Start Guide

## Option 1: Docker (Recommended)

```bash
# 1. Copy environment file
cp .env.example .env

# 2. Edit .env with your MongoDB Atlas and SMTP credentials
nano .env

# 3. Start the application
cd docker
docker-compose up -d --build

# 4. Access the app
# Web: http://localhost:8080
# RabbitMQ: http://localhost:15672 (admin/admin123)
```

## Option 2: Local Development

```bash
# 1. Install PHP dependencies
cd htdocs
composer install

# 2. Configure credentials
cp api/config.example.php api/config.php
nano api/config.php

# 3. Start PHP server
php -S 0.0.0.0:8080

# 4. Access at http://localhost:8080
```

## Troubleshooting

### MongoDB Connection Issues
- Check your MongoDB Atlas whitelist (add 0.0.0.0/0 for development)
- Verify credentials in .env file
- Set `MONGO_ALLOW_INVALID_CERTS=true` for debugging

### RabbitMQ Issues
- Wait 30 seconds for RabbitMQ to start
- Check logs: `docker-compose logs rabbitmq`

### Email Not Sending
- For Gmail, use App Password (not regular password)
- Enable 2FA on Google Account first
