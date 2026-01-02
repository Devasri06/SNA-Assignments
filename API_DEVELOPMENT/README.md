# User Management API

A RESTful API for managing users, built with raw PHP and MySQL.

## Tech Stack
- **Language**: PHP
- **Database**: MySQL
- **Architecture**: Simple MVC-like structure
- **Auth**: JWT (Custom implementation, no external dependencies)

## Setup Instructions

### 1. Database Setup
1. Create a MySQL database named `user_api_db`.
   ```sql
   CREATE DATABASE user_api_db;
   ```
2. Import the schema to create the `users` table.
   You can run the SQL command found in `sql/schema.sql`:
   ```bash
   mysql -u root -p user_api_db < sql/schema.sql
   ```
   Or manually copy-paste the SQL into your database client.

### 2. Configuration
Open `config/Database.php` and update the database credentials if necessary:
```php
private $host = "localhost";
private $db_name = "user_api_db";
private $username = "root";
private $password = "";
```

### 3. Run the API
Serve the application using the PHP built-in server. Run this command from the project root (`User_Management_API` folder):

```bash
php -S localhost:8000 -t public
```

The API will be available at `http://localhost:8000`.

## API Documentation

### Authentication

#### 1. Signup
- **Endpoint**: `POST /auth/signup`
- **Body**:
  ```json
  {
      "username": "johndoe",
      "email": "john@example.com",
      "password": "secretpassword"
  }
  ```
- **Response**: `201 Created`

#### 2. Login
- **Endpoint**: `POST /auth/login`
- **Body**:
  ```json
  {
      "email": "john@example.com",
      "password": "secretpassword"
  }
  ```
- **Response**: `200 OK`
  ```json
  {
      "success": true,
      "message": "Login successful.",
      "data": {
          "token": "eyJ0eXAiOiJKV1QiLCF...",
          "user": { ... }
      }
  }
  ```
  **Note**: Copy the `token` from the response. You must include it in the `Authorization` header for all requests below:
  `Authorization: Bearer <your_token>`

### User Management (Protected)

#### 1. Get All Users
- **Endpoint**: `GET /api/users`
- **Headers**: `Authorization: Bearer <token>`
- **Response**: `200 OK` (List of users)

#### 2. Get Single User
- **Endpoint**: `GET /api/users/{id}`
- **Headers**: `Authorization: Bearer <token>`
- **Response**: `200 OK`

#### 3. Create User (Admin/Manual)
- **Endpoint**: `POST /api/users`
- **Headers**: `Authorization: Bearer <token>`
- **Body**: Same as Signup
- **Response**: `201 Created`

#### 4. Update User
- **Endpoint**: `PUT /api/users/{id}`
- **Headers**: `Authorization: Bearer <token>`
- **Body** (Partial updates allowed):
  ```json
  {
      "username": "john_updated"
  }
  ```
- **Response**: `200 OK`

#### 5. Delete User
- **Endpoint**: `DELETE /api/users/{id}`
- **Headers**: `Authorization: Bearer <token>`
- **Response**: `200 OK`

## Project Structure
- `config/`: Database connection.
- `src/`: Core logic (Controllers, Models, Middleware).
- `public/`: Entry point (`index.php`).
- `sql/`: Database schema.
