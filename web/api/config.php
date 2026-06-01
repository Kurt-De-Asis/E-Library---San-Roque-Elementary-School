<?php
// Database Configuration for E-Library System
// San Roque Elementary School

// CORS headers for mobile app access
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Safe replacement for getallheaders() — works when native function is disabled
function get_request_headers() {
    // Try native getallheaders() if it exists and is not disabled
    if (function_exists('getallheaders')) {
        $disabled = array_map('trim', explode(',', ini_get('disable_functions')));
        if (!in_array('getallheaders', $disabled)) {
            $headers = getallheaders();
            if (is_array($headers)) return $headers;
        }
    }
    // Fallback: build from $_SERVER
    $headers = [];
    foreach ($_SERVER as $name => $value) {
        if (substr($name, 0, 5) === 'HTTP_') {
            $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
        }
    }
    return $headers;
}

// Detect if running on localhost
$server_name = $_SERVER['SERVER_NAME'] ?? '';
$remote_addr = $_SERVER['REMOTE_ADDR'] ?? '';
$is_localhost = ($remote_addr === '127.0.0.1' || $remote_addr === '::1' || $server_name === 'localhost');
if (!defined('IS_LOCALHOST')) define('IS_LOCALHOST', $is_localhost);

// Database credentials - use environment variables for production
if (IS_LOCALHOST) {
    define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
    define('DB_USER', getenv('DB_USER') ?: 'root');
    define('DB_PASS', getenv('DB_PASS') ?: '');
    define('DB_NAME', getenv('DB_NAME') ?: 'elibrary_db');
} else {
    define('DB_HOST', getenv('DB_HOST') ?: getenv('MYSQL_HOST') ?: 'sql201.infinityfree.com');
    define('DB_USER', getenv('DB_USER') ?: getenv('MYSQL_USER') ?: 'if0_41890958');
    define('DB_PASS', getenv('DB_PASS') ?: getenv('MYSQL_PASSWORD') ?: 'YgnF4X7Nc7mw');
    define('DB_NAME', getenv('DB_NAME') ?: getenv('MYSQL_DATABASE') ?: 'if0_41890958_elibrary_db');
}

// SMTP Configuration for password reset emails (Gmail)
// Set SMTP_USER and SMTP_PASS via environment variables on InfinityFree (cPanel > Environment Variables)
// For Gmail: enable 2FA, then generate an App Password at https://myaccount.google.com/apppasswords
define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.gmail.com');
define('SMTP_PORT', getenv('SMTP_PORT') ?: 587);
define('SMTP_USER', getenv('SMTP_USER') ?: 'sres.sanpedro@gmail.com');
define('SMTP_PASS', getenv('SMTP_PASS') ?: 'eigz bygf qujn bnxw');
define('SMTP_FROM', getenv('SMTP_FROM') ?: 'sres.sanpedro@gmail.com');
define('SMTP_FROM_NAME', getenv('SMTP_FROM_NAME') ?: 'San Roque E-Library');

// Site configuration
define('SITE_NAME', getenv('SITE_NAME') ?: 'San Roque Elementary School E-Library');
define('SITE_URL', getenv('SITE_URL') ?: 'http://localhost/elibrary');

// Application version
define('APP_VERSION', '1.0.8');
// Cache buster for development - changes every second on localhost
define('CACHE_BUSTER', $is_localhost ? time() : APP_VERSION);

// File upload settings
define('UPLOAD_ROOT', __DIR__ . '/../uploads/');
define('BOOKS_PATH', UPLOAD_ROOT . 'books/');
define('COVERS_PATH', UPLOAD_ROOT . 'covers/');
define('MAX_FILE_SIZE', 500 * 1024 * 1024); // 500MB

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

// Error reporting for development
if ($is_localhost) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
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

// Get authenticated user ID (checks Bearer token first, then session)
function getAuthUserId() {
    // Check Bearer token from Authorization header
    $headers = get_request_headers();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    
    if (preg_match('/Bearer\s+(.+)$/i', $authHeader, $matches)) {
        $token = $matches[1];
        $conn = getDBConnection();
        if ($conn) {
            try {
                $stmt = $conn->prepare("SELECT user_id FROM users WHERE api_token = ? AND is_active = 1");
                if ($stmt) {
                    $stmt->bind_param("s", $token);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($result->num_rows === 1) {
                        $user = $result->fetch_assoc();
                        $stmt->close();
                        $conn->close();
                        return (int)$user['user_id'];
                    }
                    $stmt->close();
                }
            } catch (Exception $e) {
                error_log("getAuthUserId token check failed: " . $e->getMessage());
            }
            $conn->close();
        }
    }

    // Fallback to session-based auth
    return isLoggedIn() ? (int)$_SESSION['user_id'] : null;
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
    try {
        $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            error_log("logActivity: prepare failed - " . $conn->error);
            return;
        }
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $stmt->bind_param("issssss", $user_id, $action, $entity_type, $entity_id, $description, $ip, $user_agent);
        $stmt->execute();
        $stmt->close();
    } catch (Exception $e) {
        error_log("logActivity failed: " . $e->getMessage());
    }
}

