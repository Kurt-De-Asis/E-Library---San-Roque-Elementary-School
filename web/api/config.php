<?php
// Database Configuration for E-Library System
// San Roque Elementary School

// Database credentials - use environment variables for production
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'elibrary_db');

// Site configuration
define('SITE_NAME', getenv('SITE_NAME') ?: 'San Roque Elementary School E-Library');
define('SITE_URL', getenv('SITE_URL') ?: 'http://localhost/elibrary');

// File upload settings
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('BOOKS_PATH', __DIR__ . '/../uploads/books/');
define('COVERS_PATH', __DIR__ . '/../uploads/covers/');
define('MAX_FILE_SIZE', 50 * 1024 * 1024); // 50MB

// Create database connection
function getDBConnection() {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        
        $conn->set_charset("utf8mb4");
        return $conn;
        
    } catch (Exception $e) {
        error_log($e->getMessage());
        return null;
    }
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Check if user is admin
function isAdmin() {
    return isLoggedIn() && $_SESSION['user_type'] === 'admin';
}

// Check if user is teacher
function isTeacher() {
    return isLoggedIn() && $_SESSION['user_type'] === 'teacher';
}

// Check if user is student
function isStudent() {
    return isLoggedIn() && $_SESSION['user_type'] === 'student';
}

// Get current user's grade level
function getUserGradeLevel() {
    return isset($_SESSION['grade_level']) ? $_SESSION['grade_level'] : 'n/a';
}

// Sanitize input
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Log activity
function logActivity($conn, $user_id, $action, $entity_type = null, $entity_id = null, $description = null) {
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $ip = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    $stmt->bind_param("issssss", $user_id, $action, $entity_type, $entity_id, $description, $ip, $user_agent);
    $stmt->execute();
    $stmt->close();
}

// Format file size
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

// Generate random filename
function generateUniqueFileName($originalName) {
    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
    return uniqid() . '_' . time() . '.' . $extension;
}
?>
