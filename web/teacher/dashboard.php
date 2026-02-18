<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'teacher') {
    header('Location: ../index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#4A90E2">
    <title>Teacher Dashboard - San Roque Elementary School E-Library</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="teacher-dashboard">
    <!-- Header -->
    <header class="main-header">
        <div class="header-container">
            <div class="header-left">
                <div class="header-logos">
                    <img src="../assets/logos/deped-logo.png" alt="DepEd Logo" class="header-logo" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2250%22 height=%2250%22%3E%3Crect fill=%22%234169E1%22 width=%2250%22 height=%2250%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 font-size=%2210%22 fill=%22white%22 text-anchor=%22middle%22 dy=%22.3em%22%3EDepEd%3C/text%3E%3C/svg%3E'">
                    <img src="../assets/logos/school-logo.png" alt="School Logo" class="header-logo" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2250%22 height=%2250%22%3E%3Crect fill=%22%23228B22%22 width=%2250%22 height=%2250%22%3E%3Crect fill=%22%23228B22%22 width=%2250%22 height=%2250%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 font-size=%2210%22 fill=%22white%22 text-anchor=%22middle%22 dy=%22.3em%22%3ESRES%3C/text%3E%3C/svg%3E'">
                </div>
                <div class="site-title">
                    <h1>San Roque Elementary School</h1>
                    <p>E-Library System</p>
                </div>
            </div>
            <div class="header-right">
                <div class="user-info">
                    <span id="userName">Loading...</span>
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

    <!-- Teacher Navigation -->
    <nav class="main-nav">
        <div class="nav-container">
            <button class="nav-item active" onclick="showTeacherSection('overview')">
                <i class="fas fa-tachometer-alt"></i> Overview
            </button>
            <button class="nav-item" onclick="showTeacherSection('students')">
                <i class="fas fa-users"></i> My Students
            </button>
            <button class="nav-item" onclick="showTeacherSection('sections')">
                <i class="fas fa-chalkboard"></i> My Sections
            </button>
            <button class="nav-item" onclick="showTeacherSection('uploads')">
                <i class="fas fa-upload"></i> My Uploads
            </button>
            <button class="nav-item" onclick="window.location.href='upload.php'">
                <i class="fas fa-plus"></i> Upload Book
            </button>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Overview Section -->
        <section id="overview-section" class="content-section active">
            <div class="welcome-banner">
                <h2>Welcome back, <span id="teacherName">Teacher</span>!</h2>
                <p>Manage your students and educational materials</p>
            </div>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <h3 id="totalStudents">0</h3>
                        <p>Total Students</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-chalkboard"></i>
                    </div>
                    <div class="stat-content">
                        <h3 id="totalSections">0</h3>
                        <p>My Sections</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="stat-content">
                        <h3 id="totalUploads">0</h3>
                        <p>My Uploads</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <h3 id="pendingApprovals">0</h3>
                        <p>Pending Approval</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Students Section -->
        <section id="students-section" class="content-section">
            <div class="section-header">
                <h2><i class="fas fa-users"></i> My Students</h2>
                <div class="filter-controls">
                    <select id="sectionFilter" onchange="loadStudents()">
                        <option value="">All Sections</option>
                    </select>
                </div>
            </div>

            <div class="data-table">
                <table id="studentsTable">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Grade Level</th>
                            <th>Section</th>
                            <th>Last Login</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="studentsTableBody">
                        <tr>
                            <td colspan="6" class="loading">Loading students...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Sections Section -->
        <section id="sections-section" class="content-section">
            <div class="section-header">
                <h2><i class="fas fa-chalkboard"></i> My Sections</h2>
            </div>

            <div class="sections-grid" id="sectionsGrid">
                <div class="loading">Loading sections...</div>
            </div>
        </section>

        <!-- Uploads Section -->
        <section id="uploads-section" class="content-section">
            <div class="section-header">
                <h2><i class="fas fa-upload"></i> My Uploads</h2>
                <button onclick="window.location.href='upload.php'" class="btn btn-success">
                    <i class="fas fa-plus"></i> Upload New Book
                </button>
            </div>

            <div class="data-table">
                <table id="uploadsTable">
                    <thead>
                        <tr>
                            <th>Cover</th>
                            <th>Title</th>
                            <th>Subject</th>
                            <th>Grade Level</th>
                            <th>Section</th>
                            <th>Status</th>
                            <th>Upload Date</th>
                        </tr>
                    </thead>
                    <tbody id="uploadsTableBody">
                        <tr>
                            <td colspan="7" class="loading">Loading uploads...</td>
                        </tr>
                    </tbody>
                </table>
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

    <script>
        let currentTeacherSection = 'overview';

        // DOM Content Loaded
        document.addEventListener('DOMContentLoaded', async function() {
            await loadTeacherInfo();
            await loadOverviewStats();
            initializeTeacherDashboard();
        });

        // Load teacher information
        async function loadTeacherInfo() {
            try {
                const response = await fetch('../api/auth.php?action=check-session');
                const result = await response.json();

                if (result.logged_in) {
                    document.getElementById('userName').textContent = result.user.full_name;
                    document.getElementById('teacherName').textContent = result.user.full_name.split(' ')[0];
                }
            } catch (error) {
                console.error('Error loading teacher info:', error);
            }
        }

        // Load overview statistics
        async function loadOverviewStats() {
            try {
                const response = await fetch('../api/teacher.php?action=get_stats');
                const result = await response.json();

                if (result.success) {
                    document.getElementById('totalStudents').textContent = result.stats.total_students || 0;
                    document.getElementById('totalSections').textContent = result.stats.total_sections || 0;
                    document.getElementById('totalUploads').textContent = result.stats.total_uploads || 0;
                    document.getElementById('pendingApprovals').textContent = result.stats.pending_approvals || 0;
                }
            } catch (error) {
                console.error('Error loading stats:', error);
            }
        }

        // Initialize teacher dashboard
        function initializeTeacherDashboard() {
            // Initialize navigation
            const navItems = document.querySelectorAll('.main-nav .nav-item');
            navItems.forEach(item => {
                item.addEventListener('click', function() {
                    const section = this.getAttribute('onclick')?.match(/showTeacherSection\('(.+?)'\)/)?.[1];
                    if (section) {
                        showTeacherSection(section);
                    }
                });
            });
        }

        // Show teacher section
        function showTeacherSection(sectionName) {
            // Update navigation
            document.querySelectorAll('.main-nav .nav-item').forEach(item => {
                item.classList.remove('active');
            });

            const activeNav = document.querySelector(`[onclick="showTeacherSection('${sectionName}')"]`);
            if (activeNav) {
                activeNav.classList.add('active');
            }

            // Update content sections
            document.querySelectorAll('.content-section').forEach(section => {
                section.classList.remove('active');
            });

            const activeSection = document.getElementById(`${sectionName}-section`);
            if (activeSection) {
                activeSection.classList.add('active');
            }

            currentTeacherSection = sectionName;

            // Load section data
            loadTeacherSectionData(sectionName);
        }

        // Load teacher section data
        async function loadTeacherSectionData(section) {
            try {
                switch (section) {
                    case 'students':
                        await loadStudents();
                        break;
                    case 'sections':
                        await loadTeacherSections();
                        break;
                    case 'uploads':
                        await loadTeacherUploads();
                        break;
                }
            } catch (error) {
                console.error(`Error loading ${section} data:`, error);
            }
        }

        // Load students
        async function loadStudents() {
            const tableBody = document.getElementById('studentsTableBody');
            if (!tableBody) return;

            tableBody.innerHTML = '<tr><td colspan="6" class="loading">Loading students...</td></tr>';

            try {
                const response = await fetch('../api/teacher.php?action=get_students');
                const result = await response.json();

                if (result.success) {
                    renderStudentsTable(tableBody, result.students);
                } else {
                    tableBody.innerHTML = '<tr><td colspan="6" class="loading">No students found.</td></tr>';
                }
            } catch (error) {
                console.error('Error loading students:', error);
                tableBody.innerHTML = '<tr><td colspan="6" class="loading">Error loading students.</td></tr>';
            }
        }

        // Render students table
        function renderStudentsTable(tableBody, students) {
            tableBody.innerHTML = '';

            if (!students || students.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="6" class="loading">No students found.</td></tr>';
                return;
            }

            students.forEach(student => {
                const row = document.createElement('tr');

                const gradeText = student.grade_level === 'n/a' ? 'N/A' :
                    student.grade_level.charAt(0).toUpperCase() + student.grade_level.slice(1);

                const lastLogin = student.last_login ? new Date(student.last_login).toLocaleDateString() : 'Never';
                const statusClass = student.is_active ? 'active' : 'inactive';
                const statusText = student.is_active ? 'Active' : 'Inactive';

                row.innerHTML = `
                    <td>${student.full_name}</td>
                    <td>${student.username}</td>
                    <td>${gradeText}</td>
                    <td>${student.section_name || 'Not assigned'}</td>
                    <td>${lastLogin}</td>
                    <td><span class="status ${statusClass}">${statusText}</span></td>
                `;

                tableBody.appendChild(row);
            });
        }

        // Load teacher sections
        async function loadTeacherSections() {
            const grid = document.getElementById('sectionsGrid');
            if (!grid) return;

            grid.innerHTML = '<div class="loading">Loading sections...</div>';

            try {
                const response = await fetch('../api/teacher.php?action=get_my_sections');
                const result = await response.json();

                if (result.success) {
                    renderSectionsGrid(grid, result.sections);
                } else {
                    grid.innerHTML = '<div class="loading">No sections found.</div>';
                }
            } catch (error) {
                console.error('Error loading sections:', error);
                grid.innerHTML = '<div class="loading">Error loading sections.</div>';
            }
        }

        // Render sections grid
        function renderSectionsGrid(grid, sections) {
            grid.innerHTML = '';

            if (!sections || sections.length === 0) {
                grid.innerHTML = '<div class="loading">No sections assigned.</div>';
                return;
            }

            sections.forEach(section => {
                const card = document.createElement('div');
                card.className = 'section-card';

                const gradeText = section.grade_level.charAt(0).toUpperCase() + section.grade_level.slice(1);

                card.innerHTML = `
                    <div class="section-header">
                        <h3>${section.section_name}</h3>
                        <span class="grade-badge">${gradeText}</span>
                    </div>
                    <div class="section-stats">
                        <div class="stat">
                            <span class="stat-number">${section.student_count || 0}</span>
                            <span class="stat-label">Students</span>
                        </div>
                    </div>
                `;

                grid.appendChild(card);
            });
        }

        // Load teacher uploads
        async function loadTeacherUploads() {
            const tableBody = document.getElementById('uploadsTableBody');
            if (!tableBody) return;

            tableBody.innerHTML = '<tr><td colspan="7" class="loading">Loading uploads...</td></tr>';

            try {
                const response = await fetch('../api/teacher.php?action=get_my_uploads');
                const result = await response.json();

                if (result.success) {
                    renderUploadsTable(tableBody, result.uploads);
                } else {
                    tableBody.innerHTML = '<tr><td colspan="7" class="loading">No uploads found.</td></tr>';
                }
            } catch (error) {
                console.error('Error loading uploads:', error);
                tableBody.innerHTML = '<tr><td colspan="7" class="loading">Error loading uploads.</td></tr>';
            }
        }

        // Render uploads table
        function renderUploadsTable(tableBody, uploads) {
            tableBody.innerHTML = '';

            if (!uploads || uploads.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="7" class="loading">No uploads found.</td></tr>';
                return;
            }

            uploads.forEach(upload => {
                const row = document.createElement('tr');

                const coverUrl = upload.cover_image ?
                    `../uploads/covers/${upload.cover_image}` :
                    '../assets/images/default-book.png';

                const gradeText = upload.grade_level === 'all' ? 'All Grades' :
                    upload.grade_level.charAt(0).toUpperCase() + upload.grade_level.slice(1);

                const statusClass = upload.is_approved ? 'approved' : 'pending';
                const statusText = upload.is_approved ? 'Approved' : 'Pending';

                const uploadDate = new Date(upload.created_at).toLocaleDateString();

                row.innerHTML = `
                    <td><img src="${coverUrl}" alt="${upload.title}" class="table-cover" onerror="this.src='../assets/images/default-book.png'"></td>
                    <td>${upload.title}</td>
                    <td>${upload.subject || 'N/A'}</td>
                    <td>${gradeText}</td>
                    <td>${upload.section_name || 'All Sections'}</td>
                    <td><span class="status ${statusClass}">${statusText}</span></td>
                    <td>${uploadDate}</td>
                `;

                tableBody.appendChild(row);
            });
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
    </script>
</body>
</html>