// Return all table definitions for database setup
function getTableDefinitions() {
    return [
        "users" => "CREATE TABLE IF NOT EXISTS `users` (
          `user_id` int(11) NOT NULL AUTO_INCREMENT,
          `username` varchar(50) NOT NULL,
          `email` varchar(100) NOT NULL,
          `password_hash` varchar(255) NOT NULL,
          `full_name` varchar(100) NOT NULL,
          `user_type` enum('admin','teacher','student') NOT NULL,
          `grade_level` varchar(20) DEFAULT 'n/a',
          `section_id` int(11) DEFAULT NULL,
          `profile_image` varchar(255) DEFAULT NULL,
          `api_token` varchar(255) DEFAULT NULL,
          `is_active` tinyint(1) DEFAULT 1,
          `last_login` datetime DEFAULT NULL,
          `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`user_id`),
          UNIQUE KEY `username` (`username`),
          UNIQUE KEY `email` (`email`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "categories" => "CREATE TABLE IF NOT EXISTS `categories` (
          `category_id` int(11) NOT NULL AUTO_INCREMENT,
          `category_name` varchar(50) NOT NULL,
          `description` text DEFAULT NULL,
          `icon` varchar(50) DEFAULT 'fas fa-book',
          `display_order` int(11) DEFAULT 0,
          `is_active` tinyint(1) DEFAULT 1,
          PRIMARY KEY (`category_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "ebooks" => "CREATE TABLE IF NOT EXISTS `ebooks` (
          `ebook_id` int(11) NOT NULL AUTO_INCREMENT,
          `title` varchar(255) NOT NULL,
          `author` varchar(100) DEFAULT NULL,
          `description` text DEFAULT NULL,
          `category` varchar(50) DEFAULT NULL,
          `subject` varchar(50) DEFAULT NULL,
          `grade_level` varchar(20) DEFAULT NULL,
          `section_id` int(11) DEFAULT NULL,
          `content_type` varchar(20) DEFAULT 'pdf',
          `cover_image` varchar(255) DEFAULT NULL,
          `file_path` varchar(255) NOT NULL,
          `uploaded_by` int(11) DEFAULT NULL,
          `is_approved` tinyint(1) DEFAULT 0,
          `is_active` tinyint(1) DEFAULT 1,
          `is_featured` tinyint(1) DEFAULT 0,
          `view_count` int(11) DEFAULT 0,
          `download_count` int(11) DEFAULT 0,
          `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`ebook_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "sections" => "CREATE TABLE IF NOT EXISTS `sections` (
          `section_id` int(11) NOT NULL AUTO_INCREMENT,
          `section_name` varchar(50) NOT NULL,
          `grade_level` varchar(20) NOT NULL,
          `teacher_id` int(11) DEFAULT NULL,
          `is_active` tinyint(1) DEFAULT 1,
          PRIMARY KEY (`section_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "password_resets" => "CREATE TABLE IF NOT EXISTS `password_resets` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `email` varchar(255) NOT NULL,
            `otp_code` varchar(6) NOT NULL,
            `verified` tinyint(1) DEFAULT 0,
            `used` tinyint(1) DEFAULT 0,
            `expires_at` datetime NOT NULL,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            INDEX idx_email (email),
            INDEX idx_otp (otp_code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "settings" => "CREATE TABLE IF NOT EXISTS `settings` (
          `setting_key` varchar(50) NOT NULL,
          `setting_value` text DEFAULT NULL,
          PRIMARY KEY (`setting_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "reading_progress" => "CREATE TABLE IF NOT EXISTS `reading_progress` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `user_id` int(11) NOT NULL,
          `ebook_id` int(11) NOT NULL,
          `current_page` int(11) DEFAULT 1,
          `total_pages` int(11) DEFAULT 0,
          `progress_percentage` decimal(5,2) DEFAULT 0.00,
          `last_accessed` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          UNIQUE KEY `user_ebook` (`user_id`,`ebook_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "reading_history" => "CREATE TABLE IF NOT EXISTS `reading_history` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `user_id` int(11) NOT NULL,
          `ebook_id` int(11) NOT NULL,
          `action` enum('view','download') NOT NULL,
          `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "activity_logs" => "CREATE TABLE IF NOT EXISTS `activity_logs` (
          `log_id` int(11) NOT NULL AUTO_INCREMENT,
          `user_id` int(11) DEFAULT NULL,
          `action` varchar(50) NOT NULL,
          `entity_type` varchar(50) DEFAULT NULL,
          `entity_id` varchar(50) DEFAULT NULL,
          `description` text DEFAULT NULL,
          `ip_address` varchar(45) DEFAULT NULL,
          `user_agent` text DEFAULT NULL,
          `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`log_id`),
          KEY `idx_user_id` (`user_id`),
          KEY `idx_action` (`action`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "bookmarks" => "CREATE TABLE IF NOT EXISTS `bookmarks` (
          `bookmark_id` int(11) NOT NULL AUTO_INCREMENT,
          `user_id` int(11) NOT NULL,
          `ebook_id` int(11) NOT NULL,
          `page_number` int(11) DEFAULT NULL,
          `note` text DEFAULT NULL,
          `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`bookmark_id`),
          KEY `idx_user_ebook` (`user_id`,`ebook_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    ];
}

// Return missing column checks for existing tables
function getColumnMigrations() {
    return [
        ['users', 'api_token', "ALTER TABLE users ADD COLUMN api_token VARCHAR(255) DEFAULT NULL AFTER profile_image"],
    ];
}

// Default admin credentials
function getDefaultAdminEmail() { return 'admin@sres.edu.ph'; }
function getDefaultAdminPassword() { return 'admin123'; }

// Ensure database is fully set up — creates tables, adds missing columns, admin, and categories
function ensureDatabaseSetup($conn) {
    if (!$conn) return;

    // Create tables
    foreach (getTableDefinitions() as $name => $sql) {
        $conn->query($sql);
    }

    // Add missing columns
    foreach (getColumnMigrations() as $col) {
        list($table, $column, $alter_sql) = $col;
        $check = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
        if ($check && $check->num_rows == 0) {
            $conn->query($alter_sql);
        }
    }

    // Ensure admin user exists
    $result = $conn->query("SELECT user_id FROM users WHERE username = 'admin'");
    $admin_email = getDefaultAdminEmail();
    $password_hash = password_hash(getDefaultAdminPassword(), PASSWORD_DEFAULT);
    if ($result && $result->num_rows > 0) {
        $stmt = $conn->prepare("UPDATE users SET email = ?, password_hash = ? WHERE username = 'admin'");
        if ($stmt) {
            $stmt->bind_param("ss", $admin_email, $password_hash);
            $stmt->execute();
            $stmt->close();
        }
    } else {
        $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash, full_name, user_type, grade_level, is_active) VALUES ('admin', ?, ?, 'System Administrator', 'admin', 'n/a', 1)");
        if ($stmt) {
            $stmt->bind_param("ss", $admin_email, $password_hash);
            $stmt->execute();
            $stmt->close();
        }
    }

    // Ensure default categories exist
    $categories = [
        ['English', 'English language books and materials', 'fas fa-book-open', 1],
        ['Mathematics', 'Math books, workbooks and exercises', 'fas fa-calculator', 2],
        ['Science', 'Science books and learning materials', 'fas fa-flask', 3],
        ['Filipino', 'Filipino language books and materials', 'fas fa-language', 4],
        ['Araling Panlipunan', 'Social studies and history', 'fas fa-globe-asia', 5],
        ['MAPEH', 'Music, Arts, PE and Health', 'fas fa-music', 6],
        ['Storybooks', 'Fiction and storybooks for children', 'fas fa-book', 7],
        ['Reference', 'Reference materials and guides', 'fas fa-bookmark', 8]
    ];
    foreach ($categories as $cat) {
        $checkCat = $conn->query("SELECT category_id FROM categories WHERE category_name = '" . $conn->real_escape_string($cat[0]) . "'");
        if ($checkCat && $checkCat->num_rows == 0) {
            $stmt = $conn->prepare("INSERT INTO categories (category_name, description, icon, display_order, is_active) VALUES (?, ?, ?, ?, 1)");
            if ($stmt) {
                $stmt->bind_param("sssi", $cat[0], $cat[1], $cat[2], $cat[3]);
                $stmt->execute();
                $stmt->close();
            }
        }
    }
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
