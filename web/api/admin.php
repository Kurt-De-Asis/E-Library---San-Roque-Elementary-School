<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in output
ini_set('log_errors', 1);
ini_set('error_log', '../logs/php_errors.log');

// Start session explicitly
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';

header('Content-Type: application/json');

// Check if user is admin
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Session not started']);
    exit;
}

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

if ($_SESSION['user_type'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access - not admin']);
    exit;
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'get_books':
        getBooks();
        break;
    case 'get_users':
        getUsers();
        break;
    case 'get_sections':
        getSections();
        break;
    case 'get_teachers':
        getTeachers();
        break;
    case 'get_categories':
        getCategories();
        break;
    case 'get_reports':
        getReports();
        break;
    case 'get_settings':
        getSettings();
        break;
    case 'add_book':
        addBook();
        break;
    case 'update_book':
        updateBook();
        break;
    case 'approve_book':
        approveBook();
        break;
    case 'delete_book':
        deleteBook();
        break;
    case 'add_section':
        addSection();
        break;
    case 'delete_section':
        deleteSection();
        break;
    case 'add_category':
        addCategory();
        break;
    case 'save_settings':
        saveSettings();
        break;
    case 'add_teacher':
        addTeacher();
        break;
    case 'update_user_status':
        updateUserStatus();
        break;
    case 'delete_user':
        deleteUser();
        break;
    case 'get_user':
        getUser();
        break;
    case 'update_user':
        updateUser();
        break;
    case 'get_sections_by_grade':
        getSectionsByGrade();
        break;
    case 'get_section':
        getSection();
        break;
    case 'update_section':
        updateSection();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function getBooks() {
    $conn = getDBConnection();
    if (!$conn) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        return;
    }

    $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
    
    $sql = "SELECT * FROM ebooks";
    
    switch ($filter) {
        case 'approved':
            $sql .= " WHERE is_approved = 1";
            break;
        case 'pending':
            $sql .= " WHERE is_approved = 0";
            break;
        case 'featured':
            $sql .= " WHERE is_featured = 1";
            break;
    }
    
    $sql .= " ORDER BY created_at DESC";
    
    $result = $conn->query($sql);
    $books = [];
    
    while ($row = $result->fetch_assoc()) {
        $books[] = $row;
    }
    
    echo json_encode(['success' => true, 'books' => $books]);
    $conn->close();
}

function getUsers() {
    $conn = getDBConnection();
    if (!$conn) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        return;
    }

    $result = $conn->query("SELECT user_id, username, email, full_name, user_type, grade_level, is_active, last_login, created_at FROM users ORDER BY created_at DESC");
    $users = [];
    
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    
    echo json_encode(['success' => true, 'users' => $users]);
    $conn->close();
}

function getCategories() {
    $conn = getDBConnection();
    if (!$conn) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        return;
    }

    $result = $conn->query("SELECT * FROM categories ORDER BY display_order ASC");
    $categories = [];
    
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
    
    echo json_encode(['success' => true, 'categories' => $categories]);
    $conn->close();
}

function getReports() {
    $conn = getDBConnection();
    if (!$conn) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        return;
    }

    $reports = [];

    // Total books
    $result = $conn->query("SELECT COUNT(*) as count FROM ebooks");
    if ($result) {
        $reports['total_books'] = $result->fetch_assoc()['count'];
    } else {
        $reports['total_books'] = 0;
    }

    // Total users
    $result = $conn->query("SELECT COUNT(*) as count FROM users");
    if ($result) {
        $reports['total_users'] = $result->fetch_assoc()['count'];
    } else {
        $reports['total_users'] = 0;
    }

    // Total downloads (from reading_history or use 0 if table doesn't exist)
    $result = $conn->query("SELECT COUNT(*) as count FROM reading_history WHERE action = 'download'");
    if ($result) {
        $reports['total_downloads'] = $result->fetch_assoc()['count'];
    } else {
        $reports['total_downloads'] = 0;
    }

    // Total views
    $result = $conn->query("SELECT COUNT(*) as count FROM reading_history WHERE action = 'view'");
    if ($result) {
        $reports['total_views'] = $result ? $result->fetch_assoc()['count'] : 0;
    } else {
        $reports['total_views'] = 0;
    }

    echo json_encode(['success' => true, 'reports' => $reports]);
    $conn->close();
}

