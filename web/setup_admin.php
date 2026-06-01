<?php
// Setup script to create/update admin user and categories
require_once 'api/config.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$conn = getDBConnection();

if (!$conn) {
    die("Database connection failed. Make sure your database credentials in api/config.php are correct and the database exists.");
}

echo "<h2>E-Library Setup</h2>";

// Delegate all setup to the shared function in config.php
echo "<h3>Initializing Database Tables...</h3>";
foreach (getTableDefinitions() as $name => $sql) {
    if ($conn->query($sql)) {
        echo "<p style='color: green;'>✓ Table '$name' ready</p>";
    } else {
        echo "<p style='color: red;'>Error creating table '$name': " . $conn->error . "</p>";
    }
}

echo "<h3>Checking for missing columns...</h3>";
foreach (getColumnMigrations() as $col) {
    list($table, $column, $alter_sql) = $col;
    $check = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    if ($check && $check->num_rows == 0) {
        if ($conn->query($alter_sql)) {
            echo "<p style='color: green;'>✓ Added column '$column' to table '$table'</p>";
        } else {
            echo "<p style='color: red;'>Error adding column '$column' to '$table': " . $conn->error . "</p>";
        }
    } else {
        echo "<p>Column '$column' already exists in '$table'</p>";
    }
}

// Ensure admin user exists
$result = $conn->query("SELECT user_id FROM users WHERE username = 'admin'");
$adminEmail = 'admin@sres.edu.ph';
$password_hash = password_hash('admin123', PASSWORD_DEFAULT);

if ($result && $result->num_rows > 0) {
    $stmt = $conn->prepare("UPDATE users SET email = ?, password_hash = ? WHERE username = 'admin'");
    $stmt->bind_param("ss", $adminEmail, $password_hash);
    if ($stmt->execute()) {
        echo "<p style='color: green;'>✓ Admin user updated successfully!</p>";
    } else {
        echo "<p style='color: red;'>Error updating admin: " . $conn->error . "</p>";
    }
    $stmt->close();
} else {
    $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash, full_name, user_type, grade_level, is_active) VALUES ('admin', ?, ?, 'System Administrator', 'admin', 'n/a', 1)");
    $stmt->bind_param("ss", $adminEmail, $password_hash);
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
$categoryCount = 0;
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
        $stmt->bind_param("sssi", $cat[0], $cat[1], $cat[2], $cat[3]);
        if ($stmt->execute()) {
            $categoryCount++;
        }
        $stmt->close();
    }
}
echo $categoryCount > 0
    ? "<p style='color: green;'>✓ Added {$categoryCount} categories</p>"
    : "<p>Categories already exist</p>";

$conn->close();

echo "<br><a href='login.php'>Go to Login Page</a>";
echo " | <a href='admin/dashboard.php'>Go to Admin Dashboard</a>";
echo "<br><br><strong style='color: orange;'>⚠️ Delete this file after use for security!</strong>";
?>
