// Admin Dashboard JavaScript for E-Library System
// San Roque Elementary School

let currentAdminSection = 'books';

// DOM Content Loaded
document.addEventListener('DOMContentLoaded', async function() {
    // Check authentication and admin privileges
    const sessionCheck = await checkSession();

    if (!sessionCheck.logged_in || sessionCheck.user.user_type !== 'admin') {
        window.location.href = 'index.php';
        return;
    }

    // Update admin name
    const adminNameElement = document.getElementById('adminName');
    if (adminNameElement) {
        adminNameElement.textContent = sessionCheck.user.full_name;
    }

    // Initialize admin dashboard
    initializeAdminDashboard();

    // Load initial data
    await loadAdminData();
});

// Initialize admin dashboard
function initializeAdminDashboard() {
    // Initialize navigation
    const navItems = document.querySelectorAll('.admin-nav .nav-item');
    navItems.forEach(item => {
        item.addEventListener('click', function() {
            const section = this.getAttribute('onclick')?.match(/showAdminSection\('(.+?)'\)/)?.[1];
            if (section) {
                showAdminSection(section);
            }
        });
    });

    // Initialize forms
    initializeBookForm();
    initializeCategoryForm();
}

// Show admin section
function showAdminSection(sectionName) {
    // Update navigation
    document.querySelectorAll('.admin-nav .nav-item').forEach(item => {
        item.classList.remove('active');
    });

    const activeNav = document.querySelector(`[onclick="showAdminSection('${sectionName}')"]`);
    if (activeNav) {
        activeNav.classList.add('active');
    }

    // Update content sections
    document.querySelectorAll('.admin-section').forEach(section => {
        section.classList.remove('active');
    });

    const activeSection = document.getElementById(`${sectionName}-section`);
    if (activeSection) {
        activeSection.classList.add('active');
    }

    currentAdminSection = sectionName;

    // Load section data
    loadAdminSectionData(sectionName);
}

// Load admin data
async function loadAdminData() {
    try {
        // Load books by default
        await loadBooks();
        await loadReports();
    } catch (error) {
        console.error('Error loading admin data:', error);
    }
}

// Load admin section data
async function loadAdminSectionData(section) {
    try {
        switch (section) {
            case 'books':
                await loadBooks();
                break;
            case 'users':
                await loadUsers();
                break;
            case 'sections':
                await loadSections();
                break;
            case 'categories':
                await loadCategoriesAdmin();
                break;
            case 'reports':
                await loadReports();
                break;
            case 'settings':
                await loadSettings();
                break;
        }
    } catch (error) {
        console.error(`Error loading ${section} data:`, error);
    }
}

// Load books for admin
async function loadBooks(filter = 'all') {
    const tableBody = document.getElementById('booksTableBody');
    if (!tableBody) return;

    tableBody.innerHTML = '<tr><td colspan="7" class="loading">Loading books...</td></tr>';

    try {
        const response = await fetch(`../api/admin.php?action=get_books&filter=${filter}`);
        const result = await response.json();

        if (result.success) {
            renderBooksTable(tableBody, result.books);
        } else {
            tableBody.innerHTML = '<tr><td colspan="7" class="loading">No books found.</td></tr>';
        }
    } catch (error) {
        console.error('Error loading books:', error);
        tableBody.innerHTML = '<tr><td colspan="7" class="loading">Error loading books.</td></tr>';
    }
}

