# Book Library Management System (Docker + MySQL)

A Full Stack Web Application for managing book records, powered by MySQL and containerized with Docker.

## Features
- **Create**: Add new books with details (Title, Author, Genre, Year).
- **Read**: View a list of all books in the library.
- **Update**: Edit existing book details.
- **Delete**: Remove books from the system.
- **Premium UI**: Modern glassmorphism design with teal/cyan theme.
- **Dockerized**: Easy setup with Docker Compose.
- **Database**: Robust MySQL database.

## Tech Stack
- **Frontend**: HTML5, CSS3, JavaScript (Vanilla) - Located in `public/`
- **Backend**: Node.js, Express.js (v18) - Located in `backend/`
- **Database**: MySQL 8.0
- **Containerization**: Docker & Docker Compose

## Setup & Running

PRO TIP: The easiest way to run this application is using Docker.

### Option 1: Using Docker (Recommended)
Prerequisites: Docker and Docker Compose installed.

1.  **Open Terminal** in the project root.
2.  **Run with Docker Compose**:
    ```bash
    docker-compose up --build
    ```
3.  **Access the App**:
    Open [http://localhost:3000](http://localhost:3000) in your browser.
    
    *Note: The first time you run this, it might take a few seconds for the MySQL database to initialize. The backend will automatically retry connecting until the database is ready.*

### Option 2: Running Locally (Manual Setup)
Prerequisites: Node.js and a running MySQL server.

1.  **Database Setup**:
    - Ensure you have a MySQL server running.
    - Create a database named `library_db`.
    - Update connection details in `backend/server.js` or use Environment Variables.

2.  **Install Backend Dependencies**:
    ```bash
    cd backend
    npm install
    ```

3.  **Run the Server**:
    ```bash
    npm start
    ```
    (Note: The server expects the `public` folder to be in the parent directory `../public`).

4.  **Access the App**:
    Open [http://localhost:3000](http://localhost:3000).

## Project Structure
- `backend/` - Contains the Node.js Express server and logic.
- `public/` - Contains the frontend HTML, CSS, and JS files.
- `docker-compose.yml` - Configuration for Docker services.
