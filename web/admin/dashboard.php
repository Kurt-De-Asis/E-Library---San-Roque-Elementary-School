<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

// Prevent browser caching
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Admin Dashboard - San Roque Elementary School E-Library</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="admin-dashboard">
    <!-- Admin Header -->
    <header class="admin-header">
        <div class="header-container">
            <div class="header-left">
                <h1><i class="fas fa-cog"></i> Admin Dashboard</h1>
                <p>San Roque Elementary School E-Library</p>
            </div>
            <div class="header-right">
                <div class="user-info">
                    <span id="adminName">Loading...</span>
                    <button onclick="window.location.href='../index.php'" class="btn btn-secondary">
                        <i class="fas fa-home"></i> Back to Library
                    </button>
                    <button onclick="logout()" class="btn btn-logout">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Admin Navigation -->
    <nav class="admin-nav">
        <div class="nav-container">
            <button class="nav-item active" onclick="showAdminSection('books')">
                <i class="fas fa-book"></i> Manage Books
            </button>
            <button class="nav-item" onclick="showAdminSection('users')">
                <i class="fas fa-users"></i> Manage Users
            </button>
            <button class="nav-item" onclick="showAdminSection('sections')">
                <i class="fas fa-chalkboard"></i> Sections
            </button>
            <button class="nav-item" onclick="showAdminSection('categories')">
                <i class="fas fa-th"></i> Categories
            </button>
            <button class="nav-item" onclick="showAdminSection('reports')">
                <i class="fas fa-chart-bar"></i> Reports
            </button>
            <button class="nav-item" onclick="showAdminSection('settings')">
                <i class="fas fa-cog"></i> Settings
            </button>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="admin-content">
        <!-- Books Management Section -->
        <section id="books-section" class="admin-section active">
            <div class="section-header">
                <h2><i class="fas fa-book"></i> Book Management</h2>
                <button onclick="showAddBookModal()" class="btn btn-success">
                    <i class="fas fa-plus"></i> Add New Book
                </button>
            </div>

            <!-- Books Filter -->
            <div class="filter-controls">
                <select id="booksFilter" onchange="loadBooks()">
                    <option value="all">All Books</option>
                    <option value="approved">Approved</option>
                    <option value="pending">Pending Approval</option>
                    <option value="featured">Featured</option>
                </select>
                <input type="text" id="booksSearch" placeholder="Search books..." onkeyup="searchBooks()">
            </div>

            <!-- Books Table -->
            <div class="data-table">
                <table id="booksTable">
                    <thead>
                        <tr>
                            <th>Cover</th>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Category</th>
                            <th>Grade Level</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="booksTableBody">
                        <tr>
                            <td colspan="7" class="loading">Loading books...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Users Management Section -->
        <section id="users-section" class="admin-section">
            <div class="section-header">
                <h2><i class="fas fa-users"></i> User Management</h2>
                <button onclick="showAddTeacherModal()" class="btn btn-success">
                    <i class="fas fa-chalkboard-teacher"></i> Add Teacher
                </button>
            </div>

            <!-- Users Table -->
            <div class="data-table">
                <table id="usersTable">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Username</th>
                            <th>User Type</th>
                            <th>Grade Level</th>
                            <th>Status</th>
                            <th>Last Login</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="usersTableBody">
                        <tr>
                            <td colspan="7" class="loading">Loading users...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Sections Management Section -->
        <section id="sections-section" class="admin-section">
            <div class="section-header">
                <h2><i class="fas fa-chalkboard"></i> Section Management</h2>
                <button onclick="showAddSectionModal()" class="btn btn-success">
                    <i class="fas fa-plus"></i> Add Section
                </button>
            </div>

            <!-- Sections Table -->
            <div class="data-table">
                <table id="sectionsTable">
                    <thead>
                        <tr>
                            <th>Section Name</th>
                            <th>Grade Level</th>
                            <th>Teacher</th>
                            <th>Students</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="sectionsTableBody">
                        <tr>
                            <td colspan="6" class="loading">Loading sections...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Categories Section -->
        <section id="categories-section" class="admin-section">
            <div class="section-header">
                <h2><i class="fas fa-th"></i> Category Management</h2>
                <button onclick="showAddCategoryModal()" class="btn btn-success">
                    <i class="fas fa-plus"></i> Add Category
                </button>
            </div>

            <!-- Categories Table -->
            <div class="data-table">
                <table id="categoriesTable">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Icon</th>
                            <th>Display Order</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="categoriesTableBody">
                        <tr>
                            <td colspan="6" class="loading">Loading categories...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Reports Section -->
        <section id="reports-section" class="admin-section">
            <div class="section-header">
                <h2><i class="fas fa-chart-bar"></i> System Reports</h2>
            </div>

            <div class="reports-grid">
                <div class="report-card">
                    <h3><i class="fas fa-book"></i> Total Books</h3>
                    <div class="report-value" id="totalBooks">Loading...</div>
                </div>
                <div class="report-card">
                    <h3><i class="fas fa-users"></i> Total Users</h3>
                    <div class="report-value" id="totalUsers">Loading...</div>
                </div>
                <div class="report-card">
                    <h3><i class="fas fa-download"></i> Downloads</h3>
                    <div class="report-value" id="totalDownloads">Loading...</div>
                </div>
                <div class="report-card">
                    <h3><i class="fas fa-eye"></i> Views</h3>
                    <div class="report-value" id="totalViews">Loading...</div>
                </div>
            </div>
        </section>

        <!-- Settings Section -->
        <section id="settings-section" class="admin-section">
            <div class="section-header">
                <h2><i class="fas fa-cog"></i> System Settings</h2>
            </div>

            <div class="settings-form">
                <div class="form-group">
                    <label for="siteName">Site Name</label>
                    <input type="text" id="siteName" value="San Roque Elementary School E-Library">
                </div>

                <div class="form-group">
                    <label for="maxDownloads">Max Downloads per User</label>
                    <input type="number" id="maxDownloads" value="10">
                </div>

                <div class="form-group">
                    <label for="enableOffline">Enable Offline Reading</label>
                    <select id="enableOffline">
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="enableTeacherUploads">Allow Teacher Uploads</label>
                    <select id="enableTeacherUploads">
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                </div>

                <button onclick="saveSettings()" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Settings
                </button>
            </div>
        </section>
    </main>

    <!-- Add/Edit Book Modal -->
    <div id="bookModal" class="modal">
        <div class="modal-content large-modal">
            <span class="modal-close" onclick="closeBookModal()">&times;</span>
            <h3 id="bookModalTitle">Add New Book</h3>

            <form id="bookForm" enctype="multipart/form-data">
                <input type="hidden" id="bookId" name="book_id">

                <div class="form-row">
                    <div class="form-group">
                        <label for="bookTitle">Title *</label>
                        <input type="text" id="bookTitle" name="title" required>
                    </div>

                    <div class="form-group">
                        <label for="bookAuthor">Author</label>
                        <input type="text" id="bookAuthor" name="author">
                    </div>
                </div>

                <div class="form-group">
                    <label for="bookDescription">Description</label>
                    <textarea id="bookDescription" name="description" rows="3"></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="bookCategory">Category</label>
                        <select id="bookCategory" name="category">
                            <option value="">Select Category</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="bookSubject">Subject</label>
                        <select id="bookSubject" name="subject">
                            <option value="English">English</option>
                            <option value="Mathematics">Mathematics</option>
                            <option value="Science">Science</option>
                            <option value="Filipino">Filipino</option>
                            <option value="Araling Panlipunan">Araling Panlipunan</option>
                            <option value="MAPEH">MAPEH</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="bookGradeLevel">Grade Level</label>
                        <select id="bookGradeLevel" name="grade_level">
                            <option value="all">All Grades</option>
                            <option value="kindergarten">Kindergarten</option>
                            <option value="grade1">Grade 1</option>
                            <option value="grade2">Grade 2</option>
                            <option value="grade3">Grade 3</option>
                            <option value="grade4">Grade 4</option>
                            <option value="grade5">Grade 5</option>
                            <option value="grade6">Grade 6</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="bookContentType">Content Type</label>
                        <select id="bookContentType" name="content_type">
                            <option value="book">Book</option>
                            <option value="module">Module</option>
                            <option value="lesson">Lesson</option>
                            <option value="reference">Reference</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="bookCover">Cover Image</label>
                        <input type="file" id="bookCover" name="cover_image" accept="image/*">
                    </div>

                    <div class="form-group">
                        <label for="bookFile">Book File (PDF/EPUB) *</label>
                        <input type="file" id="bookFile" name="book_file" accept=".pdf,.epub" required>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" onclick="closeBookModal()" class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Book</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Teacher Modal -->
    <div id="teacherModal" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeTeacherModal()">&times;</span>
            <h3><i class="fas fa-chalkboard-teacher"></i> Add Teacher Account</h3>
            <p style="color: var(--text-gray); margin-bottom: 20px;">Create a new teacher account. Teachers can upload books and modules for students.</p>

            <form id="teacherForm">
                <div class="form-group">
                    <label for="teacherFullName"><i class="fas fa-user"></i> Full Name *</label>
                    <input type="text" id="teacherFullName" name="full_name" required placeholder="e.g., Juan Dela Cruz">
                </div>

                <div class="form-group">
                    <label for="teacherEmail"><i class="fas fa-envelope"></i> Email *</label>
                    <input type="email" id="teacherEmail" name="email" required placeholder="e.g., teacher@sres.edu.ph">
                </div>

                <div class="form-group">
                    <label for="teacherUsername"><i class="fas fa-at"></i> Username *</label>
                    <input type="text" id="teacherUsername" name="username" required placeholder="e.g., jdelacruz">
                </div>

                <div class="form-group">
                    <label for="teacherPassword"><i class="fas fa-lock"></i> Password *</label>
                    <input type="password" id="teacherPassword" name="password" required placeholder="At least 6 characters">
                </div>

                <div class="form-actions">
                    <button type="button" onclick="closeTeacherModal()" class="btn btn-secondary">Cancel</button>
                    <button type="button" onclick="addTeacher()" class="btn btn-success">
                        <i class="fas fa-user-plus"></i> Create Teacher Account
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div id="userModal" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeUserModal()">&times;</span>
            <h3 id="userModalTitle"><i class="fas fa-user-edit"></i> Edit User</h3>
            <p style="color: var(--text-gray); margin-bottom: 20px;">Modify user account information and permissions.</p>

            <form id="userForm">
                <input type="hidden" id="userId" name="user_id">

                <div class="form-group">
                    <label for="userFullName"><i class="fas fa-user"></i> Full Name *</label>
                    <input type="text" id="userFullName" name="full_name" required placeholder="e.g., Juan Dela Cruz">
                </div>

                <div class="form-group">
                    <label for="userEmail"><i class="fas fa-envelope"></i> Email *</label>
                    <input type="email" id="userEmail" name="email" required placeholder="e.g., user@sres.edu.ph">
                </div>

                <div class="form-group">
                    <label for="userUsername"><i class="fas fa-at"></i> Username *</label>
                    <input type="text" id="userUsername" name="username" required placeholder="e.g., jdelacruz">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="userType"><i class="fas fa-user-tag"></i> User Type *</label>
                        <select id="userType" name="user_type" required>
                            <option value="student">Student</option>
                            <option value="teacher">Teacher</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="userGradeLevel"><i class="fas fa-graduation-cap"></i> Grade Level</label>
                        <select id="userGradeLevel" name="grade_level">
                            <option value="n/a">N/A</option>
                            <option value="kindergarten">Kindergarten</option>
                            <option value="grade1">Grade 1</option>
                            <option value="grade2">Grade 2</option>
                            <option value="grade3">Grade 3</option>
                            <option value="grade4">Grade 4</option>
                            <option value="grade5">Grade 5</option>
                            <option value="grade6">Grade 6</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="userPassword"><i class="fas fa-lock"></i> New Password (leave blank to keep current)</label>
                    <input type="password" id="userPassword" name="password" placeholder="At least 6 characters">
                    <small style="color: var(--text-gray); font-size: 0.8rem;">Leave blank to keep the current password</small>
                </div>

                <div class="form-actions">
                    <button type="button" onclick="closeUserModal()" class="btn btn-secondary">Cancel</button>
                    <button type="button" onclick="updateUser()" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update User
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Category Modal -->
    <div id="categoryModal" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeCategoryModal()">&times;</span>
            <h3>Add New Category</h3>

            <form id="categoryForm">
                <div class="form-group">
                    <label for="categoryName">Category Name *</label>
                    <input type="text" id="categoryName" name="category_name" required>
                </div>

                <div class="form-group">
                    <label for="categoryDescription">Description</label>
                    <textarea id="categoryDescription" name="description" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="categoryIcon">Icon Class</label>
                    <input type="text" id="categoryIcon" name="icon" placeholder="fas fa-book">
                </div>

                <div class="form-group">
                    <label for="categoryOrder">Display Order</label>
                    <input type="number" id="categoryOrder" name="display_order" value="0">
                </div>

                <div class="form-actions">
                    <button type="button" onclick="closeCategoryModal()" class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Category</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add/Edit Section Modal -->
    <div id="sectionModal" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeSectionModal()">&times;</span>
            <h3 id="sectionModalTitle"><i class="fas fa-chalkboard"></i> Add New Section</h3>
            <p style="color: var(--text-gray); margin-bottom: 20px;">Create a new class section and assign a teacher to it.</p>

            <form id="sectionForm">
                <input type="hidden" id="sectionId" name="section_id">
                <div class="form-group">
                    <label for="sectionName"><i class="fas fa-tag"></i> Section Name *</label>
                    <input type="text" id="sectionName" name="section_name" required placeholder="e.g., Rose, Sunflower, etc.">
                </div>

                <div class="form-group">
                    <label for="sectionGradeLevel"><i class="fas fa-graduation-cap"></i> Grade Level *</label>
                    <select id="sectionGradeLevel" name="grade_level" required>
                        <option value="">Select Grade Level</option>
                        <option value="kindergarten">Kindergarten</option>
                        <option value="grade1">Grade 1</option>
                        <option value="grade2">Grade 2</option>
                        <option value="grade3">Grade 3</option>
                        <option value="grade4">Grade 4</option>
                        <option value="grade5">Grade 5</option>
                        <option value="grade6">Grade 6</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="sectionTeacher"><i class="fas fa-chalkboard-teacher"></i> Assigned Teacher</label>
                    <select id="sectionTeacher" name="teacher_id">
                        <option value="">Select Teacher (Optional)</option>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="button" onclick="closeSectionModal()" class="btn btn-secondary">Cancel</button>
                    <button type="button" id="sectionSubmitBtn" onclick="addSection()" class="btn btn-success">
                        <i class="fas fa-plus"></i> Create Section
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="../js/admin.js"></script>
</body>
</html>
