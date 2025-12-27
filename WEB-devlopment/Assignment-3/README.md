# 📋 TaskFlow - Smart Task Manager Application

> **Live Demo:** [https://taskflow.selfmade.one/](https://taskflow.selfmade.one/)

A Full Stack Web Application developed for **SNA Assignment 3** - A smart task management system with user authentication, MongoDB integration, and asynchronous task processing.

---

## 📋 Table of Contents

- [Live Demo](#live-demo)
- [Features](#features)
- [Technical Stack](#technical-stack)
- [Architecture](#architecture)
- [Setup & Installation](#setup--installation)
  - [Docker (Recommended)](#method-1-docker-recommended)
  - [Without Docker](#method-2-without-docker)
- [API Documentation](#api-documentation)
- [Security Features](#security-features)
- [CI/CD Pipeline](#cicd-pipeline)
- [Assignment Requirements Checklist](#assignment-requirements-checklist)

---

## 🌐 Live Demo

**Production URL:** [https://taskflow.selfmade.one/](https://taskflow.selfmade.one/)

---

## ✨ Features

| Feature | Description |
|---------|-------------|
| 🔐 **Secure Authentication** | User registration & login with session management |
| 📋 **CRUD Tasks** | Create, Read, Update, Delete personal tasks |
| 🛡️ **Session Fingerprinting** | FingerprintJS integration to prevent session hijacking |
| 📧 **Email Notifications** | Welcome emails via RabbitMQ async queue |
| 🔄 **Daily Maintenance** | Automated cron job for cleanup tasks |
| 📱 **Responsive Design** | Mobile-friendly Bootstrap 5 interface |
| 🏷️ **Priority Tags** | Organize tasks by priority (High, Medium, Low) |
| ✅ **Task Completion** | Mark tasks as complete with progress tracking |

---

## 🛠️ Technical Stack

| Category | Technologies |
|----------|-------------|
| **Frontend** | HTML5, CSS3, JavaScript, Bootstrap 5 |
| **Backend** | PHP 8.2 (OOP), RESTful APIs |
| **Database** | MongoDB Atlas (NoSQL) |
| **Containerization** | Docker, Docker Compose |
| **Message Queue** | RabbitMQ |
| **Task Runner** | Grunt.js (CSS/JS minification, file copying) |
| **CI/CD** | GitLab CI/CD |
| **Security** | FingerprintJS, Input Sanitization, Session Management |

---

## 🏗️ Architecture

```
Assignment-3/
├── workspace/                    # Source code & build configuration
│   ├── src/
│   │   ├── backend/              # PHP source files
│   │   ├── css/                  # CSS source
│   │   └── js/                   # JS source
│   ├── Gruntfile.js              # Grunt task configuration
│   └── package.json              # Node.js dependencies
│
├── htdocs/                       # Production-ready files (served by Apache)
│   ├── api/                      # Backend PHP API
│   │   ├── Database.php          # MongoDB connection handler
│   │   ├── User.php              # User model & authentication
│   │   ├── Task.php              # Tasks CRUD operations
│   │   ├── login.php             # Login API endpoint
│   │   ├── register.php          # Registration API endpoint
│   │   └── tasks.php             # Tasks API endpoint
│   ├── js/                       # Minified JavaScript
│   ├── css/                      # Minified CSS
│   ├── vendor/                   # Composer dependencies
│   └── index.html                # Main entry point
│
├── docker/                       # Docker configuration
│   ├── docker-compose.yml        # Service orchestration
│   ├── Dockerfile                # Web container
│   ├── Dockerfile.worker         # RabbitMQ worker container
│   ├── entrypoint.sh             # Container startup script
│   └── daily_script.sh           # Daily cron job script
│
├── .gitlab-ci.yml                # CI/CD pipeline configuration
├── .env.example                  # Environment variables template
├── README.md                     # This documentation
├── QUICKSTART.md                 # Quick setup guide
└── PRODUCTION_GUIDE.md           # Production deployment guide
```

---

## 🚀 Setup & Installation

### Prerequisites

- Git
- Docker & Docker Compose (for Docker method)
- PHP 8.2+ with MongoDB extension (for non-Docker method)
- Composer
- MongoDB Atlas account (free tier)

---

### Method 1: Docker (Recommended)

```bash
# 1. Clone the repository
git clone <repository-url>
cd Assignment-3

# 2. Create environment file
cp .env.example .env

# 3. Edit .env with your credentials
# - MONGO_URI: Your MongoDB Atlas connection string
# - MONGO_DB: Database name
# - SMTP settings: For email functionality

# 4. Build and start containers
cd docker
docker-compose up -d --build

# 5. Access the application
# Web App: http://localhost:8080
# RabbitMQ Dashboard: http://localhost:15672
```

---

### Method 2: Without Docker

```bash
# 1. Clone the repository
git clone <repository-url>
cd Assignment-3/htdocs

# 2. Install PHP dependencies
composer install

# 3. Configure credentials
cp api/config.example.php api/config.php
# Edit api/config.php with your MongoDB URI

# 4. Start PHP development server
php -S 0.0.0.0:8080

# 5. Access at http://localhost:8080
```

---

## 📡 API Documentation

### Authentication Endpoints

| Method | Endpoint | Description | Request Body |
|--------|----------|-------------|--------------|
| POST | `/api/register.php` | Register new user | `{email, password, name}` |
| POST | `/api/login.php` | User login | `{email, password, fingerprint}` |
| POST | `/api/logout.php` | User logout | - |

### Tasks Endpoints

| Method | Endpoint | Description | Request Body |
|--------|----------|-------------|--------------|
| GET | `/api/tasks.php` | Get all user tasks | - |
| POST | `/api/tasks.php` | Create new task | `{title, description, priority, due_date}` |
| PUT | `/api/tasks.php` | Update task | `{id, title, description, priority, status}` |
| DELETE | `/api/tasks.php` | Delete task | `{id}` |

---

## 🔐 Security Features

| Feature | Implementation |
|---------|----------------|
| **Session Fingerprinting** | FingerprintJS generates unique browser fingerprint |
| **Password Hashing** | `password_hash()` with bcrypt algorithm |
| **Input Sanitization** | `htmlspecialchars()` on all user inputs |
| **Session Management** | Secure session handling with timeout |
| **CSRF Protection** | Session-based token validation |

---

## ✅ Assignment Requirements Checklist

| Requirement | Status | Implementation |
|-------------|--------|----------------|
| **Frontend** | ✅ | HTML, CSS, JavaScript, Bootstrap 5 |
| **Backend** | ✅ | PHP 8.2 with OOP, Session Management |
| **Database** | ✅ | MongoDB Atlas |
| **RESTful APIs** | ✅ | Registration, Login, Tasks CRUD |
| **Docker** | ✅ | Dockerfile, docker-compose.yml |
| **CI/CD** | ✅ | .gitlab-ci.yml with auto-deploy |
| **Cron Jobs** | ✅ | Daily maintenance script |
| **Grunt** | ✅ | CSS/JS minification, file copying |
| **RabbitMQ** | ✅ | Async email queue processing |
| **Defensive Programming** | ✅ | Input validation, error handling |
| **Security** | ✅ | FingerprintJS, secure sessions |

---

## 👨‍💻 Author

**Devasri**

- Live Demo: [https://taskflow.selfmade.one/](https://taskflow.selfmade.one/)
- Repository: GitLab

---

*Built with ❤️ for SNA Assignment 3*