// Render books table
function renderBooksTable(tableBody, books) {
    tableBody.innerHTML = '';

    if (!books || books.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="7" class="loading">No books found.</td></tr>';
        return;
    }

    books.forEach(book => {
        const row = document.createElement('tr');

        const coverUrl = book.cover_image ?
            `../uploads/covers/${book.cover_image}` :
            '../assets/images/default-book.png';

        const statusClass = book.is_approved ? 'approved' : 'pending';
        const statusText = book.is_approved ? 'Approved' : 'Pending';

        row.innerHTML = `
            <td><img src="${coverUrl}" alt="${book.title}" class="table-cover" onerror="this.src='../assets/images/default-book.png'"></td>
            <td>${book.title}</td>
            <td>${book.author || 'Unknown'}</td>
            <td>${book.category}</td>
            <td>${book.grade_level === 'all' ? 'All Grades' : book.grade_level.charAt(0).toUpperCase() + book.grade_level.slice(1)}</td>
            <td><span class="status ${statusClass}">${statusText}</span></td>
            <td class="actions">
                <button onclick="editBook(${book.ebook_id})" class="btn btn-secondary btn-sm">
                    <i class="fas fa-edit"></i>
                </button>
                <button onclick="approveBook(${book.ebook_id})" class="btn btn-success btn-sm" ${book.is_approved ? 'disabled' : ''}>
                    <i class="fas fa-check"></i>
                </button>
                <button onclick="deleteBook(${book.ebook_id})" class="btn btn-danger btn-sm">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;

        tableBody.appendChild(row);
    });
}

// Load users for admin
async function loadUsers() {
    const tableBody = document.getElementById('usersTableBody');
    if (!tableBody) return;

    tableBody.innerHTML = '<tr><td colspan="7" class="loading">Loading users...</td></tr>';

    try {
        const response = await fetch('../api/admin.php?action=get_users');
        const result = await response.json();

        if (result.success) {
            renderUsersTable(tableBody, result.users);
        } else {
            tableBody.innerHTML = '<tr><td colspan="7" class="loading">No users found.</td></tr>';
        }
    } catch (error) {
        console.error('Error loading users:', error);
        tableBody.innerHTML = '<tr><td colspan="7" class="loading">Error loading users.</td></tr>';
    }
}

// Render users table
function renderUsersTable(tableBody, users) {
    tableBody.innerHTML = '';

    if (!users || users.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="7" class="loading">No users found.</td></tr>';
        return;
    }

    users.forEach(user => {
        const row = document.createElement('tr');

        const gradeText = user.grade_level === 'n/a' ? 'N/A' :
            user.grade_level.charAt(0).toUpperCase() + user.grade_level.slice(1);

        const lastLogin = user.last_login ? new Date(user.last_login).toLocaleDateString() : 'Never';

        row.innerHTML = `
            <td>${user.full_name}</td>
            <td>${user.username}</td>
            <td><span class="user-type ${user.user_type}">${user.user_type.charAt(0).toUpperCase() + user.user_type.slice(1)}</span></td>
            <td>${gradeText}</td>
            <td><span class="status ${user.is_active ? 'active' : 'inactive'}">${user.is_active ? 'Active' : 'Inactive'}</span></td>
            <td>${lastLogin}</td>
            <td class="actions">
                <button onclick="editUser(${user.user_id})" class="btn btn-secondary btn-sm">
                    <i class="fas fa-edit"></i>
                </button>
                <button onclick="toggleUserStatus(${user.user_id}, ${user.is_active ? 0 : 1})" class="btn ${user.is_active ? 'btn-warning' : 'btn-success'} btn-sm">
                    <i class="fas ${user.is_active ? 'fa-ban' : 'fa-check'}"></i>
                </button>
                <button onclick="deleteUser(${user.user_id})" class="btn btn-danger btn-sm">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;

        tableBody.appendChild(row);
    });
}

// Load categories for admin
async function loadCategoriesAdmin() {
    const tableBody = document.getElementById('categoriesTableBody');
    if (!tableBody) return;

    tableBody.innerHTML = '<tr><td colspan="6" class="loading">Loading categories...</td></tr>';

    try {
        const response = await fetch('../api/admin.php?action=get_categories');
        const result = await response.json();

        if (result.success) {
            renderCategoriesTable(tableBody, result.categories);
        } else {
            tableBody.innerHTML = '<tr><td colspan="6" class="loading">No categories found.</td></tr>';
        }
    } catch (error) {
        console.error('Error loading categories:', error);
        tableBody.innerHTML = '<tr><td colspan="6" class="loading">Error loading categories.</td></tr>';
    }
}

