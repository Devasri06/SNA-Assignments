// =============================================
// TASKFLOW - Smart Task Manager Application
// =============================================

// Initialize FingerprintJS for Security
let visitorId;
let currentUser = null;
let currentTheme = localStorage.getItem('theme') || 'dark';

// Apply saved theme on load
document.documentElement.setAttribute('data-theme', currentTheme);
document.documentElement.setAttribute('data-bs-theme', currentTheme);

(async () => {
    const fp = await FingerprintJS.load();
    const result = await fp.get();
    visitorId = result.visitorId;
    console.log("🔐 Device Signature:", visitorId);
})();

// Theme Toggle
function toggleTheme() {
    currentTheme = currentTheme === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', currentTheme);
    document.documentElement.setAttribute('data-bs-theme', currentTheme);
    localStorage.setItem('theme', currentTheme);

    const icon = document.getElementById('themeIcon');
    icon.className = currentTheme === 'dark' ? 'fas fa-moon' : 'fas fa-sun';
}

// Navigation: Show Register Form
function showRegister() {
    const app = document.getElementById('app');
    app.innerHTML = `
        <div class="auth-container fade-in">
            <div class="text-center mb-4">
                <i class="fas fa-user-plus fa-3x" style="color: var(--secondary); opacity: 0.8;"></i>
            </div>
            <h3 class="text-center">Create Account</h3>
            <p class="text-center text-muted small mb-4">Start managing your tasks efficiently</p>
            <form id="registerForm" onsubmit="handleRegister(event)">
                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0 border-secondary text-muted"><i class="fas fa-user"></i></span>
                        <input type="text" class="form-control border-start-0 ps-0" id="regName" placeholder="John Doe" required minlength="2" maxlength="100">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0 border-secondary text-muted"><i class="fas fa-envelope"></i></span>
                        <input type="email" class="form-control border-start-0 ps-0" id="regEmail" placeholder="name@example.com" required>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0 border-secondary text-muted"><i class="fas fa-key"></i></span>
                        <input type="password" class="form-control border-start-0 ps-0" id="regPass" placeholder="Min 6 characters" required minlength="6">
                    </div>
                </div>
                <button type="submit" class="btn btn-success w-100 shadow-lg">
                    <i class="fas fa-rocket me-2"></i>Get Started
                </button>
            </form>
            <p class="mt-4 text-center text-muted small">
                Already have an account? <a href="#" onclick="location.reload()" class="fw-bold">Sign In</a>
            </p>
        </div>
    `;
}

// Handle Registration
async function handleRegister(e) {
    e.preventDefault();
    const name = document.getElementById('regName').value;
    const email = document.getElementById('regEmail').value;
    const password = document.getElementById('regPass').value;

    try {
        const res = await fetch('api/register.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Visitor-Id': visitorId },
            body: JSON.stringify({ name, email, password })
        });

        const data = await res.json();
        if (data.success) {
            alert("🎉 Account Created Successfully! Please login.");
            location.reload();
        } else {
            alert("Error: " + data.message);
        }
    } catch (error) {
        console.error(error);
        alert("Network Error");
    }
}

// Handle Login
document.getElementById('loginForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    const loader = document.getElementById('loader');

    loader.style.display = 'block';

    try {
        const res = await fetch('api/login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Visitor-Id': visitorId },
            body: JSON.stringify({ email, password })
        });

        const data = await res.json();
        loader.style.display = 'none';

        if (data.success) {
            currentUser = data.user;
            renderDashboard(data.user);
        } else {
            alert("Login Failed: " + data.message);
        }
    } catch (error) {
        loader.style.display = 'none';
        console.error(error);
        alert("Network Error");
    }
});

