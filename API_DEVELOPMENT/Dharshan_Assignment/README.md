# User API Project

This is a RESTful API project for managing user accounts.

## Stack
- PHP
- MySQL
- JWT Authentication

## Setup
1. Import `sql/schema.sql` into a database named `user_api_db` (or configure in `config/DatabaseConnection.php`).
2. Run database server.
3. Start PHP server:
   ```bash
   cd public
   php -S localhost:8000
   ```

## Endpoints

### Auth
- `POST /auth/signup` - Register
- `POST /auth/login` - Login

### Users (Protected)
- `GET /api/fetch-all-accounts`
- `GET /api/retrieve-account/{id}`
- `POST /api/register-account`
- `PUT /api/modify-account/{id}`
- `DELETE /api/remove-account/{id}`
