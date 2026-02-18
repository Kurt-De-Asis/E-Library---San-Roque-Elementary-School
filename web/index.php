<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#4A90E2">
    <meta name="description" content="San Roque Elementary School E-Library - Digital reading platform for students">
    <title>San Roque Elementary School E-Library</title>
    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" href="assets/logos/school-logo.png">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Navigation Header -->
    <header class="main-header">
        <div class="header-container">
            <div class="header-left">
                <div class="header-logos">
                    <img src="assets/logos/deped-logo.png" alt="DepEd Logo" class="header-logo" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2250%22 height=%2250%22%3E%3Crect fill=%22%234169E1%22 width=%2250%22 height=%2250%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 font-size=%2210%22 fill=%22white%22 text-anchor=%22middle%22 dy=%22.3em%22%3EDepEd%3C/text%3E%3C/svg%3E'">
                    <img src="assets/logos/school-logo.png" alt="School Logo" class="header-logo" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2250%22 height=%2250%22%3E%3Crect fill=%22%23228B22%22 width=%2250%22 height=%2250%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 font-size=%2210%22 fill=%22white%22 text-anchor=%22middle%22 dy=%22.3em%22%3ESRES%3C/text%3E%3C/svg%3E'">
                </div>
                <div class="site-title">
                    <h1>San Roque Elementary School</h1>
                    <p>E-Library System</p>
                </div>
            </div>
            <div class="header-right">
                <div class="user-info" id="userInfo">
                    <span id="userName">Loading...</span>
                    <button onclick="logout()" class="btn btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</button>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Navigation -->
    <nav class="main-nav">
        <div class="nav-container">
            <button class="nav-item active" onclick="showSection('home')">
                <i class="fas fa-home"></i> Home
            </button>
            <button class="nav-item" onclick="showSection('browse')">
                <i class="fas fa-book"></i> Browse Books
            </button>
            <button class="nav-item" onclick="showSection('categories')">
                <i class="fas fa-th"></i> Categories
            </button>
            <button class="nav-item" onclick="showSection('my-books')">
                <i class="fas fa-bookmark"></i> My Books
            </button>
            <button class="nav-item" id="teacherNav" style="display: none;" onclick="location.href='teacher/upload.php'">
                <i class="fas fa-upload"></i> Upload
            </button>
            <button class="nav-item" id="adminNav" style="display: none;" onclick="location.href='admin/dashboard.php?t=' + Date.now()">
                <i class="fas fa-cog"></i> Admin Panel
            </button>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Home Section -->
        <section id="home-section" class="content-section active">
            <div class="welcome-banner">
                <h2>Welcome to the E-Library, <span id="welcomeUser">Student</span>!</h2>
                <p id="gradeLevelInfo">Showing books for your grade level</p>
            </div>

            <!-- Search Bar -->
            <div class="search-container">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Search for books, authors, or subjects..." autocomplete="off">
                    <button onclick="searchBooks()" class="btn btn-primary">Search</button>
                    <div id="searchSuggestions" class="search-suggestions"></div>
                </div>
            </div>

            <!-- Featured Books -->
            <div class="section-header">
                <h3><i class="fas fa-star"></i> Featured Books</h3>
            </div>
            <div id="featuredBooks" class="books-grid">
                <div class="loading">Loading books...</div>
            </div>

            <!-- Recent Additions -->
            <div class="section-header">
                <h3><i class="fas fa-clock"></i> Recently Added</h3>
            </div>
            <div id="recentBooks" class="books-grid">
                <div class="loading">Loading books...</div>
            </div>
        </section>

        <!-- Browse Section -->
        <section id="browse-section" class="content-section">
            <div class="section-header">
                <h2><i class="fas fa-book"></i> Browse All Books</h2>
                <div class="filter-controls">
                    <select id="subjectFilter" onchange="filterBooks()">
                        <option value="">All Subjects</option>
                        <option value="English">English</option>
                        <option value="Mathematics">Mathematics</option>
                        <option value="Science">Science</option>
                        <option value="Filipino">Filipino</option>
                        <option value="Araling Panlipunan">Araling Panlipunan</option>
                        <option value="MAPEH">MAPEH</option>
                    </select>
                    <select id="contentTypeFilter" onchange="filterBooks()">
                        <option value="">All Types</option>
                        <option value="book">Books</option>
                        <option value="module">Modules</option>
                        <option value="lesson">Lessons</option>
                        <option value="reference">Reference</option>
                    </select>
                </div>
            </div>
            <div id="allBooks" class="books-grid">
                <div class="loading">Loading books...</div>
            </div>
        </section>

        <!-- Categories Section -->
        <section id="categories-section" class="content-section">
            <div class="section-header">
                <h2><i class="fas fa-th"></i> Browse by Category</h2>
            </div>
            <div id="categoriesGrid" class="categories-grid">
                <div class="loading">Loading categories...</div>
            </div>
        </section>

        <!-- My Books Section -->
        <section id="my-books-section" class="content-section">
            <div class="section-header">
                <h2><i class="fas fa-bookmark"></i> My Reading History</h2>
            </div>
            <div id="myBooks" class="books-grid">
                <div class="loading">Loading your books...</div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="main-footer">
        <div class="footer-content">
            <p>&copy; 2025 San Roque Elementary School - E-Library System</p>
            <p>Department of Education - Division of San Pedro, Laguna</p>
        </div>
    </footer>

    <!-- Book Details Modal -->
    <div id="bookModal" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeBookModal()">&times;</span>
            <div id="bookDetails"></div>
        </div>
    </div>

    <script src="js/main.js"></script>
    <script>
        // Register Service Worker for offline support
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('sw.js')
                    .then(registration => {
                        console.log('Service Worker registered:', registration.scope);
                    })
                    .catch(error => {
                        console.log('Service Worker registration failed:', error);
                    });
            });
        }
        

    </script>
</body>
</html>