function getSettings() {
    $conn = getDBConnection();
    if (!$conn) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        return;
    }

    $result = $conn->query("SELECT * FROM settings");
    $settings = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $settings[] = $row;
        }
    }
    
    echo json_encode(['success' => true, 'settings' => $settings]);
    $conn->close();
}

function addBook() {
    $conn = getDBConnection();
    if (!$conn) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        return;
    }

    $title = sanitizeInput($_POST['title'] ?? '');
    $author = sanitizeInput($_POST['author'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    $category = sanitizeInput($_POST['category'] ?? '');
    $subject = sanitizeInput($_POST['subject'] ?? '');
    $grade_level = sanitizeInput($_POST['grade_level'] ?? 'all');
    $content_type = sanitizeInput($_POST['content_type'] ?? 'book');
    
    if (empty($title)) {
        echo json_encode(['success' => false, 'message' => 'Title is required']);
        return;
    }

    // Handle file uploads
    $cover_image = '';
    $file_path = '';
    
    // Cover image upload
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
        $cover_image = generateUniqueFileName($_FILES['cover_image']['name']);
        move_uploaded_file($_FILES['cover_image']['tmp_name'], COVERS_PATH . $cover_image);
    }
    
    // Book file upload
    if (isset($_FILES['book_file']) && $_FILES['book_file']['error'] === UPLOAD_ERR_OK) {
        $file_path = generateUniqueFileName($_FILES['book_file']['name']);
        move_uploaded_file($_FILES['book_file']['tmp_name'], BOOKS_PATH . $file_path);
    }

    $stmt = $conn->prepare("INSERT INTO ebooks (title, author, description, category, subject, grade_level, content_type, cover_image, file_path, uploaded_by, is_approved) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
    $uploaded_by = $_SESSION['user_id'];
    $stmt->bind_param("sssssssssi", $title, $author, $description, $category, $subject, $grade_level, $content_type, $cover_image, $file_path, $uploaded_by);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Book added successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add book']);
    }
    
    $stmt->close();
    $conn->close();
}

function getSection() {
    $conn = getDBConnection();
    if (!$conn) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        return;
    }

    $section_id = intval($_GET['section_id'] ?? 0);

    if (empty($section_id)) {
        echo json_encode(['success' => false, 'message' => 'Section ID is required']);
        return;
    }

    $stmt = $conn->prepare("SELECT * FROM sections WHERE section_id = ?");
    $stmt->bind_param("i", $section_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $section = $result->fetch_assoc();
        echo json_encode(['success' => true, 'section' => $section]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Section not found']);
    }

    $stmt->close();
    $conn->close();
}

function updateSection() {
    $conn = getDBConnection();
    if (!$conn) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);

    $section_id = intval($data['section_id'] ?? 0);
    $section_name = sanitizeInput($data['section_name'] ?? '');
    $grade_level = sanitizeInput($data['grade_level'] ?? '');
    $teacher_id = intval($data['teacher_id'] ?? 0);

    if (empty($section_id) || empty($section_name) || empty($grade_level)) {
        echo json_encode(['success' => false, 'message' => 'Section ID, name, and grade level are required']);
        return;
    }

    // Check if another section with the same name exists for this grade level (excluding current section)
    $checkStmt = $conn->prepare("SELECT section_id FROM sections WHERE section_name = ? AND grade_level = ? AND section_id != ?");
    $checkStmt->bind_param("ssi", $section_name, $grade_level, $section_id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Another section with this name already exists for this grade level']);
        $checkStmt->close();
        $conn->close();
        return;
    }
    $checkStmt->close();

    $stmt = $conn->prepare("UPDATE sections SET section_name = ?, grade_level = ?, teacher_id = ? WHERE section_id = ?");
    $teacher_id_value = $teacher_id > 0 ? $teacher_id : null;
    $stmt->bind_param("ssii", $section_name, $grade_level, $teacher_id_value, $section_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Section updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update section']);
    }

    $stmt->close();
    $conn->close();
}

