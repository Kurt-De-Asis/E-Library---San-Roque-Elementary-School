<?php
require_once '../api/config.php';

// Prevent browser caching
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

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
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <meta name="theme-color" content="#4A90E2">
    <title>Upload Book - San Roque Elementary School E-Library</title>
    <link rel="icon" type="image/png" href="../assets/logos/school-logo.png?v=<?php echo CACHE_BUSTER; ?>">
    <link rel="shortcut icon" href="../assets/logos/school-logo.png?v=<?php echo CACHE_BUSTER; ?>">
    <link rel="stylesheet" href="../css/style.css?v=<?php echo CACHE_BUSTER; ?>">
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

    <!-- Type Selection -->
    <div id="uploadTypeSelection" class="upload-type-selection">
        <div class="selection-container">
            <h2><i class="fas fa-upload"></i> What would you like to upload?</h2>
            <p>Select the type of material you want to share with your students.</p>
            
            <div class="type-cards">
                <div class="type-card" onclick="selectUploadType('book')">
                    <div class="type-icon"><i class="fas fa-book-open"></i></div>
                    <h3>Book / Module</h3>
                    <p>Upload PDF or EPUB educational books and modules.</p>
                </div>
                
                <div class="type-card" onclick="selectUploadType('lesson')">
                    <div class="type-icon"><i class="fas fa-file-powerpoint"></i></div>
                    <h3>Lesson Plan / PPT</h3>
                    <p>Share your presentation slides (PPT/PPTX) and lesson plans.</p>
                </div>
                
                <div class="type-card" onclick="selectUploadType('video')">
                    <div class="type-icon"><i class="fas fa-video"></i></div>
                    <h3>Educational Video</h3>
                    <p>Upload MP4 or WebM videos for visual learning.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Upload Form -->
    <main class="upload-content" id="uploadFormContainer" style="display: none;">
        <div class="upload-container">
            <div class="upload-header">
                <button onclick="showTypeSelection()" class="btn btn-secondary btn-sm" style="margin-bottom: 1rem;">
                    <i class="fas fa-arrow-left"></i> Change Type
                </button>
                <h2 id="formTitle"><i class="fas fa-upload"></i> Upload New Material</h2>
                <p>Share educational materials with your students. Your uploads will be automatically available for students to view.</p>
            </div>

            <form id="uploadForm" enctype="multipart/form-data">
                <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo MAX_FILE_SIZE; ?>">
                <input type="hidden" id="selectedContentType" name="content_type" value="book">
                
                <?php if (IS_LOCALHOST): ?>
                <div style="background: #fff3cd; color: #856404; padding: 1.25rem; border-radius: 8px; margin-bottom: 2rem; font-size: 0.95rem; border: 1px solid #ffeeba; line-height: 1.6;">
                    <i class="fas fa-exclamation-triangle" style="font-size: 1.2rem; margin-right: 0.5rem;"></i> <strong>Action Required for Large Uploads:</strong><br>
                    You are using <strong>mod_fcgid (FastCGI)</strong> in WAMP. For videos to upload successfully, you MUST:
                    <ul style="margin-top: 0.5rem; padding-left: 1.5rem;">
                        <li>Open your <code>httpd-fcgid.conf</code> (found in <code>bin\apache\apacheX.X\conf\extra\</code>)</li>
                        <li>Find <code>FcgidMaxRequestLen</code> and change it to <code>524288000</code></li>
                        <li>Restart all services in WAMP</li>
                    </ul>
                </div>
                <?php endif; ?>

                <div class="form-section">
                    <h3 id="infoSectionTitle">Book Information</h3>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="bookTitle" id="labelTitle">Title *</label>
                            <input type="text" id="bookTitle" name="title" required placeholder="Enter book title">
                        </div>

                        <div class="form-group">
                            <label for="bookAuthor" id="labelAuthor">Author</label>
                            <input type="text" id="bookAuthor" name="author" placeholder="Enter author name">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="bookDescription" id="labelDescription">Description</label>
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
                        <div class="form-group" id="gradeLevelGroup">
                            <label for="bookGradeLevel" id="labelGradeLevel">Target Grade Level</label>
                            <select id="bookGradeLevel" name="grade_level" onchange="loadSections()">
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

                        <div class="form-group" id="sectionGroup">
                            <label for="bookSection" id="labelSection">Target Section (Optional)</label>
                            <select id="bookSection" name="section_id">
                                <option value="">All Sections</option>
                            </select>
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
                            <label for="bookFile">File *</label>
                            <input type="file" id="bookFile" name="book_file" accept=".pdf,.epub,.ppt,.pptx,.mp4,.webm" required>
                            <small class="form-hint">Required. Upload PDF, EPUB, PPT, or Video (MP4/WebM)</small>
                        </div>
                    </div>
                </div>

                <div class="upload-progress-container" id="uploadProgressContainer">
                    <div class="progress-label">
                        <span>Uploading...</span>
                        <span id="progressPercent">0%</span>
                    </div>
                    <div class="progress-bar-bg">
                        <div class="progress-bar-fill" id="progressBarFill"></div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" onclick="window.location.href='../index.php'" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" id="submitBtn" class="btn btn-primary">
                        <i class="fas fa-upload"></i> Upload Material
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
        const MAX_UPLOAD_SIZE = <?php echo MAX_FILE_SIZE; ?>;
        
        // Load user info and categories on page load
        document.addEventListener('DOMContentLoaded', async function() {
            await loadUserInfo();
            await loadCategories();
            await loadSections();
            initializeForm();
        });

        // Load sections for the selected grade level
        async function loadSections() {
            const gradeLevel = document.getElementById('bookGradeLevel').value;
            const sectionSelect = document.getElementById('bookSection');
            
            try {
                // If 'all' is selected, get all sections
                let url = '../api/ebooks.php?action=get_sections';
                if (gradeLevel !== 'all') {
                    url += `&grade_level=${gradeLevel}`;
                }
                
                const response = await fetch(url);
                const result = await response.json();
                
                sectionSelect.innerHTML = '<option value="">All Sections</option>';
                
                if (result.success) {
                    result.sections.forEach(section => {
                        const option = document.createElement('option');
                        option.value = section.section_id;
                        option.textContent = (gradeLevel === 'all') ? `${section.grade_level.charAt(0).toUpperCase() + section.grade_level.slice(1)} - ${section.section_name}` : section.section_name;
                        sectionSelect.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('Error loading sections:', error);
            }
        }

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

        // Select upload type
        function selectUploadType(type) {
            const selection = document.getElementById('uploadTypeSelection');
            const formContainer = document.getElementById('uploadFormContainer');
            const contentInput = document.getElementById('selectedContentType');
            const fileInput = document.getElementById('bookFile');
            const fileHint = fileInput.nextElementSibling;
            const formTitle = document.getElementById('formTitle');
            const gradeLevelGroup = document.getElementById('gradeLevelGroup');
            const sectionGroup = document.getElementById('sectionGroup');
            
            // Elements to change based on type
            const infoSectionTitle = document.getElementById('infoSectionTitle');
            const labelTitle = document.getElementById('labelTitle');
            const bookTitle = document.getElementById('bookTitle');
            const labelAuthor = document.getElementById('labelAuthor');
            const bookAuthor = document.getElementById('bookAuthor');
            const labelDescription = document.getElementById('labelDescription');
            const bookDescription = document.getElementById('bookDescription');
            const submitBtn = document.getElementById('submitBtn');

            contentInput.value = type;
            selection.style.display = 'none';
            formContainer.style.display = 'block';

            // Customize based on type
            if (type === 'book') {
                formTitle.innerHTML = '<i class="fas fa-book-open"></i> Upload Book / Module';
                infoSectionTitle.textContent = 'Book Information';
                labelTitle.textContent = 'Book Title *';
                bookTitle.placeholder = 'e.g. Mathematics for Grade 1';
                labelAuthor.textContent = 'Author / Publisher';
                bookAuthor.placeholder = 'e.g. DepEd';
                labelDescription.textContent = 'Book Description';
                bookDescription.placeholder = 'Brief summary of the book content';
                submitBtn.innerHTML = '<i class="fas fa-upload"></i> Upload Book';
                
                fileInput.accept = '.pdf,.epub';
                fileHint.textContent = 'Required. Upload PDF or EPUB file';
                gradeLevelGroup.style.display = 'block';
                sectionGroup.style.display = 'block';
            } else if (type === 'lesson') {
                formTitle.innerHTML = '<i class="fas fa-file-powerpoint"></i> Upload Lesson Plan / PPT';
                infoSectionTitle.textContent = 'Lesson Information';
                labelTitle.textContent = 'Lesson / PPT Title *';
                bookTitle.placeholder = 'e.g. Introduction to Fractions';
                labelAuthor.textContent = 'Created By (Teacher Name)';
                bookAuthor.placeholder = 'e.g. Mrs. Smith';
                labelDescription.textContent = 'Lesson Objectives / Description';
                bookDescription.placeholder = 'What will students learn from this presentation?';
                submitBtn.innerHTML = '<i class="fas fa-upload"></i> Upload Lesson';

                fileInput.accept = '.ppt,.pptx,.pdf';
                fileHint.textContent = 'Required. Upload PPT, PPTX or PDF file';
                gradeLevelGroup.style.display = 'block';
                sectionGroup.style.display = 'block';
            } else if (type === 'video') {
                formTitle.innerHTML = '<i class="fas fa-video"></i> Upload Educational Video';
                infoSectionTitle.textContent = 'Video Information';
                labelTitle.textContent = 'Video Title *';
                bookTitle.placeholder = 'e.g. Science Experiment: Water Cycle';
                labelAuthor.textContent = 'Presenter / Source';
                bookAuthor.placeholder = 'e.g. National Geographic Kids';
                labelDescription.textContent = 'Video Summary';
                bookDescription.placeholder = 'Provide a short description of what the video is about';
                submitBtn.innerHTML = '<i class="fas fa-upload"></i> Upload Video';

                fileInput.accept = '.mp4,.webm';
                fileHint.textContent = 'Required. Upload MP4 or WebM video';
                gradeLevelGroup.style.display = 'block';
                sectionGroup.style.display = 'block';
            }
            
            // Scroll to top of form
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // Show type selection
        function showTypeSelection() {
            document.getElementById('uploadTypeSelection').style.display = 'block';
            document.getElementById('uploadFormContainer').style.display = 'none';
            document.getElementById('uploadProgressContainer').style.display = 'none';
            document.getElementById('uploadForm').reset();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // Handle form submission
        async function handleUpload(e) {
            e.preventDefault();

            const fileInput = document.getElementById('bookFile');
            const submitButton = document.getElementById('submitBtn');
            const originalText = submitButton.innerHTML;
            const contentType = document.getElementById('selectedContentType').value;
            const progressContainer = document.getElementById('uploadProgressContainer');
            const progressFill = document.getElementById('progressBarFill');
            const progressPercent = document.getElementById('progressPercent');

            // Client-side validation
            if (!fileInput.files || fileInput.files.length === 0) {
                showMessage('Please select a file to upload', 'error');
                return;
            }

            const file = fileInput.files[0];
            if (file.size > MAX_UPLOAD_SIZE) {
                showMessage(`File is too large (${(file.size / (1024 * 1024)).toFixed(2)}MB). Maximum allowed size is ${MAX_UPLOAD_SIZE / (1024 * 1024)}MB.`, 'error');
                return;
            }

            // Show loading and progress
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            progressContainer.style.display = 'block';
            progressFill.style.width = '0%';
            progressPercent.textContent = '0%';

            try {
                const formData = new FormData(e.target);
                
                // Use XMLHttpRequest for progress tracking
                const xhr = new XMLHttpRequest();
                xhr.open('POST', '../api/ebooks.php?action=upload_book', true);

                xhr.upload.onprogress = function(event) {
                    if (event.lengthComputable) {
                        const percent = Math.round((event.loaded / event.total) * 100);
                        progressFill.style.width = percent + '%';
                        progressPercent.textContent = percent + '%';
                        if (percent === 100) {
                            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving to Database...';
                        } else {
                            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading (' + percent + '%)...';
                        }
                    }
                };

                xhr.onload = function() {
                    let result;
                    try {
                        result = JSON.parse(xhr.responseText);
                    } catch (err) {
                        console.error('Server Response:', xhr.responseText);
                        
                        // Extract PHP error if possible
                        let errorDetail = '';
                        if (xhr.responseText.includes('Fatal error') || xhr.responseText.includes('Parse error')) {
                            const match = xhr.responseText.match(/(?:Fatal|Parse) error: (.*?) in /);
                            if (match) errorDetail = ': ' + match[1];
                        }
                        
                        if (xhr.status === 413) {
                            showMessage('File is too large for the server configuration (413 Payload Too Large).', 'error');
                        } else if (xhr.status === 500) {
                            showMessage('Internal Server Error (500). The server encountered an issue processing the file' + errorDetail, 'error');
                        } else {
                            showMessage('Server returned an invalid response. This often means the file is too large or there is a database error.', 'error');
                        }
                        return;
                    }

                    if (xhr.status === 200 && result.success) {
                        const typeName = contentType === 'video' ? 'Video' : (contentType === 'lesson' ? 'Lesson Plan' : 'Book');
                        showMessage(typeName + ' uploaded successfully! It is now available for students.', 'success');
                        e.target.reset();
                        setTimeout(showTypeSelection, 2000);
                    } else {
                        showMessage(result.message || 'Failed to upload material', 'error');
                    }
                };

                xhr.onerror = function() {
                    console.error('XHR Network Error');
                    showMessage('Network error or server connection closed. This usually happens if the file is too large for PHP settings.', 'error');
                };

                xhr.send(formData);

            } catch (error) {
                console.error('Upload error:', error);
                showMessage('An error occurred. Please try again.', 'error');
                submitButton.disabled = false;
                submitButton.innerHTML = originalText;
            } finally {
                // Keep button disabled until we get a result
            }
        }

        // Show message
        function showMessage(message, type = 'info') {
            const container = document.getElementById('messageContainer');
            const content = document.getElementById('messageContent');

            content.textContent = message;
            content.className = `message ${type}`;
            container.style.display = 'block';

            // Auto hide after 8 seconds for large files
            setTimeout(() => {
                container.style.display = 'none';
            }, 8000);
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