// Render categories table
function renderCategoriesTable(tableBody, categories) {
    tableBody.innerHTML = '';

    if (!categories || categories.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="6" class="loading">No categories found.</td></tr>';
        return;
    }

    categories.forEach(category => {
        const row = document.createElement('tr');

        row.innerHTML = `
            <td>${category.category_name}</td>
            <td>${category.description}</td>
            <td><i class="${category.icon}"></i> ${category.icon}</td>
            <td>${category.display_order}</td>
            <td><span class="status ${category.is_active ? 'active' : 'inactive'}">${category.is_active ? 'Active' : 'Inactive'}</span></td>
            <td class="actions">
                <button onclick="editCategory(${category.category_id})" class="btn btn-secondary btn-sm">
                    <i class="fas fa-edit"></i>
                </button>
                <button onclick="deleteCategory(${category.category_id})" class="btn btn-danger btn-sm">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;

        tableBody.appendChild(row);
    });
}

// Load reports
async function loadReports() {
    try {
        const response = await fetch('../api/admin.php?action=get_reports');
        const result = await response.json();

        if (result.success) {
            // Update report cards
            document.getElementById('totalBooks').textContent = result.reports.total_books || 0;
            document.getElementById('totalUsers').textContent = result.reports.total_users || 0;
            document.getElementById('totalDownloads').textContent = result.reports.total_downloads || 0;
            document.getElementById('totalViews').textContent = result.reports.total_views || 0;
        }
    } catch (error) {
        console.error('Error loading reports:', error);
    }
}

// Load settings
async function loadSettings() {
    try {
        const response = await fetch('../api/admin.php?action=get_settings');
        const result = await response.json();

        if (result.success) {
            // Update settings form
            result.settings.forEach(setting => {
                const element = document.getElementById(setting.setting_key);
                if (element) {
                    element.value = setting.setting_value;
                }
            });
        }
    } catch (error) {
        console.error('Error loading settings:', error);
    }
}

// Initialize book form
function initializeBookForm() {
    const form = document.getElementById('bookForm');
    if (!form) return;

    form.addEventListener('submit', handleBookSubmit);

    // Load categories for dropdown
    loadBookCategories();
}

// Load book categories for form
async function loadBookCategories() {
    try {
        const response = await fetch('../api/ebooks.php?action=get_categories');
        const result = await response.json();

        if (result.success) {
            const categorySelect = document.getElementById('bookCategory');
            if (categorySelect) {
                result.categories.forEach(category => {
                    const option = document.createElement('option');
                    option.value = category.category_name;
                    option.textContent = category.category_name;
                    categorySelect.appendChild(option);
                });
            }
        }
    } catch (error) {
        console.error('Error loading categories for form:', error);
    }
}

// Handle book form submission
async function handleBookSubmit(e) {
    e.preventDefault();

    const formData = new FormData(e.target);
    const submitButton = e.target.querySelector('button[type="submit"]');
    const modalTitle = document.getElementById('bookModalTitle');

    const isEdit = formData.get('book_id') ? true : false;
    const action = isEdit ? 'update_book' : 'add_book';

    // Show loading
    submitButton.disabled = true;
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

    try {
        const response = await fetch(`../api/admin.php?action=${action}`, {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            showMessage('Book saved successfully!', 'success');
            closeBookModal();
            loadBooks(); // Reload books list
        } else {
            showMessage(result.message || 'Failed to save book', 'error');
        }
    } catch (error) {
        console.error('Book save error:', error);
        showMessage('An error occurred. Please try again.', 'error');
    } finally {
        submitButton.disabled = false;
        submitButton.innerHTML = 'Save Book';
    }
}

// Show add book modal
function showAddBookModal() {
    const modal = document.getElementById('bookModal');
    const form = document.getElementById('bookForm');
    const modalTitle = document.getElementById('bookModalTitle');

    // Reset form
    form.reset();
    document.getElementById('bookId').value = '';

    // Update title
    modalTitle.textContent = 'Add New Book';

    // Show modal
    modal.style.display = 'block';
}

// Close book modal
function closeBookModal() {
    const modal = document.getElementById('bookModal');
    modal.style.display = 'none';
}

// Edit book
async function editBook(ebookId) {
    try {
        const response = await fetch(`../api/ebooks.php?action=get_book&id=${ebookId}`);
        const result = await response.json();

        if (result.success) {
            const book = result.book;
            const modal = document.getElementById('bookModal');
            const modalTitle = document.getElementById('bookModalTitle');

            // Fill form
            document.getElementById('bookId').value = book.ebook_id;
            document.getElementById('bookTitle').value = book.title;
            document.getElementById('bookAuthor').value = book.author || '';
            document.getElementById('bookDescription').value = book.description || '';
            document.getElementById('bookCategory').value = book.category;
            document.getElementById('bookSubject').value = book.subject;
            document.getElementById('bookGradeLevel').value = book.grade_level;
            document.getElementById('bookContentType').value = book.content_type;

            // Update title
            modalTitle.textContent = 'Edit Book';

            // Show modal
            modal.style.display = 'block';
        } else {
            showMessage('Failed to load book details', 'error');
        }
    } catch (error) {
        console.error('Edit book error:', error);
        showMessage('An error occurred', 'error');
    }
}

// Approve book
async function approveBook(ebookId) {
    if (!confirm('Are you sure you want to approve this book?')) return;

    try {
        const response = await fetch('../api/admin.php?action=approve_book', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ ebook_id: ebookId })
        });

        const result = await response.json();

        if (result.success) {
            showMessage('Book approved successfully!', 'success');
            loadBooks(); // Reload books list
        } else {
            showMessage(result.message || 'Failed to approve book', 'error');
        }
    } catch (error) {
        console.error('Approve book error:', error);
        showMessage('An error occurred', 'error');
    }
}

// Delete book
async function deleteBook(ebookId) {
    if (!confirm('Are you sure you want to delete this book? This action cannot be undone.')) return;

    try {
        const response = await fetch('../api/admin.php?action=delete_book', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ ebook_id: ebookId })
        });

        const result = await response.json();

        if (result.success) {
            showMessage('Book deleted successfully!', 'success');
            loadBooks(); // Reload books list
        } else {
            showMessage(result.message || 'Failed to delete book', 'error');
        }
    } catch (error) {
        console.error('Delete book error:', error);
        showMessage('An error occurred', 'error');
    }
}

// Initialize category form
function initializeCategoryForm() {
    const form = document.getElementById('categoryForm');
    if (!form) return;

    form.addEventListener('submit', handleCategorySubmit);
}

// Handle category form submission
async function handleCategorySubmit(e) {
    e.preventDefault();

    const formData = new FormData(e.target);
    const submitButton = e.target.querySelector('button[type="submit"]');

    // Show loading
    submitButton.disabled = true;
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

    try {
        const response = await fetch('../api/admin.php?action=add_category', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            showMessage('Category added successfully!', 'success');
            closeCategoryModal();
            loadCategoriesAdmin(); // Reload categories list
        } else {
            showMessage(result.message || 'Failed to add category', 'error');
        }
    } catch (error) {
        console.error('Category save error:', error);
        showMessage('An error occurred. Please try again.', 'error');
    } finally {
        submitButton.disabled = false;
        submitButton.innerHTML = 'Add Category';
    }
}

// Show add category modal
function showAddCategoryModal() {
    const modal = document.getElementById('categoryModal');
    const form = document.getElementById('categoryForm');

    // Reset form
    form.reset();

    // Show modal
    modal.style.display = 'block';
}

// Close category modal
function closeCategoryModal() {
    const modal = document.getElementById('categoryModal');
    modal.style.display = 'none';
}

// Save settings
async function saveSettings() {
    const settings = {
        site_name: document.getElementById('siteName').value,
        max_downloads_per_user: document.getElementById('maxDownloads').value,
        enable_offline_reading: document.getElementById('enableOffline').value,
        enable_teacher_uploads: document.getElementById('enableTeacherUploads').value
    };

    try {
        const response = await fetch('../api/admin.php?action=save_settings', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(settings)
        });

        const result = await response.json();

        if (result.success) {
            showMessage('Settings saved successfully!', 'success');
        } else {
            showMessage(result.message || 'Failed to save settings', 'error');
        }
    } catch (error) {
        console.error('Save settings error:', error);
        showMessage('An error occurred', 'error');
    }
}

// Show message
function showMessage(message, type = 'info') {
    // Create message element if it doesn't exist
    let messageElement = document.getElementById('adminMessage');
    if (!messageElement) {
        messageElement = document.createElement('div');
        messageElement.id = 'adminMessage';
        messageElement.className = 'message';
        document.querySelector('.admin-content').prepend(messageElement);
    }

    messageElement.innerHTML = message;
    messageElement.className = `message ${type}`;
    messageElement.style.display = 'block';

    // Auto hide after 3 seconds
    setTimeout(() => {
        messageElement.style.display = 'none';
    }, 3000);
}

// Show add teacher modal
function showAddTeacherModal() {
    const modal = document.getElementById('teacherModal');
    const form = document.getElementById('teacherForm');
    
    // Reset form
    if (form) form.reset();
    
    // Show modal
    modal.style.display = 'flex';
}

// Close teacher modal
function closeTeacherModal() {
    const modal = document.getElementById('teacherModal');
    modal.style.display = 'none';
}

// Handle add teacher
async function addTeacher() {
    const fullName = document.getElementById('teacherFullName').value.trim();
    const email = document.getElementById('teacherEmail').value.trim();
    const username = document.getElementById('teacherUsername').value.trim();
    const password = document.getElementById('teacherPassword').value;
    
    if (!fullName || !email || !username || !password) {
        showMessage('All fields are required', 'error');
        return;
    }
    
    if (password.length < 6) {
        showMessage('Password must be at least 6 characters', 'error');
        return;
    }
    
    const btn = document.querySelector('#teacherModal .btn-success');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';
    
    try {
        const response = await fetch('../api/admin.php?action=add_teacher', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ full_name: fullName, email, username, password })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showMessage('Teacher account created successfully!', 'success');
            closeTeacherModal();
            loadUsers();
        } else {
            showMessage(result.message || 'Failed to create teacher account', 'error');
        }
    } catch (error) {
        console.error('Add teacher error:', error);
        showMessage('An error occurred', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-user-plus"></i> Create Teacher Account';
    }
}

// Edit user
async function editUser(userId) {
    try {
        const response = await fetch(`../api/admin.php?action=get_user&user_id=${userId}`);
        const result = await response.json();

        if (result.success) {
            const user = result.user;
            const modal = document.getElementById('userModal');
            const modalTitle = document.getElementById('userModalTitle');

            // Fill form
            document.getElementById('userId').value = user.user_id;
            document.getElementById('userFullName').value = user.full_name;
            document.getElementById('userEmail').value = user.email;
            document.getElementById('userUsername').value = user.username;
            document.getElementById('userType').value = user.user_type;
            document.getElementById('userGradeLevel').value = user.grade_level;
            document.getElementById('userPassword').value = ''; // Clear password field

            // Update title
            modalTitle.textContent = 'Edit User';

            // Show modal
            modal.style.display = 'flex';
        } else {
            showMessage(result.message || 'Failed to load user details', 'error');
        }
    } catch (error) {
        console.error('Edit user error:', error);
        showMessage('An error occurred', 'error');
    }
}

// Toggle user status
async function toggleUserStatus(userId, newStatus) {
    const action = newStatus ? 'activate' : 'deactivate';
    if (!confirm(`Are you sure you want to ${action} this user?`)) return;
    
    try {
        const response = await fetch('../api/admin.php?action=update_user_status', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: userId, is_active: newStatus })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showMessage(result.message, 'success');
            loadUsers();
        } else {
            showMessage(result.message || 'Failed to update user status', 'error');
        }
    } catch (error) {
        console.error('Toggle user status error:', error);
        showMessage('An error occurred', 'error');
    }
}

// Delete user
async function deleteUser(userId) {
    if (!confirm('Are you sure you want to delete this user? This action cannot be undone.')) return;
    
    try {
        const response = await fetch('../api/admin.php?action=delete_user', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: userId })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showMessage('User deleted successfully!', 'success');
            loadUsers();
        } else {
            showMessage(result.message || 'Failed to delete user', 'error');
        }
    } catch (error) {
        console.error('Delete user error:', error);
        showMessage('An error occurred', 'error');
    }
}

function editCategory(categoryId) {
    showMessage('Category editing functionality not yet implemented', 'info');
}

function deleteCategory(categoryId) {
    showMessage('Category deletion functionality not yet implemented', 'info');
}

// Update user
async function updateUser() {
    const userId = document.getElementById('userId').value;
    const fullName = document.getElementById('userFullName').value.trim();
    const email = document.getElementById('userEmail').value.trim();
    const username = document.getElementById('userUsername').value.trim();
    const userType = document.getElementById('userType').value;
    const gradeLevel = document.getElementById('userGradeLevel').value;
    const password = document.getElementById('userPassword').value;

    if (!fullName || !email || !username || !userType) {
        showMessage('Full name, email, username, and user type are required', 'error');
        return;
    }

    const btn = document.querySelector('#userModal .btn-primary');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';

    try {
        const response = await fetch('../api/admin.php?action=update_user', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                user_id: userId,
                full_name: fullName,
                email,
                username,
                user_type: userType,
                grade_level: gradeLevel,
                password
            })
        });

        const result = await response.json();

        if (result.success) {
            showMessage('User updated successfully!', 'success');
            closeUserModal();
            loadUsers();
        } else {
            showMessage(result.message || 'Failed to update user', 'error');
        }
    } catch (error) {
        console.error('Update user error:', error);
        showMessage('An error occurred', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i> Update User';
    }
}

// Close user modal
function closeUserModal() {
    const modal = document.getElementById('userModal');
    modal.style.display = 'none';
}

// Close modals when clicking outside
window.onclick = function(event) {
    const bookModal = document.getElementById('bookModal');
    const categoryModal = document.getElementById('categoryModal');
    const teacherModal = document.getElementById('teacherModal');
    const userModal = document.getElementById('userModal');
    const sectionModal = document.getElementById('sectionModal');

    if (event.target === bookModal) {
        bookModal.style.display = 'none';
    }

    if (event.target === categoryModal) {
        categoryModal.style.display = 'none';
    }

    if (event.target === teacherModal) {
        teacherModal.style.display = 'none';
    }

    if (event.target === userModal) {
        userModal.style.display = 'none';
    }

    if (event.target === sectionModal) {
        sectionModal.style.display = 'none';
    }
}

// Check session status
async function checkSession() {
    try {
        const response = await fetch('../api/auth.php?action=check-session');
        const result = await response.json();
        return result;
    } catch (error) {
        console.error('Session check error:', error);
        return { success: true, logged_in: false };
    }
}

// Logout function
async function logout() {
    try {
        const response = await fetch('../api/auth.php?action=logout');
        const result = await response.json();
        if (result.success) {
            window.location.href = '../login.php';
        }
    } catch (error) {
        console.error('Logout error:', error);
        window.location.href = '../login.php';
    }
}

// Section Management Functions

// Load sections for admin
async function loadSections() {
    const tableBody = document.getElementById('sectionsTableBody');
    if (!tableBody) return;

    tableBody.innerHTML = '<tr><td colspan="6" class="loading">Loading sections...</td></tr>';

    try {
        const response = await fetch('../api/admin.php?action=get_sections');
        const result = await response.json();

        if (result.success) {
            renderSectionsTable(tableBody, result.sections);
        } else {
            tableBody.innerHTML = '<tr><td colspan="6" class="loading">No sections found.</td></tr>';
        }
    } catch (error) {
        console.error('Error loading sections:', error);
        tableBody.innerHTML = '<tr><td colspan="6" class="loading">Error loading sections.</td></tr>';
    }
}

// Render sections table
function renderSectionsTable(tableBody, sections) {
    tableBody.innerHTML = '';

    if (!sections || sections.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="6" class="loading">No sections found.</td></tr>';
        return;
    }

    sections.forEach(section => {
        const row = document.createElement('tr');

        const gradeText = section.grade_level.charAt(0).toUpperCase() + section.grade_level.slice(1);
        const teacherName = section.teacher_name || 'Not assigned';
        const studentCount = section.student_count || 0;
        const statusClass = section.is_active ? 'active' : 'inactive';
        const statusText = section.is_active ? 'Active' : 'Inactive';

        row.innerHTML = `
            <td>${section.section_name}</td>
            <td>${gradeText}</td>
            <td>${teacherName}</td>
            <td>${studentCount} students</td>
            <td><span class="status ${statusClass}">${statusText}</span></td>
            <td class="actions">
                <button onclick="editSection(${section.section_id})" class="btn btn-secondary btn-sm">
                    <i class="fas fa-edit"></i>
                </button>
                <button onclick="deleteSection(${section.section_id})" class="btn btn-danger btn-sm">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;

        tableBody.appendChild(row);
    });
}

