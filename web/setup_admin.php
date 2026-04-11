<?php
// Setup script to create/update admin user and categories
require_once 'api/config.php';

// Enable error reporting to find the 500 error cause
error_reporting(E_ALL);
ini_set('display_errors', 1);

$conn = getDBConnection();

if (!$conn) {
    die("Database connection failed. Make sure your database credentials in api/config.php are correct and the database exists.");
}

echo "<h2>E-Library Setup</h2>";

// Table Creation Queries
$tables = [
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
];

echo "<h3>Initializing Database Tables...</h3>";
foreach ($tables as $name => $sql) {
    if ($conn->query($sql)) {
        echo "<p style='color: green;'>✓ Table '$name' ready</p>";
    } else {
        echo "<p style='color: red;'>Error creating table '$name': " . $conn->error . "</p>";
    }
}

// Check if admin user exists
$result = $conn->query("SELECT user_id FROM users WHERE username = 'admin'");

if ($result && $result->num_rows > 0) {
    // Update existing admin
    $password_hash = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET email = 'admin@sres.edu.ph', password_hash = ? WHERE username = 'admin'");
    $stmt->bind_param("s", $password_hash);
    
    if ($stmt->execute()) {
        echo "<p style='color: green;'>✓ Admin user updated successfully!</p>";
    } else {
        echo "<p style='color: red;'>Error updating admin: " . $conn->error . "</p>";
    }
    $stmt->close();
} else {
    // Create new admin
    $password_hash = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash, full_name, user_type, grade_level, is_active) VALUES ('admin', 'admin@sres.edu.ph', ?, 'System Administrator', 'admin', 'n/a', 1)");
    $stmt->bind_param("s", $password_hash);
    
    if ($stmt->execute()) {
        echo "<p style='color: green;'>✓ Admin user created successfully!</p>";
    } else {
        echo "<p style='color: red;'>Error creating admin: " . $conn->error . "</p>";
    }
    $stmt->close();
}

echo "<p><strong>Admin Login:</strong> Email: admin@sres.edu.ph | Password: admin123</p>";

// Add default categories
echo "<h3>Setting up Categories...</h3>";

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

$categoryCount = 0;
foreach ($categories as $cat) {
    $checkCat = $conn->query("SELECT category_id FROM categories WHERE category_name = '" . $conn->real_escape_string($cat[0]) . "'");
    if ($checkCat && $checkCat->num_rows == 0) {
        $stmt = $conn->prepare("INSERT INTO categories (category_name, description, icon, display_order, is_active) VALUES (?, ?, ?, ?, 1)");
        $stmt->bind_param("sssi", $cat[0], $cat[1], $cat[2], $cat[3]);
        if ($stmt->execute()) {
            $categoryCount++;
        }
        $stmt->close();
    }
}

if ($categoryCount > 0) {
    echo "<p style='color: green;'>✓ Added {$categoryCount} categories</p>";
} else {
    echo "<p>Categories already exist</p>";
}

$conn->close();

echo "<br><a href='login.php'>Go to Login Page</a>";
echo " | <a href='admin/dashboard.php'>Go to Admin Dashboard</a>";
echo "<br><br><strong style='color: orange;'>⚠️ Delete this file after use for security!</strong>";
?>
