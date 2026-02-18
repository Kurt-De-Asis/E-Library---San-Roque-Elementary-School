<?php
// Setup script to create/update admin user and categories
require_once 'api/config.php';

$conn = getDBConnection();

if (!$conn) {
    die("Database connection failed. Make sure WAMP is running and the database 'elibrary_db' exists.");
}

echo "<h2>E-Library Setup</h2>";

// First, clean up duplicate categories
echo "<h3>Cleaning up duplicate categories...</h3>";
$conn->query("DELETE FROM categories WHERE category_name = 'Story Books'");
$conn->query("DELETE FROM categories WHERE category_name = 'Modules'");
echo "<p>Removed duplicate/invalid categories</p>";

// Create password_resets table if not exists
echo "<h3>Setting up Password Reset table...</h3>";
$createPasswordResetsTable = "CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    otp_code VARCHAR(6) NOT NULL,
    verified TINYINT(1) DEFAULT 0,
    used TINYINT(1) DEFAULT 0,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_otp (otp_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($createPasswordResetsTable)) {
    echo "<p style='color: green;'>✓ Password resets table ready</p>";
} else {
    echo "<p style='color: red;'>Error creating password_resets table: " . $conn->error . "</p>";
}

// Check if admin user exists
$result = $conn->query("SELECT user_id FROM users WHERE username = 'admin'");

if ($result->num_rows > 0) {
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
    $checkCat = $conn->query("SELECT category_id FROM categories WHERE category_name = '{$cat[0]}'");
    if ($checkCat->num_rows == 0) {
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