// Show add section modal
function showAddSectionModal() {
    const modal = document.getElementById('sectionModal');
    const form = document.getElementById('sectionForm');

    // Reset form
    form.reset();

    // Load teachers for dropdown
    loadTeachersForSection();

    // Show modal
    modal.style.display = 'flex';
}

// Close section modal
function closeSectionModal() {
    const modal = document.getElementById('sectionModal');
    const form = document.getElementById('sectionForm');
    const modalTitle = document.getElementById('sectionModalTitle');
    const submitBtn = document.getElementById('sectionSubmitBtn');

    // Reset form and modal state
    form.reset();
    document.getElementById('sectionId').value = '';
    modalTitle.textContent = 'Add New Section';
    submitBtn.innerHTML = '<i class="fas fa-plus"></i> Create Section';
    submitBtn.setAttribute('onclick', 'addSection()');

    modal.style.display = 'none';
}

// Load teachers for section assignment
async function loadTeachersForSection() {
    try {
        const response = await fetch('../api/admin.php?action=get_teachers');
        const result = await response.json();

        if (result.success) {
            const teacherSelect = document.getElementById('sectionTeacher');
            teacherSelect.innerHTML = '<option value="">Select Teacher (Optional)</option>';

            result.teachers.forEach(teacher => {
                const option = document.createElement('option');
                option.value = teacher.user_id;
                option.textContent = teacher.full_name;
                teacherSelect.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error loading teachers:', error);
    }
}

// Handle add section
async function addSection() {
    const sectionName = document.getElementById('sectionName').value.trim();
    const gradeLevel = document.getElementById('sectionGradeLevel').value;
    const teacherId = document.getElementById('sectionTeacher').value;

    if (!sectionName || !gradeLevel) {
        showMessage('Section name and grade level are required', 'error');
        return;
    }

    const btn = document.querySelector('#sectionModal .btn-success');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';

    try {
        const response = await fetch('../api/admin.php?action=add_section', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                section_name: sectionName,
                grade_level: gradeLevel,
                teacher_id: teacherId || null
            })
        });

        const result = await response.json();

        if (result.success) {
            showMessage('Section created successfully!', 'success');
            closeSectionModal();
            loadSections();
        } else {
            showMessage(result.message || 'Failed to create section', 'error');
        }
    } catch (error) {
        console.error('Add section error:', error);
        showMessage('An error occurred', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-plus"></i> Create Section';
    }
}

// Edit section
async function editSection(sectionId) {
    try {
        const response = await fetch(`../api/admin.php?action=get_section&section_id=${sectionId}`);
        const result = await response.json();

        if (result.success) {
            const section = result.section;
            const modal = document.getElementById('sectionModal');
            const modalTitle = document.getElementById('sectionModalTitle');
            const submitBtn = document.getElementById('sectionSubmitBtn');

            // Fill form with section data
            document.getElementById('sectionId').value = section.section_id;
            document.getElementById('sectionName').value = section.section_name;
            document.getElementById('sectionGradeLevel').value = section.grade_level;

            // Load teachers and set current teacher
            await loadTeachersForSection();
            document.getElementById('sectionTeacher').value = section.teacher_id || '';

            // Update modal title and button
            modalTitle.textContent = 'Edit Section';
            submitBtn.innerHTML = '<i class="fas fa-save"></i> Update Section';
            submitBtn.setAttribute('onclick', 'updateSection()');

            // Show modal
            modal.style.display = 'flex';
        } else {
            showMessage(result.message || 'Failed to load section details', 'error');
        }
    } catch (error) {
        console.error('Edit section error:', error);
        showMessage('An error occurred', 'error');
    }
}

// Update section
async function updateSection() {
    const sectionId = document.getElementById('sectionId').value;
    const sectionName = document.getElementById('sectionName').value.trim();
    const gradeLevel = document.getElementById('sectionGradeLevel').value;
    const teacherId = document.getElementById('sectionTeacher').value;

    if (!sectionName || !gradeLevel) {
        showMessage('Section name and grade level are required', 'error');
        return;
    }

    const btn = document.getElementById('sectionSubmitBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';

    try {
        const response = await fetch('../api/admin.php?action=update_section', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                section_id: sectionId,
                section_name: sectionName,
                grade_level: gradeLevel,
                teacher_id: teacherId || null
            })
        });

        const result = await response.json();

        if (result.success) {
            showMessage('Section updated successfully!', 'success');
            closeSectionModal();
            loadSections();
        } else {
            showMessage(result.message || 'Failed to update section', 'error');
        }
    } catch (error) {
        console.error('Update section error:', error);
        showMessage('An error occurred', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i> Update Section';
    }
}

// Delete section
async function deleteSection(sectionId) {
    if (!confirm('Are you sure you want to delete this section? This will unassign all students and teachers.')) return;

    try {
        const response = await fetch('../api/admin.php?action=delete_section', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ section_id: sectionId })
        });

        const result = await response.json();

        if (result.success) {
            showMessage('Section deleted successfully!', 'success');
            loadSections();
        } else {
            showMessage(result.message || 'Failed to delete section', 'error');
        }
    } catch (error) {
        console.error('Delete section error:', error);
        showMessage('An error occurred', 'error');
    }
}