function getSectionsByGrade() {
    $conn = getDBConnection();
    if (!$conn) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        return;
    }

    $grade_level = sanitizeInput($_GET['grade_level'] ?? '');

    if (empty($grade_level)) {
        echo json_encode(['success' => false, 'message' => 'Grade level is required']);
        return;
    }

    $stmt = $conn->prepare("SELECT section_id, section_name FROM sections WHERE grade_level = ? AND is_active = 1 ORDER BY section_name ASC");
    $stmt->bind_param("s", $grade_level);
    $stmt->execute();
    $result = $stmt->get_result();

    $sections = [];
    while ($row = $result->fetch_assoc()) {
        $sections[] = $row;
    }

    echo json_encode(['success' => true, 'sections' => $sections]);

    $stmt->close();
    $conn->close();
}

function getSections() {
    $conn = getDBConnection();
    if (!$conn) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        return;
    }

    $result = $conn->query("
        SELECT s.*, u.full_name as teacher_name,
               (SELECT COUNT(*) FROM users WHERE section_id = s.section_id AND user_type = 'student') as student_count
        FROM sections s
        LEFT JOIN users u ON s.teacher_id = u.user_id
        ORDER BY s.grade_level ASC, s.section_name ASC
    ");
    $sections = [];

    while ($row = $result->fetch_assoc()) {
        $sections[] = $row;
    }

    echo json_encode(['success' => true, 'sections' => $sections]);
    $conn->close();
}

function getTeachers() {
    $conn = getDBConnection();
    if (!$conn) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        return;
    }

    $result = $conn->query("SELECT user_id, full_name FROM users WHERE user_type = 'teacher' AND is_active = 1 ORDER BY full_name ASC");
    $teachers = [];

    while ($row = $result->fetch_assoc()) {
        $teachers[] = $row;
    }

    echo json_encode(['success' => true, 'teachers' => $teachers]);
    $conn->close();
}

function addSection() {
    $conn = getDBConnection();
    if (!$conn) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);

    $section_name = sanitizeInput($data['section_name'] ?? '');
    $grade_level = sanitizeInput($data['grade_level'] ?? '');
    $teacher_id = intval($data['teacher_id'] ?? 0);

    if (empty($section_name) || empty($grade_level)) {
        echo json_encode(['success' => false, 'message' => 'Section name and grade level are required']);
        return;
    }

    // Check if section already exists for this grade level
    $checkStmt = $conn->prepare("SELECT section_id FROM sections WHERE section_name = ? AND grade_level = ?");
    $checkStmt->bind_param("ss", $section_name, $grade_level);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Section already exists for this grade level']);
        $checkStmt->close();
        $conn->close();
        return;
    }
    $checkStmt->close();

    $stmt = $conn->prepare("INSERT INTO sections (section_name, grade_level, teacher_id, is_active) VALUES (?, ?, ?, 1)");
    $teacher_id_value = $teacher_id > 0 ? $teacher_id : null;
    $stmt->bind_param("sssi", $section_name, $grade_level, $teacher_id_value, 1);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Section created successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create section: ' . $stmt->error . ' | Data: ' . json_encode($data)]);
    }

    $stmt->close();
    $conn->close();
}

function deleteSection() {
    $conn = getDBConnection();
    if (!$conn) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $section_id = intval($data['section_id'] ?? 0);

    if (empty($section_id)) {
        echo json_encode(['success' => false, 'message' => 'Section ID is required']);
        return;
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Unassign students from this section
        $stmt1 = $conn->prepare("UPDATE users SET section_id = NULL WHERE section_id = ?");
        $stmt1->bind_param("i", $section_id);
        $stmt1->execute();
        $stmt1->close();

        // Delete the section
        $stmt2 = $conn->prepare("DELETE FROM sections WHERE section_id = ?");
        $stmt2->bind_param("i", $section_id);
        $stmt2->execute();
        $stmt2->close();

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Section deleted successfully']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed to delete section']);
    }

    $conn->close();
}

