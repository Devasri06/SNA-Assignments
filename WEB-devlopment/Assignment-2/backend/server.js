const express = require('express');
const mysql = require('mysql2');
const bodyParser = require('body-parser');
const cors = require('cors');
const path = require('path');

const app = express();
const PORT = 3000;

// Middleware
app.use(cors());
app.use(bodyParser.json());
// Serve static files from ../public (since backend is in a subdir but public is in root)
app.use(express.static(path.join(__dirname, '../public')));

// Database Configuration
const dbConfig = {
    host: process.env.DB_HOST || 'mysql-db',
    user: process.env.DB_USER || 'root',
    password: process.env.DB_PASSWORD || 'rootpassword',
    database: process.env.DB_NAME || 'library_db',
    waitForConnections: true,
    connectionLimit: 10,
    queueLimit: 0
};

let pool;

// Connect to Database with Retry Logic
const connectWithRetry = () => {
    console.log('Attempting to connect to MySQL...');
    pool = mysql.createPool(dbConfig);

    pool.getConnection((err, connection) => {
        if (err) {
            console.error('MySQL Connection Error:', err.code);
            console.log('Retrying in 5 seconds...');
            setTimeout(connectWithRetry, 5000);
        } else {
            console.log('Connected to MySQL Database.');
            initializeTable();
            connection.release();
        }
    });
};

function initializeTable() {
    const tableQuery = `
        CREATE TABLE IF NOT EXISTS books (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            author VARCHAR(255) NOT NULL,
            genre VARCHAR(255) NOT NULL,
            year INT NOT NULL
        )
    `;
    pool.query(tableQuery, (err) => {
        if (err) {
            console.error("Table creation error:", err);
        } else {
            console.log("Books table ready.");
        }
    });
}

connectWithRetry();

// Routes

// Get all books
app.get('/api/books', (req, res) => {
    if (!pool) return res.status(500).json({ error: "Database not connected" });

    pool.query("SELECT * FROM books ORDER BY id DESC", (err, rows) => {
        if (err) {
            res.status(400).json({ "error": err.message });
            return;
        }
        res.json({
            "message": "success",
            "data": rows
        });
    });
});

// Create a new book
app.post('/api/books', (req, res) => {
    const { title, author, genre, year } = req.body;
    const sql = "INSERT INTO books (title, author, genre, year) VALUES (?,?,?,?)";

    pool.query(sql, [title, author, genre, year], function (err, result) {
        if (err) {
            res.status(400).json({ "error": err.message });
            return;
        }
        res.json({
            "message": "success",
            "data": { id: result.insertId, title, author, genre, year }
        });
    });
});

// Update a book
app.put('/api/books/:id', (req, res) => {
    const { title, author, genre, year } = req.body;
    const sql = `UPDATE books SET 
                 title = COALESCE(?, title), 
                 author = COALESCE(?, author), 
                 genre = COALESCE(?, genre), 
                 year = COALESCE(?, year) 
                 WHERE id = ?`;

    pool.query(sql, [title, author, genre, year, req.params.id], function (err, result) {
        if (err) {
            res.status(400).json({ "error": err.message });
            return;
        }
        res.json({
            "message": "success",
            "changes": result.affectedRows
        });
    });
});

// Delete a book
app.delete('/api/books/:id', (req, res) => {
    const sql = 'DELETE FROM books WHERE id = ?';

    pool.query(sql, [req.params.id], function (err, result) {
        if (err) {
            res.status(400).json({ "error": err.message });
            return;
        }
        res.json({ "message": "deleted", changes: result.affectedRows });
    });
});

// Start Server
app.listen(PORT, () => {
    console.log(`Server is running on port ${PORT}`);
});