// Render Dashboard
function renderDashboard(user) {
    document.getElementById('app').innerHTML = `
        <div class="row mb-4">
            <div class="col-md-8">
                <div class="dashboard-header fade-in">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="fw-bold mb-1">Welcome back, <span style="color: var(--primary)">${user.name}</span></h2>
                            <p class="text-muted mb-0"><i class="fas fa-shield-check me-2 text-success"></i>Your tasks are secure</p>
                        </div>
                        <div>
                            <button class="btn btn-outline-secondary btn-sm me-2" onclick="showActivity()">
                                <i class="fas fa-history"></i>
                            </button>
                            <button class="btn btn-outline-secondary btn-sm me-2" onclick="exportTasks()">
                                <i class="fas fa-download"></i>
                            </button>
                            <button class="btn btn-outline-danger btn-sm" onclick="location.reload()">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card fade-in h-100 d-flex flex-column justify-content-center">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-chart-line fa-2x me-3"></i>
                        <div>
                            <h3 class="mb-0" id="completedTasks">-</h3>
                            <small>Completed / <span id="totalTasks">-</span> Total</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
            <div class="d-flex align-items-center gap-2">
                <h4 class="mb-0"><i class="fas fa-list-check me-2"></i>My Tasks</h4>
                <div class="btn-group ms-3">
                    <button class="btn btn-sm btn-outline-secondary active" onclick="filterTasks(null, null, this)">All</button>
                    <button class="btn btn-sm btn-outline-danger" onclick="filterTasks('high', null, this)">🔴 High</button>
                    <button class="btn btn-sm btn-outline-warning" onclick="filterTasks('medium', null, this)">🟡 Medium</button>
                    <button class="btn btn-sm btn-outline-success" onclick="filterTasks('low', null, this)">🟢 Low</button>
                </div>
                <div class="btn-group ms-2">
                    <button class="btn btn-sm btn-outline-info" onclick="filterTasks(null, 'pending', this)">Pending</button>
                    <button class="btn btn-sm btn-outline-success" onclick="filterTasks(null, 'completed', this)">Done</button>
                </div>
            </div>
            <div class="d-flex gap-2">
                <input type="text" class="search-box" id="searchBox" placeholder="🔍 Search tasks..." oninput="searchTasks(this.value)">
                <button class="btn btn-primary" onclick="openTaskModal()">
                    <i class="fas fa-plus me-2"></i>Add Task
                </button>
            </div>
        </div>
        
        <div class="row" id="tasksContainer">
            <div class="col-12 text-center text-muted py-5">
                <div class="loader" style="display: block;"></div> Loading tasks...
            </div>
        </div>
    `;

    loadTasks();
    loadStats();
}

let currentPriorityFilter = null;
let currentStatusFilter = null;