function updateBook() {
    $conn = getDBConnection();
    if (!$conn) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        return;
    }

    $book_id = intval($_POST['book_id'] ?? 0);
    $title = sanitizeInput($_POST['title'] ?? '');
    $author = sanitizeInput($_POST['author'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    $category = sanitizeInput($_POST['category'] ?? '');
    $subject = sanitizeInput($_POST['subject'] ?? '');
    $grade_level = sanitizeInput($_POST['grade_level'] ?? 'all');
    $content_type = sanitizeInput($_POST['content_type'] ?? 'book');
    
    if (empty($book_id) || empty($title)) {
        echo json_encode(['success' => false, 'message' => 'Book ID and title are required']);
        return;
    }

    $stmt = $conn->prepare("UPDATE ebooks SET title = ?, author = ?, description = ?, category = ?, subject = ?, grade_level = ?, content_type = ? WHERE ebook_id = ?");
    $stmt->bind_param("sssssssi", $title, $author, $description, $category, $subject, $grade_level, $content_type, $book_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Book updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update book']);
    }
    
    $stmt->close();
    $conn->close();
}

function approveBook() {
    $conn = getDBConnection();
    if (!$conn) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $book_id = intval($data['ebook_id'] ?? 0);
    
    if (empty($book_id)) {
        echo json_encode(['success' => false, 'message' => 'Book ID is required']);
        return;
    }

    $stmt = $conn->prepare("UPDATE ebooks SET is_approved = 1 WHERE ebook_id = ?");
    $stmt->bind_param("i", $book_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Book approved successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to approve book']);
    }
    
    $stmt->close();
    $conn->close();
}

function deleteBook() {
    $conn = getDBConnection();
    if (!$conn) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $book_id = intval($data['ebook_id'] ?? 0);
    
    if (empty($book_id)) {
        echo json_encode(['success' => false, 'message' => 'Book ID is required']);
        return;
    }

    $stmt = $conn->prepare("DELETE FROM ebooks WHERE ebook_id = ?");
    $stmt->bind_param("i", $book_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Book deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete book']);
    }
    
    $stmt->close();
    $conn->close();
}

function addCategory() {
    $conn = getDBConnection();
    if (!$conn) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        return;
    }

    $name = sanitizeInput($_POST['category_name'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    $icon = sanitizeInput($_POST['icon'] ?? 'fas fa-book');
    $display_order = intval($_POST['display_order'] ?? 0);
    
    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Category name is required']);
        return;
    }

    $stmt = $conn->prepare("INSERT INTO categories (category_name, description, icon, display_order, is_active) VALUES (?, ?, ?, ?, 1)");
    $stmt->bind_param("sssi", $name, $description, $icon, $display_order);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Category added successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add category']);
    }
    
    $stmt->close();
    $conn->close();
}

function saveSettings() {
    $conn = getDBConnection();
    if (!$conn) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    
    foreach ($data as $key => $value) {
        $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->bind_param("sss", $key, $value, $value);
        $stmt->execute();
        $stmt->close();
    }
    
    echo json_encode(['success' => true, 'message' => 'Settings saved successfully']);
    $conn->close();
}

function addTeacher() {
    $conn = getDBConnection();
    if (!$conn) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    
    $full_name = sanitizeInput($data['full_name'] ?? '');
    $email = sanitizeInput($data['email'] ?? '');
    $username = sanitizeInput($data['username'] ?? '');
    $password = $data['password'] ?? '';
    
    if (empty($full_name) || empty($email) || empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        return;
    }
    
    // Check if username or email already exists
    $checkStmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
    $checkStmt->bind_param("ss", $username, $email);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Username or email already exists']);
        $checkStmt->close();
        $conn->close();
        return;
    }
    $checkStmt->close();
    
    // Hash password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert teacher
    $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash, full_name, user_type, grade_level, is_active) VALUES (?, ?, ?, ?, 'teacher', 'n/a', 1)");
    $stmt->bind_param("ssss", $username, $email, $password_hash, $full_name);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Teacher account created successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create teacher account']);
    }
    
    $stmt->close();
    $conn->close();
}

