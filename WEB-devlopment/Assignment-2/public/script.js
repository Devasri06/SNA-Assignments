const API_URL = '/api/books';

// DOM Elements
const bookGrid = document.getElementById('book-grid');
const modal = document.getElementById('bookModal');
const bookForm = document.getElementById('bookForm');
const modalTitle = document.getElementById('modalTitle');
const bookIdInput = document.getElementById('bookId');
const titleInput = document.getElementById('title');
const authorInput = document.getElementById('author');
const genreInput = document.getElementById('genre');
const yearInput = document.getElementById('year');

// State
let isEditing = false;

// Fetch and Display Books
async function fetchBooks() {
    try {
        const response = await fetch(API_URL);
        const result = await response.json();
        renderBooks(result.data);
    } catch (error) {
        console.error('Error fetching books:', error);
        bookGrid.innerHTML = '<div class="empty-state"><i class="fas fa-exclamation-triangle"></i><p>Failed to load data. Please try again later.</p></div>';
    }
}

function renderBooks(books) {
    bookGrid.innerHTML = '';

    if (books.length === 0) {
        bookGrid.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-books"></i>
                <p>No books found. Click "Add Book" to get started.</p>
            </div>
        `;
        return;
    }

    books.forEach(book => {
        const card = document.createElement('div');
        card.className = 'card';
        card.innerHTML = `
            <div class="card-icon">
                <i class="fas fa-book"></i>
            </div>
            <h3>${book.title}</h3>
            <p><i class="fas fa-user-pen"></i> ${book.author}</p>
            <p><i class="fas fa-bookmark"></i> ${book.genre}</p>
            <p><i class="fas fa-calendar-alt"></i> Published: ${book.year}</p>
            <div class="card-actions">
                <button class="btn-icon btn-edit" onclick="editBook(${book.id}, '${escapeQuotes(book.title)}', '${escapeQuotes(book.author)}', '${escapeQuotes(book.genre)}', ${book.year})">
                    <i class="fas fa-pen"></i>
                </button>
                <button class="btn-icon btn-delete" onclick="deleteBook(${book.id})">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;
        bookGrid.appendChild(card);
    });
}

// Escape quotes for safe HTML attribute insertion
function escapeQuotes(str) {
    return str.replace(/'/g, "\\'").replace(/"/g, '\\"');
}

// Open/Close Modal
function openModal() {
    modal.classList.add('active');
    if (!isEditing) {
        bookForm.reset();
        modalTitle.textContent = 'Add New Book';
        bookIdInput.value = '';
    }
}

function closeModal() {
    modal.classList.remove('active');
    isEditing = false;
    bookForm.reset();
}

// Edit Book - Pre-fill form
window.editBook = (id, title, author, genre, year) => {
    isEditing = true;
    bookIdInput.value = id;
    titleInput.value = title.replace(/\\'/g, "'").replace(/\\"/g, '"');
    authorInput.value = author.replace(/\\'/g, "'").replace(/\\"/g, '"');
    genreInput.value = genre.replace(/\\'/g, "'").replace(/\\"/g, '"');
    yearInput.value = year;
    modalTitle.textContent = 'Edit Book';
    openModal();
};

// Handle Form Submit (Create or Update)
bookForm.addEventListener('submit', async (e) => {
    e.preventDefault();

    const bookData = {
        title: titleInput.value,
        author: authorInput.value,
        genre: genreInput.value,
        year: yearInput.value
    };

    const id = bookIdInput.value;
    const method = isEditing ? 'PUT' : 'POST';
    const url = isEditing ? `${API_URL}/${id}` : API_URL;

    try {
        const response = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(bookData)
        });

        const result = await response.json();

        if (response.ok) {
            closeModal();
            fetchBooks();
        } else {
            alert('Error: ' + result.error);
        }
    } catch (error) {
        console.error('Error saving book:', error);
        alert('Failed to save book.');
    }
});

// Delete Book
window.deleteBook = async (id) => {
    if (confirm('Are you sure you want to delete this book?')) {
        try {
            const response = await fetch(`${API_URL}/${id}`, {
                method: 'DELETE'
            });

            if (response.ok) {
                fetchBooks();
            } else {
                alert('Failed to delete book.');
            }
        } catch (error) {
            console.error('Error deleting book:', error);
        }
    }
};

// Close modal when clicking outside
modal.addEventListener('click', (e) => {
    if (e.target === modal) {
        closeModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && modal.classList.contains('active')) {
        closeModal();
    }
});

// Initial Load
document.addEventListener('DOMContentLoaded', fetchBooks);
