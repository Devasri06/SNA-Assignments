# Production Deployment Guide

## Server Requirements

- Ubuntu 20.04+ or similar Linux
- Docker & Docker Compose
- Domain with SSL (Let's Encrypt)
- MongoDB Atlas account

## Deployment Steps

### 1. Server Setup

```bash
# Install Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

# Install Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose
```

### 2. Deploy Application

```bash
# Clone repository
git clone <repository-url> /var/www/taskflow
cd /var/www/taskflow

# Configure environment
cp .env.example .env
nano .env  # Add production credentials

# Start services
cd docker
docker-compose up -d --build
```

### 3. Nginx Reverse Proxy

```nginx
server {
    listen 80;
    server_name taskflow.yourdomain.com;
    
    location / {
        proxy_pass http://localhost:8080;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
}
```

### 4. SSL with Certbot

```bash
sudo certbot --nginx -d taskflow.yourdomain.com
```

## Maintenance

```bash
# View logs
docker-compose logs -f

# Restart services
docker-compose restart

# Update application
git pull
docker-compose up -d --build
```