function updateUserStatus() {
    $conn = getDBConnection();
    if (!$conn) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $user_id = intval($data['user_id'] ?? 0);
    $is_active = intval($data['is_active'] ?? 0);
    
    if (empty($user_id)) {
        echo json_encode(['success' => false, 'message' => 'User ID is required']);
        return;
    }
    
    // Don't allow deactivating own account
    if ($user_id == $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'Cannot modify your own account status']);
        return;
    }

    $stmt = $conn->prepare("UPDATE users SET is_active = ? WHERE user_id = ?");
    $stmt->bind_param("ii", $is_active, $user_id);
    
    if ($stmt->execute()) {
        $status = $is_active ? 'activated' : 'deactivated';
        echo json_encode(['success' => true, 'message' => "User {$status} successfully"]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update user status']);
    }
    
    $stmt->close();
    $conn->close();
}

function deleteUser() {
    $conn = getDBConnection();
    if (!$conn) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $user_id = intval($data['user_id'] ?? 0);

    if (empty($user_id)) {
        echo json_encode(['success' => false, 'message' => 'User ID is required']);
        return;
    }

    // Don't allow deleting own account
    if ($user_id == $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'Cannot delete your own account']);
        return;
    }

    $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete user']);
    }

    $stmt->close();
    $conn->close();
}

function getUser() {
    $conn = getDBConnection();
    if (!$conn) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        return;
    }

    $user_id = intval($_GET['user_id'] ?? 0);

    if (empty($user_id)) {
        echo json_encode(['success' => false, 'message' => 'User ID is required']);
        return;
    }

    $stmt = $conn->prepare("SELECT user_id, username, email, full_name, user_type, grade_level, is_active FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        echo json_encode(['success' => true, 'user' => $user]);
    } else {
        echo json_encode(['success' => false, 'message' => 'User not found']);
    }

    $stmt->close();
    $conn->close();
}

function updateUser() {
    $conn = getDBConnection();
    if (!$conn) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);

    $user_id = intval($data['user_id'] ?? 0);
    $full_name = sanitizeInput($data['full_name'] ?? '');
    $email = sanitizeInput($data['email'] ?? '');
    $username = sanitizeInput($data['username'] ?? '');
    $user_type = sanitizeInput($data['user_type'] ?? '');
    $grade_level = sanitizeInput($data['grade_level'] ?? '');
    $password = $data['password'] ?? '';

    if (empty($user_id) || empty($full_name) || empty($email) || empty($username) || empty($user_type)) {
        echo json_encode(['success' => false, 'message' => 'User ID, full name, email, username, and user type are required']);
        return;
    }

    // Don't allow modifying own account type
    if ($user_id == $_SESSION['user_id'] && $user_type !== $_SESSION['user_type']) {
        echo json_encode(['success' => false, 'message' => 'Cannot change your own account type']);
        return;
    }

    // Check if username or email already exists (excluding current user)
    $checkStmt = $conn->prepare("SELECT user_id FROM users WHERE (username = ? OR email = ?) AND user_id != ?");
    $checkStmt->bind_param("ssi", $username, $email, $user_id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Username or email already exists']);
        $checkStmt->close();
        $conn->close();
        return;
    }
    $checkStmt->close();

    // Build update query
    $updateFields = "full_name = ?, email = ?, username = ?, user_type = ?, grade_level = ?";
    $params = [$full_name, $email, $username, $user_type, $grade_level];
    $types = "sssss";

    // Add password if provided
    if (!empty($password)) {
        if (strlen($password) < 6) {
            echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
            return;
        }
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $updateFields .= ", password_hash = ?";
        $params[] = $password_hash;
        $types .= "s";
    }

    $params[] = $user_id;
    $types .= "i";

    $stmt = $conn->prepare("UPDATE users SET {$updateFields} WHERE user_id = ?");
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'User updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update user']);
    }

    $stmt->close();
    $conn->close();
}
?>
