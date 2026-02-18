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
    <title>Upload Book - San Roque Elementary School E-Library</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="upload-page">
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

    <!-- Upload Form -->
    <main class="upload-content">
        <div class="upload-container">
            <div class="upload-header">
                <h2><i class="fas fa-upload"></i> Upload New Book</h2>
                <p>Share educational materials with your students. All uploads require admin approval before becoming available.</p>
            </div>

            <form id="uploadForm" enctype="multipart/form-data">
                <div class="form-section">
                    <h3>Book Information</h3>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="bookTitle">Title *</label>
                            <input type="text" id="bookTitle" name="title" required placeholder="Enter book title">
                        </div>

                        <div class="form-group">
                            <label for="bookAuthor">Author</label>
                            <input type="text" id="bookAuthor" name="author" placeholder="Enter author name">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="bookDescription">Description</label>
                        <textarea id="bookDescription" name="description" rows="3" placeholder="Brief description of the book content"></textarea>
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
                            <label for="bookSection">Section (Optional)</label>
                            <select id="bookSection" name="section_id">
                                <option value="">All Sections</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="bookContentType">Content Type</label>
                            <select id="bookContentType" name="content_type">
                                <option value="book">Book</option>
                                <option value="module">Module</option>
                                <option value="lesson">Lesson</option>
                                <option value="reference">Reference</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <!-- Empty for spacing -->
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3>File Upload</h3>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="bookCover">Cover Image</label>
                            <input type="file" id="bookCover" name="cover_image" accept="image/*">
                            <small class="form-hint">Optional. Upload a cover image (JPG, PNG, GIF)</small>
                        </div>

                        <div class="form-group">
                            <label for="bookFile">Book File *</label>
                            <input type="file" id="bookFile" name="book_file" accept=".pdf,.epub" required>
                            <small class="form-hint">Required. Upload PDF or EPUB file</small>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" onclick="window.location.href='../index.php'" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload"></i> Upload Book
                    </button>
                </div>
            </form>
        </div>
    </main>

    <!-- Success/Error Messages -->
    <div id="messageContainer" class="message-container" style="display: none;">
        <div id="messageContent" class="message"></div>
    </div>

    <script>
        // Load user info and categories on page load
        document.addEventListener('DOMContentLoaded', async function() {
            await loadUserInfo();
            await loadCategories();
            initializeForm();
        });

        // Load user information
        async function loadUserInfo() {
            try {
                const response = await fetch('../api/auth.php?action=check-session');
                const result = await response.json();

                if (result.logged_in) {
                    document.getElementById('userName').textContent = result.user.full_name;
                }
            } catch (error) {
                console.error('Error loading user info:', error);
            }
        }

        // Load categories for dropdown
        async function loadCategories() {
            try {
                const response = await fetch('../api/ebooks.php?action=get_categories');
                const result = await response.json();

                if (result.success) {
                    const categorySelect = document.getElementById('bookCategory');
                    result.categories.forEach(category => {
                        const option = document.createElement('option');
                        option.value = category.category_name;
                        option.textContent = category.category_name;
                        categorySelect.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('Error loading categories:', error);
            }
        }

        // Initialize form
        function initializeForm() {
            const form = document.getElementById('uploadForm');
            form.addEventListener('submit', handleUpload);
        }

        // Handle form submission
        async function handleUpload(e) {
            e.preventDefault();

            const submitButton = e.target.querySelector('button[type="submit"]');
            const originalText = submitButton.innerHTML;

            // Show loading
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';

            try {
                const formData = new FormData(e.target);

                const response = await fetch('../api/ebooks.php?action=upload_book', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showMessage('Book uploaded successfully! It will be reviewed by an administrator before becoming available.', 'success');
                    // Reset form
                    e.target.reset();
                } else {
                    showMessage(result.message || 'Failed to upload book', 'error');
                }
            } catch (error) {
                console.error('Upload error:', error);
                showMessage('An error occurred. Please try again.', 'error');
            } finally {
                submitButton.disabled = false;
                submitButton.innerHTML = originalText;
            }
        }

        // Show message
        function showMessage(message, type = 'info') {
            const container = document.getElementById('messageContainer');
            const content = document.getElementById('messageContent');

            content.textContent = message;
            content.className = `message ${type}`;
            container.style.display = 'block';

            // Auto hide after 5 seconds
            setTimeout(() => {
                container.style.display = 'none';
            }, 5000);
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