// Load Tasks
async function loadTasks(search = null, priority = null, status = null) {
    try {
        let url = 'api/tasks.php';
        const params = new URLSearchParams();
        if (search) params.append('search', search);
        if (priority) params.append('priority', priority);
        if (status) params.append('status', status);
        if (params.toString()) url += '?' + params.toString();

        const res = await fetch(url);
        const data = await res.json();

        const container = document.getElementById('tasksContainer');

        if (!data.success) {
            container.innerHTML = `<div class="col-12 text-center text-danger">${data.message}</div>`;
            return;
        }

        if (data.data.length === 0) {
            container.innerHTML = `
                <div class="col-12 text-center text-muted py-5">
                    <i class="fas fa-clipboard-list fa-3x mb-3" style="opacity: 0.3;"></i>
                    <p>No tasks found. Add your first task!</p>
                </div>
            `;
            return;
        }

        container.innerHTML = data.data.map(task => `
            <div class="col-md-4 mb-4">
                <div class="card h-100 ${task.status === 'completed' ? 'status-completed' : ''}">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="priority priority-${task.priority}">${getPriorityEmoji(task.priority)} ${capitalize(task.priority)}</span>
                            <span class="status-badge ${task.status === 'completed' ? 'status-completed-badge' : 'status-pending'}">
                                ${task.status === 'completed' ? '✓ Done' : '⏳ Pending'}
                            </span>
                        </div>
                        <h5 class="card-title mt-2">${escapeHtml(task.title)}</h5>
                        <p class="card-text text-muted small">${escapeHtml((task.description || '').substring(0, 100))}${(task.description || '').length > 100 ? '...' : ''}</p>
                        ${task.due_date ? `<p class="due-date mb-0"><i class="fas fa-calendar-alt me-1"></i>Due: ${task.due_date}</p>` : ''}
                    </div>
                    <div class="card-footer bg-transparent border-secondary d-flex justify-content-between align-items-center">
                        <small class="text-muted"><i class="fas fa-clock me-1"></i>${task.created_at}</small>
                        <div>
                            <button class="btn btn-sm btn-outline-success me-1" onclick="toggleTask('${task.id}')" title="Toggle Status">
                                <i class="fas fa-check"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-primary me-1" onclick="editTask('${task.id}')">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteTask('${task.id}')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');

    } catch (error) {
        console.error(error);
        document.getElementById('tasksContainer').innerHTML = `<div class="col-12 text-center text-danger">Failed to load tasks</div>`;
    }
}

// Load Stats
async function loadStats() {
    try {
        const res = await fetch('api/tasks.php?action=stats');
        const data = await res.json();
        if (data.success) {
            document.getElementById('totalTasks').textContent = data.data.total;
            document.getElementById('completedTasks').textContent = data.data.completed;
        }
    } catch (e) { console.error(e); }
}

// Filter Tasks
function filterTasks(priority, status, btn) {
    if (priority !== null) currentPriorityFilter = priority;
    if (status !== null) currentStatusFilter = status;

    // Reset filters if clicking same button
    if (priority === currentPriorityFilter && status === null) {
        currentPriorityFilter = priority;
    }
    if (status === currentStatusFilter && priority === null) {
        currentStatusFilter = status;
    }

    loadTasks(null, currentPriorityFilter, currentStatusFilter);
}

// Search Tasks
let searchTimeout;
function searchTasks(query) {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        loadTasks(query, currentPriorityFilter, currentStatusFilter);
    }, 300);
}

// Open Task Modal
function openTaskModal(id = null) {
    document.getElementById('taskId').value = id || '';
    document.getElementById('taskTitle').value = '';
    document.getElementById('taskDescription').value = '';
    document.getElementById('taskPriority').value = 'medium';
    document.getElementById('taskDueDate').value = '';
    document.getElementById('taskModalTitle').innerHTML = id ? '<i class="fas fa-edit me-2"></i>Edit Task' : '<i class="fas fa-plus me-2"></i>Add Task';

    new bootstrap.Modal(document.getElementById('taskModal')).show();
}

// Edit Task
async function editTask(id) {
    try {
        const res = await fetch(`api/tasks.php?action=single&id=${id}`);
        const data = await res.json();
        if (data.success) {
            const task = data.data;
            document.getElementById('taskId').value = task.id;
            document.getElementById('taskTitle').value = task.title;
            document.getElementById('taskDescription').value = task.description || '';
            document.getElementById('taskPriority').value = task.priority;
            document.getElementById('taskDueDate').value = task.due_date || '';
            document.getElementById('taskModalTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Edit Task';
            new bootstrap.Modal(document.getElementById('taskModal')).show();
        }
    } catch (e) { console.error(e); alert('Failed to load task'); }
}

// Save Task
async function saveTask() {
    const id = document.getElementById('taskId').value;
    const title = document.getElementById('taskTitle').value;
    const description = document.getElementById('taskDescription').value;
    const priority = document.getElementById('taskPriority').value;
    const dueDate = document.getElementById('taskDueDate').value;

    if (!title.trim()) { alert('Title is required'); return; }

    try {
        const method = id ? 'PUT' : 'POST';
        const body = id
            ? { id, title, description, priority, due_date: dueDate }
            : { title, description, priority, due_date: dueDate };

        const res = await fetch('api/tasks.php', {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        });

        const data = await res.json();
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('taskModal')).hide();
            loadTasks(null, currentPriorityFilter, currentStatusFilter);
            loadStats();
        } else {
            alert('Error: ' + data.message);
        }
    } catch (e) { console.error(e); alert('Failed to save task'); }
}

// Toggle Task Status
async function toggleTask(id) {
    try {
        const res = await fetch(`api/tasks.php?action=toggle&id=${id}`);
        const data = await res.json();
        if (data.success) {
            loadTasks(null, currentPriorityFilter, currentStatusFilter);
            loadStats();
        } else {
            alert('Error: ' + data.message);
        }
    } catch (e) { console.error(e); alert('Failed to toggle task'); }
}

// Delete Task
async function deleteTask(id) {
    if (!confirm('⚠️ Are you sure you want to delete this task?')) return;

    try {
        const res = await fetch(`api/tasks.php?id=${id}`, { method: 'DELETE' });
        const data = await res.json();
        if (data.success) {
            loadTasks(null, currentPriorityFilter, currentStatusFilter);
            loadStats();
        } else {
            alert('Error: ' + data.message);
        }
    } catch (e) { console.error(e); alert('Failed to delete task'); }
}

// Show Activity Log
async function showActivity() {
    try {
        const res = await fetch('api/tasks.php?action=activity');
        const data = await res.json();

        const content = document.getElementById('activityContent');
        if (data.success && data.data.length > 0) {
            content.innerHTML = data.data.map(a => `
                <div class="activity-item">
                    <strong>${formatAction(a.action)}</strong>
                    <p class="mb-0 small text-muted">${a.details}</p>
                    <small class="text-muted">${a.timestamp}</small>
                </div>
            `).join('');
        } else {
            content.innerHTML = '<p class="text-muted text-center">No activity yet</p>';
        }

        new bootstrap.Modal(document.getElementById('activityModal')).show();
    } catch (e) { console.error(e); }
}

// Export Tasks
async function exportTasks() {
    try {
        const res = await fetch('api/tasks.php?action=export');
        const data = await res.json();
        if (data.success) {
            const blob = new Blob([JSON.stringify(data.data, null, 2)], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'taskflow_export.json';
            a.click();
            URL.revokeObjectURL(url);
        }
    } catch (e) { console.error(e); alert('Failed to export'); }
}

// Utility Functions
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text || '';
    return div.innerHTML;
}

function getPriorityEmoji(priority) {
    const emojis = { high: '🔴', medium: '🟡', low: '🟢' };
    return emojis[priority] || '⚪';
}

function capitalize(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

function formatAction(action) {
    const icons = {
        'task_created': '📋 Task Created',
        'task_updated': '✏️ Task Updated',
        'task_deleted': '🗑️ Task Deleted',
        'task_status_changed': '✅ Status Changed',
        'tasks_exported': '📤 Tasks Exported',
        'login': '🔓 Login'
    };
    return icons[action] || action;
}
